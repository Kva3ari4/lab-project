<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireRole('student');

$pdo = \App\Database::getConnection();
$st = $pdo->prepare('SELECT id FROM candidates WHERE user_id = ?');
$st->execute([\App\Auth::userId()]);
$c = $st->fetch();
if (!$c) { header('Location: /candidate-profile.php'); exit; }
$pdo->prepare('UPDATE candidates SET status = ? WHERE id = ?')->execute(['submitted', $c['id']]);
header('Location: /candidate-profile.php?submitted=1');
exit;
