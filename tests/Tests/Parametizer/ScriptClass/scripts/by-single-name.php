<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$scriptClassName = $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

$scriptClassDetector = (new ScriptClassDetector(throwOnException: true))
    ->scriptClassName($scriptClassName);
(new ScriptLauncher($scriptClassDetector))
    ->throwOnException()
    ->execute();
