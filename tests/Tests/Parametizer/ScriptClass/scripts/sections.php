<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

/** @noinspection PhpFullyQualifiedNameUsageInspection */
$scriptClassDetector = ScriptClassDetector::create(throwOnException: true)
    ->scriptClassNames([
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Single::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Double::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Triple::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections\Spaced::class,
    ]);

ScriptClassLauncher::create($scriptClassDetector)
    ->throwOnException()
    ->execute();
