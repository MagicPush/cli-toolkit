<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newOption('--type')
    ->allowedValues(['bool', 'int', 'float', 'string'])

    ->newOption('--single')

    ->newArrayOption('--array')
    ->validatorCallback(function (&$value) {
        // Very odd value filtration, but useful for the type casting test.
        $value = match ($value) {
            '-'     => null,
            '1'     => true,
            default => $value,
        };

        return true;
    })

    ->run();

switch ($request->getParam('type')) {
    case 'bool':
        $valueSingle = $request->getParamAsBool('single');
        $valueArray  = $request->getParam('array'); // No bool casting is implemented for array elements.
        break;

    case 'int':
        $valueSingle = $request->getParamAsInt('single');
        $valueArray  = $request->getParamAsIntList('array');
        break;

    case 'float':
        $valueSingle = $request->getParamAsFloat('single');
        $valueArray  = $request->getParamAsFloatList('array');
        break;

    case 'string':
        $valueSingle = $request->getParamAsString('single');
        $valueArray  = $request->getParamAsStringList('array');
        break;

    default:
        $valueSingle = $request->getParam('single');
        $valueArray  = $request->getParam('array');
        break;
}

echo json_encode(['single' => $valueSingle, 'array' => $valueArray]);
