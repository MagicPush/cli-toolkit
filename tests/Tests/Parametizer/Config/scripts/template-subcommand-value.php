<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand($argv[1] ?? '', Parametizer::newConfig());
