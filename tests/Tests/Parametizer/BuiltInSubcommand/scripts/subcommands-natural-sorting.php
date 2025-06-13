<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    // Let's add subcommands with names in a "random" order. The sorting logic should fix the order.
    ->newSubcommand('script2', TestUtils::newConfig())
    ->newSubcommand('script', TestUtils::newConfig())
    ->newSubcommand('scripts', TestUtils::newConfig())
    ->newSubcommand('script10', TestUtils::newConfig())
    ->newSubcommand('script1', TestUtils::newConfig())

    ->run();
