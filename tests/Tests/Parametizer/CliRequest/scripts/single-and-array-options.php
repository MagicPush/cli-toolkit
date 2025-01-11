<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newOption('--type')
    ->allowedValues(['bool', 'int', 'float'])

    ->newOption('--single')

    ->newArrayOption('--array')

    ->run();

switch ($request->getParam('type')) {
    case 'bool':
        $valueSingle = $request->getParamAsBool('single');
        $valueArray  = $request->getParam('array'); // No bool casting for array elements.
        break;

    case 'int':
        $valueSingle = $request->getParamAsInt('single');
        $valueArray  = $request->getParamAsIntList('array');
        break;

    case 'float':
        $valueSingle = $request->getParamAsFloat('single');
        $valueArray  = $request->getParamAsFloatList('array');
        break;

    default:
        $valueSingle = $request->getParam('single');
        $valueArray  = $request->getParam('array');
        break;
}

echo json_encode(['single' => $valueSingle, 'array' => $valueArray]);
