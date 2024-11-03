<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newArgument('arg')
    ->required(false)
    ->validatorCallback(function (&$value) {
        $value .= ' UPDATED ARGUMENT';

        return true;
    })

    ->newArrayOption('--opt-list')
    ->validatorCallback(function (&$value) {
        $value .= ' UPDATED ELEMENT';

        return true;
    })

    ->run();

$valueArg     = $request->getParam('arg');
$valueOptList = $request->getParam('opt-list');
if ($valueArg) {
    echo $valueArg;
} elseif ($valueOptList) {
    echo implode('; ', $valueOptList);
}
