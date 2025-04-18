<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/ScriptFormatter.php';

use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;

$helpFormatter = HelpFormatter::createForStdOut();
$request       = Parametizer::newConfig()
    ->description('
        Generates a file with Bash completion scripts, which you can include in your Bash profile.

        Each time you add or delete a Parametizer-powered script, you should:
            1. Launch this script - so the generated completion script is updated.
            2. Relaunch your Bash (or call the generated script manually) - so the updated list of aliases'
                . ' is loaded into your session.

        The script works this way:
            1. Detects all Parametizer-powered php scripts located in ' . $helpFormatter->paramTitle('<search-paths>')
                . ' (recursive scan).
            2. Compiles aliases that consist of ' . $helpFormatter->paramTitle('--alias-prefix')
                . ' + script names (without extensions).
            3. Generates a Bash completion script for each detected script and its compiled alias.
            4. Places all this generated stuff into ' . $helpFormatter->paramTitle('--output-filepath') . '.

        After you include the generated file in your Bash profile
        (you may specify ' . $helpFormatter->paramTitle('--verbose') . ' for the example inclusion command to be shown)
        and apply it (`source /path/to/your_bash_profile` or restart your Bash),
        you are able to:
            * call any of previously detected scripts by its alias from any path you are located on;
            * auto-complete all option names and all parameter values
            (if a list of allowed values is specified for a particular parameter).
    ')

    ->usage('', 'If you are OK with the default values, just launch the script without any params')
    ->usage(
        '--output-filepath=my-cool-project/generated/autocompletion.sh my-cool-project/console/main my-cool-project/console/debug --verbose',
        'Set your own paths for the generated file and source directories, observe all the process details',
    )

    ->newOption('--alias-prefix', '-p')
    ->description('
        For example, your script "/some/path/cool-script.php" with the prefix "s-"
        will become available by the alias "s-cool-script" from any path.
    ')
    ->default('s-')

    ->newOption('--output-filepath', '-o')
    ->description('Location of the generated file.')
    ->default(realpath(__DIR__ . '/../..') . '/local/cli-tools-autocompletion.sh')

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
    ->default([realpath(__DIR__)])
    ->validatorCallback(
        function (&$value) {
            $value = realpath(trim($value));

            return false !== $value && is_readable($value);
        },
        'Path should be readable.',
    )

    ->run();

set_exception_handler(function (Throwable $e) {
    fwrite(STDERR, ScriptFormatter::createForStdErr()->error($e->getMessage() . PHP_EOL));

    exit(Parametizer::ERROR_EXIT_CODE);
});

$searchPaths        = $request->getParamAsStringList('search-paths');
$isVerbose          = $request->getParamAsBool('verbose');
$aliasPrefix        = $request->getParamAsString('alias-prefix');
$executionFormatter = ScriptFormatter::createForStdOut();

$scriptPathsByAliases = [];
if ($isVerbose) {
    echo $executionFormatter->section('=== SCANNING SEARCH PATHS for Pararmetizer-based scripts ===')
        . PHP_EOL. PHP_EOL;
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
        if (
            !str_contains($contents, 'Parametizer::newConfig(')
            || !str_contains($contents, '->run()')
        ) {
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
if (!$scriptPathsByAliases) {
    if ($isVerbose) {
        echo $executionFormatter->error('No scripts were found') . PHP_EOL;
    }

    exit(Parametizer::ERROR_EXIT_CODE);
}

if ($isVerbose) {
    echo $executionFormatter->section('=== GENERATING A FILE with aliases and auto-complete scripts ===')
        . PHP_EOL . PHP_EOL;
}
$outputFilepath  = $request->getParamAsString('output-filepath');
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
            throw new RuntimeException("Unable to write data for alias '{$scriptAlias}' into {$outputFilepath}");
        }
    }

    if ($isVerbose) {

        $bashIncludeCommand = $executionFormatter->command(
            PHP_EOL
            . 'echo -e "if [ -f ' . $outputFilepath . ' ]; then" \\' . PHP_EOL
            . '"\n    source ' . $outputFilepath . '" \\' . PHP_EOL
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

        echo PHP_EOL . 'Include the generated file into your bash profile (execute the command below):' . PHP_EOL
            . $bashIncludeCommand;
    }
} finally {
    fclose($fileHandler);
}
