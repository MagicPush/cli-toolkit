<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate;

use FilesystemIterator;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

class AutocompletionScript extends CliToolkitGenerateScriptAbstract {
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        $helpFormatter = HelpFormatter::createForStdOut();

        return static::newConfig(envConfig: $envConfig, throwOnException: $throwOnException)
            ->description('
                Generates a file with Bash completion scripts, which you can include in your Bash profile.
        
                Each time you add or delete a Parametizer-powered script, you should:
                    1. Launch this script - so the generated completion script is updated.
                    2. Relaunch your Bash (or call the generated script manually) - so the updated list of aliases'
                        . ' is loaded into your session.
        
                The script works this way:
                    1. Detects all Parametizer-powered php scripts located in '
                        . $helpFormatter->paramTitle('<search-paths>') . ' (recursive scan).
                    2. Compiles aliases that consist of ' . $helpFormatter->paramTitle('--alias-prefix')
                        . ' + script names (without extensions).
                    3. Generates a Bash completion script for each detected script and its compiled alias.
                    4. Places all this generated stuff into ' . $helpFormatter->paramTitle('--output-filepath') . '.
        
                After you include the generated file in your Bash profile (you may specify '
                    . $helpFormatter->paramTitle('--verbose') . ' for the example inclusion command to be shown)
                and apply it (`source /path/to/your_bash_profile` or restart your Bash), you are able to:
                    * call any of previously detected scripts by its alias from any path you are located on;
                    * auto-complete all option names and all parameter values
                    (if a list of allowed values is specified for a particular parameter).
            ')

            ->usage('', 'If you are OK with the default values, just launch the script without any params')
            ->usage(
                '--output-filepath=my-cool-project/generated/autocompletion.sh'
                    . ' my-cool-project/console/main my-cool-project/console/debug --verbose',
                'Set your own paths for the generated file and source directories, observe all the process details',
            )

            ->newOption('--alias-prefix', '-p')
            ->description('
                Specify ' . $helpFormatter->paramValue('" "'). ' (a space character) to disable prefixes.
            
                For example, your script "/some/path/cool-script.php" with the prefix "s-"
                will become available by the alias "s-cool-script" from any path.
            ')
            ->default('s-')
            ->validatorCallback(
                function (&$value) {
                    $value = trim($value);

                    return true;
                },
            )

            ->newOption('--output-filepath', '-o')
            ->description('Location of the generated file.')
            ->default(realpath(__DIR__ . '/' . '../../../..') . '/local/cli-toolkit-autocompletion.sh')

            ->newFlag('--verbose', '-v')
            ->description('
                Show various details during the generation process.
                Also show a ready-to-copy-and-paste command to include the generated file.
            ')

            ->newArrayArgument('search-paths')
            ->description('
                Scan this list of directories recursively to detect all Parametizer-powered php scripts.
                You may specify absolute or relative paths - each element will be processed with `'
                        . $helpFormatter->command('realpath()') . '` by the validator.
            ')
            ->default([realpath(__DIR__ . '/' . '../../')])
            ->validatorCallback(
                function (&$value) {
                    $value = realpath(trim($value));

                    return false !== $value && is_readable($value) && is_dir($value);
                },
                'Path should be a readable directory.',
            );
    }

    public function execute(): void {
        set_exception_handler(function (Throwable $e) {
            fwrite(STDERR, ScriptFormatter::createForStdErr()->error($e->getMessage() . PHP_EOL));

            exit(Parametizer::ERROR_EXIT_CODE);
        });

        $searchPaths        = $this->request->getParamAsStringList('search-paths');
        $isVerbose          = $this->request->getParamAsBool('verbose');
        $aliasPrefix        = $this->request->getParamAsString('alias-prefix');
        $executionFormatter = ScriptFormatter::createForStdOut();

        $scriptPathsByAliases = [];
        if ($isVerbose) {
            echo $executionFormatter->section('=== SCANNING SEARCH PATHS for Parametizer-based scripts ===')
                . PHP_EOL . PHP_EOL;
        }
        foreach ($searchPaths as $searchPath) {
            if ($isVerbose) {
                echo 'Search path: ' . $executionFormatter->pathProcessed($searchPath . '/') . PHP_EOL;
            }

            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchPath, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($files as $file) {
                if ('php' !== $file->getExtension()) {
                    continue;
                }

                $path = $file->getRealPath();
                if (false === $path) {
                    continue;
                }

                $contents = file_get_contents($path);
                $isScriptDetected =
                    !preg_match('/' . PHP_EOL . '([a-z]* )*class .+' . PHP_EOL . '?{/', $contents)
                    && (
                        (
                            str_contains($contents, 'Parametizer::newConfig(') /** @see Parametizer::newConfig() */
                            && str_contains($contents, '->run()')              /** @see Parametizer::run() */
                        )
                        || (
                            str_contains($contents, 'new ScriptLauncher(')  /** @see ScriptLauncher::__construct() */
                            && str_contains($contents, '->execute()')       /** @see ScriptLauncher::execute() */
                        )
                    );
                if (!$isScriptDetected) {
                    continue;
                }

                $scriptPathsByAliases[$aliasPrefix . $file->getBasename('.' . $file->getExtension())] = $path;
            }

            if ($isVerbose) {
                $numberLength = mb_strlen((string) count($scriptPathsByAliases));
                $pathNumber   = 0;
                echo 'Scripts found:' . PHP_EOL;
                foreach ($scriptPathsByAliases as $path) {
                    $pathNumber++;
                    echo '    ' . mb_str_pad((string) $pathNumber, $numberLength, pad_type: STR_PAD_LEFT) . '. '
                        . $executionFormatter->pathMentioned($path) . PHP_EOL;
                }
                echo PHP_EOL;
            }
        }

        $outputFilepath = $this->request->getParamAsString('output-filepath');

        if (!$scriptPathsByAliases) {
            // Let's try removing the output file (if exists) to indicate the situation more explicitly:
            if (file_exists($outputFilepath)) {
                unlink($outputFilepath);
            }

            throw new RuntimeException('No scripts were found');
        }

        if ($isVerbose) {
            echo $executionFormatter->section('=== GENERATING A FILE with aliases and auto-complete scripts ===')
                . PHP_EOL . PHP_EOL;
        }
        $outputDirectory = dirname($outputFilepath);
        if (!file_exists($outputDirectory)) {
            if (!mkdir(directory: $outputDirectory, recursive: true)) {
                throw new RuntimeException('Unable to create a directory: ' . var_export($outputDirectory, true));
            }
            if ($isVerbose) {
                echo 'A directory has been created: '
                    . $executionFormatter->success($outputDirectory)
                    . PHP_EOL;
            }
        }

        $fileHandler = fopen($outputFilepath, 'w');
        if (false === $fileHandler) {
            throw new RuntimeException('Unable to open or create a file: ' . var_export($outputFilepath, true));
        }

        try {
            if ($isVerbose) {
                echo 'Writing stuff into ' . $executionFormatter->pathProcessed($outputFilepath) . ' ...'
                    . PHP_EOL;
            }

            foreach ($scriptPathsByAliases as $scriptAlias => $scriptPath) {
                if (false === fwrite($fileHandler, Completion::generateAutocompleteScript($scriptAlias, $scriptPath))) {
                    throw new RuntimeException(
                        "Unable to write data for alias '{$scriptAlias}' into {$outputFilepath}",
                    );
                }
            }

            if ($isVerbose) {
                $outputFilepathReal = realpath($outputFilepath);
                $bashIncludeCommand = $executionFormatter->command(
                    PHP_EOL
                    . 'echo -e "if [ -f ' . $outputFilepathReal . ' ]; then" \\' . PHP_EOL
                    . '"\n    source ' . $outputFilepathReal . '" \\' . PHP_EOL
                    . '"\nfi\n" \\' . PHP_EOL
                    . '>> ~/.bashrc' . PHP_EOL
                    . PHP_EOL,
                );

                $numberLength = mb_strlen((string) count($scriptPathsByAliases));
                $aliasNumber  = 0;
                echo 'Entries added:' . PHP_EOL;
                foreach ($scriptPathsByAliases as $alias => $notUsed) {
                    $aliasNumber++;
                    echo '    ' . mb_str_pad((string) $aliasNumber, $numberLength, pad_type: STR_PAD_LEFT) . '. '
                        . $executionFormatter->success($alias) . PHP_EOL;
                }

                echo PHP_EOL . 'Include the generated file into your bash profile (execute the command below):'
                    . PHP_EOL
                    . $bashIncludeCommand;
            }
        } finally {
            fclose($fileHandler);
        }
    }
}
