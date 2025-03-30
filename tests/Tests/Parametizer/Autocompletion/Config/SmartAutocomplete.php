<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

class SmartAutocomplete {
    public static function getConfigBuilder(): BuilderInterface {
        return TestUtils::newConfig()
            ->newOption('--opt', '-o')
            ->allowedValues([100, 200])

            ->newArrayOption('--opt-arr', '-a')
            ->allowedValues([80, 443], true) // Values are hidden from help, but not from completion.

            ->newFlag('--flag', '-f')

            ->newArrayArgument('arg-arr')
            ->allowedValues(['asd', 'qwe', 'zxc']);
    }
}
