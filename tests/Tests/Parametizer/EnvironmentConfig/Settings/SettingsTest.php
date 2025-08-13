<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\Settings;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringEndsWith;
use function PHPUnit\Framework\assertStringStartsWith;
use function PHPUnit\Framework\assertTrue;

final class SettingsTest extends TestCaseAbstract {
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

    /**
     * Tests setting up the 'help' option short name - some character and no character (disabling a short name).
     *
     * @see EnvironmentConfig::$optionHelpShortName
     * @see Config::addDefaultOptions()
     */
    #[DataProvider('provideOptionHelpShortName')]
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
                'expectedOutputSubstring' => PHP_EOL . '  -h, --' . Config::PARAMETER_NAME_HELP . '    Show full help page.',
            ],
            'short-name-null' => [
                'parametersString'        => '--' . Config::PARAMETER_NAME_HELP,
                'expectedOutputSubstring' => PHP_EOL . '  --' . Config::PARAMETER_NAME_HELP . '    Show full help page.',
            ],
        ];
    }

    /**
     * Tests short description output with different limits.
     *
     * @see EnvironmentConfig::$helpGeneratorShortDescriptionCharsMinBeforeFullStop
     * @see EnvironmentConfig::$helpGeneratorShortDescriptionCharsMax
     * @see HelpGenerator::getShortDescription()
     */
    #[DataProvider('provideHelpGeneratorShortDescriptionLength')]
    public function testHelpGeneratorShortDescriptionLength(
        string $parametersString,
        string $expectedOutputSubstring,
    ): void {
        assertStringContainsString(
            $expectedOutputSubstring,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-help-short-descriptions.php',
                ListSubcommands::getScriptName() . ' ' . $parametersString,
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

    /**
     * Tests main left padding for generated help pages.
     *
     * @see EnvironmentConfig::$helpGeneratorPaddingLeftMain
     * @see HelpGenerator::getDescriptionBlock()
     * @see HelpGenerator::getUsagesBlock()
     * @see HelpGenerator::makeDefinitionList()
     */
    #[DataProvider('provideHelpGeneratorPaddingLeftMain')]
    public function testHelpGeneratorPaddingLeftMain(
        int $paddingSize,
        string $expectedOutput,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-help-padding-left-main.php',
                "{$paddingSize} --" . Config::PARAMETER_NAME_HELP,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideHelpGeneratorPaddingLeftMain(): array {
        return [
            'optimal' => [
                'paddingSize'    => 2,
                'expectedOutput' => <<<TEXT

                      Script description.
                      Some more description.

                    USAGE

                      template-help-padding-left-main.php <some-argument>

                    OPTIONS

                      --help    Show full help page.

                    ARGUMENTS

                      <some-argument>
                      (required)


                    TEXT,
            ],
            'large' => [
                'paddingSize'    => 20,
                'expectedOutput' => <<<TEXT

                                        Script description.
                                        Some more description.

                    USAGE

                                        template-help-padding-left-main.php <some-argument>

                    OPTIONS

                                        --help    Show full help page.

                    ARGUMENTS

                                        <some-argument>
                                        (required)


                    TEXT,
            ],
            'zero' => [
                'paddingSize'    => 0,
                'expectedOutput' => <<<TEXT

                    Script description.
                    Some more description.

                    USAGE

                    template-help-padding-left-main.php <some-argument>

                    OPTIONS

                    --help    Show full help page.

                    ARGUMENTS

                    <some-argument>
                    (required)


                    TEXT,
            ],
            'error-negative' => [
                'paddingSize'    => -1,
                'expectedOutput' => <<<TEXT

                    Script description.
                    Some more description.

                    USAGE

                    template-help-padding-left-main.php <some-argument>

                    OPTIONS

                    --help    Show full help page.

                    ARGUMENTS

                    <some-argument>
                    (required)


                    TEXT,
            ],
        ];
    }

    /**
     * Tests parameters' descriptions (options and arguments) left padding for generated help pages.
     *
     * @see EnvironmentConfig::$helpGeneratorPaddingLeftParameterDescription
     * @see HelpGenerator::makeDefinitionList()
     */
    #[DataProvider('provideHelpGeneratorPaddingLeftParameterDescription')]
    public function testHelpGeneratorPaddingLeftParameterDescription(
        int $paddingSize,
        string $expectedOutputEnding,
    ): void {
        assertStringEndsWith(
            $expectedOutputEnding,
            static::assertNoErrorsOutput(
                __DIR__ . '/' . 'scripts/template-help-padding-left-param-description.php',
                "{$paddingSize} --" . Config::PARAMETER_NAME_HELP,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideHelpGeneratorPaddingLeftParameterDescription(): array {
        return [
            'optimal' => [
                'paddingSize'          => 2,
                'expectedOutputEnding' => <<<TEXT

                      --help           Show full help page.

                      --some-option=…  Option description.

                    ARGUMENTS

                      <some-argument>  Argument description.
                      (required)


                    TEXT,
            ],
            'large' => [
                'paddingSize'          => 20,
                'expectedOutputEnding' => <<<TEXT

                      --help                             Show full help page.

                      --some-option=…                    Option description.

                    ARGUMENTS

                      <some-argument>                    Argument description.
                      (required)


                    TEXT,
            ],
            'zero' => [
                'paddingSize'          => 0,
                'expectedOutputEnding' => <<<TEXT

                      --help         Show full help page.

                      --some-option=…Option description.

                    ARGUMENTS

                      <some-argument>Argument description.
                      (required)


                    TEXT,
            ],
            'negative' => [
                'paddingSize'          => -1,
                'expectedOutputEnding' => <<<TEXT

                      --help         Show full help page.

                      --some-option=…Option description.

                    ARGUMENTS

                      <some-argument>Argument description.
                      (required)


                    TEXT,
            ],
        ];
    }

    /**
     * Tests how non-required options are noted in a help page usage template depending on the 'max' setting.
     *
     * @see EnvironmentConfig::$helpGeneratorUsageNonRequiredOptionsMax
     * @see HelpGenerator::getUsageTemplate()
     */
    #[DataProvider('provideHelpGeneratorUsageNonRequiredOptionsMax')]
    public function testHelpGeneratorUsageNonRequiredOptionsMax(int $value, string $expectedUsageSubstring): void {
        assertStringContainsString(
            $expectedUsageSubstring,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-help-usage-opt-optional-max.php',
                "{$value} --" . Config::PARAMETER_NAME_HELP,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideHelpGeneratorUsageNonRequiredOptionsMax(): array {
        return [
            'too-much' => [
                'value'                  => 100,
                'expectedUsageSubstring' => '[-fs] [--opt-1=…] [--opt-2=…] [--flag-1] [--flag-2] --required-1=… --required-2=… --required-3=… --required-4=…',
            ],
            'exact' => [
                'value'                  => 4,
                'expectedUsageSubstring' => '[-fs] [--opt-1=…] [--opt-2=…] [--flag-1] [--flag-2] --required-1=… --required-2=… --required-3=… --required-4=…',
            ],
            'too-low' => [
                'value'                  => 3,
                'expectedUsageSubstring' => '[-fs] [options] --required-1=… --required-2=… --required-3=… --required-4=…',
            ],
            'zero' => [
                'value'                  => 0,
                'expectedUsageSubstring' => '[-fs] [options] --required-1=… --required-2=… --required-3=… --required-4=…',
            ],
            'negative' => [
                'value'                  => -100,
                'expectedUsageSubstring' => '[-fs] [options] --required-1=… --required-2=… --required-3=… --required-4=…',
            ],
        ];
    }

    /**
     * Tests the padding between any content and the left terminal screen border for {@see ListSubcommands} output.
     *
     * @see EnvironmentConfig::$listPaddingLeftMain
     * @see ListSubcommands::outputNode()
     */
    #[DataProvider('provideListPaddingLeftMain')]
    public function testListPaddingLeftMain(bool $isSlim, int $value, string $expectedOutputStart): void {
        assertStringStartsWith(
            $expectedOutputStart,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-subcommands-list-padding.php',
                "{$value} 2 4 " . ListSubcommands::getScriptName() . ($isSlim ? ' --slim' : ''),
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideListPaddingLeftMain(): array {
        return [
            'high' => [
                'isSlim'              => false,
                'value'               => 10,
                'expectedOutputStart' => <<<TEXT
                    BEGIN
                              Built-in:
                                help                               Outputs a help page for a specified subcommand.
                                list                               Shows available subcommands.

                              --
                                something                          Some description

                              blue:
                                blue:suit                          Some description
                    TEXT,
            ],
            'ignored-if-slim' => [
                'isSlim'              => true,
                'value'               => 10,
                'expectedOutputStart' => <<<TEXT
                    BEGIN
                    help                             Outputs a help page for a specified subcommand.
                    list                             Shows available subcommands.
                    blue:flower:tea                  Some description
                    blue:suit                        Some description
                    something                        Some description
                    something:with:very-long-name    Some description
                    TEXT,
            ],
            'low' => [
                'isSlim'              => false,
                'value'               => 1,
                'expectedOutputStart' => <<<TEXT
                    BEGIN
                     Built-in:
                       help                               Outputs a help page for a specified subcommand.
                       list                               Shows available subcommands.

                     --
                       something                          Some description

                     blue:
                       blue:suit                          Some description
                    TEXT,
            ],
            'zero' => [
                'isSlim'              => false,
                'value'               => 0,
                'expectedOutputStart' => <<<TEXT
                    BEGIN
                    Built-in:
                      help                               Outputs a help page for a specified subcommand.
                      list                               Shows available subcommands.

                    --
                      something                          Some description

                    blue:
                      blue:suit                          Some description
                    TEXT,
            ],
            'negative' => [
                'isSlim'              => false,
                'value'               => -100,
                'expectedOutputStart' => <<<TEXT
                    BEGIN
                    Built-in:
                      help                               Outputs a help page for a specified subcommand.
                      list                               Shows available subcommands.

                    --
                      something                          Some description

                    blue:
                      blue:suit                          Some description
                    TEXT,
            ],
        ];
    }

    /**
     * Tests the padding before each node (command or section) listed in {@see ListSubcommands} output.
     *
     * @see EnvironmentConfig::$listPaddingLeftCommand
     * @see ListSubcommands::execute()
     * @see ListSubcommands::outputNode()
     */
    #[DataProvider('provideListPaddingLeftCommand')]
    public function testListPaddingLeftCommand(
        bool $isSlim,
        int $paddingMain,
        int $paddingCommand,
        string $expectedOutput,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-subcommands-list-padding.php',
                "{$paddingMain} {$paddingCommand} 4 " . ListSubcommands::getScriptName() . ($isSlim ? ' --slim' : ''),
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideListPaddingLeftCommand(): array {
        return [
            'high' => [
                'isSlim'         => false,
                'paddingMain'    => 1,
                'paddingCommand' => 10,
                'expectedOutput' => <<<TEXT
                    BEGIN
                     Built-in:
                               help                                       Outputs a help page for a specified subcommand.
                               list                                       Shows available subcommands.

                     --
                               something                                  Some description

                     blue:
                               blue:suit                                  Some description

                               blue:flower:
                                         blue:flower:tea                  Some description

                     something:
                               something:with:
                                         something:with:very-long-name    Some description

                    TEXT,
            ],
            'ignored-if-slim' => [
                'isSlim'         => true,
                'paddingMain'    => 1,
                'paddingCommand' => 10,
                'expectedOutput' => <<<TEXT
                    BEGIN
                    help                             Outputs a help page for a specified subcommand.
                    list                             Shows available subcommands.
                    blue:flower:tea                  Some description
                    blue:suit                        Some description
                    something                        Some description
                    something:with:very-long-name    Some description

                    TEXT,
            ],
            'low' => [
                'isSlim'         => false,
                'paddingMain'    => 1,
                'paddingCommand' => 1,
                'expectedOutput' => <<<TEXT
                    BEGIN
                     Built-in:
                      help                              Outputs a help page for a specified subcommand.
                      list                              Shows available subcommands.

                     --
                      something                         Some description

                     blue:
                      blue:suit                         Some description

                      blue:flower:
                       blue:flower:tea                  Some description

                     something:
                      something:with:
                       something:with:very-long-name    Some description

                    TEXT,
            ],
            'low-other-main' => [
                'isSlim'         => false,
                'paddingMain'    => 4,
                'paddingCommand' => 1,
                'expectedOutput' => <<<TEXT
                    BEGIN
                        Built-in:
                         help                              Outputs a help page for a specified subcommand.
                         list                              Shows available subcommands.

                        --
                         something                         Some description

                        blue:
                         blue:suit                         Some description

                         blue:flower:
                          blue:flower:tea                  Some description

                        something:
                         something:with:
                          something:with:very-long-name    Some description

                    TEXT,
            ],
            'zero' => [
                'isSlim'         => false,
                'paddingMain'    => 1,
                'paddingCommand' => 0,
                'expectedOutput' => <<<TEXT
                    BEGIN
                     Built-in:
                     help                             Outputs a help page for a specified subcommand.
                     list                             Shows available subcommands.

                     --
                     something                        Some description

                     blue:
                     blue:suit                        Some description

                     blue:flower:
                     blue:flower:tea                  Some description

                     something:
                     something:with:
                     something:with:very-long-name    Some description

                    TEXT,
            ],
            'negative' => [
                'isSlim'         => false,
                'paddingMain'    => 1,
                'paddingCommand' => -100,
                'expectedOutput' => <<<TEXT
                    BEGIN
                     Built-in:
                     help                             Outputs a help page for a specified subcommand.
                     list                             Shows available subcommands.

                     --
                     something                        Some description

                     blue:
                     blue:suit                        Some description

                     blue:flower:
                     blue:flower:tea                  Some description

                     something:
                     something:with:
                     something:with:very-long-name    Some description

                    TEXT,
            ],
        ];
    }

    /**
     * Tests the padding before a command description in {@see ListSubcommands} output.
     *
     * @see EnvironmentConfig::$listPaddingLeftCommandDescription
     * @see ListSubcommands::outputNode()
     */
    #[DataProvider('provideListPaddingLeftCommandDescription')]
    public function testListPaddingLeftCommandDescription(
        bool $isSlim,
        int $paddingMain,
        int $paddingCommand,
        int $paddingCommandDescription,
        string $expectedOutput,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/template-subcommands-list-padding.php',
                "{$paddingMain} {$paddingCommand} {$paddingCommandDescription} "
                    . ListSubcommands::getScriptName() . ($isSlim ? ' --slim' : ''),
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideListPaddingLeftCommandDescription(): array {
        return [
            'high' => [
                'isSlim'                    => false,
                'paddingMain'               => 1,
                'paddingCommand'            => 2,
                'paddingCommandDescription' => 10,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                     Built-in:
                       help                                     Outputs a help page for a specified subcommand.
                       list                                     Shows available subcommands.

                     --
                       something                                Some description

                     blue:
                       blue:suit                                Some description

                       blue:flower:
                         blue:flower:tea                        Some description

                     something:
                       something:with:
                         something:with:very-long-name          Some description

                    TEXT,
            ],
            'works-even-if-slim' => [
                'isSlim'                    => true,
                'paddingMain'               => 1,
                'paddingCommand'            => 2,
                'paddingCommandDescription' => 10,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                    help                                   Outputs a help page for a specified subcommand.
                    list                                   Shows available subcommands.
                    blue:flower:tea                        Some description
                    blue:suit                              Some description
                    something                              Some description
                    something:with:very-long-name          Some description

                    TEXT,
            ],
            'low' => [
                'isSlim'                    => false,
                'paddingMain'               => 1,
                'paddingCommand'            => 2,
                'paddingCommandDescription' => 1,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                     Built-in:
                       help                            Outputs a help page for a specified subcommand.
                       list                            Shows available subcommands.

                     --
                       something                       Some description

                     blue:
                       blue:suit                       Some description

                       blue:flower:
                         blue:flower:tea               Some description

                     something:
                       something:with:
                         something:with:very-long-name Some description

                    TEXT,
            ],
            'low-other-paddings-high' => [
                'isSlim'                    => false,
                'paddingMain'               => 2,
                'paddingCommand'            => 4,
                'paddingCommandDescription' => 1,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                      Built-in:
                          help                              Outputs a help page for a specified subcommand.
                          list                              Shows available subcommands.

                      --
                          something                         Some description

                      blue:
                          blue:suit                         Some description

                          blue:flower:
                              blue:flower:tea               Some description

                      something:
                          something:with:
                              something:with:very-long-name Some description

                    TEXT,
            ],
            'zero' => [
                'isSlim'                    => false,
                'paddingMain'               => 1,
                'paddingCommand'            => 2,
                'paddingCommandDescription' => 0,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                     Built-in:
                       help                           Outputs a help page for a specified subcommand.
                       list                           Shows available subcommands.

                     --
                       something                      Some description

                     blue:
                       blue:suit                      Some description

                       blue:flower:
                         blue:flower:tea              Some description

                     something:
                       something:with:
                         something:with:very-long-nameSome description

                    TEXT,
            ],
            'negative' => [
                'isSlim'                    => false,
                'paddingMain'               => 1,
                'paddingCommand'            => 2,
                'paddingCommandDescription' => -100,
                'expectedOutput'            => <<<TEXT
                    BEGIN
                     Built-in:
                       help                           Outputs a help page for a specified subcommand.
                       list                           Shows available subcommands.

                     --
                       something                      Some description

                     blue:
                       blue:suit                      Some description

                       blue:flower:
                         blue:flower:tea              Some description

                     something:
                       something:with:
                         something:with:very-long-nameSome description

                    TEXT,
            ],
        ];
    }
}
