<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

final class SomeClassNoNamespace extends ScriptAbstract {
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
            ->description('This class has no namespace');
    }


    public function execute(): void { }
}
