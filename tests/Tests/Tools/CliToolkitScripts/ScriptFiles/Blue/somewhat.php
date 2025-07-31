<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../../init-console.php';

$config = Parametizer::newConfig(new EnvironmentConfig(), throwOnException: true);
$config
    ->description('asd')
    ->newFlag('--asd');
$config->run();
