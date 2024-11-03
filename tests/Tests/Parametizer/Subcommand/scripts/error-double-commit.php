<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$config = Parametizer::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig())
    ->newSubcommand('test2', Parametizer::newConfig())

    ->getConfig();

/** This will be called inside of {@see Parametizer::run()}, causing an error. */
$config->finalize();

Parametizer::run($config);
