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

    protected static string $topmostProjectRootDirectory;

    /**
     * Performs bottom-up search for a path with a `vendor` directory inside it, which is located closest to `/`.
     * If fails to find one, eventually returns a topmost directory path in a file system (like `/`).
     *
     * For example: the function will return `/home/user/cool-project`, if starts searching from
     * `/home/user/cool-project/vendor/sup-project/vendor/MagicPush/cli-tool/src/ToolBelt.php`
     */
    public static function detectTopmostProjectRootDirectory(): string {
        if (!isset(static::$topmostProjectRootDirectory)) {
            $currentDirPath            = __DIR__;
            $highestDirPathAboveVendor = null;
            while (true) {
                if (file_exists($currentDirPath . '/vendor')) {
                    $highestDirPathAboveVendor = $currentDirPath;
                }

                $previousDirPath = $currentDirPath;
                $currentDirPath  = dirname($previousDirPath);
                // We can't go higher than a filesystem's top:
                if ($currentDirPath === $previousDirPath) {
                    static::$topmostProjectRootDirectory = $highestDirPathAboveVendor ?? $currentDirPath;

                    break;
                }
            }
        }

        return static::$topmostProjectRootDirectory;
    }
}
