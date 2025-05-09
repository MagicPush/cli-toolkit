<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache;

use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use RuntimeException;

class ClearCacheContext {
    /** @see ScriptDetector::getCacheFilePath() */
    public readonly string $cacheFilePath;


    public function __construct(string $cacheFilePath) {
        if ('' === $cacheFilePath || !is_readable($cacheFilePath)) {
            throw new RuntimeException(
                'ScriptDetector cache file must exist and be readable: ' . var_export($cacheFilePath, true),
            );
        }

        $this->cacheFilePath = $cacheFilePath;
    }
}
