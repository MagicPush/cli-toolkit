<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

$isSameEnvConfigForSubcommands = (bool) $_SERVER['argv'][1];
$isEnvConfigManual             = (bool) $_SERVER['argv'][2];
unset($_SERVER['argv'][1], $_SERVER['argv'][2]);

$scriptDetector = (new ScriptDetector(true))
    ->searchDirectory(__DIR__ . '/../ScriptClasses');

if ($isEnvConfigManual) {
    $envConfig = new EnvironmentConfig();

    $envConfig->optionHelpShortName = 'M';

    $configBuilder = TestUtils::newConfig($envConfig);
} else {
    $configBuilder = null;
}

(new ScriptLauncher($scriptDetector, $configBuilder))
    ->useParentEnvConfigForSubcommands($isSameEnvConfigForSubcommands)
    ->execute();
