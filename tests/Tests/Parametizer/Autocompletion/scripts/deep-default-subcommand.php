<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommand(
        'level-2',
        TestUtils::newConfig()
            ->newSubcommand(
                'level-3',
                TestUtils::newConfig()
                    ->newArgument('argument')
                    ->allowedValues(['asd', 'zxc', 'qwe']),
            ),
    )

    ->run();
