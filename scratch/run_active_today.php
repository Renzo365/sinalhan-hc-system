<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once $baseDir . '/app/helpers.php';

$c = new \App\Controllers\QueueController();
$c->activeToday();
