<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'multistring',
        Parametizer::newConfig(throwOnException: true)
            ->description('
                Short description on the first line.
                The rest of long description is omitted while shown beside subcommand possible values.
            ')
    )
    ->newSubcommand(
        'long-string',
        Parametizer::newConfig(throwOnException: true)
            ->description('
                Here is a sort of... short description. The long description continues on the same line and this line'
                . ' is too long, but it is still not enough so...
                Here is another line :)
            ')
    )
    ->newSubcommand(
        'long-string-short-sentence',
        Parametizer::newConfig(throwOnException: true)
            ->description('
                Too short to stop here. So the description continues for some more words before the limit is reached.
            '),
    )
    ->newSubcommand(
        'unbreakable-long-line',
        Parametizer::newConfig(throwOnException: true)
            ->description('
                Thatisareallylonglinebutthereisnowaytobreakitcorrectlysothelinewillbecutbrutallyafterthecharacterslimitisreached.
            '),
    )

    ->run();
