<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

require_once __DIR__ . '/../../../../init-console.php';

$detector = new ScriptClassDetector();
$configBuilder = Parametizer::newConfig();
(new ScriptLauncher($detector, $configBuilder))
    ->throwOnException()
    ->useParentEnvConfigForSubcommands()
    ->execute();
