<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$request = TestUtils::newConfig()
    ->newOption('--opt') // The option is available on the "main" level, but it will be read from a subcommand request.

    ->newSubcommandSwitch('subcommand-name')
    ->newSubcommand('test', TestUtils::newConfig())

    ->run();

$subcommandRequest = $request->getSubcommandRequest();

echo var_export($subcommandRequest->parent->getParamAsString('opt'), true);
