<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit;

abstract class ToolBelt {
    /**
     * Extracts and returns a short name (a namespace is stripped) of a provided fully qualified class name.
     *
     * Names of classes without namespaces are supported too - those are returned as is.
     */
    public static function getClassShortName(string $className): string {
        return mb_substr(mb_strrchr("\\{$className}", '\\'), 1);
    }
}
