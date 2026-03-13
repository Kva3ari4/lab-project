<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
\App\Auth::requireLogin();
\App\Auth::requireAnyRole(['manager', 'admin']);

$pdo = \App\Database::getConnection();
$candidateId = (int)($_POST['candidate_id'] ?? $_GET['candidate_id'] ?? 0);
$programId = (int)($_POST['program_id'] ?? $_GET['program_id'] ?? 0);
if (!$candidateId || !$programId) {
    header('Location: /assignments.php');
    exit;
}
$exists = $pdo->prepare('SELECT id FROM assignments WHERE candidate_id = ? AND program_id = ?');
$exists->execute([$candidateId, $programId]);
if (!$exists->fetch()) {
    $pdo->prepare('INSERT INTO assignments (candidate_id, program_id, status) VALUES (?,?,?)')->execute([$candidateId, $programId, 'proposed']);
}
header('Location: /assignments.php?program_id=' . $programId);
exit;
