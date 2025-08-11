<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$detectorThrowOnException = $_SERVER['argv'][1];
$detectorCacheFilePath    = $_SERVER['argv'][2];
unset($_SERVER['argv'][1], $_SERVER['argv'][2]);

$scriptClassDetector = ScriptClassDetector::create((bool) $detectorThrowOnException)
    ->cacheFilePath('' !== $detectorCacheFilePath ? $detectorCacheFilePath : null)
    ->searchDirectory(__DIR__, isRecursive: false); // It does not matter where to search.
ScriptClassLauncher::create($scriptClassDetector)
    ->throwOnException()
    ->execute();
