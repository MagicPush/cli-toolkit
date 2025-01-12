<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig(throwOnException: true)
    ->newOption('--opt')
    ->default('opt-level-1')

    ->newSubcommandSwitch('branch')
    ->newSubcommand(
        'branch-red',
        Parametizer::newConfig(throwOnException: true)
            ->newOption('--opt')
            ->default('opt-level-2-red'),
    )
    ->newSubcommand(
        'branch-blue',
        Parametizer::newConfig(throwOnException: true)
            ->newOption('--opt')
            ->default('opt-level-2-blue'),
    )

    ->run();

$branch = $request->getParam('branch');
echo $request->getParam($branch)['opt']
    . ', '
    // Let's show that the method below is a more handy version of reading nested parameters, rendering the same values.
    . $request
        ->getSubcommandRequest($branch)
        ->getParam('opt');
