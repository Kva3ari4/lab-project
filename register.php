<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';

if (\App\Auth::isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    if (!$email || !$password || !$full_name) {
        $error = 'Заполните все поля.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль не менее 6 символов.';
    } else {
        $pdo = \App\Database::getConnection();
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Такой email уже зарегистрирован.';
        } else {
            $roleStudent = $pdo->query("SELECT id FROM roles WHERE code = 'student'")->fetchColumn();
            $pdo->beginTransaction();
            try {
                $st = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)');
                $st->execute([$email, password_hash($password, PASSWORD_DEFAULT), $full_name]);
                $userId = $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleStudent]);
                $pdo->commit();
                $success = 'Регистрация успешна. Войдите в систему.';
                $_POST = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Ошибка регистрации. Попробуйте позже.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — ИИС ППР</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1>Регистрация</h1>
        <p class="subtitle">Создание аккаунта студента</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (!$success): ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="full_name">ФИО</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Зарегистрироваться</button>
        </form>
        <?php endif; ?>
        <p style="margin-top:16px; font-size:0.9rem; color:var(--text-muted);">
            <a href="/login.php">Вход</a>
        </p>
    </div>
</body>
</html>
