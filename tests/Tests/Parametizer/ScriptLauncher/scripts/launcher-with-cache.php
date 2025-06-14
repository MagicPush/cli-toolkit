<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$detectorThrowOnException = $_SERVER['argv'][1];
$detectorCacheFilePath    = $_SERVER['argv'][2];
unset($_SERVER['argv'][1], $_SERVER['argv'][2]);

$scriptClassDetector = (new ScriptClassDetector((bool) $detectorThrowOnException))
    ->cacheFilePath('' !== $detectorCacheFilePath ? $detectorCacheFilePath : null)
    ->searchDirectory(__DIR__, false); // It does not matter where to search.
(new ScriptLauncher($scriptClassDetector))
    ->throwOnException()
    ->execute();
