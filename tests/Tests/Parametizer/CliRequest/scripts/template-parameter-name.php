<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$parameterName = $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

$request = Parametizer::newConfig()
    ->newOption('--option-parameter')
    ->default('option_value')

    ->newArgument('argument-parameter')
    ->default('argument_value')

    ->run();

echo json_encode([
    'parameter_value'      => $request->getParam($parameterName),
    'all_parameter_values' => $request->getParams(),
]);
