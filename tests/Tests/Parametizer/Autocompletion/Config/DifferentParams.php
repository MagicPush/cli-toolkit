<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Parametizer;

class DifferentParams {
    public static function getConfigBuilder(): BuilderInterface {
        return Parametizer::newConfig(throwOnException: true)
            ->newOption('--opt', '-o')
            ->allowedValuesDescribed([
                '100'     => 'One hundred',
                '200'     => 'Two hundred',
                '1000000' => 'One million',
            ])

            ->newOption('--any-value', '-a')

            ->newFlag('--flag', '-f')
            ->newFlag('--second-flag', '-s')

            ->newArgument('arg')
            ->allowedValues(['super', 'prefix', 'premium'])

            ->newArgument('arg-any-value')
            ->allowedValues(['aaa', 'bbb', 'ccc'])
            ->allowedValues([]); // Overwrite allowed values and ensure that previous completion list is emptied.
    }
}
