<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$request = TestUtils::newConfig()
    ->newOption('--opt')
    ->default('opt-level-1')

    ->newSubcommandSwitch('branch')
    ->newSubcommand(
        'branch-red',
        TestUtils::newConfig()
            ->newOption('--opt')
            ->default('opt-level-2-red'),
    )
    ->newSubcommand(
        'branch-blue',
        TestUtils::newConfig()
            ->newOption('--opt')
            ->default('opt-level-2-blue'),
    )

    ->run();

echo $request->getParam($request->getSubcommandRequestName())['opt']
    . ', '
    // Let's show that the method below is a more handy version of reading nested parameters, rendering the same values.
    . $request
        ->getSubcommandRequest()
        ->getParam('opt');
