<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$parentEnvConfig = new EnvironmentConfig();

$parentEnvConfig->optionHelpShortName = 'X';

TestUtils::newConfig($parentEnvConfig)
    ->newSubcommand('something', TestUtils::newConfig())

    ->run();
