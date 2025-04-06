<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Classes;

class AutoloadDetector {
    public static function detectAndRequire(): void {
        $currentDirPath = __DIR__;
        $detectedPath   = null;
        while (true) {
            $autoloaderPath = $currentDirPath . '/vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                require_once $autoloaderPath;

                return;
            }

            $previousDirPath = $currentDirPath;
            $currentDirPath  = dirname($previousDirPath);
            // We can't go higher than a filesystem's top:
            if ($currentDirPath === $previousDirPath) {
                return;
            }
        }
    }
}
