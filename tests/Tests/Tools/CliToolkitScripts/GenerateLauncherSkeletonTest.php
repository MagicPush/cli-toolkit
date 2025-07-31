<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateLauncherSkeleton;

use FilesystemIterator;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\CliToolkitScriptTestAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate\AutocompletionScript;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate\LauncherSkeleton;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function PHPUnit\Framework\assertDirectoryIsReadable;
use function PHPUnit\Framework\assertNotFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class GenerateLauncherSkeletonTest extends CliToolkitScriptTestAbstract {
    private const string GENERATED_SKELETON_DIRECTORY_PATH = self::GENERATED_DIRECTORY_PATH . '/GenerateLauncherSkeletonTest/Cli';


    private string $scriptName;


    protected function setUp(): void {
        parent::setUp();

        $this->scriptName = LauncherSkeleton::getScriptName();
    }

    /**
     * Tests the "generator suit": generated files, their contents and how those operate.
     *
     * @see LauncherSkeleton::execute()
     */
    public function testSuccess(): void {
        // 1. Launch the generator, ensure no errors and expected STDOUT.

        $libraryRootDirectoryPath   = realpath(__DIR__ . '/../../../../../cli-toolkit');
        $librarySourceDirectoryPath = realpath(__DIR__ . '/../../../../src');
        $outputSubstitutions        = [
            '%%GENERATED_SKELETON_DIRECTORY_PATH%%' => self::GENERATED_SKELETON_DIRECTORY_PATH,
        ];
        /** @noinspection SpellCheckingInspection */
        assertSame(
            str_replace(
                array_keys($outputSubstitutions),
                $outputSubstitutions,
                <<<TEXT
                    Path to your scripts launcher and related generated stuff ({$librarySourceDirectoryPath}/Cli): 
                    Files created by the skeleton generator:

                           Script class example: %%GENERATED_SKELETON_DIRECTORY_PATH%%/ScriptClasses/MyFirstScript.php
                               CLI setup script: %%GENERATED_SKELETON_DIRECTORY_PATH%%/init-cli.php
                               Scripts launcher: %%GENERATED_SKELETON_DIRECTORY_PATH%%/launcher.php
                    Completion script generator: %%GENERATED_SKELETON_DIRECTORY_PATH%%/generate-completion.sh
                              Completion script: %%GENERATED_SKELETON_DIRECTORY_PATH%%/local/completion.sh

                    Completion setup:

                    If you apply the just generated completion script...

                    source %%GENERATED_SKELETON_DIRECTORY_PATH%%/local/completion.sh

                    ... you will be able to call the launcher from any path by its alias 'ctlauncher',
                    which supports autocompletion for available commands, their option names
                    and parameter values (if configured for particular parameters).

                    If you want the completion script to be applied each time you open a terminal,
                    append its sourcing to your '.bashrc' file:

                    echo -e "if [ -f %%GENERATED_SKELETON_DIRECTORY_PATH%%/local/completion.sh ]; then" \
                    "\\n    source %%GENERATED_SKELETON_DIRECTORY_PATH%%/local/completion.sh" \
                    "\\nfi\\n" \
                    >> \$HOME/.bashrc

                    Starter cheat sheet:

                    You may call your launcher by an alias 'ctlauncher' (assuming you have enabled it; see above how)
                    or in a traditional way:

                    php %%GENERATED_SKELETON_DIRECTORY_PATH%%/launcher.php

                    Below are a few call examples (based on the launcher alias):
                      ctlauncher list                   # List available commands.
                      ctlauncher help my-first-script   # Show a command's help page.
                      ctlauncher my-first-script --help # Same as above, works even with the launcher itself.
                      ctlauncher my-first-script a -ob  # Example script call with parameters.

                    If you want to know more, read the manual pages:
                        - {$libraryRootDirectoryPath}/README.md
                        - {$libraryRootDirectoryPath}/docs/features-manual.md


                    TEXT,
            ),
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                "{$this->scriptName}",
                // Instead of using self::GENERATED_SKELETON_DIRECTORY_PATH,
                // let's intentionally pass some relative (with '..') path along with not existing part ('.../Cli'):
                [__DIR__ . '/' . '../../Tools/CliToolkitScripts/generated/GenerateLauncherSkeletonTest/Cli'],
            )
                ->getStdOut(),
        );

        // 2. All necessary files are generated and have expected names and paths.

        assertDirectoryIsReadable(self::GENERATED_SKELETON_DIRECTORY_PATH);

        $generatedDirectoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::GENERATED_SKELETON_DIRECTORY_PATH, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );
        $actualGeneratedFilePaths = [];
        /** @var SplFileInfo $item */
        foreach ($generatedDirectoryIterator as $item) {
            $actualGeneratedFilePaths[] = $item->getRealPath();
        }
        sort($actualGeneratedFilePaths);

        assertSame(
            [
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/ScriptClasses/MyFirstScript.php',
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/generate-completion.sh',
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/init-cli.php',
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php',
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/local/completion.sh',
            ],
            $actualGeneratedFilePaths,
        );

        // 3. Completion script generator must contain expected comments.

        $completionScriptGeneratorContents = file_get_contents(
            self::GENERATED_SKELETON_DIRECTORY_PATH . '/generate-completion.sh',
        );
        assertNotFalse($completionScriptGeneratorContents);
        /** @noinspection SpellCheckingInspection */
        assertStringContainsString("# TODO It will create an alias 'ctlauncher'", $completionScriptGeneratorContents);
        assertStringContainsString(
            "# TODO so your launcher Bash alias will be just 'launcher'",
            $completionScriptGeneratorContents,
        );
        assertStringContainsString(
            sprintf(
                "# TODO Launch 'php %s %s --%s'",
                realpath(static::LAUNCHER_PATH),
                AutocompletionScript::getScriptName(),
                Config::OPTION_NAME_HELP,
            ),
            $completionScriptGeneratorContents,
        );

        // 4. Launcher must contain expected comments.

        $launcherContents = file_get_contents(self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php');
        assertNotFalse($launcherContents);
        assertStringContainsString('Make sure {@see ./ScriptClasses} classes are watched', $launcherContents);
        assertStringContainsString('namespace in {@see ./ScriptClasses/MyFirstScript.php}', $launcherContents);
        assertStringContainsString(
            sprintf(
                "*  'php %s %s'",
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php',
                Config::PARAMETER_NAME_LIST,
            ),
            $launcherContents,
        );
        assertStringContainsString("*  will show 'my-first-script' entry", $launcherContents);
        // The path below must be relative for `@see` phpdoc to become IDE-friendly,
        // so the file can be opened by clicking on it:
        assertStringContainsString(
            '*  read {@see ../../../../../../../docs/features-manual.md#environment-config}',
            $launcherContents,
        );

        // 5. Completion script must contain expected alias and launcher path:

        $completionFileContents = file_get_contents(self::GENERATED_SKELETON_DIRECTORY_PATH . '/local/completion.sh');
        assertNotFalse($completionFileContents);
        assertSame(1, mb_substr_count($completionFileContents, 'function _parametizer-autocomplete_'));
        /** @noinspection SpellCheckingInspection */
        assertStringContainsString("alias 'ctlauncher'=", $completionFileContents);
        assertStringContainsString(
            sprintf("'%s'", self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php'),
            $completionFileContents,
        );

        // 6. Launcher must detect expected scripts.

        assertSame(
            <<<TEXT
                 Built-in:
                    help               Outputs a help page for a specified subcommand.
                    list               Shows available subcommands.

                 --
                    my-first-script

                TEXT,
            static::assertNoErrorsOutput(
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php',
                Config::PARAMETER_NAME_LIST,
            )
                ->getStdOut(),
        );

        // 7. Generated example script class must be launched successfully via the launcher
        // and receive passed parameters as expected.
        assertSame(
            <<<TEXT
                Option: 'option-value'
                Argument: 'argument-value'

                TEXT,
            static::assertNoErrorsOutput(
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/launcher.php',
                'my-first-script argument-value -o option-value',
            )
                ->getStdOut(),
        );

        // 8. Calling a completion alias must run the launcher.

        // Let's do here the same thing as above, but via the alias:
        /** @noinspection SpellCheckingInspection */
        assertSame(
            <<<TEXT
                Option: 'option-value'
                Argument: 'argument-value'

                TEXT,
            static::getBashAliasExecutionOutput(
                self::GENERATED_SKELETON_DIRECTORY_PATH . '/local/completion.sh',
                'ctlauncher my-first-script argument-value -o option-value',
            ),
        );
    }
}
