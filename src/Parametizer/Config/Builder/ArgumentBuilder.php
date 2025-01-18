<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;

class ArgumentBuilder extends VariableBuilderAbstract {
    public function __construct(ConfigBuilder $configBuilder, string $name) {
        $this->configBuilder = $configBuilder;

        $this->param = (new Argument($name))
            ->require();

        $this->configBuilder->getConfig()->registerArgument($this->param);
    }
}
