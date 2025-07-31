<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../init-console.php';

/** @noinspection PhpFullyQualifiedNameUsageInspection */
(new \MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher((new \MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector())->scriptClassName(\MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\ScriptFiles\Blue\Something::class)))->execute();
