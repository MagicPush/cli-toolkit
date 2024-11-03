<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    // Forgot to set up the subcommand switch.
    ->newSubcommand('test1', Parametizer::newConfig())
    ->newSubcommand('test2', Parametizer::newConfig())
    ->run();
