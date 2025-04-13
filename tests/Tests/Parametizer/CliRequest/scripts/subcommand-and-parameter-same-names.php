<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$request = TestUtils::newConfig()
    ->newArgument('argument')

    ->newOption('--option')
    ->default('option-default')

    ->newSubcommandSwitch('subcommand')
    ->newSubcommand('argument', TestUtils::newConfig())
    ->newSubcommand('option', TestUtils::newConfig())

    ->run();

echo json_encode($request->getParams());
