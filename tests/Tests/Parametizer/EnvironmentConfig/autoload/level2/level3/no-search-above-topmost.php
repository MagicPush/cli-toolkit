<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../../init-console.php';

$envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy(__DIR__, __DIR__, true);
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
