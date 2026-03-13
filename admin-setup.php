<?php
/**
 * Установка пароля admin123 для admin@iis.local
 * УДАЛИТЕ этот файл после использования!
 */
define('IIS_PPR', true);
header('Content-Type: text/html; charset=utf-8');
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('<h1>Ошибка</h1><p>Файл config.php не найден.</p>');
}
$config = require $configPath;
$db = $config['database'];
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
        $db['user'],
        $db['password']
    );
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $st->execute([$hash, 'admin@iis.local']);
    $msg = $st->rowCount() ? 'Пароль для admin@iis.local установлен (admin123).' : 'Пользователь admin@iis.local не найден. Сначала импортируйте schema.sql в БД.';
} catch (PDOException $e) {
    $msg = 'Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '. Проверьте config.php.';
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Настройка</title></head>
<body style="font-family:sans-serif;padding:40px;">
<h1>Установка пароля администратора</h1>
<p><?= htmlspecialchars($msg) ?></p>
<p><a href="login.php">Перейти на вход</a></p>
<p style="color:red;margin-top:30px;">После использования удалите файл admin-setup.php!</p>
</body>
</html>
