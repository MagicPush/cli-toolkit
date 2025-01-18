<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;

final class FlagBuilder extends BuilderAbstract {
    public function __construct(ConfigBuilder $configBuilder, string $name, ?string $shortName) {
        $this->configBuilder = $configBuilder;

        $validatedName      = static::getValidatedOptionName($name);
        $validatedShortName = static::getValidatedOptionShortName($shortName);

        $this->param = (new Option($validatedName))
            ->shortName($validatedShortName)
            ->flagValue(true)
            ->default(false);

        $this->configBuilder->getConfig()->registerOption($this->param);
    }
}
