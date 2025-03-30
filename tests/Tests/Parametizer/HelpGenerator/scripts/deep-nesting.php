<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    // When you observe the whole config tree looking from the perspective of the very parent,
    // you may manually specify a deep subcommand usage example...
    ->usage(
        'test11 --name-l2=supername test23 --name-l3=nameLevelThree test32',
        'Very deep call',
    )

    ->newSubcommandSwitch('switchme')
    ->description('LEVEL 1')
    ->newSubcommand(
        'test11',
        TestUtils::newConfig()
            ->newSubcommandSwitch('switchme-l2')
            ->description('LEVEL 2')
            ->newSubcommand('test21', TestUtils::newConfig())
            ->newSubcommand('test22', TestUtils::newConfig())
            ->newSubcommand(
                'test23',
                TestUtils::newConfig()
                    /*
                     * ... However when looking from the perspective of a branch, you should not be aware of parent
                     * configs - think of branches as configs included automatically during runtime.
                     * The point is that the help generator should detect correct paths up to the very top
                     * and reflect it in the subcommand help usage block.
                     */
                    ->usage('test31')
                    ->usage(
                        '--name-l3=nameLevelThree test32',
                        'Very deep call',
                    )

                    ->newSubcommandSwitch('switchme-l3')
                    ->description('LEVEL 3')
                    ->newSubcommand('test31', TestUtils::newConfig())
                    ->newSubcommand('test32', TestUtils::newConfig())

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )
    ->newSubcommand('test12', TestUtils::newConfig())

    ->run();
