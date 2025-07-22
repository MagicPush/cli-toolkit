<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

$launcherThrowOnException = (bool) $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

// No ConfigBuilder instance must be specified to ensure `throwOnException` flag
// is passed to the automatically created instance.
(new ScriptLauncher((new ScriptClassDetector())->searchDirectory(__DIR__), configBuilder: null))
    ->throwOnException($launcherThrowOnException)
    ->execute();
