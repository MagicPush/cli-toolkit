<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    // When you observe the whole config tree looking from the perspective of the very parent,
    // you may manually specify a deep subcommand usage example...
    ->usage(
        'test11 --name-l2=supername test23 --name-l3=nameLevelThree test32',
        'Very deep call',
    )

    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'test11',
        Parametizer::newConfig(throwOnException: true)
            ->newSubcommandSwitch('switchme-l2')
            ->newSubcommand('test21', Parametizer::newConfig(throwOnException: true))
            ->newSubcommand('test22', Parametizer::newConfig(throwOnException: true))
            ->newSubcommand(
                'test23',
                Parametizer::newConfig(throwOnException: true)
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
                    ->newSubcommand('test31', Parametizer::newConfig(throwOnException: true))
                    ->newSubcommand('test32', Parametizer::newConfig(throwOnException: true))

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )
    ->newSubcommand('test12', Parametizer::newConfig(throwOnException: true))

    ->run();
