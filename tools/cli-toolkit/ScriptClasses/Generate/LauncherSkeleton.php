<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate;

use Composer\Autoload\ClassLoader;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ShowHelpPage;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use MagicPush\CliToolkit\Question\Question;
use MagicPush\CliToolkit\Utils;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use Override;
use RuntimeException;

class LauncherSkeleton extends CliToolkitGenerateScriptAbstract {
    protected const string SCRIPT_CLASSES_DIRECTORY_NAME = 'ScriptClasses';
    protected const string EXAMPLE_CLASS_NAME            = 'MyFirstScript';

    protected const string LAUNCHER_BASENAME     = 'launcher.php';
    protected const string LAUNCHER_ALIAS_PREFIX = 'ct';


    protected readonly ScriptFormatter $formatter;

    protected readonly string $parentProjectRootDirectoryPath;
    protected readonly string $parentProjectClassesMainLibraryPath;
    protected readonly string $parentProjectNamespace;
    protected readonly string $generatedDirectoryPath;
    protected readonly string $generatedExampleClassPath;
    protected readonly string $generatedExampleClassNamespace;
    protected readonly string $generatedLauncherPath;

    /**
     * @var string[] [0] namespace prefix, [1] directory path;
     *               a set of parameters for {@see ClassLoader::addPsr4()}
     */
    protected readonly array $exampleClassCustomPsr4Entry;

    /** @var array<string, string> (string) path => (string) comment */
    protected array $createdEntries = [];

    protected readonly string $completionScriptPath;
    protected readonly string $exampleClassScriptName;


    #[Override]
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
            ->description('
                Generates a skeleton for your class-based scripts.

                The generator will ask you for the directory path where the skeleton files will be located.
                Non-existing directories along the path will be created automatically.

                All generated files will contain some comments as a "starter manual", so you will be able to know
                a bit more about the skeleton created and how to set up it.

                The generated structure should be considered just as an example - one of possible ways to store
                connected scripts. The main issues for you will be namespaces (autoload setup) and script classes
                detection rules. See the comments and docs to know more.
            ');
    }

    /**
     * Example: function ('/home/user/cool-project/vendor/autoload.php', '/home/user/cool-project/src/Cli')
     *  returns '/../../vendor/autoload.php'
     *
     * @param string $targetAbsolutePath Can not be validated (without complex logic), should 'look' absolute.
     */
    protected static function getPathRelativeSuffix(string $targetAbsolutePath, string $baseDirectoryPath): string {
        $baseDirectoryRealPath = is_dir($baseDirectoryPath) ? realpath($baseDirectoryPath) : false;
        if (false === $baseDirectoryRealPath) {
            throw new RuntimeException(
                'Base directory path does not exist: ' . var_export($baseDirectoryPath, true),
            );
        }

        $targetComponents = explode('/', ltrim($targetAbsolutePath, '/'));
        $baseComponents   = explode('/', ltrim($baseDirectoryRealPath, '/'));
        $componentIndex   = 0;
        $commonPath       = '';
        while (true) {
            $targetComponent = $targetComponents[$componentIndex] ?? null;
            $baseComponent = $baseComponents[$componentIndex] ?? null;
            if (null === $targetComponent || $targetComponent !== $baseComponent) {
                break;
            }

            $commonPath .= "/{$targetComponent}";
            $componentIndex++;
        }

        $uniqueBaseComponentsCount = count($baseComponents) - $componentIndex;

        return str_repeat('/..', max(0, $uniqueBaseComponentsCount))
            . str_replace($commonPath, '', $targetAbsolutePath);
    }

    protected static function getCanonicalizedPath(string $relativePath): string {
        $relativePathPrefix     = $relativePath;
        $realPathPrefix         = realpath($relativePathPrefix);
        $missingComponentsCount = 0;
        while (false === $realPathPrefix) {
            $relativePathPrefix = dirname($relativePathPrefix);
            $realPathPrefix     = realpath($relativePathPrefix);
            $missingComponentsCount++;
        }

        $canonicalizedPath = $realPathPrefix;
        if ($missingComponentsCount > 0) {
            $relativePathComponents    = explode('/', $relativePath);
            $nonExistingPathComponents = array_slice(
                $relativePathComponents,
                count($relativePathComponents) - $missingComponentsCount,
                $missingComponentsCount,
            );

            if (in_array('..', $nonExistingPathComponents)) {
                throw new RuntimeException(
                    "Unable to canonicalize the path: {$relativePath}"
                    . PHP_EOL . "Ensure there is no '/../' parts within non-existing part of the path."
                );
            }

            $canonicalizedPath .= '/' . implode('/', $nonExistingPathComponents);
        }

        return $canonicalizedPath;
    }


    public function execute(): void {
        $this->formatter = ScriptFormatter::createForStdOut();

        $this
            ->setUpGenerator()
            ->generateScriptClassExample()
            ->generateScriptClassesLauncher()
            ->generateCompletionsGenerator()
            ->showUsefulInfo();
    }

    protected function setUpGenerator(): static {
        $parentProjectRootDirectoryPath      = Utils::detectTopmostProjectRootDirectory();
        $parentProjectClassesMainLibraryPath = $parentProjectRootDirectoryPath;
        $parentProjectNamespace              = 'UnknownVendor\\UnknownProject\\';

        $composerClassLoaders        = ClassLoader::getRegisteredLoaders();
        $composerFirstClassLoaderKey = array_key_first($composerClassLoaders);
        if ($composerFirstClassLoaderKey && str_ends_with($composerFirstClassLoaderKey, '/vendor')) {
            $parentProjectRootDirectoryPath = dirname($composerFirstClassLoaderKey);
        }

        $psr4Prefixes = $composerClassLoaders[$composerFirstClassLoaderKey]->getPrefixesPsr4() ?? [];
        if ($psr4Prefixes) {
            $parentProjectNamespace = array_key_first($psr4Prefixes);
            $firstPsr4RealPath      = realpath($psr4Prefixes[$parentProjectNamespace][0]);
            if (false !== $firstPsr4RealPath) {
                $parentProjectClassesMainLibraryPath = $firstPsr4RealPath;
            }
        }

        $this->parentProjectRootDirectoryPath      = $parentProjectRootDirectoryPath;
        $this->parentProjectClassesMainLibraryPath = $parentProjectClassesMainLibraryPath;
        $this->parentProjectNamespace              = $parentProjectNamespace;


        $generatedDirectoryRelativePath = Question::create('Path to your scripts launcher and related generated stuff')
            ->defaultAnswer($this->parentProjectClassesMainLibraryPath . '/Cli')
            ->ask();
        $this->generatedDirectoryPath = static::getCanonicalizedPath($generatedDirectoryRelativePath);
        echo PHP_EOL;

        $this->generatedExampleClassPath   = "{$this->generatedDirectoryPath}/" . static::SCRIPT_CLASSES_DIRECTORY_NAME
            . '/' . static::EXAMPLE_CLASS_NAME . '.php';
        $this->generatedExampleClassNamespace = $this->createNamespaceByClassPath($this->generatedExampleClassPath);


        $this->generatedLauncherPath = $this->generatedDirectoryPath . '/' . static::LAUNCHER_BASENAME;


        return $this;
    }

    protected function generateScriptClassesLauncher(): static {
        $initPathSuffix = '/init-cli.php';
        $initPath       = $this->generatedDirectoryPath . $initPathSuffix;
        $initSubstitutes = [
            '%%DO%%'       => 'TODO',
            '%%AUTOLOAD%%' => static::getPathRelativeSuffix(
                $this->parentProjectRootDirectoryPath . '/vendor/autoload.php',
                dirname($initPath),
            ),
        ];
        $initTemplate = <<<PHP
            <?php

            declare(strict_types=1);

            require_once __DIR__ . '%%AUTOLOAD%%';

            mb_internal_encoding('UTF-8');
            setlocale(LC_ALL, 'en_US.UTF-8');

            PHP;
        if (isset($this->exampleClassCustomPsr4Entry)) {
            $initTemplate .= <<<PHP

                // %%DO%% Remove the lines below and set up your composer.json "autoload" settings accordingly.
                \$composerLoader = new \Composer\Autoload\ClassLoader();
                \$composerLoader->addPsr4(
                    '{$this->exampleClassCustomPsr4Entry[0]}',
                    [
                        '{$this->exampleClassCustomPsr4Entry[1]}',
                    ],
                 );
                \$composerLoader->register();

                PHP;
        }
        $initContents = str_replace(
            array_keys($initSubstitutes),
            $initSubstitutes,
            $initTemplate,
        );
        $this->createFile($initPath, $initContents, 'CLI setup script');


        require_once $this->generatedExampleClassPath;
        /** @var ScriptClassAbstract $generatedExampleClassFQName */
        $generatedExampleClassFQName       = $this->generatedExampleClassNamespace . '\\' . static::EXAMPLE_CLASS_NAME;
        $this->exampleClassScriptName      = $generatedExampleClassFQName::getScriptName();
        $generatedExampleClassRelativePath = str_replace(
            $this->generatedDirectoryPath,
            '.',
            $this->generatedExampleClassPath,
        );

        /**
         * This names processing is needed solely for {@see ScriptFileDetector::processDetectedFileContents()} rules
         * to not consider this class as a plain script (because af specific substrings presented here).
         */
        /** @var callable $launcherClassCallable This hint is needed only for the class method to be IDE-detectable. */
        $launcherClassCallable  = [ScriptClassLauncher::class, 'execute'];
        $launcherClassShortName = Utils::getClassShortName(ScriptClassLauncher::class);

        $launcherSubstitutes = [
            '%%DO%%'                            => 'TODO',
            '%%SCRIPT_CLASSES_DIRECTORY_NAME%%' => static::SCRIPT_CLASSES_DIRECTORY_NAME,
            '%%SUBCOMMAND_NAME_LIST%%'          => ListSubcommands::getScriptName(),
            '%%DOCS_ENV_CONFIG%%'               => ltrim(
                static::getPathRelativeSuffix(
                    realpath(__DIR__ . '/../../../../docs/features-manual.md'),
                    dirname($this->generatedLauncherPath),
                ),
                '/',
            ) . '#environment-config',
        ];
        $launcherTemplate = <<<PHP
            <?php

            declare(strict_types=1);

            /**
             * %%DO%%
             *  Make sure {@see ./%%SCRIPT_CLASSES_DIRECTORY_NAME%%} classes are watched by your autoloader
             *  (see also the autogenerated namespace in {@see {$generatedExampleClassRelativePath}}).
             *  If everything is done right, just launching
             *  'php {$this->generatedLauncherPath} %%SUBCOMMAND_NAME_LIST%%'
             *  will show '{$this->exampleClassScriptName}' entry along with built-in subcommands.
             */
            require_once __DIR__ . '{$initPathSuffix}';

            use {$launcherClassCallable[0]};
            use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

            /**
             * %%DO%%
             *  It is the default state for detectors.
             *  Thus, it is safe to just remove 'throwOnException: true' from the line below.
             *  --
             *  It is advisable to always keep exceptions ENABLED for the detector:
             *  its setup mistakes can cause some or all of your scripts to be unavailable for the launcher.
             */
            \$scriptClassDetector = ScriptClassDetector::create(throwOnException: true)
                ->searchDirectory(__DIR__ . '/%%SCRIPT_CLASSES_DIRECTORY_NAME%%', isRecursive: true);

            {$launcherClassShortName}::create(\$scriptClassDetector)
                /**
                 * %%DO%%
                 *  It's the default setting for configs being built.
                 *  Thus, it's safe to just remove '->throwOnException(false)' line.
                 *  --
                 *  It is advisable to IGNORE exceptions for all script configs being built in the launcher:
                 *  as for now, only {@see EnvironmentConfig::createFromConfigsBottomUpHierarchy()} may cause exceptions
                 *  (and only if you explicitly create invalid or unreadable config files),
                 *  but in the current library version no supported environment settings may affect your scripts logic.
                 *  --
                 *  If you want to know more about env configs,
                 *  read {@see %%DOCS_ENV_CONFIG%%}
                 */
                ->throwOnException(false)
                ->{$launcherClassCallable[1]}();
            PHP;
        $launcherContents = str_replace(
            array_keys($launcherSubstitutes),
            $launcherSubstitutes,
            $launcherTemplate,
        );
        $this->createFile($this->generatedLauncherPath, $launcherContents, 'Scripts launcher');

        return $this;
    }

    protected function generateScriptClassExample(): static {
        $classSubstitutes = [
            '%%DO%%'         => 'TODO',
            '%%CLASS_NAME%%' => static::EXAMPLE_CLASS_NAME,
        ];
        $classTemplate = <<<PHP
            <?php

            declare(strict_types=1);

            /*
             * %%DO%%
             *  Make sure the namespace is valid for this class location
             *  and your project composer.json "autoload" settings.
             */
            namespace {$this->generatedExampleClassNamespace};

            use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
            use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
            use Override;

            /** @noinspection PhpUnused */
            class %%CLASS_NAME%% extends ScriptClassAbstract {
                /*
                 * %%DO%%
                 *  Optionally, you may group scripts with this method.
                 *  Each subsequent array element adds a subgroup. For instance, with the script name 'processor'
                 *  and the sections array ['super', 'mega'] your script name will be 'super:mega:processor'.
                 */
                #[Override]
                public static function getNameSections(): array {
                    return array_merge(parent::getNameSections(), ['example-scripts']);
                }

                #[Override]
                protected static function setUpConfig(ConfigBuilder \$configBuilder): void {
                    parent::setUpConfig(\$configBuilder);

                    \$configBuilder
                        ->newOption('--some-option', '-o')

                        ->newArgument('some-argument')
                        ->required(false);
                }


                public function execute(): void {
                    echo 'Option: ' . var_export(\$this->request->getParamAsString('some-option'), true) . PHP_EOL;
                    echo 'Argument: ' . var_export(\$this->request->getParamAsString('some-argument'), true) . PHP_EOL;
                }
            }


            PHP;
        $classContents = str_replace(
            array_keys($classSubstitutes),
            $classSubstitutes,
            $classTemplate,
        );
        $this->createFile($this->generatedExampleClassPath, $classContents, 'Script class example');

        return $this;
    }

    protected function generateCompletionsGenerator(): static {
        $completionScriptPathSuffix           = '/local/completion.sh';
        $generatedLauncherBasenameNoExtension = basename(static::LAUNCHER_BASENAME, '.php');
        $completionGeneratorPath              = $this->generatedDirectoryPath . '/generate-completion.sh';

        $stockLauncherAbsolutePath = realpath(__DIR__ . '/../../run.php');
        $stockLauncherRelativePath = static::getPathRelativeSuffix(
            $stockLauncherAbsolutePath,
            dirname($completionGeneratorPath),
        );

        $completionGeneratorSubstitutes = [
            '%%DO%%'                              => 'TODO',
            '%%COMPLETION_GENERATOR_SUBCOMMAND%%' => CompletionScript::getScriptName(),
            '%%ALIAS_PREFIX%%'                    => static::LAUNCHER_ALIAS_PREFIX,
            '%%LAUNCHER_BASENAME%%'               => static::LAUNCHER_BASENAME,
            '%%PARAMETER_HELP%%'                  => Config::PARAMETER_NAME_HELP,
        ];
        $completionGeneratorTemplate = <<<SHELL
            #!/bin/bash
            scriptDirectoryPath="$(cd "$(dirname "\${BASH_SOURCE[0]}")" &> /dev/null && pwd)"
            stockCliToolkitLauncherPath=$(readlink -f "\$scriptDirectoryPath{$stockLauncherRelativePath}")

            php \$stockCliToolkitLauncherPath %%COMPLETION_GENERATOR_SUBCOMMAND%% \
                --verbose \
                --alias-prefix='%%ALIAS_PREFIX%%' \
                --output-filepath="\$scriptDirectoryPath{$completionScriptPathSuffix}" \
                --include-script="\$scriptDirectoryPath/%%LAUNCHER_BASENAME%%"

            # %%DO%% [--verbose]: Remove the line below after you get acquainted with the completion generator.

            # %%DO%% [--alias-prefix='%%ALIAS_PREFIX%%']:
            # %%DO%% It will create an alias '%%ALIAS_PREFIX%%{$generatedLauncherBasenameNoExtension}'
            # %%DO%% for your newly generated launcher.
            # %%DO%% You may replace it with any other substring or even ' ' (a space character), which works as no prefix,
            # %%DO%% so your launcher Bash alias will be just '{$generatedLauncherBasenameNoExtension}'.

            # %%DO%% [--output-filepath='...{$completionScriptPathSuffix}']:
            # %%DO%% You should generate the completion script each time you make relevant changes (or build a container)
            # %%DO%% and ignore the script in your VCS (or pick your own already ignored directory for that) - because of
            # %%DO%% absolute paths inside and because later you may add here for your completion script more launchers
            # %%DO%% and non-class scripts.

            # %%DO%% [--include-script='.../%%LAUNCHER_BASENAME%%']:
            # %%DO%% For now, there is just the generated launcher. But you may add more entries,
            # %%DO%% even whole directories to scan.

            # %%DO%% Launch 'php {$stockLauncherAbsolutePath} %%COMPLETION_GENERATOR_SUBCOMMAND%% --%%PARAMETER_HELP%%'
            # %%DO%% to read more about possible options.


            SHELL;
        $completionGeneratorContents = str_replace(
            array_keys($completionGeneratorSubstitutes),
            $completionGeneratorSubstitutes,
            $completionGeneratorTemplate,
        );
        $this->createFile($completionGeneratorPath, $completionGeneratorContents, 'Completion script generator');

        if (false === chmod($completionGeneratorPath, 0755)) {
            throw new RuntimeException(
                "Unable to change permissions for the completion script generator: {$completionGeneratorPath}",
            );
        }

        /** @var int $exitCode */
        if (false === exec($completionGeneratorPath, result_code: $exitCode) || 0 !== $exitCode) {
            throw new RuntimeException(
                "Unable to launch the completion generator file: {$completionGeneratorPath}",
            );
        }
        $this->completionScriptPath = $this->generatedDirectoryPath . $completionScriptPathSuffix;
        $this->createdEntries[$this->completionScriptPath] = 'Completion script';

        return $this;
    }

    protected function showUsefulInfo(): static {
        $launcherAlias          = static::LAUNCHER_ALIAS_PREFIX . basename(static::LAUNCHER_BASENAME, '.php');
        $launcherAliasFormatted = $this->formatter->note($launcherAlias);

        $createdEntriesWithSuffixes = $this->createdEntries;
        $descriptionSuffix          = ': ';
        $longestDescriptionLength   = 0;
        foreach ($createdEntriesWithSuffixes as &$description) {
            $description       .= $descriptionSuffix;
            $descriptionLength = mb_strlen($description);
            if ($longestDescriptionLength < $descriptionLength) {
                $longestDescriptionLength = $descriptionLength;
            }
        }
        unset($description);

        echo $this->formatter->section('Files created by the skeleton generator:') . PHP_EOL . PHP_EOL;
        foreach ($createdEntriesWithSuffixes as $path => $description) {
            echo $this->formatter->note(mb_str_pad($description, $longestDescriptionLength, pad_type: STR_PAD_LEFT))
                . $this->formatter->pathProcessed($path)
                . PHP_EOL;
        }
        echo PHP_EOL;

        echo $this->formatter->section('Completion setup:') . PHP_EOL . PHP_EOL;
        echo 'If you apply the just generated completion script...' . PHP_EOL;
        echo $this->formatter->command(PHP_EOL . "source {$this->completionScriptPath}" . PHP_EOL);
        echo <<<TEXT

                ... you will be able to call the launcher from any path by its alias '{$launcherAliasFormatted}',
                which supports completion for available commands, their option names
                and parameter values (if configured for particular parameters).

                If you want the completion script to be applied each time you open a terminal,
                append its sourcing to your '.bashrc' file:

                TEXT;
        echo $this->formatter->command(
            PHP_EOL
                . 'echo -e "if [ -f ' . $this->completionScriptPath . ' ]; then" \\' . PHP_EOL
                . '"\n    source ' . $this->completionScriptPath . '" \\' . PHP_EOL
                . '"\nfi\n" \\' . PHP_EOL
                . '>> $HOME/.bashrc' . PHP_EOL,
        )
            . PHP_EOL;

        $launcherPhpCallFormatted = $this->formatter->command(PHP_EOL . "php {$this->generatedLauncherPath}" . PHP_EOL);
        echo $this->formatter->section('Starter cheat sheet:') . PHP_EOL . PHP_EOL;
        echo <<<TEXT
            You may call your launcher by an alias '{$launcherAliasFormatted}' (assuming you have enabled it; see above how)
            or in a traditional way:
            {$launcherPhpCallFormatted}

            TEXT;

        $descriptionsByCommands = [
            ListSubcommands::getScriptName()                                   => 'List available commands',
            ShowHelpPage::getScriptName() . " {$this->exampleClassScriptName}" => 'Show a command\'s help page',
            "{$this->exampleClassScriptName} --" . Config::PARAMETER_NAME_HELP => 'Same as above, works even with the launcher itself',
            "{$this->exampleClassScriptName} a -ob"                            => 'Example script call with parameters',
        ];
        $longestCommandLength = 0;
        foreach ($descriptionsByCommands as $command => $description) {
            $commandLength = mb_strlen($command);
            if ($longestCommandLength < $commandLength) {
                $longestCommandLength = $commandLength;
            }
        }
        $commandPrefix        = "  {$launcherAlias} ";
        $longestCommandLength += mb_strlen($commandPrefix);

        echo 'Below are a few call examples (based on the launcher alias):' . PHP_EOL;
        foreach ($descriptionsByCommands as $command => $description) {
            echo $this->formatter->command(
                    mb_str_pad("{$commandPrefix}{$command}", $longestCommandLength, pad_type: STR_PAD_RIGHT),
                )
                . $this->formatter->note(" # {$description}") . '.' . PHP_EOL;
        }

        $readmePath         = realpath(__DIR__ . '/../../../../README.md');
        $featuresManualPath = realpath(__DIR__ . '/../../../../docs/features-manual.md');
        echo PHP_EOL . 'If you want to know more, read the manual pages:'
            . PHP_EOL . "    - {$readmePath}"
            . PHP_EOL . "    - {$featuresManualPath}"
            . PHP_EOL. PHP_EOL;

        return $this;
    }

    protected function createFile(string $path, string $contents, string $description): void {
        $directoryPath = dirname($path);
        if (!is_dir($directoryPath)) {
            if (!mkdir($directoryPath, recursive: true)) {
                throw new RuntimeException(
                    'Unable to create a directory: ' . var_export($directoryPath, true),
                );
            }
        }

        if (false === file_put_contents($path, $contents)) {
            throw new RuntimeException("Unable to write contents into the file: {$path}");
        }
        $this->createdEntries[$path] = $description;
    }

    protected function createNamespaceByClassPath(string $classAbsoluteFilePath): string {
        $classDirectoryPath    = dirname($classAbsoluteFilePath);
        $mainLibraryPathPrefix = $this->parentProjectClassesMainLibraryPath;
        $shouldAddCustomPsr4   = false;
        if (!str_starts_with($classDirectoryPath, $mainLibraryPathPrefix)) {
            $shouldAddCustomPsr4   = true;
            $mainLibraryPathPrefix = dirname($this->parentProjectClassesMainLibraryPath);
            while (!str_starts_with($classDirectoryPath, $mainLibraryPathPrefix)) {
                $mainLibraryPathPrefix = dirname($mainLibraryPathPrefix);
            }
        }
        $pathSuffix = ltrim(str_replace($mainLibraryPathPrefix, '', $classDirectoryPath), '/');

        $namespace = $this->parentProjectNamespace;
        foreach (explode('/', $pathSuffix) as $pathComponent) {
            $namespace .= mb_strtoupper(mb_substr($pathComponent, 0, 1)) . mb_substr($pathComponent, 1) . '\\';
        }

        if ($shouldAddCustomPsr4) {
            $this->exampleClassCustomPsr4Entry = [str_replace('\\', '\\\\', $namespace), $classDirectoryPath];
        }

        return rtrim($namespace, '\\');
    }
}
