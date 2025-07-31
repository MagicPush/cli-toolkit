<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;

abstract class SomethingAbstract extends ScriptAbstract {
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
            ->description('Does something in all child classes.')

            ->newFlag('--flag', '-f')
            ->newOption('--option', '-o');
    }


    public function execute(): void {
        echo 'Flag: ' . $this->request->getParamAsInt('flag') . PHP_EOL;
        echo 'Option: "' . $this->request->getParamAsString('option') . '"' . PHP_EOL;
    }
}
