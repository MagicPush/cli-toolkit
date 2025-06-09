<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tools\CliToolkit\Classes\AutoloadDetector;

require_once __DIR__ . '/Classes/AutoloadDetector.php';

AutoloadDetector::detectAndRequire();

use Composer\Autoload\ClassLoader;

$composerLoader = new ClassLoader(__DIR__ . '/../../vendor');
$composerLoader->addPsr4('MagicPush\\CliToolkit\\Tools\\CliToolkit\\', [__DIR__]);
$composerLoader->register();
