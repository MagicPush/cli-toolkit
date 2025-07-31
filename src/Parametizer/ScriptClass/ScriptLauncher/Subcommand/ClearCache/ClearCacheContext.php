<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptLauncher\Subcommand\ClearCache;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Utils;
use RuntimeException;

class ClearCacheContext {
    /** @see ScriptClassDetector::getCacheFilePath() */
    public readonly string $detectorCacheFilePath;


    public function __construct(string $cacheFilePath) {
        if ('' === $cacheFilePath || !is_readable($cacheFilePath)) {
            throw new RuntimeException(
                Utils::getClassShortName(ScriptClassDetector::class) . ' cache file must exist and be readable: '
                    . var_export($cacheFilePath, true),
            );
        }

        $this->detectorCacheFilePath = $cacheFilePath;
    }
}
