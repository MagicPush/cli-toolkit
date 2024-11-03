<?php
declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Parser;

class ParsedOption {
    public function __construct(
        public readonly mixed $value,
        public readonly string $alias,
    ) { }
}
