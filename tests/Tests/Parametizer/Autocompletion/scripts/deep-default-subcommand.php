<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand')
    ->newSubcommand(
        'level-2',
        TestUtils::newConfig()
            ->newSubcommandSwitch('subcommand-l2')
            ->newSubcommand(
                'level-3',
                TestUtils::newConfig()
                    ->newArgument('argument')
                    ->allowedValues(['asd', 'zxc', 'qwe']),
            ),
    )

    ->run();
