<?php
/**
 * Установка пароля admin123 для пользователя admin@iis.local
 * Запуск: php update_admin_password.php
 */
$config = require __DIR__ . '/config.php';
$db = $config['database'];
$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'],
    $db['password']
);
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
$st->execute([$hash, 'admin@iis.local']);
echo $st->rowCount() ? "Пароль для admin@iis.local установлен (admin123).\n" : "Пользователь не найден. Сначала выполните schema.sql.\n";
