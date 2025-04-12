<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand')
    ->newSubcommand('test', TestUtils::newConfig())
    ->newSubcommand(
        'level-2',
        TestUtils::newConfig()
            ->newSubcommandSwitch('subcommand')
            ->newSubcommand('test-2', TestUtils::newConfig()),
    )

    ->run();

throw new Exception('No built-in subcommand call');
