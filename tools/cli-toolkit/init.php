<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Composer\Autoload\ClassLoader;

$composerLoader = new ClassLoader(__DIR__ . '/../../vendor');
$composerLoader->addPsr4('MagicPush\\CliToolkit\\Tools\\CliToolkit\\', [__DIR__]);
$composerLoader->register();

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
