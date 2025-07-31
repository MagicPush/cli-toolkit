<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config\SmartCompletion;

require_once __DIR__ . '/../../../init-console.php';

SmartCompletion::getConfigBuilder()->run();
