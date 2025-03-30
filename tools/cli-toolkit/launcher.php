<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;

$scriptDetector = (new ScriptDetector(true))
    ->searchClassPath(__DIR__ . '/Scripts')
    ->detect();
$classNamesBySubcommandNames = $scriptDetector->getFQClassNamesByScriptNames();

$builder = Parametizer::newConfig();
$builder
    ->description('A launcher for cli-toolkit stock scripts.')
    ->newSubcommandSwitch('subcommand');

foreach ($classNamesBySubcommandNames as $subcommandName => $className) {
    $builder->newSubcommand($subcommandName, $className::getConfiguration());
}

$request = $builder->run();

$className   = $classNamesBySubcommandNames[$request->getSubcommandRequestName()];
$scriptClass = new $className($request->getSubcommandRequest());
$scriptClass->execute();
