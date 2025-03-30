<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$config = TestUtils::newConfig();

$subcommandName = $argv[1] ?? null;
if (null !== $subcommandName) {
    $config
        ->newSubcommandSwitch('subcommand-name')
        ->newSubcommand(
            $subcommandName,
            TestUtils::newConfig()
                ->newArgument('arg')
                ->default('default-value'),
        );
}

$request = $config->run();

echo json_encode([
    'subcommand_name'           => $request->getSubcommandRequestName(),
    'subcommand_request_params' => $request->getSubcommandRequest()?->getParams(),
]);
