<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    // Forgot to set up the subcommand switch.
    ->newSubcommand('test1', TestUtils::newConfig())
    ->newSubcommand('test2', TestUtils::newConfig())
    ->run();
