<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();

$currentPage = 'dashboard';
$pageTitle = 'Главная';
$pdo = \App\Database::getConnection();

// Статистика по ролям
$stats = [];
$stats['candidates'] = $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn();
$stats['programs'] = $pdo->query('SELECT COUNT(*) FROM programs WHERE is_active = 1')->fetchColumn();
$stats['scores'] = $pdo->query('SELECT COUNT(*) FROM scores')->fetchColumn();
$stats['assignments'] = $pdo->query('SELECT COUNT(*) FROM assignments WHERE status = "approved"')->fetchColumn();

if (\App\Auth::isStudent()) {
    $st = $pdo->prepare('SELECT id, status FROM candidates WHERE user_id = ?');
    $st->execute([\App\Auth::userId()]);
    $myCandidate = $st->fetch();
}
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Главная</h1>
    <p class="subtitle">Интеллектуальная система поддержки принятия решений для программ стажировок и практик</p>
</div>

<?php if (\App\Auth::isStudent()): ?>
    <?php
    $programsList = $pdo->query('SELECT id, name, direction, city FROM programs WHERE is_active = 1 ORDER BY name')->fetchAll();
    $myScores = [];
    $myAssignments = []; 
    if (!empty($myCandidate)) {
        $st = $pdo->prepare('
            SELECT s.score, s.rank_position, p.name as program_name
            FROM scores s
            JOIN programs p ON p.id = s.program_id
            JOIN scoring_runs r ON r.id = s.scoring_run_id
            WHERE s.candidate_id = ? AND r.status = "completed"
            AND r.id = (SELECT MAX(id) FROM scoring_runs WHERE program_id = p.id AND status = "completed")
            ORDER BY s.score DESC LIMIT 5
        ');
        $st->execute([$myCandidate['id']]);
        $myScores = $st->fetchAll();
        $st = $pdo->prepare('SELECT a.status, p.name as program_name FROM assignments a JOIN programs p ON p.id = a.program_id WHERE a.candidate_id = ? ORDER BY a.created_at DESC LIMIT 5');
        $st->execute([$myCandidate['id']]);
        $myAssignments = $st->fetchAll();
    }
    ?>
    <div class="student-welcome">
        <h1 class="welcome-title">Добро пожаловать в личный кабинет</h1>
        <p class="welcome-subtitle">Система поддержки принятия решений для программ стажировок</p>
    </div>
    <div class="student-dashboard">
        <div class="student-status-card card">
            <h2 class="card-title">Статус анкеты</h2>
            <?php if ($myCandidate): ?>
                <p class="status-text">Ваша анкета: <span class="badge badge-<?= htmlspecialchars($myCandidate['status']) ?>"><?= htmlspecialchars($myCandidate['status']) ?></span></p>
                <div class="student-actions">
                    <a href="/candidate-profile.php" class="btn btn-primary">Редактировать анкету</a>
                    <a href="/my-applications.php" class="btn btn-secondary">Мои заявки</a>
                </div>
            <?php else: ?>
                <p class="status-text">Заполните анкету для участия в программах стажировок.</p>
                <a href="/candidate-profile.php" class="btn btn-primary">Заполнить анкету</a>
            <?php endif; ?>
        </div>
        <?php if (!empty($myScores)): ?>
        <div class="card student-scores-card">
            <h2 class="card-title">Ваши позиции в рейтингах</h2>
            <div class="scores-grid">
                <?php foreach ($myScores as $s): ?>
                <div class="score-item">
                    <span class="score-program"><?= htmlspecialchars($s['program_name']) ?></span>
                    <span class="score-value"><?= number_format($s['score'], 1) ?></span>
                    <span class="score-place">Место <?= (int)$s['rank_position'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="/my-applications.php" class="btn btn-secondary btn-sm">Подробнее</a>
        </div>
        <?php endif; ?>
        <?php if (!empty($myAssignments)): ?>
        <div class="card">
            <h2 class="card-title">Распределение</h2>
            <ul class="assignments-list">
                <?php foreach ($myAssignments as $a): ?>
                <li><span class="badge badge-<?= $a['status'] === 'approved' ? 'assigned' : ($a['status'] === 'rejected' ? 'rejected' : 'in_review') ?>"><?= htmlspecialchars($a['status']) ?></span> <?= htmlspecialchars($a['program_name']) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="/my-applications.php" class="btn btn-secondary btn-sm">Подробнее</a>
        </div>
        <?php endif; ?>
        <?php if (!empty($programsList)): ?>
        <div class="card programs-listing">
            <h2 class="card-title">Активные программы</h2>
            <div class="programs-grid">
                <?php foreach ($programsList as $prog): ?>
                <div class="program-card">
                    <h3><?= htmlspecialchars($prog['name']) ?></h3>
                    <?php if (!empty($prog['direction'])): ?><p class="program-meta"><?= htmlspecialchars($prog['direction']) ?><?= !empty($prog['city']) ? ' · ' . htmlspecialchars($prog['city']) : '' ?></p><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="value"><?= (int) $stats['candidates'] ?></div>
            <div class="label">Кандидатов</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= (int) $stats['programs'] ?></div>
            <div class="label">Программ</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= (int) $stats['scores'] ?></div>
            <div class="label">Оценок</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= (int) $stats['assignments'] ?></div>
            <div class="label">Распределено</div>
        </div>
    </div>
    <div class="card">
        <h2 class="card-title">Действия</h2>
        <?php if (\App\Auth::canManageCandidates()): ?>
            <a href="/candidates.php" class="btn btn-primary">Анкеты кандидатов</a>
            <a href="/candidates-import.php" class="btn btn-secondary">Импорт</a>
        <?php endif; ?>
        <?php if (\App\Auth::canManagePrograms()): ?>
            <a href="/programs.php" class="btn btn-primary">Программы</a>
            <a href="/scoring.php" class="btn btn-primary">Запустить анализ</a>
            <a href="/assignments.php" class="btn btn-secondary">Распределение</a>
        <?php endif; ?>
        <?php if (\App\Auth::canManageUsers()): ?>
            <a href="/users.php" class="btn btn-secondary">Пользователи</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
