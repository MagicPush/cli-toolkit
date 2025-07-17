<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;

class Something extends SomethingAbstract {
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
            ->description("
                {$configBuilder->getConfig()->getDescription()}
                
                This exact class processes different parameter types.
            ")

            ->newArrayArgument('array-argument');
    }


    public function execute(): void {
        parent::execute();

        echo 'Argument list: ' . implode('|', $this->request->getParamAsStringList('array-argument')) . PHP_EOL;
    }
}
