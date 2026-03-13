<?php
/**
 * Диагностика — показывает ошибки PHP и проверку подключения к БД
 * Удалите этот файл после отладки!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo '<h1>Диагностика ИИС ППР</h1>';
echo '<p>PHP: ' . phpversion() . '</p>';
echo '<p>PDO: ' . (extension_loaded('pdo') ? 'да' : 'нет') . '</p>';
echo '<p>PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'да' : 'нет') . '</p>';

$configPath = __DIR__ . '/config.php';
echo '<p>Путь к config: ' . $configPath . '</p>';
echo '<p>Config существует: ' . (file_exists($configPath) ? 'да' : 'нет') . '</p>';

if (!file_exists($configPath)) {
    echo '<p style="color:red;">Ошибка: config.php не найден.</p>';
    exit;
}

$config = require $configPath;
$db = $config['database'] ?? [];

echo '<h2>Настройки БД</h2>';
echo '<p>host: ' . htmlspecialchars($db['host'] ?? '') . '</p>';
echo '<p>name: ' . htmlspecialchars($db['name'] ?? '') . '</p>';
echo '<p>user: ' . htmlspecialchars($db['user'] ?? '') . '</p>';

try {
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=" . ($db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'] ?? '', $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo '<p style="color:green;">Подключение к БД: успешно</p>';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Таблицы: ' . implode(', ', $tables) . '</p>';
} catch (PDOException $e) {
    echo '<p style="color:red;">Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Проверьте в config.php: host, name, user, password.</p>';
}
