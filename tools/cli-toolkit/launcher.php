<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

$scriptDetector = (new ScriptDetector(throwOnException: true))
    ->searchClassPath(__DIR__ . '/Scripts');
$configBuilder = Parametizer::newConfig(throwOnException: true);
$configBuilder->description('A launcher for cli-toolkit stock scripts.');

(new ScriptLauncher($scriptDetector, $configBuilder))
    ->execute();
