<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;

class HelpParameterDefinition {
    public function __construct(
        public readonly string $name,
        public readonly string $shortName,
        public readonly string $description,
        public readonly string $required,
    ) { }
}
