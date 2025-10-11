<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['KERNEL_CLASS'] ??= 'App\Kernel';
$_SERVER['DATABASE_URL'] ??= 'sqlite:///%kernel.cache_dir%/test.db';

date_default_timezone_set('Europe/Madrid');
