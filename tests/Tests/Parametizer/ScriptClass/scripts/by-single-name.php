<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

$scriptClassName = $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

$scriptDetector = (new ScriptDetector(throwOnException: true))
    ->scriptClassName($scriptClassName);
(new ScriptLauncher($scriptDetector))
    ->throwOnException()
    ->execute();
