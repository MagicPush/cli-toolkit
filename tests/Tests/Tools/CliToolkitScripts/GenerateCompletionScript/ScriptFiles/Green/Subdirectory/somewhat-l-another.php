<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../../init-console.php';

/** @noinspection PhpFullyQualifiedNameUsageInspection */
\MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher::create(\MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector::create()->scriptClassName(\MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateCompletionScript\ScriptFiles\Blue\Something::class))->execute();
