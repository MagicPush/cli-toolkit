<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test', TestUtils::newConfig())

    ->run();

throw new Exception('No built-in subcommand call');
