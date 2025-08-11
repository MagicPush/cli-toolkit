<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$scriptClassDetector = ScriptClassDetector::create(throwOnException: true)
    ->searchDirectory(__DIR__ . '/ScriptClasses');
$configBuilder = Parametizer::newConfig(throwOnException: true)
    ->description('A launcher for cli-toolkit stock scripts.');

ScriptClassLauncher::create($scriptClassDetector, $configBuilder)
    ->throwOnException()
    ->execute();
