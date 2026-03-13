<?php
namespace App;

class Auth
{
    private const ROLE_STUDENT = 'student';
    private const ROLE_HR = 'hr';
    private const ROLE_MANAGER = 'manager';
    private const ROLE_ADMIN = 'admin';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/config.php';
            session_name($config['session']['name'] ?? 'iis_ppr_session');
            session_start();
        }
    }

    public static function login(int $userId, string $email, array $roles): void
    {
        self::init();
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['roles'] = $roles;
        $_SESSION['login_at'] = time();
    }

    public static function logout(): void
    {
        self::init();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        self::init();
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        self::init();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function userRoles(): array
    {
        self::init();
        return $_SESSION['roles'] ?? [];
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::userRoles(), true);
    }

    public static function isStudent(): bool { return self::hasRole(self::ROLE_STUDENT); }
    public static function isHr(): bool { return self::hasRole(self::ROLE_HR); }
    public static function isManager(): bool { return self::hasRole(self::ROLE_MANAGER); }
    public static function isAdmin(): bool { return self::hasRole(self::ROLE_ADMIN); }

    /** Доступ к админ-функциям (управление пользователями, роли) */
    public static function canManageUsers(): bool
    {
        return self::isAdmin();
    }

    /** Доступ к анкетам кандидатов (создание, редактирование, импорт) */
    public static function canManageCandidates(): bool
    {
        return self::isHr() || self::isAdmin();
    }

    /** Доступ к программам, требованиям, квотам, запуск анализа */
    public static function canManagePrograms(): bool
    {
        return self::isManager() || self::isAdmin();
    }

    /** Просмотр рейтингов и распределения */
    public static function canViewScoring(): bool
    {
        return self::isHr() || self::isManager() || self::isAdmin();
    }

    /** Утверждение распределения */
    public static function canApproveAssignment(): bool
    {
        return self::isManager() || self::isAdmin();
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (!self::hasRole($role)) {
            self::forbidden();
        }
    }

    public static function requireAnyRole(array $roles): void
    {
        self::requireLogin();
        foreach ($roles as $role) {
            if (self::hasRole($role)) return;
        }
        self::forbidden();
    }

    public static function forbidden(): void
    {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Доступ запрещён</title></head><body><h1>403 — Доступ запрещён</h1><p>Недостаточно прав.</p><a href="/">На главную</a></body></html>';
        exit;
    }
}
