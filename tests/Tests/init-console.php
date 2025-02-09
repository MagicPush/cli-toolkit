<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Composer\Autoload\ClassLoader;

// Loading test classes manually to ensure that the test classes are not loaded accidentally inside '/src' code.
$composerLoader = new ClassLoader(__DIR__ . '/../../vendor');
$composerLoader->addPsr4('MagicPush\\CliToolkit\\Tests\\', [__DIR__ . '/../../tests']);
$composerLoader->register();

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
ini_set('max_execution_time', 2);
