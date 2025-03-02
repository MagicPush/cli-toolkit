<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;

$scriptDetector = (new ScriptDetector(true))
    ->addSearchClassPath(__DIR__ . '/Scripts')
    ->detect();
$classNamesBySubcommandNames = $scriptDetector->getFQClassNamesByScriptNames();

$builder = Parametizer::newConfig();
$builder
    ->newSubcommandSwitch('subcommand');

foreach ($classNamesBySubcommandNames as $subcommandName => $className) {
    $builder->newSubcommand($subcommandName, $className::getConfiguration());
}

$request = $builder->run();

$subcommandName    = $request->getParamAsString('subcommand');
$subcommandRequest = $request->getSubcommandRequest($subcommandName);

$className   = $classNamesBySubcommandNames[$subcommandName];
$scriptClass = new $className($subcommandRequest);
$scriptClass->execute();
