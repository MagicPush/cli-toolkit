<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

$isSameEnvConfigForSubcommands = (bool) $_SERVER['argv'][1];
$isEnvConfigManual             = (bool) $_SERVER['argv'][2];
$cacheFilePath                 = $_SERVER['argv'][3];
unset($_SERVER['argv'][1], $_SERVER['argv'][2], $_SERVER['argv'][3]);

$scriptClassDetector = (new ScriptClassDetector(true))
    ->cacheFilePath($cacheFilePath)
    ->searchDirectory(__DIR__ . '/../ScriptClasses');

if ($isEnvConfigManual) {
    $envConfig = new EnvironmentConfig();

    $envConfig->optionHelpShortName = 'M';

    $configBuilder = TestUtils::newConfig($envConfig);
} else {
    $configBuilder = null;
}

(new ScriptClassLauncher($scriptClassDetector, $configBuilder))
    ->useParentEnvConfigForSubcommands($isSameEnvConfigForSubcommands)
    ->execute();
