<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';

if (\App\Auth::isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Введите email и пароль.';
    } else {
        $pdo = \App\Database::getConnection();
        $st = $pdo->prepare('SELECT id, email, password_hash, full_name FROM users WHERE email = ? AND is_active = 1');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $stRoles = $pdo->prepare('SELECT r.code FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?');
            $stRoles->execute([$user['id']]);
            $roles = $stRoles->fetchAll(PDO::FETCH_COLUMN);
            \App\Auth::login((int) $user['id'], $user['email'], $roles);
            header('Location: /dashboard.php');
            exit;
        }
        $error = 'Неверный email или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — ИИС ППР</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1>ИИС ППР</h1>
        <p class="subtitle">Интеллектуальная система поддержки принятия решений</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Войти</button>
        </form>
        <p style="margin-top:16px; font-size:0.9rem; color:var(--text-muted);">
            Нет аккаунта? <a href="/register.php">Регистрация</a>
        </p>
    </div>
</body>
</html>
