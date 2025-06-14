<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptDetector;

class SearchDirectoryContext {
    public function __construct(public readonly string $normalizedPath, public readonly bool $isRecursive) { }
}
