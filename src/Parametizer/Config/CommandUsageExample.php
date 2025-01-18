<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config;

class CommandUsageExample {
    public function __construct(public readonly string $example, public readonly ?string $description = null) { }
}
