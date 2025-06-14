<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

$launcherThrowOnException = (bool) $_SERVER['argv'][1];
$damageScriptFilename     = (bool) $_SERVER['argv'][2];
unset($_SERVER['argv'][1], $_SERVER['argv'][2]);

if ($damageScriptFilename) {
    $_SERVER['SCRIPT_FILENAME'] = '';
}

// Neither ScriptClassDetector nor ConfigBuilder instances must be specified to ensure `throwOnException` flag
// is passed to the automatically created instances.
(new ScriptLauncher(scriptClassDetector: null, configBuilder: null))
    ->throwOnException($launcherThrowOnException)
    ->execute();
