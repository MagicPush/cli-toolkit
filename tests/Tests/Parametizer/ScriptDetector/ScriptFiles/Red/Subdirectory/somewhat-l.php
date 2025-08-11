<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

require_once __DIR__ . '/../../../../../init-console.php';

$detector      = ScriptClassDetector::create();
$configBuilder = Parametizer::newConfig();
ScriptClassLauncher::create($detector, $configBuilder)
    ->throwOnException()
    ->useParentEnvConfigForSubcommands()
    ->execute();
