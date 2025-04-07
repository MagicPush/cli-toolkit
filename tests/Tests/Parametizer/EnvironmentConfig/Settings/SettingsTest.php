<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\Settings;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

class SettingsTest extends TestCaseAbstract {
    /**
     * Ensures all settings have their corresponding paragraphs in the manual.
     *
     * @see EnvironmentConfig
     */
    public function testAllSettingsAreDescribedInManual(): void {
        $envConfigSettings = array_keys(json_decode((new EnvironmentConfig())->toJsonFileContent(), true));
        $manualRealpath    = realpath(__DIR__ . '/' . '../scripts/../../../../../docs/features-manual.md');
        $manualContents    = file_get_contents($manualRealpath);
        foreach ($envConfigSettings as $settingName) {
            $settingParagraph = "#### {$settingName}";
            // Let's not use `assertStringContainsString()`: in case of an error we do not want to see
            // a lengthy error message containing the whole manual page contents.
            $isFound = str_contains($manualContents, $settingParagraph);
            assertTrue(
                $isFound,
                "'$settingParagraph' paragraph is not found in the manual: {$manualRealpath}" . PHP_EOL,
            );
        }
    }

    #[DataProvider('provideOptionHelpShortName')]
    /**
     * Tests setting up the 'help' option short name - some character and no character (disabling a short name).
     *
     * @see EnvironmentConfig::$optionHelpShortName
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
                'parametersString'        => '-h h',
                'expectedOutputSubstring' => PHP_EOL . '  -h, --' . Config::OPTION_NAME_HELP . '   Show full help page.',
            ],
            'short-name-null' => [
                'parametersString'        => '--' . Config::OPTION_NAME_HELP,
                'expectedOutputSubstring' => PHP_EOL . '  --' . Config::OPTION_NAME_HELP . '   Show full help page.',
            ],
        ];
    }

    #[DataProvider('provideHelpGeneratorShortDescriptionLength')]
    /**
     * Tests short description output with different limits.
     *
     * @see EnvironmentConfig::$helpGeneratorShortDescriptionCharsMinBeforeFullStop
     * @see EnvironmentConfig::$helpGeneratorShortDescriptionCharsMax
     * @see HelpGenerator::getShortDescription()
     */
    public function testHelpGeneratorShortDescriptionLength(
        string $parametersString,
        string $expectedOutputSubstring,
    ): void {
        assertStringContainsString(
            $expectedOutputSubstring,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-short-descriptions.php',
                Config::PARAMETER_NAME_LIST . ' ' . $parametersString,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideHelpGeneratorShortDescriptionLength(): array {
        return [
            /** @noinspection SpellCheckingInspection */
            // Raw cut: 'Just a very long single-line string that has lots of charact'. Graceful cut:
            'graceful-cut' => [
                'parametersString'        => '0 60',
                'expectedOutputSubstring' => 'conf-s1    Just a very long single-line string that has lots of' . PHP_EOL,
            ],
            // The case when a possible graceful cut has the same length as maximum:
            'graceful-same-as-max' => [
                'parametersString'        => '0 87',
                'expectedOutputSubstring' => 'conf-s1    Just a very long single-line string that has lots of characters, thus should be trimmed' . PHP_EOL,
            ],
            'max-zero-disables-description' => [
                'parametersString'        => '0 0',
                'expectedOutputSubstring' => 'conf-s1' . PHP_EOL,
            ],

            // With such a low MAX only a single sentence is expected:
            'min-short' => [
                'parametersString'        => '5 30',
                'expectedOutputSubstring' => 'conf-s2    Too short string.' . PHP_EOL,
            ],
            // Zero minimum works similar - a full sentence of any length will suffice:
            'min-zero-short' => [
                'parametersString'        => '0 30',
                'expectedOutputSubstring' => 'conf-s2    Too short string.' . PHP_EOL,
            ],
            // If a minimal length is too high, the usual graceful cut approach is applied:
            'min-too-long' => [
                'parametersString'        => '19 30',
                'expectedOutputSubstring' => 'conf-s2    Too short string. Another' . PHP_EOL,
            ],
            // If MAX allows, we expect as many full sentences as possible within the MAX substring.
            // Also, let's ensure that we don't have extra words,
            // if minimum equals to a length of a substring ending at the end of an allowed full sentence plus a space.
            'min-edge' => [
                'parametersString'        => '34 50',
                'expectedOutputSubstring' => 'conf-s2    Too short string. Another shorty.' . PHP_EOL,
            ],
        ];
    }
}
