<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config\DifferentParams;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config\SmartAutocomplete;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    /*
     * Let's add some parameters with the same short names as in subcommand configs,
     * but of other types - a flag instead of option and vice versa.
     * This way we ensure that names from the main config do not interfere with names in branch (subcommand) configs.
     */
    ->newFlag('--not-used-flag', '-o')
    ->newFlag('--not-used-flag-2', '-a')
    ->newOption('--not-used-option', '-f')

    ->newSubcommandSwitch('sub-script')
    ->newSubcommand('different-params', DifferentParams::getConfigBuilder())
    ->newSubcommand('smart-autocomplete', SmartAutocomplete::getConfigBuilder())

    ->run();
