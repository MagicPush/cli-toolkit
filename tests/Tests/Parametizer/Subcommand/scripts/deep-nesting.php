<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$configBuilder = TestUtils::newConfig();
$config        = $configBuilder->getConfig();

$configBuilder
    ->newSubcommandSwitch('subcommand-name')
    ->newSubcommand(
        'test11',
        TestUtils::newConfig()
            ->newSubcommandSwitch('subcommand-name-l2')
            ->newSubcommand('test21', TestUtils::newConfig())
            ->newSubcommand('test22', TestUtils::newConfig())
            ->newSubcommand(
                'test23',
                TestUtils::newConfig()
                    ->usage('test11 --name-l2=superName test23 test31')
                    ->usage(
                        'test11 --name-l2=superName test23 --name-l3=nameLevelThree test32',
                        'Very deep call',
                    )

                    ->newSubcommandSwitch('subcommand-name-l3')
                    ->newSubcommand('test31', TestUtils::newConfig())
                    ->newSubcommand('test32', TestUtils::newConfig())

                    ->newOption('--name-l3'),
            )

            ->newOption('--name-l2'),
    )
    ->newSubcommand('test12', TestUtils::newConfig())

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
