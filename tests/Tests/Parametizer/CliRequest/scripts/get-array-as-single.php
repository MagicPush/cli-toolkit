<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newOption('--type')
    ->allowedValues(['bool', 'int', 'float', 'string'])

    ->newArrayOption('--option-array')

    ->run();

switch ($request->getParam('type')) {
    case 'bool':
        $request->getParamAsBool('option-array');
        break;

    case 'int':
        $request->getParamAsInt('option-array');
        break;

    case 'float':
        $request->getParamAsFloat('option-array');
        break;

    case 'string':
        $request->getParamAsString('option-array');
        break;

    default:
        break;
}
