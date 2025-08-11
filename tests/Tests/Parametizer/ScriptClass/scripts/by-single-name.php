<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$scriptClassName = $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

$scriptClassDetector = ScriptClassDetector::create(throwOnException: true)
    ->scriptClassName($scriptClassName);
ScriptClassLauncher::create($scriptClassDetector)
    ->throwOnException()
    ->execute();
