<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->optionHelpShortName = $argv[2] ?? null;

Parametizer::newConfig($envConfig, throwOnException: true)
    ->run();
