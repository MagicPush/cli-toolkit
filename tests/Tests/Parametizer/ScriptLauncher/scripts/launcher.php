<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

$detectorThrowOnException = $argv[1];
$detectorCacheFilePath    = $argv[2];
unset ($_SERVER['argv'][1], $_SERVER['argv'][2]);

$scriptDetector = (new ScriptDetector((bool) $detectorThrowOnException))
    ->cacheFilePath('' !== $detectorCacheFilePath ? $detectorCacheFilePath : null)
    ->searchClassPath(__DIR__);
(new ScriptLauncher($scriptDetector, Parametizer::newConfig(throwOnException: true)))
    ->execute();
