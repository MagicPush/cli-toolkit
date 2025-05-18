<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

$detectorThrowOnException = $_SERVER['argv'][1];
$detectorCacheFilePath    = $_SERVER['argv'][2];
unset($_SERVER['argv'][1], $_SERVER['argv'][2]);

$scriptDetector = (new ScriptDetector((bool) $detectorThrowOnException))
    ->cacheFilePath('' !== $detectorCacheFilePath ? $detectorCacheFilePath : null);
(new ScriptLauncher($scriptDetector))
    ->throwOnException()
    ->execute();
