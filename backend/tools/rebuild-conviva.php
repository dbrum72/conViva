<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Explicitly restricted to the database approved for this conversion.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $connection['database'] !== 'conviva_db' || ! empty($connection['url'])) {
    throw new RuntimeException('Reconstrução recusada: o alvo deve ser exclusivamente conviva_db, sem URL alternativa.');
}
$pdo = new PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';charset=utf8mb4', $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `conviva_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$exit = Artisan::call('migrate:fresh', ['--database' => 'mysql', '--seed' => true, '--force' => true]);
echo Artisan::output();
exit($exit);
