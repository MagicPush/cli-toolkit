<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

class ArrayOptionBuilder extends OptionBuilder {
    public function __construct(ConfigBuilder $configBuilder, string $name, ?string $shortName) {
        parent::__construct($configBuilder, $name, $shortName);

        $this->param
            ->setIsArray()
            ->default([]);
    }
}
