<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

/*
 * We want to make sure all subcommand switches are committed when the script is executed.
 *
 * We set up a situation here where an error will be caused by recursive handling of all config branches.
 * The exception will happen for the config that contains 2 subcommands with the same name, which is an illegal state.
 * If we get the error in the test without passing any parameter (without actually reaching the needed subcommand),
 * this means that the recursive handling actually took place.
 */

TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand-name')

    ->newSubcommand(
        'test11',
        TestUtils::newConfig()
            ->newSubcommandSwitch('subcommand-name-l2')
            ->newSubcommand('test21', TestUtils::newConfig())
            ->newSubcommand('test22', TestUtils::newConfig())

            ->newSubcommand(
                'test23',
                TestUtils::newConfig()
                    ->newSubcommandSwitch('subcommand-name-l3')
                    ->newSubcommand('test31', TestUtils::newConfig())
                    ->newSubcommand('test31', TestUtils::newConfig())
                    // Same subcommand name, exception will be thrown.

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )

    ->newSubcommand('test12', TestUtils::newConfig())

    ->newOption('--name')

    ->run();
