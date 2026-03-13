<?php
define('IIS_PPR', true);
require_once __DIR__ . '/bootstrap.php';
if (\App\Auth::isLoggedIn()) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
