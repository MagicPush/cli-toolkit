<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$scriptClassDetector = (new ScriptClassDetector(throwOnException: true))
    ->searchDirectory(__DIR__ . '/ScriptClasses');
$configBuilder = Parametizer::newConfig(throwOnException: true);
$configBuilder->description('A launcher for cli-toolkit stock scripts.');

(new ScriptLauncher($scriptClassDetector, $configBuilder))
    ->execute();
