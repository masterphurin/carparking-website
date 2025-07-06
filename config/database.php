<?php
/**
 * Database Configuration
 * การกำหนดค่าฐานข้อมูล
 */

// การกำหนดค่าฐานข้อมูล
$db_config = [
    'host' => 'localhost',
    'dbname' => 'parking',
    'username' => 'root',
    'password' => 'Kittisak644245001',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

// กำหนด timezone
date_default_timezone_set('Asia/Bangkok');

return $db_config;
