<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireRole('admin');

$pdo = \App\Database::getConnection();
$userId = (int)($_GET['id'] ?? 0);
$user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$user->execute([$userId]);
$user = $user->fetch();
if (!$user) { header('Location: /users.php'); exit; }

$roles = $pdo->query('SELECT id, code, name FROM roles ORDER BY id')->fetchAll();
$userRoleIds = $pdo->prepare('SELECT role_id FROM user_roles WHERE user_id = ?');
$userRoleIds->execute([$userId]);
$userRoleIds = array_map('intval', $userRoleIds->fetchAll(PDO::FETCH_COLUMN));

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
    foreach ($_POST['role_id'] ?? [] as $rid) {
        $rid = (int)$rid;
        if ($rid) $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)')->execute([$userId, $rid]);
    }
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$isActive, $userId]);
    $saved = true;
    $userRoleIds = isset($_POST['role_id']) ? array_map('intval', (array)$_POST['role_id']) : [];
    $user['is_active'] = $isActive;
}

$currentPage = 'users';
$pageTitle = 'Роли пользователя';
include __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Роли: <?= htmlspecialchars($user['full_name']) ?></h1>
    <p class="subtitle"><a href="/users.php">← К списку пользователей</a></p>
</div>

<?php if ($saved): ?><div class="alert alert-success">Сохранено.</div><?php endif; ?>

<div class="card">
    <form method="post">
        <div class="form-group">
            <label>Роли</label>
            <?php
var_dump($roles);
exit;
?>
            <?php foreach ($roles as $r): ?>
                <label style="display:block;">
                    <input type="checkbox" name="role_id[]" value="<?= (int)$r['id'] ?>" <?= in_array((int)$r['id'], $userRoleIds, true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($r['name']) ?> (<?= $r['code'] ?>)
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>> Активен</label>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="/users.php" class="btn btn-secondary">Отмена</a>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>

