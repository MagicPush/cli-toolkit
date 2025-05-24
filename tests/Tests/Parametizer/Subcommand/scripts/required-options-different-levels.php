<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommand(
        'test11',
        TestUtils::newConfig()
            ->newArgument('required-arg-l2')
            ->description('Subcommand required argument')

            ->newOption('--required-l2')
            ->description('Subcommand required option')
            ->required(),
    )
    ->newSubcommand('test12', TestUtils::newConfig())

    ->newOption('--required')
    ->description('Required option')
    ->required()

    ->run();
