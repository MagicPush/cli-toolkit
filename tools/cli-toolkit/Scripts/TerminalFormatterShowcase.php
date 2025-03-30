<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Scripts;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\TerminalFormatter;

class TerminalFormatterShowcase extends CliToolkitScriptAbstract {
    private const string EXAMPLE_SUBSTRING = '1iI 0oO';
    private const string CELL_PAD_VALUE    = ' ';
    private const string COLUMN_MARGIN     = ' ';
    private const int    NON_EXISTENT_CODE = -1;


    public static function getConfiguration(): BuilderInterface {
        $formatter = TerminalFormatter::createForStdOut();

        $exampleInDescription         = $formatter->apply(
            self::EXAMPLE_SUBSTRING,
            [TerminalFormatter::STYLE_BOLD, TerminalFormatter::FONT_YELLOW],
        );
        $terminalFormatterDescription = $formatter->apply(
            'TerminalFormatter',
            [TerminalFormatter::STYLE_BOLD, TerminalFormatter::FONT_CYAN],
        );

        return static::newConfig()
            ->description("
                Shows an example substring ('{$exampleInDescription}') with each standard terminal font color
                and some styles, plus custom color examples.
                You will find the mentioned codes constants in '{$terminalFormatterDescription}' class.
                
                Specify the flags for corresponding additional output.
            ")

            ->newFlag('--backgrounds', '-b')
            ->description('Adds a table for each background color.')

            ->newFlag('--styles', '-s')
            ->description('Adds a table with examples of other styles.');
    }

    public function execute(): void {
        $formatter = TerminalFormatter::createForStdOut();

        $showBackgrounds = $this->request->getParamAsBool('backgrounds');
        $showOtherStyles = $this->request->getParamAsBool('styles');

        $backgrounds = [
            TerminalFormatter::BACKGROUND_RED           => 'BACKGROUND_RED',
            TerminalFormatter::BACKGROUND_LIGHT_RED     => 'BACKGROUND_LIGHT_RED',
            TerminalFormatter::BACKGROUND_GREEN         => 'BACKGROUND_GREEN',
            TerminalFormatter::BACKGROUND_LIGHT_GREEN   => 'BACKGROUND_LIGHT_GREEN',
            TerminalFormatter::BACKGROUND_YELLOW        => 'BACKGROUND_YELLOW',
            TerminalFormatter::BACKGROUND_LIGHT_YELLOW  => 'BACKGROUND_LIGHT_YELLOW',
            TerminalFormatter::BACKGROUND_BLUE          => 'BACKGROUND_BLUE',
            TerminalFormatter::BACKGROUND_LIGHT_BLUE    => 'BACKGROUND_LIGHT_BLUE',
            TerminalFormatter::BACKGROUND_MAGENTA       => 'BACKGROUND_MAGENTA',
            TerminalFormatter::BACKGROUND_LIGHT_MAGENTA => 'BACKGROUND_LIGHT_MAGENTA',
            TerminalFormatter::BACKGROUND_CYAN          => 'BACKGROUND_CYAN',
            TerminalFormatter::BACKGROUND_LIGHT_CYAN    => 'BACKGROUND_LIGHT_CYAN',
            TerminalFormatter::BACKGROUND_BLACK         => 'BACKGROUND_BLACK',
            TerminalFormatter::BACKGROUND_DARK_GRAY     => 'BACKGROUND_DARK_GRAY',
            TerminalFormatter::BACKGROUND_LIGHT_GRAY    => 'BACKGROUND_LIGHT_GRAY',
            TerminalFormatter::BACKGROUND_WHITE         => 'BACKGROUND_WHITE',

            self::NON_EXISTENT_CODE => '(no background)',
            // Let's finish the script with a no-background table: the whole output is very long,
            // and in the majority of cases you probably prefer to see colored strings on a default background.
        ];
        $fonts = [
            self::NON_EXISTENT_CODE => '(default font)',

            TerminalFormatter::FONT_RED           => 'FONT_RED',
            TerminalFormatter::FONT_LIGHT_RED     => 'FONT_LIGHT_RED',
            TerminalFormatter::FONT_GREEN         => 'FONT_GREEN',
            TerminalFormatter::FONT_LIGHT_GREEN   => 'FONT_LIGHT_GREEN',
            TerminalFormatter::FONT_YELLOW        => 'FONT_YELLOW',
            TerminalFormatter::FONT_LIGHT_YELLOW  => 'FONT_LIGHT_YELLOW',
            TerminalFormatter::FONT_BLUE          => 'FONT_BLUE',
            TerminalFormatter::FONT_LIGHT_BLUE    => 'FONT_LIGHT_BLUE',
            TerminalFormatter::FONT_MAGENTA       => 'FONT_MAGENTA',
            TerminalFormatter::FONT_LIGHT_MAGENTA => 'FONT_LIGHT_MAGENTA',
            TerminalFormatter::FONT_CYAN          => 'FONT_CYAN',
            TerminalFormatter::FONT_LIGHT_CYAN    => 'FONT_LIGHT_CYAN',
            TerminalFormatter::FONT_BLACK         => 'FONT_BLACK',
            TerminalFormatter::FONT_DARK_GRAY     => 'FONT_DARK_GRAY',
            TerminalFormatter::FONT_LIGHT_GRAY    => 'FONT_LIGHT_GRAY',
            TerminalFormatter::FONT_WHITE         => 'FONT_WHITE',
        ];
        $mainStyles = [
            self::NON_EXISTENT_CODE => '(no style)',

            TerminalFormatter::STYLE_BOLD   => 'STYLE_BOLD',
            TerminalFormatter::STYLE_DIM    => 'STYLE_DIM',
            TerminalFormatter::STYLE_INVERT => 'STYLE_INVERT',
        ];
        $customColors = [
            TerminalFormatter::FONT_CUSTOM            => 'FONT_CUSTOM',
            TerminalFormatter::BACKGROUND_CUSTOM      => 'BACKGROUND_CUSTOM',
            TerminalFormatter::FONT_UNDERLINED_CUSTOM => 'FONT_UNDERLINED_CUSTOM',
        ];
        $otherStyles = [
            TerminalFormatter::STYLE_ITALIC            => 'STYLE_ITALIC',
            TerminalFormatter::STYLE_UNDERLINED        => 'STYLE_UNDERLINED',
            TerminalFormatter::STYLE_BLINK             => 'STYLE_BLINK',
            TerminalFormatter::STYLE_OVERLINED         => 'STYLE_OVERLINED',
            TerminalFormatter::STYLE_HIDDEN            => 'STYLE_HIDDEN',
            TerminalFormatter::STYLE_STRIKE            => 'STYLE_STRIKE',
            TerminalFormatter::STYLE_UNDERLINED_DOUBLE => 'STYLE_UNDERLINED_DOUBLE',
        ];

        $mainStylesCount    = count($mainStyles);
        $columnMarginLength = mb_strlen(self::COLUMN_MARGIN);
        $cellPadLength      = mb_strlen(self::CELL_PAD_VALUE) * 2;

        $cellLength = mb_strlen(self::EXAMPLE_SUBSTRING);
        $this->updateTitlesListAndCellLength($fonts, $cellLength);
        $this->updateTitlesListAndCellLength($mainStyles, $cellLength);
        $cellLength += $cellPadLength;

        $lineLength = ($mainStylesCount + 1) * $cellLength + $mainStylesCount * $columnMarginLength;
        $emptyCell  = str_repeat(' ', $cellLength);

        echo PHP_EOL;

        if ($showOtherStyles) {
            echo mb_str_pad(' COLORS ', $lineLength, '=', STR_PAD_BOTH) . PHP_EOL . PHP_EOL;
        }
        foreach ($backgrounds as $backgroundCode => $backgroundTitle) {
            if (!$showBackgrounds && self::NON_EXISTENT_CODE !== $backgroundCode) {
                continue;
            }

            $backgroundFullTitle = $backgroundTitle;
            if (self::NON_EXISTENT_CODE !== $backgroundCode) {
                $backgroundFullTitle .= " ({$backgroundCode})";
            }
            $headline = $this->getPaddedCell($backgroundFullTitle, $lineLength);

            echo $headline . PHP_EOL;

            echo $emptyCell;
            foreach ($mainStyles as $styleTitle) {
                echo self::COLUMN_MARGIN . $this->getPaddedCell($styleTitle, $cellLength);
            }
            echo PHP_EOL;

            foreach ($fonts as $fontCode => $fontTitle) {
                echo $this->getPaddedCell($fontTitle, $cellLength, STR_PAD_LEFT);

                foreach ($mainStyles as $styleCode => $unused) {
                    echo self::COLUMN_MARGIN
                        . $formatter->apply(
                            $this->getPaddedCell(self::EXAMPLE_SUBSTRING, $cellLength),
                            $this->getActualCodesList([$backgroundCode, $fontCode, $styleCode]),
                        );
                }

                echo PHP_EOL;
            }

            echo PHP_EOL;
        }

        echo $this->getPaddedCell(str_repeat('-', 4) . ' CUSTOM COLORS ' . str_repeat('-', 4), $cellPadLength) . PHP_EOL
            . PHP_EOL;

        $customColorCellLength = 1;
        $this->updateTitlesListAndCellLength($customColors, $customColorCellLength);
        $customColorCellLength += $cellPadLength;
        echo $this->getPaddedCell($customColors[TerminalFormatter::FONT_CUSTOM], $customColorCellLength, STR_PAD_LEFT)
            . self::COLUMN_MARGIN
            . $formatter->apply(
                self::EXAMPLE_SUBSTRING,
                [TerminalFormatter::FONT_CUSTOM, 2, 142, 69, 32],
            )
            . ' = custom font color by RGB (2;142;69;32 - "Chestnut Stallion")'
            . PHP_EOL;
        echo $this->getPaddedCell(
                $customColors[TerminalFormatter::BACKGROUND_CUSTOM],
                $customColorCellLength,
                STR_PAD_LEFT,
            )
            . self::COLUMN_MARGIN
            . $formatter->apply(
                self::EXAMPLE_SUBSTRING,
                [TerminalFormatter::BACKGROUND_CUSTOM, 5, 228],
            )
            . ' = custom background color by 256-color table (5;228 - "Bicycle Yellow")'
            . PHP_EOL;
        echo $this->getPaddedCell(
                $customColors[TerminalFormatter::FONT_UNDERLINED_CUSTOM],
                $customColorCellLength,
                STR_PAD_LEFT,
            )
            . self::COLUMN_MARGIN
            . $formatter->apply(
                self::EXAMPLE_SUBSTRING,
                [TerminalFormatter::STYLE_UNDERLINED, TerminalFormatter::FONT_UNDERLINED_CUSTOM, 2, 0, 208, 98],
            )
            . ' = custom underline color by RBG (2;0;208;98 - "Emerald")'
            . PHP_EOL;

        $customUnderlineNote = $formatter->apply(
            '(some terminals do not support underline custom color;'
                . ' in this case you will see here a default font color, no background and no underline)',
            [TerminalFormatter::STYLE_DIM],
        );
        echo $this->getPaddedCell('(all 3 mixed)', $customColorCellLength, STR_PAD_LEFT)
            . self::COLUMN_MARGIN
            . $formatter->apply(
                self::EXAMPLE_SUBSTRING,
                [
                    TerminalFormatter::FONT_CUSTOM, 2, 142, 69, 32,
                    TerminalFormatter::BACKGROUND_CUSTOM, 5, 228,
                    TerminalFormatter::STYLE_UNDERLINED_DOUBLE, TerminalFormatter::FONT_UNDERLINED_CUSTOM, 2, 0, 208, 98,
                ],
            )
            . ' = custom font, background and (double) underline colors'
            . PHP_EOL . $this->getPaddedCell(
                $customUnderlineNote,
                $customColorCellLength + $columnMarginLength + mb_strlen($customUnderlineNote),
                STR_PAD_LEFT,
            )
            . PHP_EOL;
        echo PHP_EOL;

        if ($showOtherStyles) {
            $effectCellLength = 1;
            $this->updateTitlesListAndCellLength($otherStyles, $effectCellLength);
            $effectCellLength += $cellPadLength;

            echo mb_str_pad(' STYLES ', $lineLength, '=', STR_PAD_BOTH) . PHP_EOL . PHP_EOL;
            foreach ($otherStyles as $effectCode => $effectTitle) {
                echo $this->getPaddedCell($effectTitle, $effectCellLength, STR_PAD_LEFT)
                    . self::COLUMN_MARGIN
                    . $formatter->apply(
                        $this->getPaddedCell(self::EXAMPLE_SUBSTRING, $effectCellLength),
                        [$effectCode],
                    )
                    . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }

    private function getPaddedCell(string $string, int $cellLength, int $paddingMode = STR_PAD_BOTH): string {
        return mb_str_pad($string, $cellLength, self::CELL_PAD_VALUE, $paddingMode);
    }

    private function updateTitlesListAndCellLength(&$list, &$cellLength): void {
        foreach ($list as $code => &$title) {
            if (self::NON_EXISTENT_CODE === $code) {
                continue;
            }

            $title = "{$title} ({$code})";

            $titleLength = mb_strlen($title);
            if ($titleLength > $cellLength) {
                $cellLength = $titleLength;
            }
        }
        unset($title);
    }

    /**
     * @param int[] $codes
     * @return int[]
     */
    private function getActualCodesList(array $codes): array {
        $actualCodes = [];
        foreach ($codes as $code) {
            if (self::NON_EXISTENT_CODE === $code) {
                continue;
            }

            $actualCodes[] = $code;
        }

        return $actualCodes;
    }
}
