<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireRole('student');

$pdo = \App\Database::getConnection();
$st = $pdo->prepare('SELECT c.id, c.status, c.updated_at FROM candidates c WHERE c.user_id = ?');
$st->execute([\App\Auth::userId()]);
$candidate = $st->fetch();
if (!$candidate) { header('Location: /candidate-profile.php'); exit; }

$assignments = $pdo->prepare('SELECT a.*, p.name as program_name FROM assignments a JOIN programs p ON p.id = a.program_id WHERE a.candidate_id = ? ORDER BY a.created_at DESC');
$assignments->execute([$candidate['id']]);
$assignments = $assignments->fetchAll();

$scores = $pdo->prepare('
    SELECT s.score, s.rank_position, p.name as program_name
    FROM scores s
    JOIN programs p ON p.id = s.program_id
    JOIN scoring_runs r ON r.id = s.scoring_run_id
    WHERE s.candidate_id = ? AND r.status = "completed"
    AND r.id = (SELECT MAX(id) FROM scoring_runs sr WHERE sr.program_id = p.id AND sr.status = "completed")
    ORDER BY s.score DESC
');
$scores->execute([$candidate['id']]);
$scores = $scores->fetchAll();

$currentPage = 'applications';
$pageTitle = 'Мои заявки';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Мои заявки</h1>
    <p class="subtitle">Статус анкеты: <span class="badge badge-<?= htmlspecialchars($candidate['status']) ?>"><?= htmlspecialchars($candidate['status']) ?></span></p>
</div>

<div class="card">
    <h2 class="card-title">Рекомендации по программам</h2>
    <?php if (empty($scores)): ?>
        <p>Пока нет рассчитанных рейтингов. После запуска анализа руководителем программы здесь появится ваше место в рейтинге.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Программа</th><th class="num">Оценка</th><th class="num">Место</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($scores as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['program_name']) ?></td>
                            <td class="num"><?= number_format($r['score'], 1) ?></td>
                            <td class="num"><?= (int)$r['rank_position'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="card-title">Распределение</h2>
    <?php if (empty($assignments)): ?>
        <p>Пока нет решений о распределении.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Программа</th><th>Статус</th><th>Дата</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['program_name']) ?></td>
                            <td><span class="badge badge-<?= $a['status'] === 'approved' ? 'assigned' : ($a['status'] === 'rejected' ? 'rejected' : 'in_review') ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                            <td><?= $a['decided_at'] ? date('d.m.Y', strtotime($a['decided_at'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

