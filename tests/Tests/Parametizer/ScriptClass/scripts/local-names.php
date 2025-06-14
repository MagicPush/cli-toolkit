<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

/** @noinspection PhpFullyQualifiedNameUsageInspection */
$scriptClassDetector = (new ScriptClassDetector(throwOnException: true))
    ->scriptClassNames([
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\lowercasename::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\Uppercasename::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\TwoWords::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\ABBR::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\WordABBR::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\ABBRWord::class,
        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames\SomeABBRWord::class,
    ]);

(new ScriptLauncher($scriptClassDetector))
    ->throwOnException()
    ->execute();
