<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\ToolBelt;

use MagicPush\CliToolkit\ToolBelt;

/**
 * Contains logic needed for tests only.
 */
final class ToolBeltMock extends ToolBelt {
    public static function _getCachedTopmostProjectRootDirectory(): ?string {
        return static::$topmostProjectRootDirectory ?? null;
    }

    public static function _setCachedTopmostProjectRootDirectory(string $value): void {
        static::$topmostProjectRootDirectory = $value;
    }
}
