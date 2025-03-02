<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Classes;

use MagicPush\CliToolkit\TerminalFormatter;

final class ScriptFormatter extends TerminalFormatter {
    public function section(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD]);
    }

    public function pathProcessed(string $text): string {
        return $this->apply($text, [static::FONT_CYAN]);
    }

    public function pathMentioned(string $text): string {
        return $this->apply($text, [static::FONT_YELLOW]);
    }

    public function success(string $text): string {
        return $this->apply($text, [static::FONT_GREEN, self::STYLE_BOLD]);
    }

    public function error(string $text): string {
        return $this->apply($text, [static::FONT_RED, self::STYLE_BOLD]);
    }

    public function command(string $text): string {
        return $this->apply($text, [static::STYLE_DIM]);
    }
}
