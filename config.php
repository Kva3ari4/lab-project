<?php
/**
 * Конфигурация ИИС ППР
 * Интеллектуальная система поддержки принятия решений
 */

return [
    'app' => [
        'name' => 'ИИС ППР',
        'url' => 'http://localhost',
        'timezone' => 'Asia/Almaty',
        'debug' => true,
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'taranczov1',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'iis_ppr_session',
        'lifetime' => 7200,
    ],
];
