<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->helpGeneratorUsageNonRequiredOptionsMax = (int) $argv[1];
unset($_SERVER['argv'][1]);

TestUtils::newConfig($envConfig)
    ->newOption('--opt-1')
    ->newOption('--opt-2')
    ->newFlag('--flag-1', '-f')
    ->newFlag('--flag-2', '-s')
    ->newOption('--required-1')->required()
    ->newOption('--required-2')->required()
    ->newOption('--required-3')->required()
    ->newOption('--required-4')->required()
    ->run();
