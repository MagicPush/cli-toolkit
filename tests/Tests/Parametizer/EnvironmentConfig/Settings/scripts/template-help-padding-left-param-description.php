<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->helpGeneratorPaddingLeftParameterDescription = (int) $argv[1];
unset($_SERVER['argv'][1]);

TestUtils::newConfig($envConfig)
    ->newOption('--some-option')
    ->description('Option description.')
    ->newArgument('some-argument')
    ->description('Argument description.')
    ->run();
