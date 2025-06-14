<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

/** @noinspection PhpFullyQualifiedNameUsageInspection */
$scriptClassDetector = (new ScriptClassDetector(throwOnException: true))
    ->scriptClassNames([
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Single::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Double::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Triple::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Spaced::class,
    ]);

(new ScriptLauncher($scriptClassDetector))
    ->throwOnException()
    ->execute();
