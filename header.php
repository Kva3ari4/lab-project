<?php
if (!defined('IIS_PPR')) exit;
$currentPage = $currentPage ?? 'home';
$pageTitle = $pageTitle ?? 'ИИС ППР';
$roles = \App\Auth::userRoles();
$roleNames = [
    'student' => 'Студент',
    'hr' => 'HR-оператор',
    'manager' => 'Руководитель программы',
    'admin' => 'Администратор',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — ИИС ППР</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">ИИС ППР</div>
        <nav class="sidebar-nav">
            <div class="group">Навигация</div>
            <a href="/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Главная</a>
            <?php if (\App\Auth::isStudent()): ?>
                <a href="/candidate-profile.php" class="<?= $currentPage === 'profile' ? 'active' : '' ?>">Моя анкета</a>
                <a href="/my-applications.php" class="<?= $currentPage === 'applications' ? 'active' : '' ?>">Мои заявки</a>
            <?php endif; ?>
            <?php if (\App\Auth::canManageCandidates()): ?>
                <div class="group">Кандидаты</div>
                <a href="/candidates.php" class="<?= $currentPage === 'candidates' ? 'active' : '' ?>">Анкеты кандидатов</a>
                <a href="/candidates-import.php" class="<?= $currentPage === 'import' ? 'active' : '' ?>">Импорт анкет</a>
            <?php endif; ?>
            <?php if (\App\Auth::canManagePrograms()): ?>
                <div class="group">Программы</div>
                <a href="/programs.php" class="<?= $currentPage === 'programs' ? 'active' : '' ?>">Программы стажировок</a>
                <a href="/scoring.php" class="<?= $currentPage === 'scoring' ? 'active' : '' ?>">Анализ и рейтинг</a>
                <a href="/assignments.php" class="<?= $currentPage === 'assignments' ? 'active' : '' ?>">Распределение</a>
            <?php endif; ?>
            <?php if (\App\Auth::canViewScoring() && !\App\Auth::canManagePrograms()): ?>
                <a href="/scoring.php" class="<?= $currentPage === 'scoring' ? 'active' : '' ?>">Рейтинги</a>
            <?php endif; ?>
            <?php if (\App\Auth::canManageUsers()): ?>
                <div class="group">Администрирование</div>
                <a href="/users.php" class="<?= $currentPage === 'users' ? 'active' : '' ?>">Пользователи</a>
                <a href="/skills.php" class="<?= $currentPage === 'skills' ? 'active' : '' ?>">Справочник навыков</a>
            <?php endif; ?>
        </nav>
        <div class="user-bar">
            <strong><?= htmlspecialchars($_SESSION['email'] ?? '') ?></strong><br>
            <?= implode(', ', array_map(fn($r) => $roleNames[$r] ?? $r, $roles)) ?><br>
            <a href="/logout.php">Выйти</a>
        </div>
    </aside>
    <main class="main">
