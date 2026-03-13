<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['manager', 'admin']);

$pdo = \App\Database::getConnection();
$list = $pdo->query('
    SELECT p.*, (SELECT total_places FROM program_quota WHERE program_id = p.id ORDER BY id DESC LIMIT 1) as quota_places,
           (SELECT occupied_places FROM program_quota WHERE program_id = p.id ORDER BY id DESC LIMIT 1) as quota_occupied
    FROM programs p
    ORDER BY p.name
')->fetchAll();

$currentPage = 'programs';
$pageTitle = 'Программы стажировок';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Программы стажировок</h1>
    <p class="subtitle">Управление программами и требованиями (F2)</p>
</div>

<div class="card">
    <p><a href="/program-edit.php" class="btn btn-primary">Добавить программу</a></p>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Направление</th>
                    <th>Город</th>
                    <th>Квота</th>
                    <th>Статус</th>
                    <th class="actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['direction']) ?></td>
                        <td><?= htmlspecialchars($r['city'] ?? '') ?></td>
                        <td><?= (int)($r['quota_occupied'] ?? 0) ?> / <?= (int)($r['quota_places'] ?? 0) ?></td>
                        <td><?= $r['is_active'] ? 'Активна' : 'Неактивна' ?></td>
                        <td class="actions">
                            <a href="/program-edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-secondary">Изменить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($list)): ?><p>Нет программ. Добавьте первую.</p><?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

