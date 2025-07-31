<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptFileDetector;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use RuntimeException;
use Throwable;

class CompletionScript extends CliToolkitGenerateScriptAbstract {
    protected static function validateReadableDirectory(mixed &$path): bool {
        $path = realpath(trim((string) $path));
        if (false === $path || !is_readable($path) || !is_dir($path)) {
            throw new RuntimeException('Path should be a readable directory.');
        }

        return true;
    }

    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $helpFormatter = HelpFormatter::createForStdOut();

        $pathValidationDescription = 'You may specify absolute or relative paths - each element will be processed'
            . ' with `' . $helpFormatter->command('realpath()') . '` by the validator.';

        $configBuilder
            ->shortDescription('Generates a Bash script with completion functions.')
            ->description('
                Generates a Bash script with completion functions, which you can include in your Bash profile.
        
                Each time you add or delete a Parametizer-powered plain script (not a class script), you should:
                    1. Launch this script - so the generated completion script is updated.
                    2. Relaunch your Bash (or call the generated script manually) - so the updated list of aliases'
                        . ' is loaded into your session.
        
                The script works this way:
                    1. Detects all Parametizer-powered php scripts based on search and exclude settings.
                    2. Compiles aliases that consist of ' . $helpFormatter->paramTitle('--alias-prefix')
                        . ' + script names (without extensions).
                    3. Generates a Bash completion script for each detected script and its compiled alias.
                    4. Places all this generated stuff into ' . $helpFormatter->paramTitle('--output-filepath') . '.
        
                After you include the generated file in your Bash profile (you may specify '
                    . $helpFormatter->paramTitle('--verbose') . ' for the example inclusion command to be shown)
                and apply it (`source /path/to/your_bash_profile` or restart your Bash), you are able to:
                    * call any of previously detected scripts by its alias from any path you are located on;
                    * automatically complete all option names and all parameter values
                    (if a list of allowed values is specified for a particular parameter).
            ')

            ->usage(
                '\
                    --output-filepath=my-cool-project/generated/completion.sh \
                    --search-directory-recursive=my-cool-project/console \
                    --exclude-directory=my-cool-project/console/debug \
                    --search-directory-recursive=' . realpath(__DIR__ . '/' . '../../') . ' \
                    --verbose
                ',
                'Set your own paths for the generated file and source directories (also include this library scripts)'
                    . ', observe all the process details',
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
            ->default(realpath(__DIR__ . '/' . '../../../..') . '/local/cli-toolkit-completion.sh')

            ->newArrayOption('--search-directory-recursive', '-r')
            ->description("
                Scan these directories " . $helpFormatter->helpNote('recursively') . " for scripts.
                {$pathValidationDescription}
            ")
            ->validatorCallback(static::validateReadableDirectory(...))

            ->newArrayOption('--search-directory', '-d')
            ->description("
                Scan these exact directories for scripts.
                {$pathValidationDescription}
            ")
            ->validatorCallback(static::validateReadableDirectory(...))

            ->newArrayOption('--exclude-directory', '-e')
            ->description("
                Exclude these directories while searching through "
                . $helpFormatter->paramTitle('--search-directory-recursive') . " list.
                {$pathValidationDescription}
            ")
            ->validatorCallback(static::validateReadableDirectory(...))

            ->newArrayOption('--include-script', '-s')
            ->description("
                Include scripts by these exact file paths.
                {$pathValidationDescription}
            ")
            ->validatorCallback(
                function (&$value): bool {
                    $value = realpath(trim($value));

                    return false !== $value && is_readable($value) && is_file($value);
                },
                'Path should be a readable file.',
            )

            ->newFlag('--verbose', '-v')
            ->description('
                Show various details during the generation process.
                Also show a ready-to-copy-and-paste command to include the generated file.
            ');
    }


    public function execute(): void {
        set_exception_handler(function (Throwable $e) {
            fwrite(STDERR, ScriptFormatter::createForStdErr()->error($e->getMessage() . PHP_EOL));

            exit(Parametizer::ERROR_EXIT_CODE);
        });

        $isVerbose          = $this->request->getParamAsBool('verbose');
        $aliasPrefix        = $this->request->getParamAsString('alias-prefix');
        $executionFormatter = ScriptFormatter::createForStdOut();

        if ($isVerbose) {
            echo $executionFormatter->section('=== SCANNING SEARCH PATHS for Parametizer-based scripts ===')
                . PHP_EOL . PHP_EOL;
        }

        $scriptPathsByAliases = [];
        $detectedScripts      = (new ScriptFileDetector(throwOnException: true))
            ->searchDirectories($this->request->getParamAsStringList('search-directory'), isRecursive: false)
            ->searchDirectories($this->request->getParamAsStringList('search-directory-recursive'), isRecursive: true)
            ->excludeDirectories($this->request->getParamAsStringList('exclude-directory'))
            ->scriptPaths($this->request->getParamAsStringList('include-script'))
            ->getDetectedData();

        $scriptNameMaxLength = 0;
        foreach ($detectedScripts as $scriptName => $scriptPath) {
            $scriptNameAlias = $aliasPrefix . $scriptName;

            $scriptPathsByAliases[$scriptNameAlias] = $scriptPath;

            $scriptNameLength = mb_strlen($executionFormatter->success($scriptNameAlias));
            if ($scriptNameMaxLength < $scriptNameLength) {
                $scriptNameMaxLength = $scriptNameLength;
            }
        }

        if ($isVerbose) {
            $numberLength = mb_strlen((string) count($detectedScripts));
            $pathNumber   = 0;
            echo sprintf(
                'Scripts found (%s => %s):%s',
                $executionFormatter->success('alias'),
                $executionFormatter->pathMentioned('path'),
                PHP_EOL,
            );
            foreach ($scriptPathsByAliases as $scriptNameAlias => $scriptPath) {
                $pathNumber++;
                echo sprintf(
                    '    %s. %s => %s%s',
                    mb_str_pad((string) $pathNumber, $numberLength, pad_type: STR_PAD_LEFT),
                    mb_str_pad(
                        $executionFormatter->success($scriptNameAlias),
                        $scriptNameMaxLength,
                        pad_type: STR_PAD_RIGHT,
                    ),
                    $executionFormatter->pathMentioned($scriptPath),
                    PHP_EOL,
                );
            }
            echo PHP_EOL;
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
            echo $executionFormatter->section('=== GENERATING A SCRIPT with aliases and completion functions ===')
                . PHP_EOL . PHP_EOL;
        }
        $outputDirectory = dirname($outputFilepath);
        if (!is_dir($outputDirectory)) {
            if (!mkdir($outputDirectory, recursive: true)) {
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
                if (false === fwrite($fileHandler, Completion::generateCompletionCode($scriptAlias, $scriptPath))) {
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
                        . '>> $HOME/.bashrc' . PHP_EOL,
                )
                    . PHP_EOL;

                echo PHP_EOL . 'Include the generated script into your bash profile (execute the command below):'
                    . PHP_EOL . $bashIncludeCommand;

                echo 'You can also apply the generated script right away:'
                    . PHP_EOL . $executionFormatter->command(PHP_EOL . 'source ' . $outputFilepathReal)
                    . PHP_EOL. PHP_EOL;
            }
        } finally {
            fclose($fileHandler);
        }
    }
}
