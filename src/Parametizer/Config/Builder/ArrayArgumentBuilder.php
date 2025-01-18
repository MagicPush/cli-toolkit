<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

final class ArrayArgumentBuilder extends ArgumentBuilder {
    public function __construct(ConfigBuilder $configBuilder, string $name) {
        parent::__construct($configBuilder, $name);

        $this->param
            ->setIsArray()
            ->default([]);
    }
}
