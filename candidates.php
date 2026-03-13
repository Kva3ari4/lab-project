<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['hr', 'manager', 'admin']);

$pdo = \App\Database::getConnection();
$list = $pdo->query('
    SELECT c.id, c.university, c.specialty, c.course, c.status, c.updated_at, u.full_name, u.email
    FROM candidates c
    JOIN users u ON u.id = c.user_id
    ORDER BY c.updated_at DESC
')->fetchAll();

$currentPage = 'candidates';
$pageTitle = 'Анкеты кандидатов';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Анкеты кандидатов</h1>
    <p class="subtitle">Управление анкетными данными (F1)</p>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Email</th>
                    <th>Вуз</th>
                    <th>Специальность</th>
                    <th>Курс</th>
                    <th>Статус</th>
                    <th>Обновлено</th>
                    <th class="actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['university'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['specialty'] ?? '') ?></td>
                        <td><?= (int)($r['course'] ?? 0) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                        <td><?= date('d.m.Y H:i', strtotime($r['updated_at'])) ?></td>
                        <td class="actions">
                            <a href="/candidate-profile.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-secondary">Открыть</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($list)): ?><p>Нет анкет.</p><?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

