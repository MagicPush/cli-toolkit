<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newOption('--type')
    ->allowedValues(['bool', 'int', 'float'])

    ->newOption('--option-single')

    ->run();

switch ($request->getParam('type')) {
    case 'int':
        $request->getParamAsIntList('option-single');
        break;

    case 'float':
        $request->getParamAsFloatList('option-single');
        break;

    default:
        break;
}
