<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertStringContainsString;

class SettingsTest extends TestCaseAbstract {
    #[DataProvider('provideOptionHelpShortName')]
    /**
     * Tests setting up the 'help' option short name - some character and no character (disabling a short name).
     *
     * @see Config::addDefaultOptions()
     */
    public function testOptionHelpShortName(string $parametersString, string $expectedOutputSubstring): void {
        assertStringContainsString(
            $expectedOutputSubstring,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/template-option-help-short-name.php', $parametersString)
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideOptionHelpShortName(): array {
        return [
            'short-name-set' => [
                'parametersString'        => '-h -h',
                'expectedOutputSubstring' => PHP_EOL . '  -h, --help   Show full help page.',
            ],
            'short-name-null' => [
                'parametersString'        => '--help',
                'expectedOutputSubstring' => PHP_EOL . '  --help   Show full help page.',
            ],
        ];
    }

    #[DataProvider('provideHelpGeneratorShortDescriptionLength')]
    /**
     * Tests short description output with different limits.
     *
     * @covers HelpGenerator::getShortDescription()
     */
    public function testHelpGeneratorShortDescriptionLength(
        string $parametersString,
        string $expectedOutputSubstring,
    ): void {
        assertStringContainsString(
            $expectedOutputSubstring,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-short-descriptions.php',
                '--help ' . $parametersString,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideHelpGeneratorShortDescriptionLength(): array {
        return [
            // Raw cut: 'Just a very long single-line string that has lots of charact'. Graceful cut:
            'graceful-cut' => [
                'parametersString'        => '0 60',
                'expectedOutputSubstring' => 'conf-s1   Just a very long single-line string that has lots of' . PHP_EOL,
            ],
            // The case when a possible graceful cut has the same length as maximum:
            'graceful-same-as-max' => [
                'parametersString'         => '0 87',
                'expectedOutputSubstring'  => 'conf-s1   Just a very long single-line string that has lots of characters, thus should be trimmed' . PHP_EOL,
            ],
            'max-zero-disables-description' => [
                'parametersString'         => '0 0',
                'expectedOutputSubstring'  => 'conf-s1' . PHP_EOL,
            ],

            // With such a low MAX only a single sentence is expected:
            'min-short' => [
                'parametersString'         => '5 30',
                'expectedOutputSubstring'  => 'conf-s2   Too short string.' . PHP_EOL,
            ],
            // Zero minimum works similar - a full sentence of any length will suffice:
            'min-zero-short' => [
                'parametersString'         => '0 30',
                'expectedOutputSubstring'  => 'conf-s2   Too short string.' . PHP_EOL,
            ],
            // If a minimal length is too high, the usual graceful cut approach is applied:
            'min-too-long' => [
                'parametersString'         => '19 30',
                'expectedOutputSubstring'  => 'conf-s2   Too short string. Another' . PHP_EOL,
            ],
            // If MAX allows, we expect as many full sentences as possible within the MAX substring.
            // Also, let's ensure that we don't have extra words,
            // if minimum equals to a length of a substring ending at the end of an allowed full sentence plus a space.
            'min-edge' => [
                'parametersString'         => '34 50',
                'expectedOutputSubstring'  => 'conf-s2   Too short string. Another shorty.' . PHP_EOL,
            ],
        ];
    }
}
