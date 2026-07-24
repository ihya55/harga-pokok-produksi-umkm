<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once ROOT_PATH . '/vendor/autoload.php';

$capsule = new Capsule();

$capsule->addConnection([
    'driver'    => DB_DRIVER,
    'host'      => DB_HOST,
    'port'      => DB_PORT,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASS,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();