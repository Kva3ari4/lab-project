<?php
// Быстрая проверка: работает ли PHP на сервере
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>PHP</title></head><body>';
echo '<h1>PHP работает</h1>';
echo '<p>Версия PHP: <strong>' . phpversion() . '</strong></p>';
echo '<p>Расширение PDO: ' . (extension_loaded('pdo') ? 'да' : 'нет') . '</p>';
echo '<p>PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'да' : 'нет') . '</p>';
echo '<p><a href="login.php">Перейти на вход</a> | <a href="index.php">Главная</a></p>';
echo '</body></html>';
