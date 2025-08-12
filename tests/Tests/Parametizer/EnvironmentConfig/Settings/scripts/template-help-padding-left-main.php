<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->helpGeneratorPaddingLeftMain = (int) $argv[1];
unset($_SERVER['argv'][1]);

TestUtils::newConfig($envConfig)
    ->description('
        Script description.
        Some more description.
    ')
    ->newArgument('some-argument')
    ->run();
