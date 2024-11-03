<?php
declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Parser;

class ParsedArgument {
    public function __construct(
        public readonly mixed $value,
    ) { }
}
