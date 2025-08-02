<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateCompletionScript;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\CliToolkitScriptTestAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate\CompletionScript;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileIsReadable;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

final class GenerateCompletionScriptTest extends CliToolkitScriptTestAbstract {
    /** The path should contain 2+ directories to test that directories are created recursively. */
    private const string COMPLETION_SCRIPT_PATH = self::GENERATED_DIRECTORY_PATH . '/GenerateCompletionScriptTest/completion/completion.sh';


    private string $scriptName;


    protected function setUp(): void {
        parent::setUp();

        $this->scriptName = CompletionScript::getScriptName();
    }


    /**
     * Tests the output file path is trimmed of space characters.
     *
     * @see CompletionScript::execute()
     */
    public function testOutputPathWithSpaceChars(): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertNoErrorsOutput(
            static::LAUNCHER_PATH,
            sprintf(
                '%s --output-filepath=%s --search-directory=%s',
                $this->scriptName,
                sprintf('%s	 \\%s ', self::COMPLETION_SCRIPT_PATH, PHP_EOL),
                __DIR__ . '/ScriptFiles/Red',
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
    }

    #[DataProvider('provideInvalidOutputFilePath')]
    /**
     * Tests cases when `--output-filepath` contains an invalid path.
     *
     * @see CompletionScript::execute()
     */
    public function testInvalidOutputFilePath(string $outputPath, string $expectedErrorSubstring): void {
        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            $expectedErrorSubstring,
            sprintf(
                '%s --output-filepath=%s --search-directory=%s',
                $this->scriptName,
                $outputPath,
                __DIR__ . '/ScriptFiles/Red',
            ),
        );
    }

    /**
     * @return array[]
     */
    public static function provideInvalidOutputFilePath(): array {
        return [
            'empty' => [
                'outputPath'             => '',
                'expectedErrorSubstring' => 'No value for option --output-filepath',
            ],
            'space-and-tab' => [
                'outputPath'             => ' 	\\' . PHP_EOL,
                'expectedErrorSubstring' => 'No value for option --output-filepath',
            ],
            'no-access-mkdir' => [
                'outputPath'             => '/asd-subdirectory/zxc-completion.sh',
                'expectedErrorSubstring' => "Unable to create a directory: '/asd-subdirectory'",
            ],
            'no-access-file' => [
                'outputPath'             => '/zxc-completion.sh',
                'expectedErrorSubstring' => "Unable to open or create a file: '/zxc-completion.sh'",
            ],
        ];
    }

    #[DataProvider('provideScriptsDetection')]
    /**
     * Tests detection parameters available in {@see CompletionScript::getConfigBuilder()}.
     *
     * @param array<string, string> $detectedPathsByNames (string) script name => (string) script absolute path
     * @see CompletionScript::execute()
     * @see CompletionScript::getConfigBuilder()
     */
    public function testScriptsDetection(
        string $parametersString,
        array $detectedPathsByNames,
    ): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertNoErrorsOutput(
            static::LAUNCHER_PATH,
            sprintf(
                '%s --output-filepath=%s --alias-prefix="a-"%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $parametersString ? " {$parametersString}" : '',
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
        $completionFileContents = file_get_contents(self::COMPLETION_SCRIPT_PATH);

        assertSame(
            count($detectedPathsByNames),
            mb_substr_count($completionFileContents, 'function _parametizer-complete_'),
        );
        foreach ($detectedPathsByNames as $scriptName => $scriptPath) {
            assertStringContainsString("alias 'a-{$scriptName}'=", $completionFileContents);
            assertStringContainsString("'{$scriptPath}'", $completionFileContents);

            // Ensure aliases are callable as expected:
            assertStringContainsString(
                <<<TEXT

                    USAGE

                      {$scriptName}.php
                    TEXT,
                static::getBashAliasExecutionOutput(
                    self::COMPLETION_SCRIPT_PATH,
                    "a-{$scriptName} --" . Config::PARAMETER_NAME_HELP,
                ),
            );
        }
    }

    /**
     * @return array[]
     */
    public static function provideScriptsDetection(): array {
        return [
            // The library stock functionality:
            'cli-toolkit' => [
                'parametersString'     => '--search-directory-recursive=' . dirname(static::LAUNCHER_PATH),
                'detectedPathsByNames' => [basename(static::LAUNCHER_PATH, '.php') => realpath(static::LAUNCHER_PATH)],
            ],

            'distinguish-scripts' => [
                'parametersString'     => '--search-directory=' . (__DIR__ . '/ScriptFiles/Blue'),
                'detectedPathsByNames' => [
                    'somewhat-l'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-l.php'),
                    'somewhat-6'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-6.php'),
                    'somewhat-l-another' => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-l-another.php'),
                    'somewhat-3'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-3.php'),
                    'somewhat-2'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-2.php'),
                    'somewhat-another'   => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-another.php'),
                    'somewhat-5'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-5.php'),
                    'somewhat'           => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat.php'),
                    'somewhat-4'         => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-4.php'),
                    'somewhat-class'     => realpath(__DIR__ . '/ScriptFiles/Blue/somewhat-class.php'),
                    /**
                     * These files are not (and should not be) detected:
                     *  * 'construction-l-mismatch':   wrong pairs of 'construct' and 'exec' substrings;
                     *  * 'construction-l-mismatch-2': same as above;
                     *  * 'no-config-no-l':            no exact 'construct' substrings detected,
                     *                                     {@see ScriptFileDetector::SUBSTR_*_CONSTRUCT};
                     *  * 'no-run-no-execute':         no exact 'exec' substrings detected,
                     *                                     {@see ScriptFileDetector::SUBSTR_*_EXEC};
                     *  * 'Something':                 lacks at least proper 'exec' substring
                     *                                 (for now we assume that this is good enough to distinguish
                     *                                 plain scripts and script classes);
                     *  * 'somewhat-wrong-ext':        wrong extension file
                     *                                     (not {@see ScriptDetectorAbstract::FILE_EXTENSION}).
                     */
                ],
            ],

            'recursive' => [
                'parametersString'     => '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Red'),
                'detectedPathsByNames' => [
                    'somewhat-l' => realpath(__DIR__ . '/ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat'   => realpath(__DIR__ . '/ScriptFiles/Red/somewhat.php'),
                ],
            ],
            'non-recursive' => [
                'parametersString'     => '--search-directory=' . (__DIR__ . '/ScriptFiles/Red'),
                'detectedPathsByNames' => [
                    'somewhat' => realpath(__DIR__ . '/ScriptFiles/Red/somewhat.php'),
                ],
            ],
            'recursive-and-exclude' => [
                'parametersString'     =>
                    '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Red')
                    . ' --exclude-directory=' . (__DIR__ . '/ScriptFiles/Red/Subdirectory'),
                'detectedPathsByNames' => [
                    'somewhat' => realpath(__DIR__ . '/ScriptFiles/Red/somewhat.php'),
                ],
            ],

            'array-search' => [
                'parametersString'     =>
                    '--search-directory=' . (__DIR__ . '/ScriptFiles/Red')
                    . ' --search-directory=' . (__DIR__ . '/ScriptFiles/Green'),
                'detectedPathsByNames' => [
                    'somewhat'         => realpath(__DIR__ . '/ScriptFiles/Red/somewhat.php'),
                    'somewhat-another' => realpath(__DIR__ . '/ScriptFiles/Green/somewhat-another.php'),
                ],
            ],
            'array-search-recursive' => [
                'parametersString'     =>
                    '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Red')
                    . ' --search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Green'),
                'detectedPathsByNames' => [
                    'somewhat-l'         => realpath(__DIR__ . '/' . 'ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat'           => realpath(__DIR__ . '/' . 'ScriptFiles/Red/somewhat.php'),
                    'somewhat-l-another' => realpath(
                        __DIR__ . '/' . 'ScriptFiles/Green/Subdirectory/somewhat-l-another.php'
                    ),
                    'somewhat-another'   => realpath(__DIR__ . '/' . 'ScriptFiles/Green/somewhat-another.php'),
                ],
            ],
            'array-exclude' => [
                'parametersString'     =>
                    '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles')
                    . ' --exclude-directory=' . (__DIR__ . '/ScriptFiles/Red')
                    . ' --exclude-directory=' . (__DIR__ . '/ScriptFiles/Green')
                    . ' --exclude-directory=' . (__DIR__ . '/ScriptFiles/Blue'),
                'detectedPathsByNames' => [
                    'somewhat' => realpath(__DIR__ . '/' . 'ScriptFiles/Yellow/somewhat.php'),
                ],
            ],

            'include-script' => [
                'parametersString'     =>
                    '--include-script=' . (__DIR__ . '/ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                'detectedPathsByNames' => [
                    'somewhat-l' => realpath(__DIR__ . '/ScriptFiles/Red/Subdirectory/somewhat-l.php')
                ],
            ],
            'include-script-array' => [
                'parametersString'     =>
                    '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Yellow')
                    . ' --include-script=' . (__DIR__ . '/ScriptFiles/Red/Subdirectory/somewhat-l.php')
                    . ' --include-script=' . (__DIR__ . '/ScriptFiles/Green/somewhat-another.php'),
                'detectedPathsByNames' => [
                    'somewhat'         => realpath(__DIR__ . '/' . 'ScriptFiles/Yellow/somewhat.php'),
                    'somewhat-l'       => realpath(__DIR__ . '/ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat-another' => realpath(__DIR__ . '/ScriptFiles/Green/somewhat-another.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideErrorIfNoSearchSettings')]
    /**
     * Tests an error appearance if no search setting was provided.
     *
     * @see CompletionScript::execute()
     */
    public function testErrorIfNoSearchSettings(string $parametersSubstring, ?string $errorMessage): void {
        $parametersString = sprintf(
            '%s --output-filepath=%s%s',
            $this->scriptName,
            self::COMPLETION_SCRIPT_PATH,
            $parametersSubstring ? " {$parametersSubstring}" : '',
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        if (null !== $errorMessage) {
            static::assertExecutionErrorOutput(
                static::LAUNCHER_PATH,
                $errorMessage,
                $parametersString,
            );

            assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
        } else {
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                $parametersString,
            );

            assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
        }
    }

    /**
     * @return array[]
     */
    public static function provideErrorIfNoSearchSettings(): array {
        return [
            'nothing-error' => [
                'parametersSubstring' => '',
                'errorMessage'        => 'There are no search settings specified.',
            ],
            'search-ok' => [
                'parametersSubstring' => '--search-directory=' . (__DIR__ . '/ScriptFiles/Red'),
                'errorMessage'        => null,
            ],
            'search-recursive-ok' => [
                'parametersSubstring' => '--search-directory-recursive=' . (__DIR__ . '/ScriptFiles/Red'),
                'errorMessage'        => null,
            ],
            'exclude-error' => [
                'parametersSubstring' => '--exclude-directory=' . (__DIR__ . '/ScriptFiles/Red'),
                'errorMessage'        => sprintf(
                    "Excluded path '%s' is not related to any of specified searching paths.",
                    realpath(__DIR__ . '/ScriptFiles/Red'),
                ),
            ],
            'script-ok' => [
                'parametersSubstring' => '--include-script=' . (__DIR__ . '/ScriptFiles/Red/somewhat.php'),
                'errorMessage'        => null,
            ],
        ];
    }

    /**
     * Tests zero detection for an empty string and an error message.
     *
     * @see CompletionScript::execute()
     */
    public function testErrorIfNothingDetected(): void {
        // Ensure an empty directory exists.
        if (!file_exists(__DIR__ . '/ScriptFiles/EmptyDirectory')) {
            assertTrue(mkdir(__DIR__ . '/ScriptFiles/EmptyDirectory'));
        }

        // The completion file should be deleted if no scripts were detected.
        // So let's ensure the file exists before the detection takes place.
        $completionDirectory = dirname(self::COMPLETION_SCRIPT_PATH);
        if (!file_exists($completionDirectory)) {
            assertTrue(mkdir($completionDirectory, recursive: true));
        }
        assertTrue(touch(self::COMPLETION_SCRIPT_PATH));

        static::assertFullErrorOutput(
            static::LAUNCHER_PATH,
            'No scripts were found' . PHP_EOL,
            sprintf(
                '%s --output-filepath=%s --search-directory-recursive=%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                __DIR__ . '/ScriptFiles/EmptyDirectory',
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    #[DataProvider('provideInvalidDirectoryPaths')]
    /**
     * Tests invalid '--search-directory' paths.
     *
     * @see CompletionScript::execute()
     */
    public function testInvalidSearchPaths(string $directoryPath): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            'Path should be a readable directory.',
            sprintf(
                '%s --output-filepath=%s --search-directory=%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $directoryPath,
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    #[DataProvider('provideInvalidDirectoryPaths')]
    /**
     * Tests invalid '--search-directory-recursive' paths.
     *
     * @see CompletionScript::execute()
     */
    public function testInvalidRecursiveSearchPaths(string $directoryPath): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            'Path should be a readable directory.',
            sprintf(
                '%s --output-filepath=%s --search-directory-recursive=%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $directoryPath,
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    #[DataProvider('provideInvalidDirectoryPaths')]
    /**
     * Tests invalid '--exclude-directory' paths.
     *
     * @see CompletionScript::execute()
     */
    public function testInvalidExcludePaths(string $directoryPath): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            'Path should be a readable directory.',
            sprintf(
                '%s --output-filepath=%s --exclude-directory=%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $directoryPath,
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    /**
     * @return array[]
     */
    public static function provideInvalidDirectoryPaths(): array {
        return [
            'not-existing'    => ['directoryPath' => __DIR__ . '/asd'],
            'not-a-directory' => ['directoryPath' => static::LAUNCHER_PATH],
            'not-readable'    => ['directoryPath' => '/root'],
        ];
    }

    #[DataProvider('provideInvalidIncludeScriptPaths')]
    /**
     * Tests invalid '--include-script' paths.
     *
     * @see CompletionScript::execute()
     */
    public function testInvalidIncludeScriptPaths(string $scriptPath): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            'Path should be a readable file.',
            sprintf(
                '%s --output-filepath=%s --include-script=%s',
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $scriptPath,
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    /**
     * @return array[]
     */
    public static function provideInvalidIncludeScriptPaths(): array {
        return [
            'not-existing' => ['scriptPath' => __DIR__ . '/asd'],
            'not-a-file'   => ['scriptPath' => dirname(static::LAUNCHER_PATH)],
            'not-readable' => ['scriptPath' => '/root'],
        ];
    }

    #[DataProvider('provideAliasPrefixes')]
    /**
     * Tests different prefixes for script aliases.
     *
     * @see CompletionScript::getConfigBuilder()
     * @see CompletionScript::execute()
     */
    public function testAliasPrefixes(string $aliasPrefix, string $expectedScriptAlias): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        static::assertNoErrorsOutput(
            static::LAUNCHER_PATH,
            sprintf(
                "%s --output-filepath=%s --alias-prefix='%s' --search-directory-recursive=%s",
                $this->scriptName,
                self::COMPLETION_SCRIPT_PATH,
                $aliasPrefix,
                dirname(static::LAUNCHER_PATH),
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
        assertStringContainsString("alias '{$expectedScriptAlias}'=", file_get_contents(self::COMPLETION_SCRIPT_PATH));
    }

    /**
     * @return array[]
     */
    public static function provideAliasPrefixes(): array {
        return [
            'space-characters-as-no-prefix' => [
                'aliasPrefix'         => ' ' . PHP_EOL,
                'expectedScriptAlias' => 'run',
            ],
            'some-alias' => [
                'aliasPrefix'         => '1',
                'expectedScriptAlias' => '1run',
            ],
            'some-alias-trimmed-spaces-and-tabs' => [
                'aliasPrefix'         => ' 	mega-	 ' . PHP_EOL,
                'expectedScriptAlias' => 'mega-run',
            ],
        ];
    }

    /**
     * Tests output contents with `--verbose` flag being passed.
     *
     * @see CompletionScript::execute()
     */
    public function testVerbosity(): void {
        /** @noinspection PhpFormatFunctionParametersMismatchInspection */
        assertSame(
            sprintf(
                <<<TEXT
                === SCANNING SEARCH PATHS for Parametizer-based scripts ===

                Scripts found (alias => path):
                     1. s-somewhat-l         => %1\$s/ScriptFiles/Blue/somewhat-l.php
                     2. s-somewhat-6         => %1\$s/ScriptFiles/Blue/somewhat-6.php
                     3. s-somewhat-l-another => %1\$s/ScriptFiles/Blue/somewhat-l-another.php
                     4. s-somewhat-3         => %1\$s/ScriptFiles/Blue/somewhat-3.php
                     5. s-somewhat-2         => %1\$s/ScriptFiles/Blue/somewhat-2.php
                     6. s-somewhat-another   => %1\$s/ScriptFiles/Blue/somewhat-another.php
                     7. s-somewhat-5         => %1\$s/ScriptFiles/Blue/somewhat-5.php
                     8. s-somewhat           => %1\$s/ScriptFiles/Blue/somewhat.php
                     9. s-somewhat-4         => %1\$s/ScriptFiles/Blue/somewhat-4.php
                    10. s-somewhat-class     => %1\$s/ScriptFiles/Blue/somewhat-class.php

                === GENERATING A SCRIPT with aliases and completion functions ===

                A directory has been created: %2\$s
                Writing stuff into %3\$s ...

                Include the generated script into your bash profile (execute the command below):

                echo -e "if [ -f %3\$s ]; then" \
                "\\n    source %3\$s" \
                "\\nfi\\n" \
                >> \$HOME/.bashrc
                
                You can also apply the generated script right away:

                source %3\$s


                TEXT,
                /* #1 */ realpath(__DIR__),
                /* #2 */ dirname(self::COMPLETION_SCRIPT_PATH),
                /* #3 */ self::COMPLETION_SCRIPT_PATH,
            ),
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                sprintf(
                    '%s --output-filepath=%s --search-directory-recursive=%s --verbose',
                    $this->scriptName,
                    self::COMPLETION_SCRIPT_PATH,
                    __DIR__ . '/ScriptFiles/Blue',
                ),
            )
                ->getStdOut(),
        );

        // And now let's ensure that without `--verbose` no output is generated:
        assertSame(
            '',
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                sprintf(
                    '%s --output-filepath=%s --search-directory-recursive=%s',
                    $this->scriptName,
                    self::COMPLETION_SCRIPT_PATH,
                    __DIR__ . '/ScriptFiles/Blue',
                ),
            )
                ->getStdOut(),
        );
    }
}
