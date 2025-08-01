<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

/** @noinspection PhpFullyQualifiedNameUsageInspection */
(new \MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher(new \MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector()))->execute();
