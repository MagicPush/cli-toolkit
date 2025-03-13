<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Scripts\Internal;

use FilesystemIterator;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Question\Question;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use MagicPush\CliToolkit\Tools\CliToolkit\Scripts\CliToolkitScriptAbstract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class GenerateMassTestScripts extends CliToolkitScriptAbstract {
    protected const string LAUNCHER_NAME         = 'mass-test';
    protected const string LAUNCHER_ALIAS_PREFIX = 'zz';

    protected const int DIR_CAMEL_NAME_LENGTH  = 8;
    protected const int FILE_CAMEL_NAME_LENGTH = 16;

    protected const int PARAMETER_NAME_LENGTH = 3;


    protected ScriptFormatter $formatter;

    protected string $pathDirectoryProject;
    protected string $pathDirectoryBase;
    protected string $pathDirectoryScripts;

    /** @var Array<int, string> (int) directory number => (string) directory path */
    protected array $scriptDirectoryPaths = [];

    /** @var Array<string, string> (string) directory path => (string) namespace */
    protected array $namespaceByDirectoryPath = [];

    /** @var Array<string, true> (string) generated name => true (actual values are irrelevant) */
    protected array $generatedCamelNames = [];

    /** @var Array<string, true> (string) generated name => true (actual values are irrelevant) */
    protected array $generatedParameterNames = [];

    protected readonly int  $directoriesCount;
    protected readonly int  $directoriesMaxLevel;
    protected readonly int  $scriptsCount;
    protected readonly bool $isForced;
    protected readonly bool $isQuiet;


    public static function getNameSections(): array {
        $nameSections   = parent::getNameSections();
        $nameSections[] = 'internal';

        return $nameSections;
    }

    public static function getConfiguration(): BuilderInterface {
        $formatter = HelpFormatter::createForStdOut();

        return static::newConfig()
            ->description('
                Generates script classes, a launcher and supplementary files.

                By default all files are deleted each time the script is launched,
                unless ' . $formatter->paramTitle('--force') . ' is not specified
                and you do not confirm deletion when asked.

                Script classes may be dispersed over generated subdirectories, see '
                    . $formatter->paramTitle('--dir-max-level') . ' and ' . $formatter->paramTitle('--dir-count') . '.
            ')

            ->newOption('--dir-count')
            ->description('How many subdirectories should be created inside a base directory for script classes.')
            ->default(10)
            ->validatorCallback(
                function ($value) { return ctype_digit($value); },
                'Must be a non-negative integer.',
            )

            ->newOption('--dir-max-level')
            ->description('
                The longest directories branch. Examples:
                ' . $formatter->paramValue('0') . ': no additional directories are created.
                ' . $formatter->paramValue('1') . ': directories are created only within the base directory:
                    ' . $formatter->italic('basedir/aaa') . ', ' . $formatter->italic('basedir/bbb')
                    . ', ' . $formatter->italic('basedir/ccc') . ', ...
                ' . $formatter->paramValue('3') . ': directories may be created from 1 to 3 levels deep:
                    ' . $formatter->italic('basedir/Aa') . ' (1), ' . $formatter->italic('basedir/Aa/Bb') . ' (2), '
                    . $formatter->italic('basedir/Cc/Dd') . ' (2), '
                    . $formatter->italic('basedir/Cc/Dd/Ee') . ' (3), ...

                For values >= ' . $formatter->paramValue('1')
                    . ' the actual directory level is determined randomly.
            ')
            ->default(0)
            ->validatorCallback(
                function ($value) { return ctype_digit($value); },
                'Must be a non-negative integer.',
            )

            ->newFlag('--force', '-f')
            ->description('Delete previously generated files without confirmation.')

            ->newFlag('--quiet', '-q')
            ->description('Suppress all logs.')

            ->newArgument('scripts-count')
            ->description('
                How many scripts should be generated in total across all possible generated subdirectories.
            ')
            ->default(50)
            ->validatorCallback(
                function ($value) { return ctype_digit($value) && $value > 0; },
                'Must be a positive integer.',
            );
    }


    public function __construct(CliRequest $request) {
        parent::__construct($request);

        $this->formatter = ScriptFormatter::createForStdOut();

        $this->directoriesCount    = $request->getParamAsInt('dir-count');
        $this->directoriesMaxLevel = $request->getParamAsInt('dir-max-level');
        $this->scriptsCount        = $request->getParamAsInt('scripts-count');
        $this->isForced            = $request->getParamAsBool('force');
        $this->isQuiet             = $request->getParamAsBool('quiet');
    }

    public function execute(): void {
        $this->startUp()
            ->cleanUp()
            ->generateDirectories()
            ->generateScripts()
            ->generateInitScript()
            ->generateLauncher();
    }

    protected function log(string $message): static {
        if ($this->isQuiet) {
            return $this;
        }

        echo $message . PHP_EOL;

        return $this;
    }

    protected function startUp(): static {
        $pathDirectoryProject = __DIR__ . '/../../../..';
        $pathDirectoryProjectAbsolute = realpath($pathDirectoryProject);
        if (false === $pathDirectoryProjectAbsolute) {
            throw new RuntimeException(
                'Unable to get realpath() for the project path: ' . var_export($pathDirectoryProject, true),
            );
        }
        $this->pathDirectoryProject = $pathDirectoryProjectAbsolute;

        $this->pathDirectoryBase = $this->pathDirectoryProject . '/local/MassTest';
        $this->createDirectory($this->pathDirectoryBase);
        $this->pathDirectoryScripts = $this->pathDirectoryBase . '/Scripts';
        $this->createDirectory($this->pathDirectoryScripts);

        return $this;
    }

    protected function cleanUp(): static {
        /** @var Array<string, SplFileInfo[]> $objectsToDelete (string) validated realpath => (SplFileInfo) object */
        $objectsToDelete = [];

        /** @var Array<string, string> $unexpectedTypes (string) path => (string) type */
        $unexpectedTypes = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathDirectoryScripts, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        $objectNumber = 0;

        $this->log('Already generated data:');

        /** @var SplFileInfo $fileOrDir */
        foreach ($iterator as $fileOrDir) {
            $objectType = $fileOrDir->getType();
            if (false === $objectType) {
                throw new RuntimeException("Unable to read the path's type: {$fileOrDir->getPathname()}");
            }

            $objectPath = $fileOrDir->getRealPath();
            if (false === $objectPath) {
                throw new RuntimeException(
                    "Unable to read a realpath from the directory iterator. The pathname: {$fileOrDir->getPathname()}",
                );
            }

            $objectsToDelete[$objectPath] = $fileOrDir;

            $objectTypeFormatted = sprintf('%-7s', $objectType);
            if ('file' !== $objectType && 'dir' !== $objectType) {
                $unexpectedTypes[$objectPath] = $objectType;
                $objectTypeFormatted = $this->formatter->error($objectTypeFormatted);
            } else {
                $objectTypeFormatted = $this->formatter->note($objectTypeFormatted);
            }

            $this->log(sprintf(
                '%4d. %s => %s',
                ++$objectNumber,
                $objectTypeFormatted,
                $this->formatter->pathMentioned($objectPath),
            ));
        }
        if (empty($objectsToDelete)) {
            echo "\e[F\e[K";

            return $this;
        }

        if (!$this->isForced) {
            Question::confirmOrDie(
                <<<TEXT
                Are you sure about deleting previously generated data (see above if '--quiet' flag is not specified)?
                Y - delete and proceed, N - abort the execution.

                TEXT,
            );
        }

        if (!empty($unexpectedTypes)) {
            $this->log(PHP_EOL . $this->formatter->error('Unable to proceed! Unexpected types detected: '));

            $objectNumber = 0;
            foreach ($unexpectedTypes as $path => $unexpectedType) {
                $unexpectedTypeFormatted = sprintf('%-7s', $unexpectedType);

                $this->log(sprintf(
                    '%4d. %s => %s',
                    ++$objectNumber,
                    $this->formatter->error($unexpectedTypeFormatted),
                    $path,
                ));
            }

            exit(Parametizer::ERROR_EXIT_CODE);
        }

        $deletedCount = 0;
        foreach ($objectsToDelete as $deletePath => $fileOrDir) {
            if ($fileOrDir->isDir()) {
                if (false === rmdir($deletePath)) {
                    throw new RuntimeException("Unable to delete a directory: {$deletePath}");
                }
            } elseif (false === unlink($deletePath)) {
                throw new RuntimeException("Unable to delete a file: {$deletePath}");
            }

            $deletedCount++;
        }

        $this->log('Deleted files: ' . $this->formatter->success((string) $deletedCount));

        return $this;
    }

    protected function createDirectory(string $absolutePath): string {
        if (!is_dir($absolutePath)) {
            if (!mkdir(directory: $absolutePath, recursive: true)) {
                throw new RuntimeException("Unable to create a directory: {$absolutePath}");
            }

            $this->log('A directory has been created: ' . $this->formatter->pathProcessed($absolutePath));
        }

        return $absolutePath;
    }

    protected function getNamespaceByDirectoryPath(string $directoryPath): string {
        if (!empty($this->namespaceByDirectoryPath[$directoryPath])) {
            return $this->namespaceByDirectoryPath[$directoryPath];
        }

        if (!is_readable($directoryPath)) {
            throw new RuntimeException("Path for a namespace is not readable: {$directoryPath}");
        }
        if (!str_starts_with($directoryPath, $this->pathDirectoryProject)) {
            throw new RuntimeException(
                "Path '{$directoryPath}' is not within the project directory '{$this->pathDirectoryProject}'",
            );
        }

        $pathToProcess = ltrim(
            substr_replace($directoryPath, '', 0, mb_strlen($this->pathDirectoryProject)),
            '/',
        );
        $namespaceParts = explode('/', $pathToProcess);

        $namespace = 'MagicPush\\CliToolkit';
        foreach ($namespaceParts as $namespacePart) {
            $stringProcessed = '';
            foreach (mb_str_split($namespacePart) as $symbol) {
                if ('' === $stringProcessed) {
                    $symbol = mb_strtoupper($symbol);
                }

                $stringProcessed .= $symbol;
            }

            $namespace .= '\\' . $stringProcessed;
        }

        $this->namespaceByDirectoryPath[$directoryPath] = $namespace;

        return $namespace;
    }

    protected function generateDirectories(): static {
        $this->scriptDirectoryPaths[0] = $this->pathDirectoryScripts;

        if (0 === $this->directoriesCount || 0 === $this->directoriesMaxLevel) {
            return $this;
        }

        $directoryPathsByLevels = [];
        for ($directoryNumber = 1; $directoryNumber <= $this->directoriesCount; $directoryNumber++) {
            $directoryLevel      = rand(1, $this->directoriesMaxLevel);
            $possibleParentPaths = $directoryPathsByLevels[$directoryLevel - 1] ?? null;
            while ($directoryLevel > 1) {
                if (!empty($possibleParentPaths)) {
                    break;
                }
                $directoryLevel--;
            }

            $parentPath = !empty($possibleParentPaths)
                ? $possibleParentPaths[array_rand($possibleParentPaths)]
                : $this->scriptDirectoryPaths[0];

            $directoryPath = $parentPath . '/' . $this->generateCamelName(static::DIR_CAMEL_NAME_LENGTH);
            $this->createDirectory($directoryPath);

            $directoryPathsByLevels[$directoryLevel][] = $directoryPath;
            $this->scriptDirectoryPaths[] = $directoryPath;
        }

        return $this;
    }

    protected function generateInitScript(): static {
        $searchNamespacePSR4 = $this->getNamespaceByDirectoryPath($this->pathDirectoryBase);
        $searchNamespacePSR4 .= '\\';
        $searchNamespacePSR4 = str_replace('\\', '\\\\', $searchNamespacePSR4);

        $contents = <<<TEXT
<?php

declare(strict_types=1);

require_once '{$this->pathDirectoryProject}/vendor/autoload.php';

use Composer\Autoload\ClassLoader;

\$composerLoader = new ClassLoader('{$this->pathDirectoryProject}/vendor');
\$composerLoader->addPsr4('{$searchNamespacePSR4}', [__DIR__]);
\$composerLoader->register();

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');

TEXT;

        $pathFile = $this->pathDirectoryBase . '/init.php';
        if (false === file_put_contents($pathFile, $contents)) {
            throw new RuntimeException('Unable to store an init script: ' . var_export($pathFile, true));
        }
        $this->log('Init script has been written: ' . $this->formatter->pathProcessed($pathFile));

        return $this;
    }

    protected function generateLauncher(): static {
        $contents = <<<TEXT
<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;

\$scriptDetector = (new ScriptDetector(true))
    ->addSearchClassPath('{$this->pathDirectoryScripts}')
    ->detect();
\$classNamesBySubcommandNames = \$scriptDetector->getFQClassNamesByScriptNames();

\$builder = Parametizer::newConfig();
\$builder
    ->newSubcommandSwitch('subcommand');

foreach (\$classNamesBySubcommandNames as \$subcommandName => \$className) {
    \$builder->newSubcommand(\$subcommandName, \$className::getConfiguration());
}

\$request = \$builder->run();

\$subcommandName    = \$request->getParamAsString('subcommand');
\$subcommandRequest = \$request->getSubcommandRequest(\$subcommandName);

\$className   = \$classNamesBySubcommandNames[\$subcommandName];
\$scriptClass = new \$className(\$subcommandRequest);
\$scriptClass->execute();

TEXT;

        $launcherFileName = static::LAUNCHER_NAME;
        $pathLauncher     = $this->pathDirectoryBase . "/{$launcherFileName}.php";
        if (false === file_put_contents($pathLauncher, $contents)) {
            throw new RuntimeException('Unable to store a launcher script: ' . var_export($pathLauncher, true));
        }
        $this->log('Launcher script has been written: ' . $this->formatter->pathProcessed($pathLauncher));

        $scriptAlias    = static::LAUNCHER_ALIAS_PREFIX . $launcherFileName;
        $pathCompletion = $this->pathDirectoryBase . '/completion.sh';
        if (
            false === file_put_contents(
                $pathCompletion,
                Completion::generateAutocompleteScript($scriptAlias, $pathLauncher),
            )
        ) {
            throw new RuntimeException('Unable to store a completion script: ' . var_export($pathCompletion, true));
        }
        $this->log(
            PHP_EOL . 'Completion script has been written: '  . $this->formatter->pathProcessed($pathCompletion)
                . PHP_EOL . 'Enable completion for "' . $this->formatter->pathMentioned($scriptAlias) . '":'
                . PHP_EOL . $this->formatter->command("source {$pathCompletion}"),
        );

        $this->log(
            PHP_EOL . 'Or include the completion script file into your bash profile:'
                . PHP_EOL
                . $this->formatter->command(
                    'echo -e "if [ -f ' . $pathCompletion . ' ]; then" \\' . PHP_EOL
                        . '"\n    source ' . $pathCompletion . '" \\' . PHP_EOL
                        . '"\nfi\n" \\' . PHP_EOL
                        . '>> ~/.bashrc' . PHP_EOL,
                ),
        );

        return $this;
    }

    protected function generateCamelName(int $length): string {
        do {
            $name = '';

            $availableSymbols = range('A', 'Z');
            $name .= $availableSymbols[array_rand($availableSymbols)];

            $availableSymbols = array_merge($availableSymbols, range('a', 'z'), range(0, 9));
            for ($symbolNumber = 1; $symbolNumber < $length; $symbolNumber++) {
                $name .= $availableSymbols[array_rand($availableSymbols)];
            }
        } while (array_key_exists($name, $this->generatedCamelNames));

        $this->generatedCamelNames[$name] = true;

        return $name;
    }

    protected function generateParameterName(int $length): string {
        do {
            $name = '';

            $availableSymbols = range('a', 'z');
            for ($symbolNumber = 0; $symbolNumber < $length; $symbolNumber++) {
                $name .= $availableSymbols[array_rand($availableSymbols)];
            }
        } while (array_key_exists($name, $this->generatedParameterNames));

        $this->generatedParameterNames[$name] = true;

        return $name;
    }

    protected function generateScripts(): static {
        $scriptsCountLength = strlen((string) $this->scriptsCount);

        $this->log(PHP_EOL . 'Script classes generated: ');
        $directoriesCount = count($this->scriptDirectoryPaths);
        $directoryNumber  = 0;
        for ($scriptNumber = 1; $scriptNumber <= $this->scriptsCount; $scriptNumber++) {
            $directoryPath = $this->scriptDirectoryPaths[$directoryNumber];
            $namespace     = $this->getNamespaceByDirectoryPath($directoryPath);
            $className     = $this->generateCamelName(static::FILE_CAMEL_NAME_LENGTH);

            $scriptContents = <<<TEXT
<?php

declare(strict_types=1);

namespace {$namespace};

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

final class {$className} extends ScriptAbstract {
    public static function getConfiguration(): BuilderInterface {
        return static::newConfig()
            ->newOption('--%%OPTION_NAME%%')
            
            ->newFlag('--%%FLAG_NAME%%')
            
            ->newArgument('%%ARGUMENT_NAME%%')
            ->required(false);
    }

    public function execute(): void {
        %%EXECUTION%%
    }
}

TEXT;

            // Do not force unique values across all scripts. We need unique values only within a single script.
            $this->generatedParameterNames = [];
            $scriptContentsReplacements = [
                '%%OPTION_NAME%%'   => $this->generateParameterName(static::PARAMETER_NAME_LENGTH),
                '%%FLAG_NAME%%'     => $this->generateParameterName(static::PARAMETER_NAME_LENGTH),
                '%%ARGUMENT_NAME%%' => $this->generateParameterName(static::PARAMETER_NAME_LENGTH),
            ];

            $scriptExecutionContents = '';
            $scriptExecutionEchoCommand = '        echo json_encode([' . PHP_EOL;
            foreach ($this->generatedParameterNames as $parameterName => $notUsed) {
                $scriptExecutionContents .=
                    "        \${$parameterName} = \$this->request->getParam('{$parameterName}');" . PHP_EOL;
                $scriptExecutionEchoCommand .= "            '{$parameterName}' => \${$parameterName}," . PHP_EOL;
            }
            $scriptExecutionEchoCommand .= '        ]) . PHP_EOL;' . PHP_EOL;
            $scriptContentsReplacements['%%EXECUTION%%'] = trim(
                $scriptExecutionContents . PHP_EOL . $scriptExecutionEchoCommand,
            );

            $scriptContents = str_replace(
                array_keys($scriptContentsReplacements),
                $scriptContentsReplacements,
                $scriptContents,
            );

            $scriptPath = $directoryPath . "/{$className}.php";
            if (false === file_put_contents($scriptPath, $scriptContents)) {
                throw new RuntimeException('Unable to create a class script: ' . var_export($scriptPath, true));
            }
            $this->log(
                sprintf("%{$scriptsCountLength}d. ", $scriptNumber) . $this->formatter->pathMentioned($className) . ': '
                    . $this->formatter->pathProcessed($scriptPath),
            );

            $directoryNumber++;
            if ($directoryNumber >= $directoriesCount) {
                $directoryNumber = 0;
            }
        }
        $this->log('');

        return $this;
    }
}
