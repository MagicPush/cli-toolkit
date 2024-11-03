<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;

class OptionBuilder extends VariableBuilderAbstract {
    public function __construct(ConfigBuilder $configBuilder, string $name, ?string $shortName) {
        $this->configBuilder = $configBuilder;

        $validatedName      = static::getValidatedOptionName($name);
        $validatedShortName = static::getValidatedOptionShortName($shortName);

        $this->param = (new Option($validatedName))
            ->shortName($validatedShortName);

        $this->configBuilder->getConfig()->registerOption($this->param);
    }
}
