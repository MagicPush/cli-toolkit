<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\GenerateAutocompletionScript;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileIsReadable;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

final class GenerateAutocompletionScriptTest extends TestCaseAbstract {
    private const string COMPLETION_SCRIPT_PATH = __DIR__ . '/generated/completion.sh';
    private const string LAUNCHER_PATH          = __DIR__ . '/' . '../../../../tools/cli-toolkit/launcher.php';


    private string $subcommandName;


    protected function setUp(): void {
        parent::setUp();

        require_once __DIR__ . '/' . '../../../../tools/cli-toolkit/init-autoloader.php';
        $this->subcommandName = GenerateAutocompletionScript::getFullName();

        if (file_exists(self::COMPLETION_SCRIPT_PATH)) {
            assertTrue(unlink(self::COMPLETION_SCRIPT_PATH));
        }
    }


    /**
     * Tests the output file path is trimmed of space characters.
     *
     * @see GenerateAutocompletionScript::execute()
     */
    public function testOutputPathWithSpaceChars(): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            sprintf(
                '%s --output-filepath=%s',
                $this->subcommandName,
                sprintf('%1$s	 %2$s ', self::COMPLETION_SCRIPT_PATH, PHP_EOL),
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
    }

    /**
     * Tests that subdirectories are created recursively along the way when needed.
     *
     * @see GenerateAutocompletionScript::execute()
     */
    public function testOutputPathSubdirectories(): void {
        // Previous launch cleanup:
        if (file_exists(__DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh')) {
            assertTrue(unlink(__DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh'));
        }
        if (file_exists(__DIR__ . '/generated/subdirectory1/subdirectory2')) {
            assertTrue(rmdir(__DIR__ . '/generated/subdirectory1/subdirectory2'));
        }
        if (file_exists(__DIR__ . '/generated/subdirectory1')) {
            assertTrue(rmdir(__DIR__ . '/generated/subdirectory1'));
        }

        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            sprintf(
                '%s --output-filepath=%s',
                $this->subcommandName,
                __DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh',
            ),
        );

        assertFileIsReadable(__DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh');
    }

    #[DataProvider('provideInvalidOutputFilePath')]
    /**
     * Tests cases when `--output-filepath` contains an invalid path.
     *
     * @see GenerateAutocompletionScript::execute()
     */
    public function testInvalidOutputFilePath(string $outputPath, string $expectedErrorSubstring): void {
        self::assertAnyErrorOutput(
            self::LAUNCHER_PATH,
            $expectedErrorSubstring,
            sprintf('%s --output-filepath=%s', $this->subcommandName, $outputPath),
            shouldAssertExitCode: true,
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
            'spaced' => [
                'outputPath'             => ' 	' . PHP_EOL,
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
     * Tests `search-paths` and various scripts detection.
     *
     * @param string[] $searchPaths
     * @param string[] $detectedPaths
     * @see GenerateAutocompletionScript::execute()
     */
    public function testScriptsDetection(array $searchPaths, array $detectedPaths): void {
        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);

        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            sprintf(
                '%s --output-filepath=%s %s',
                $this->subcommandName,
                self::COMPLETION_SCRIPT_PATH,
                $searchPaths ? "'" . implode("' '", $searchPaths) . "'" : '',
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
        $completionFileContents = file_get_contents(self::COMPLETION_SCRIPT_PATH);

        assertSame(
            count($detectedPaths),
            mb_substr_count($completionFileContents, 'function _parametizer-autocomplete_'),
        );
        foreach ($detectedPaths as $path) {
            assertStringContainsString(sprintf("'%s'", realpath($path)), $completionFileContents);
        }
    }

    /**
     * @return array[]
     */
    public static function provideScriptsDetection(): array {
        return [
            // The library stock functionality:
            'cli-toolkit' => [
                'searchPaths'   => [dirname(self::LAUNCHER_PATH)],
                'detectedPaths' => [self::LAUNCHER_PATH],
            ],

            // Here we test the parameter's default value.
            // It should be the same as above - the detection result should be the same.
            'cli-toolkit-default-search' => [
                'searchPaths'   => [],
                'detectedPaths' => [self::LAUNCHER_PATH],
            ],

            'artificial-examples' => [
                // The parameter should be able to process an array of paths:
                'searchPaths'   => [
                    __DIR__ . '/stuff-to-detect/red', // Should be red recursively
                    __DIR__ . '/stuff-to-detect/blue',
                ],
                'detectedPaths' => [
                    __DIR__ . '/' . 'stuff-to-detect/blue/class-processor.php',
                    __DIR__ . '/' . 'stuff-to-detect/red/plain.php',
                    __DIR__ . '/' . 'stuff-to-detect/red/subdirectory/plain-multiline.php',
                ],
            ],
        ];
    }

    /**
     * Tests zero detection for an empty string and an error message.
     *
     * @see GenerateAutocompletionScript::execute()
     */
    public function testErrorIfNothingDetected(): void {
        // Ensure an empty directory exists.
        if (!file_exists(__DIR__ . '/stuff-to-detect/green')) {
            assertTrue(mkdir(__DIR__ . '/stuff-to-detect/green'));
        }

        // The completion file should be deleted if no scripts were detected.
        // So let's ensure the file exists before the detection takes place.
        assertTrue(touch(self::COMPLETION_SCRIPT_PATH));

        self::assertFullErrorOutput(
            self::LAUNCHER_PATH,
            'No scripts were found' . PHP_EOL,
            sprintf(
                '%s --output-filepath=%s %s',
                $this->subcommandName,
                self::COMPLETION_SCRIPT_PATH,
                sprintf("'%s'", __DIR__ . '/stuff-to-detect/green'),
            ),
        );

        assertFileDoesNotExist(self::COMPLETION_SCRIPT_PATH);
    }

    #[DataProvider('provideInvalidSearchPaths')]
    /**
     * @see GenerateAutocompletionScript::getConfiguration()
     */
    public function testInvalidSearchPaths(string $searchPath): void {
        self::assertAnyErrorOutput(
            self::LAUNCHER_PATH,
            'Path should be a readable directory.',
            sprintf(
                '%s --output-filepath=%s %s',
                $this->subcommandName,
                self::COMPLETION_SCRIPT_PATH,
                $searchPath,
            ),
            shouldAssertExitCode: true,
        );
    }

    /**
     * @return array[]
     */
    public static function provideInvalidSearchPaths(): array {
        return [
            'not-existing'    => ['searchPath' => '/non-existing-path'],
            'not-a-directory' => ['searchPath' => self::LAUNCHER_PATH],
            'not-readable'    => ['searchPath' => '/etc/shadow'],
        ];
    }

    #[DataProvider('provideAliasPrefixes')]
    /**
     * Tests different prefixes for script aliases.
     *
     * @see GenerateAutocompletionScript::getConfiguration()
     * @see GenerateAutocompletionScript::execute()
     */
    public function testAliasPrefixes(string $aliasPrefix, string $expectedScriptAlias): void {
        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            sprintf(
                "%s --output-filepath=%s --alias-prefix='%s' %s",
                $this->subcommandName,
                self::COMPLETION_SCRIPT_PATH,
                $aliasPrefix,
                dirname(self::LAUNCHER_PATH),
            ),
        );

        assertFileIsReadable(self::COMPLETION_SCRIPT_PATH);
        assertStringContainsString("alias '{$expectedScriptAlias}'=", file_get_contents(self::COMPLETION_SCRIPT_PATH));
    }

    /**
     * @return array[]
     * @noinspection SpellCheckingInspection
     */
    public static function provideAliasPrefixes(): array {
        return [
            'space-characters-as-no-prefix' => [
                'aliasPrefix'         => ' 	' . PHP_EOL,
                'expectedScriptAlias' => 'launcher',
            ],
            'some-alias' => [
                'aliasPrefix'         => 'mega',
                'expectedScriptAlias' => 'megalauncher',
            ],
            'some-alias-trimmed-spaces' => [
                'aliasPrefix'         => 'mega- 	' . PHP_EOL,
                'expectedScriptAlias' => 'mega-launcher',
            ],
        ];
    }

    /**
     * Tests output contents with `--verbose` flag being passed.
     *
     * @see GenerateAutocompletionScript::execute()
     */
    public function testVerbosity(): void {
        // Previous launch cleanup:
        if (file_exists(__DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh')) {
            assertTrue(unlink(__DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh'));
        }
        if (file_exists(__DIR__ . '/generated/subdirectory1/subdirectory2')) {
            assertTrue(rmdir(__DIR__ . '/generated/subdirectory1/subdirectory2'));
        }
        if (file_exists(__DIR__ . '/generated/subdirectory1')) {
            assertTrue(rmdir(__DIR__ . '/generated/subdirectory1'));
        }

        /** @noinspection PhpFormatFunctionParametersMismatchInspection */
        assertSame(
            sprintf(
                <<<TEXT
                === SCANNING SEARCH PATHS for Parametizer-based scripts ===

                Search path: %1\$s
                Scripts found:
                    1. %2\$s

                === GENERATING A FILE with aliases and auto-complete scripts ===

                A directory has been created: %3\$s
                Writing stuff into %4\$s ...
                Entries added:
                    1. s-launcher

                Include the generated file into your bash profile (execute the command below):

                echo -e "if [ -f %4\$s ]; then" \
                "\\n    source %4\$s" \
                "\\nfi\\n" \
                >> ~/.bashrc


                TEXT,
                realpath(dirname(self::LAUNCHER_PATH)) . '/',
                realpath(self::LAUNCHER_PATH),
                dirname(realpath(__DIR__ . '/generated') . '/subdirectory1/subdirectory2/completion.sh'),
                realpath(__DIR__ . '/generated') . '/subdirectory1/subdirectory2/completion.sh',
            ),
            self::assertNoErrorsOutput(
                self::LAUNCHER_PATH,
                sprintf(
                    '%s --output-filepath=%s %s --verbose',
                    $this->subcommandName,
                    __DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh',
                    dirname(self::LAUNCHER_PATH),
                ),
            )
                ->getStdOut(),
        );

        // And now let's ensure that without `--verbose` no output is generated:
        assertSame(
            '',
            self::assertNoErrorsOutput(
                self::LAUNCHER_PATH,
                sprintf(
                    '%s --output-filepath=%s %s',
                    $this->subcommandName,
                    __DIR__ . '/generated/subdirectory1/subdirectory2/completion.sh',
                    dirname(self::LAUNCHER_PATH),
                ),
            )
                ->getStdOut(),
        );
    }
}
