<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer;

use MagicPush\CliToolkit\TerminalFormatter;

final class HelpFormatter extends TerminalFormatter {
    public function italic(string $text): string {
        return $this->apply($text, [static::STYLE_ITALIC]);
    }

    public function command(string $text): string {
        return $this->apply($text, [static::STYLE_DIM]);
    }

    public function paramTitle(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD, static::FONT_LIGHT_GREEN]);
    }

    public function paramValue(string $text): string {
        return $this->apply($text, [static::STYLE_ITALIC, static::FONT_LIGHT_GREEN]);
    }

    public function paramRequired(string $text): string {
        return $this->apply($text, [static::FONT_LIGHT_RED]);
    }

    public function helpNote(string $text): string {
        return $this->apply($text, [static::FONT_YELLOW]);
    }

    public function helpImportant(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD, static::FONT_YELLOW]);
    }

    public function section(string $text): string {
        return $this->apply($text, [static::STYLE_UNDERLINED, static::STYLE_BOLD]);
    }

    public function error(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD, static::FONT_LIGHT_RED]);
    }
}
