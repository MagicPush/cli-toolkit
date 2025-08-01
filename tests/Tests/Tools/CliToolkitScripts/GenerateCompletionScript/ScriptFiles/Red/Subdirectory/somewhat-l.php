<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateCompletionScript\ScriptFiles\Blue\Something;

require_once __DIR__ . '/../../../../../../init-console.php';

$detector = (new ScriptClassDetector())->scriptClassName(Something::class);
$configBuilder = Parametizer::newConfig();
(new ScriptClassLauncher($detector, $configBuilder))
    ->throwOnException()
    ->useParentEnvConfigForSubcommands()
    ->execute();
