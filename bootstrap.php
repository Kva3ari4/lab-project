<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

\App\Auth::init();
