<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', TestUtils::newConfig())
    ->newSubcommand('test2', TestUtils::newConfig())

    ->newOption('--allowed')   // Options can be defined after subcommand switch,

    ->newArgument('forbidden') // ... but arguments can not.

    ->run();
