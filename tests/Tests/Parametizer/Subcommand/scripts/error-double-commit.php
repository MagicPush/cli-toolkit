<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$config = TestUtils::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', TestUtils::newConfig())
    ->newSubcommand('test2', TestUtils::newConfig())

    ->getConfig();

/** This will be called inside of {@see Parametizer::run()}, causing an error. */
$config->finalize();

Parametizer::run($config);
