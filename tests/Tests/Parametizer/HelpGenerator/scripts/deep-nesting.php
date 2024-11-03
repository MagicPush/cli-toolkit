<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'test11',
        Parametizer::newConfig()
            ->newSubcommandSwitch('switchme-l2')
            ->newSubcommand('test21', Parametizer::newConfig())
            ->newSubcommand('test22', Parametizer::newConfig())
            ->newSubcommand(
                'test23',
                Parametizer::newConfig()
                    ->usage('test11 --name-l2=supername test23 test31')
                    ->usage(
                        'test11 --name-l2=supername test23 --name-l3=nameLevelThree test32',
                        'Very deep call',
                    )

                    ->newSubcommandSwitch('switchme-l3')
                    ->newSubcommand('test31', Parametizer::newConfig())
                    ->newSubcommand('test32', Parametizer::newConfig())

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )
    ->newSubcommand('test12', Parametizer::newConfig())

    ->run();
