<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$envConfigMain = new EnvironmentConfig();
$envConfigL2S2 = new EnvironmentConfig();
$envConfigL3S1 = new EnvironmentConfig();

$envConfigMain->optionHelpShortName = 'X';
$envConfigL2S2->optionHelpShortName = 'y';
$envConfigL3S1->optionHelpShortName = 'z';

Parametizer::newConfig($envConfigMain, throwOnException: true)
    ->newSubcommandSwitch('switchme-l1')
    ->newSubcommand('conf-l2-s1', Parametizer::newConfig(throwOnException: true))
    ->newSubcommand(
        'conf-l2-s2',
        Parametizer::newConfig($envConfigL2S2, throwOnException: true)
            ->newSubcommandSwitch('switchme-l2-s2')
            ->newSubcommand('conf-l3-s1', Parametizer::newConfig($envConfigL3S1, throwOnException: true))
            ->newSubcommand('conf-l3-s2', Parametizer::newConfig(throwOnException: true)),
    )
    ->run();
