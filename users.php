<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireRole('admin');

$pdo = \App\Database::getConnection();
$users = $pdo->query('
    SELECT u.id, u.email, u.full_name, u.is_active, u.created_at,
           GROUP_CONCAT(r.name ORDER BY r.name) as roles
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.id
    LEFT JOIN roles r ON r.id = ur.role_id
    GROUP BY u.id
    ORDER BY u.email
')->fetchAll();

$currentPage = 'users';
$pageTitle = 'Пользователи';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Пользователи</h1>
    <p class="subtitle">Управление доступом (F10)</p>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>ФИО</th>
                    <th>Роли</th>
                    <th>Активен</th>
                    <th>Создан</th>
                    <th class="actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['roles'] ?? '—') ?></td>
                        <td><?= $u['is_active'] ? 'Да' : 'Нет' ?></td>
                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                        <td class="actions">
                            <a href="/user-edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-secondary">Роли</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

