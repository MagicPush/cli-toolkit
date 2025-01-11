<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig(throwOnException: true)
    ->newOption('--type')
    ->allowedValues(['int', 'float', 'string'])

    ->newOption('--option-single')

    ->run();

switch ($request->getParam('type')) {
    case 'int':
        $request->getParamAsIntList('option-single');
        break;

    case 'float':
        $request->getParamAsFloatList('option-single');
        break;

    case 'string':
        $request->getParamAsStringList('option-single');
        break;

    default:
        break;
}
