<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\TerminalFormatter;

use MagicPush\CliToolkit\TerminalFormatter;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

use function PHPUnit\Framework\assertSame;

class TerminalFormatterTest extends TestCaseAbstract {
    private TerminalFormatter $formatter;

    protected function setUp(): void {
        parent::setUp();

        $this->formatter = TerminalFormatter::createForStdOut();
    }

    /**
     * Test a typical styling of a string.
     *
     * @covers TerminalFormatter::apply()
     */
    public function testSimpleStyling(): void {
        assertSame(
            "\e[91msome string\e[0m",
            $this->formatter->apply('some string', [TerminalFormatter::FONT_LIGHT_RED]),
        );
    }

    /**
     * Test if a substring with its own style does not interfere in an outer string styling.
     *
     * @covers TerminalFormatter::apply()
     */
    public function testRestyleAlreadyStylizedSubstring(): void {
        assertSame(
            "\e[91msome stylish string with \e[33mANOTHER STYLE\e[0m\e[91m substring\e[0m",
            $this->formatter->apply(
                'some stylish string with '
                . $this->formatter->apply('ANOTHER STYLE', [TerminalFormatter::FONT_YELLOW])
                . ' substring',
                [TerminalFormatter::FONT_LIGHT_RED],
            ),
            'Stylized "inner" substrings inside stylized "outer" strings in the end should restore the "outer" style.',
        );
    }

    /**
     * Styling should be disabled when not in a terminal.
     *
     * @covers TerminalFormatter::__construct()
     */
    public function testNoStyleWhenNotInTerminal(): void {
        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/no-style-when-not-in-terminal.php');
        assertSame(
            [
                'some string',
                'some stylish string',
            ],
            $result->getStdOutAsArray(),
            'Both strings should lack style codes because the output is got not in a terminal.',
        );
    }
}
