<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['hr', 'manager', 'admin']);

$pdo = \App\Database::getConnection();
$programs = $pdo->query('SELECT id, name FROM programs WHERE is_active = 1 ORDER BY name')->fetchAll();

$runMessage = '';
$runError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \App\Auth::canManagePrograms()) {
    $programId = (int)($_POST['program_id'] ?? 0);
    if (!$programId) {
        $runError = 'Выберите программу.';
    } else {
        $pdo->prepare('INSERT INTO scoring_runs (program_id, status, initiated_by) VALUES (?, ?, ?)')->execute([$programId, 'running', \App\Auth::userId()]);
        $runId = $pdo->lastInsertId();
        $program = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
        $program->execute([$programId]);
        $program = $program->fetch();
        $candidates = $pdo->query('SELECT c.* FROM candidates c WHERE c.status IN ("submitted","in_review","assigned")')->fetchAll();
        $scores = [];
        foreach ($candidates as $c) {
            $admitted = true;
            $score = 0.0;
            $reasons = [];
            if ($program['min_course'] && ($c['course'] ?? 0) < $program['min_course']) { $admitted = false; $reasons[] = 'курс ниже минимального'; }
            if ($program['min_gpa'] && ($c['gpa'] ?? 0) < $program['min_gpa']) { $admitted = false; $reasons[] = 'GPA ниже минимального'; }
            if ($program['min_experience_years'] !== null && ($c['experience_years'] ?? 0) < $program['min_experience_years']) { $admitted = false; $reasons[] = 'опыт ниже минимального'; }
            if ($admitted) {
                $score = 50;
                if ($c['gpa']) $score += min(20, (float)$c['gpa'] * 5);
                if ($c['experience_years']) $score += min(15, (float)$c['experience_years'] * 5);
                if ($c['projects_count']) $score += min(15, (int)$c['projects_count'] * 3);
                if ($c['motivation_text']) $score += 5;
                if ($c['experience_text']) $score += 5;
                $score = min(100, round($score, 2));
                $reasons[] = 'GPA, опыт, проекты, тексты анкеты';
            }
            $scores[] = ['candidate_id' => $c['id'], 'score' => $score, 'admitted' => $admitted ? 1 : 0, 'explanation' => implode('; ', $reasons)];
        }
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        $rank = 1;
        $insertScore = $pdo->prepare('INSERT INTO scores (scoring_run_id, candidate_id, program_id, score, admitted, explanation, rank_position) VALUES (?,?,?,?,?,?,?)');
        foreach ($scores as $s) {
            $insertScore->execute([$runId, $s['candidate_id'], $programId, $s['score'], $s['admitted'], $s['explanation'], $rank++]);
        }
        $pdo->prepare('UPDATE scoring_runs SET status = ?, finished_at = NOW() WHERE id = ?')->execute(['completed', $runId]);
        $runMessage = 'Анализ выполнен. Оценено кандидатов: ' . count($scores) . '.';
    }
}

$selectedProgramId = (int)($_GET['program_id'] ?? $_POST['program_id'] ?? 0);
$ranking = [];
if ($selectedProgramId) {
    // Берём только последний запуск анализа по программе (последние по логике)
    $ranking = $pdo->prepare('
        SELECT s.score, s.admitted, s.explanation, s.rank_position, c.id as candidate_id, u.full_name, c.university, c.specialty
        FROM scores s
        JOIN candidates c ON c.id = s.candidate_id
        JOIN users u ON u.id = c.user_id
        JOIN scoring_runs r ON r.id = s.scoring_run_id
        WHERE s.program_id = ? AND r.status = "completed"
        AND r.id = (SELECT MAX(id) FROM scoring_runs WHERE program_id = ? AND status = "completed")
        ORDER BY s.rank_position ASC
        LIMIT 500
    ');
    $ranking->execute([$selectedProgramId, $selectedProgramId]);
    $ranking = $ranking->fetchAll();
}

$currentPage = 'scoring';
$pageTitle = 'Анализ и рейтинг';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Анализ и рейтинг кандидатов</h1>
    <p class="subtitle">Интеллектуальная оценка и ранжирование (F5, F6)</p>
</div>

<?php if ($runMessage): ?><div class="alert alert-success"><?= htmlspecialchars($runMessage) ?></div><?php endif; ?>
<?php if ($runError): ?><div class="alert alert-danger"><?= htmlspecialchars($runError) ?></div><?php endif; ?>

<?php if (\App\Auth::canManagePrograms()): ?>
<div class="card">
    <h2 class="card-title">Запуск анализа</h2>
    <form method="post" style="display:flex; gap:12px; align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Программа</label>
            <select name="program_id" class="form-control" required>
                <option value="">— Выберите —</option>
                <?php foreach ($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selectedProgramId === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Запустить анализ</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Рейтинг по программе</h2>
    <form method="get" style="margin-bottom:16px;">
        <select name="program_id" class="form-control" style="width:auto; display:inline-block;" onchange="this.form.submit()">
            <option value="">— Выберите программу —</option>
            <?php foreach ($programs as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selectedProgramId === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (empty($ranking)): ?>
        <p>Выберите программу и запустите анализ или просмотрите сохранённый рейтинг.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Место</th>
                        <th>ФИО</th>
                        <th>Вуз / Специальность</th>
                        <th class="num">Оценка</th>
                        <th>Допуск</th>
                        <th>Обоснование</th>
                        <?php if (\App\Auth::canManagePrograms()): ?><th class="actions">Действия</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $r): ?>
                        <tr>
                            <td><?= (int)$r['rank_position'] ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['university'] ?? '') ?> / <?= htmlspecialchars($r['specialty'] ?? '') ?></td>
                            <td class="num"><?= number_format($r['score'], 1) ?></td>
                            <td><?= $r['admitted'] ? 'Да' : 'Нет' ?></td>
                            <td><?= htmlspecialchars($r['explanation'] ?? '') ?></td>
                            <?php if (\App\Auth::canManagePrograms()): ?>
                                <td class="actions">
                                    <a href="/assignment-add.php?candidate_id=<?= (int)$r['candidate_id'] ?>&program_id=<?= $selectedProgramId ?>" class="btn btn-sm btn-primary" onclick="return confirm('Добавить в распределение?')">В распределение</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

