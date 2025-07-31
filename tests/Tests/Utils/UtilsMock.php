<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Utils;

use MagicPush\CliToolkit\Utils;

/**
 * Contains logic needed for tests only.
 */
final class UtilsMock extends Utils {
    public static function _getCachedTopmostProjectRootDirectory(): ?string {
        return static::$topmostProjectRootDirectory ?? null;
    }

    public static function _setCachedTopmostProjectRootDirectory(string $value): void {
        static::$topmostProjectRootDirectory = $value;
    }
}
