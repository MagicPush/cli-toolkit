<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Parametizer;

class SmartAutocomplete {
    public static function getConfigBuilder(): BuilderInterface {
        return Parametizer::newConfig()
            ->newOption('--opt', '-o')
            ->allowedValues([100, 200])

            ->newArrayOption('--opt-arr', '-a')
            ->allowedValues([80, 443])

            ->newFlag('--flag', '-f')

            ->newArrayArgument('arg-arr')
            ->allowedValues(['asd', 'qwe', 'zxc']);
    }
}
