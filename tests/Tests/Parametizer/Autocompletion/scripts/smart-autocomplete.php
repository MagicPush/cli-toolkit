<?php declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config\SmartAutocomplete;

require_once __DIR__ . '/../../../init-console.php';

SmartAutocomplete::getConfigBuilder()->run();
