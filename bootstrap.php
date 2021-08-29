<?php

use Illuminate\Database\Capsule\Manager as DB;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helpers.php';

$config = include __DIR__ . '/config.php';

$db = new DB;

$db->addConnection([
    'driver' => 'sqlite',
    'database' => $config['sqlite'],
]);

$db->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => $config['db_name'],
    'username' => $config['db_user'],
    'password' => $config['db_pswd'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
], 'mysql');

$db->setAsGlobal();
$db->bootEloquent();
