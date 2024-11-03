<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Question;

use MagicPush\CliToolkit\TerminalFormatter;

final class QuestionFormatter extends TerminalFormatter {
    public function value(string $text): string {
        return $this->apply($text, [static::STYLE_ITALIC, static::FONT_LIGHT_GREEN]);
    }

    public function defaultValue(string $text): string {
        return $this->apply($text, [static::FONT_YELLOW]);
    }

    public function error(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD, static::FONT_LIGHT_RED]);
    }

    public function question(string $text): string {
        return $this->apply($text, [static::STYLE_BOLD, static::FONT_LIGHT_YELLOW]);
    }
}
