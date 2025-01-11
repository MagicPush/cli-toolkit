<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$config = Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig(throwOnException: true))
    ->newSubcommand('test2', Parametizer::newConfig(throwOnException: true))

    ->getConfig();

/** This will be called inside of {@see Parametizer::run()}, causing an error. */
$config->finalize();

Parametizer::run($config);
