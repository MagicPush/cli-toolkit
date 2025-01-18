<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

require_once __DIR__ . '/../../../init-console.php';

// Simulates a case, where config files search continues until the top of a file system is reached.
EnvironmentConfig::createFromConfigsBottomUpHierarchy('/var/log', '/tmp', true);
