<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit;

use LogicException;

class TerminalFormatter {
    /*
     * Different codes are supported by different terminal types:
     * something may work everywhere, something - only for particular terminals.
     *
     * Sources:
     *  * https://misc.flogisoft.com/bash/tip_colors_and_formatting
     *  * https://en.wikipedia.org/wiki/ANSI_escape_code
     */

    /**
     * Resets style, font color and background color.
     *
     * In some terminals may not reset {@see STYLE_INVERT}. Use explicitly {@see RESET_STYLE_INVERT} in this case.
     */
    final public const int RESET_ALL = 0;

    // FONT STYLES

    final public const int STYLE_BOLD       = 1;
    final public const int STYLE_DIM        = 2;
    final public const int STYLE_ITALIC     = 3;
    final public const int STYLE_UNDERLINED = 4;
    final public const int STYLE_BLINK      = 5;
    final public const int STYLE_INVERT     = 7;
    final public const int STYLE_STRIKE     = 9;
    final public const int STYLE_OVERLINED  = 53;

    /** Visual effect only: you will see "hidden" contents after copy-pasting it right in the same terminal. */
    final public const int STYLE_HIDDEN = 8;

    /**
     * Adds a double underline.
     *
     * In some terminals may reset bold / bright instead.
     */
    final public const int STYLE_UNDERLINED_DOUBLE = 21;

    final public const int RESET_STYLE_BOLD_OR_DIM = 22;
    final public const int RESET_STYLE_ITALIC      = 23;
    final public const int RESET_STYLE_UNDERLINED  = 24;
    final public const int RESET_STYLE_BLINK       = 25;
    final public const int RESET_STYLE_INVERT      = 27;
    final public const int RESET_STYLE_HIDDEN      = 28;
    final public const int RESET_STYLE_STRIKE      = 29;
    final public const int RESET_STYLE_OVERLINED   = 55;

    // FONT COLORS

    final public const int FONT_BLACK         = 30;
    final public const int FONT_RED           = 31;
    final public const int FONT_GREEN         = 32;
    final public const int FONT_YELLOW        = 33;
    final public const int FONT_BLUE          = 34;
    final public const int FONT_MAGENTA       = 35;
    final public const int FONT_CYAN          = 36;
    final public const int FONT_LIGHT_GRAY    = 37;
    final public const int FONT_DARK_GRAY     = 90;
    final public const int FONT_LIGHT_RED     = 91;
    final public const int FONT_LIGHT_GREEN   = 92;
    final public const int FONT_LIGHT_YELLOW  = 93;
    final public const int FONT_LIGHT_BLUE    = 94;
    final public const int FONT_LIGHT_MAGENTA = 95;
    final public const int FONT_LIGHT_CYAN    = 96;
    final public const int FONT_WHITE         = 97;

    /**
     * Special code that allows to set a font color with additional codes.
     *
     * Possible patterns:
     *  * 256-color palette - `38;5;N`, where `N` is 0-255, one of the palette colors.
     *  * RGB: `38;2;R;G;B`, where `R`, `G`, and `B` are 0-255 for red, green, and blue color strength respectively.
     */
    final public const int FONT_CUSTOM = 38;

    final public const int RESET_FONT = 39;

    /**
     * Special code that allows to set an underline color with additional codes.
     *
     * Possible patterns:
     *  * 256-color palette - `58;5;N`, where `N` is 0-255, one of the palette colors.
     *  * RGB: `58;2;R;G;B`, where `R`, `G`, and `B` are 0-255 for red, green, and blue color strength respectively.
     *
     * Enable {@see TerminalFormatter::STYLE_UNDERLINED} to notice the effect.
     *
     * **Warning!** Some terminals do not support underline custom color in a nasty way: the code is ignored
     * and the sequence after the code is treated as just other codes like styles, fonts or backgrounds.
     * For instance, the sequence after `58` - `2;0;208;98` ("emerald green" in RGB) - will be treated as a list of
     * separate codes, where '0' means not 'R' (red) part, but an independent {@see RESET_ALL} code.
     */
    final public const int FONT_UNDERLINED_CUSTOM = 58;

    /**
     * Special code that disables underline custom color ({@see TerminalFormatter::FONT_UNDERLINED_CUSTOM}).
     *
     * On the contrary, {@see TerminalFormatter::RESET_STYLE_UNDERLINED}
     * disables the underline itself ({@see TerminalFormatter::STYLE_UNDERLINED}).
     */
    final public const int RESET_FONT_UNDERLINED_CUSTOM = 59;

    // BACKGROUND COLORS

    final public const int BACKGROUND_BLACK         = 40;
    final public const int BACKGROUND_RED           = 41;
    final public const int BACKGROUND_GREEN         = 42;
    final public const int BACKGROUND_YELLOW        = 43;
    final public const int BACKGROUND_BLUE          = 44;
    final public const int BACKGROUND_MAGENTA       = 45;
    final public const int BACKGROUND_CYAN          = 46;
    final public const int BACKGROUND_LIGHT_GRAY    = 47;
    final public const int BACKGROUND_DARK_GRAY     = 100;
    final public const int BACKGROUND_LIGHT_RED     = 101;
    final public const int BACKGROUND_LIGHT_GREEN   = 102;
    final public const int BACKGROUND_LIGHT_YELLOW  = 103;
    final public const int BACKGROUND_LIGHT_BLUE    = 104;
    final public const int BACKGROUND_LIGHT_MAGENTA = 105;
    final public const int BACKGROUND_LIGHT_CYAN    = 106;
    final public const int BACKGROUND_WHITE         = 107;

    /**
     * Special code that allows to set a background color with additional codes.
     *
     * Possible patterns:
     *  * 256-color palette - `48;5;N`, where `N` is 0-255, one of the palette colors.
     *  * RGB: `48;2;R;G;B`, where `R`, `G`, and `B` are 0-255 for red, green, and blue color strength respectively.
     */
    final public const int BACKGROUND_CUSTOM = 48;

    final public const int RESET_BACKGROUND = 49;

    /**
     * If the font functionality is disabled.
     *
     * Useful when redirecting a stream with some formatted text into a file or somewhere else (like `less` tool),
     * where font escape sequences are not processed.
     */
    protected readonly bool $isDisabled;


    /**
     * @param false|resource $resource Output stream or file descriptor is used for auto-detection,
     *                                 if real output happens in terminal or somewhere else ({@see posix_isatty()}):
     *                                 actual styling is disabled if output happens not in an interactive terminal.
     */
    public function __construct(mixed $resource) {
        $this->isDisabled = !posix_isatty($resource);
    }

    /**
     * Returns a **new** instance for {@see STDOUT} output.
     *
     * Applying font escape sequences in other cases (like outputting {@see STDERR} or sending output to a file)
     * is disabled.
     */
    public static function createForStdOut(): static {
        return new static(STDOUT);
    }

    /**
     * Return an instance for {@see STDERR} output.
     *
     * Applying font escape sequences in other cases (like outputting {@see STDOUT} or sending output to a file)
     * is disabled.
     */
    public static function createForStdErr(): static {
        return new static(STDERR);
    }

    /**
     * @param int[] $formatCodes
     */
    public function apply(string $text, array $formatCodes): string {
        if ($this->isDisabled || !$formatCodes) {
            return $text;
        }

        $enableFormatString   = "\e[" . implode(';', $formatCodes) . 'm';
        $disableFormatString  = "\e[" . static::RESET_ALL . 'm';
        $disableFormatPattern = "\e\[" . static::RESET_ALL . 'm';

        // Retain existing font escape sequence found in the string
        // and ensure current stylizing for other parts of the string:
        $preStylizedText = preg_replace(
            "/{$disableFormatPattern}/",
            $disableFormatString . $enableFormatString,
            $text,
        );
        if (null === $preStylizedText) {
            throw new LogicException("Unable to parse a string: '{$text}'");
        }

        return $enableFormatString . $preStylizedText . $disableFormatString;
    }

    /**
     * Returns the length of a given multibyte string, not counting font escape sequences.
     */
    public static function mbStrlenNoFormat(string $text): int {
        return mb_strlen(preg_replace('/\e\[((\d*;)*\d*)?m/', '', $text));
    }
}
