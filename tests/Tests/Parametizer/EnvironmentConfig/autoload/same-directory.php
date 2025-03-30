<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$envConfig = TestUtils::newConfig()->getConfig()->getEnvConfig();
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
