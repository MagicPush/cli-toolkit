<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$envConfig = Parametizer::newConfig(throwOnException: true)->getConfig()->getEnvConfig();
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
