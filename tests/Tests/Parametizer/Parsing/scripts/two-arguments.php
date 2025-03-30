<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newArgument('argument-one')
    ->description('First argument')

    ->newArgument('argument-two')
    ->description('Second argument')

    ->newOption('--option')
    ->description('Some option')

    ->run();
