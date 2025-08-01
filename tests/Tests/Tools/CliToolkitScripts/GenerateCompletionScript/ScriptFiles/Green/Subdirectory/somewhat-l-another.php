<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../../init-console.php';

/** @noinspection PhpFullyQualifiedNameUsageInspection */
(new \MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher((new \MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector())->scriptClassName(\MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateCompletionScript\ScriptFiles\Blue\Something::class)))->execute();
