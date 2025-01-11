<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

/*
 * We want to make sure all subcommand switches are committed when the script is executed.
 *
 * We set up a situation here where an error will be caused by recursive handling of all config branches.
 * The exception will happen for the config in which only one subcommand is set up, which is an illegal state.
 * If we get the error in the test without passing any parameter (without actually reaching the needed subcommand),
 * this means that the recursive handling actually took place.
 */

Parametizer::newConfig(throwOnException: true)
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
                    ->newSubcommandSwitch('switchme-l3')
                    ->newSubcommand('test31', Parametizer::newConfig(throwOnException: true))
                    // No 2nd subcommand here, exception will be thrown.

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )

    ->newSubcommand('test12', Parametizer::newConfig(throwOnException: true))

    ->newOption('--name')

    ->run();
