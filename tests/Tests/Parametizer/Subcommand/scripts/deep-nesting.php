<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$configBuilder = Parametizer::newConfig();
$config        = $configBuilder->getConfig();

$configBuilder
    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'test11',
        Parametizer::newConfig()
            ->newSubcommandSwitch('switchme-l2')
            ->newSubcommand('test21', Parametizer::newConfig())
            ->newSubcommand('test22', Parametizer::newConfig())
            ->newSubcommand(
                'test23',
                Parametizer::newConfig()
                    ->usage('test11 --name-l2=supername test23 test31')
                    ->usage(
                        'test11 --name-l2=supername test23 --name-l3=nameLevelThree test32',
                        'Very deep call',
                    )

                    ->newSubcommandSwitch('switchme-l3')
                    ->newSubcommand('test31', Parametizer::newConfig())
                    ->newSubcommand('test32', Parametizer::newConfig())

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )
    ->newSubcommand('test12', Parametizer::newConfig())

    ->newFlag('--print-option-names')
    ->callback(function () use ($config) {
        echo json_encode(getOptionNamesByBranches($config));

        exit;
    })

    ->run();


/**
 * @return array[]
 */
function getOptionNamesByBranches(Config $config): array {
    $branchOptionNames = [];
    foreach ($config->getBranches() as $branchIndex => $branchConfig) {
        $branchOptionNames[$branchIndex] = getOptionNamesByBranches($branchConfig);
    }

    $optionNames = [];
    foreach ($config->getOptions() as $option) {
        $optionNames[] = $option->getName();
    }

    if ($branchOptionNames) {
        $optionNames['BRANCHES'] = $branchOptionNames;
    }

    return $optionNames;
}
