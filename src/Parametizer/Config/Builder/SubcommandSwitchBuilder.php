<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;

class SubcommandSwitchBuilder extends BuilderAbstract {
    public function __construct(ConfigBuilder $configBuilder, string $name) {
        $this->configBuilder = $configBuilder;

        $this->param = (new Argument($name))
            ->require()
            ->setIsSubcommandSwitch();

        $this->configBuilder->getConfig()->registerArgument($this->param);
    }
}
