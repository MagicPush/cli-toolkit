<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\ToolBelt;
use ReflectionClass;

abstract class ScriptClassAbstract {
    /** @see Config::newSubcommand() - allowed characters validation */
    public const string NAME_SECTION_SEPARATOR = ':';
    /** @see Config::newSubcommand() - allowed characters validation */
    public const string NAME_PART_SEPARATOR = '-';


    /**
     * The method is utilized by {@see ScriptClassDetector::processDetectedFileContents()} (only when searching by
     * directories) mainly to ignore built-in subcommands, because those are added explicitly by the library.
     *
     * However, this method does NOT restrict script classes to be actually executed.
     *
     * You may repurpose this method for any other reason you see fit. Just a few examples:
     *  * Detect particular scripts for selected environments only.
     *      Like code generators, which are not very useful on test or production servers.
     *  * Hide particular scripts from all launchers. Sometimes it might be easier to hide a script here
     *      rather excluding it in each {@see ScriptClassDetector} instance.
     */
    public static function isAvailableByDetector(): bool {
        return true;
    }

    /**
     * In comparison with {@see static::getScriptName()} the method returns only the last part
     * of a full script name - without {@see static::getNameSections()}.
     */
    public static function getScriptInnerName(): string {
        $classShortName      = ToolBelt::getClassShortName(static::class);
        $scriptName          = '';
        $previousSymbolUpper = null;
        $pendingAbbreviation = '';
        foreach (mb_str_split($classShortName) as $symbol) {
            $symbolToLower = mb_strtolower($symbol);

            if ($symbol !== $symbolToLower) {
                $symbol = $symbolToLower;
                if (null !== $previousSymbolUpper) {
                    $pendingAbbreviation .= $previousSymbolUpper;
                }
                $previousSymbolUpper = $symbol;
            } else {
                if (null !== $previousSymbolUpper) {
                    if ($scriptName) {
                        $scriptName .= static::NAME_PART_SEPARATOR;
                    }
                    if ($pendingAbbreviation) {
                        $scriptName .= $pendingAbbreviation . static::NAME_PART_SEPARATOR;
                    }
                    $scriptName .= $previousSymbolUpper;
                }
                $previousSymbolUpper = null;
                $pendingAbbreviation = '';

                $scriptName .= $symbol;
            }
        }
        if (null !== $previousSymbolUpper) {
            if ($scriptName) {
                $scriptName .= static::NAME_PART_SEPARATOR;
            }
            $scriptName .= $pendingAbbreviation . $previousSymbolUpper;
        }

        return $scriptName;
    }

    /**
     * @return string[]
     */
    public static function getNameSections(): array {
        return [];
    }

    /**
     * Returns full script name including {@see static::getNameSections()}.
     */
    public static function getScriptName(): string {
        $errorFormatter     = HelpFormatter::createForStdErr();
        $classNameFormatted = $errorFormatter->helpNote(static::class);
        $errorMessagePrefix = "Script '{$classNameFormatted}' >>> Config error:";

        $localName = trim(static::getScriptInnerName());
        if ('' === $localName) {
            throw new ConfigException("{$errorMessagePrefix} local name can not be empty.");
        }

        $fullName       = '';
        $nameSections   = static::getNameSections();
        $nameSections[] = $localName;
        foreach ($nameSections as $section) {
            $sectionFiltered = trim($section);
            if ('' === $sectionFiltered) {
                continue;
            }

            if ($fullName) {
                $fullName .= static::NAME_SECTION_SEPARATOR;
            }

            $fullName .= $sectionFiltered;
        }

        return $fullName;
    }

    protected static function setUpConfig(ConfigBuilder $configBuilder): void { }

    /**
     * @param bool $throwOnException {@see Parametizer::newConfig()}
     */
    public static function getConfigBuilder(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): ConfigBuilder {
        /*
         * Here we want to detect environment config files starting from the launched script class location.
         *
         * debug_backtrace() does not contain script classes mentioning until this method is redefined explicitly.
         * That's why we here explicitly specify the bottommost directory path.
         */
        if (null === $envConfig && is_subclass_of(static::class, ScriptClassAbstract::class)) {
            $staticClassReflection = new ReflectionClass(static::class);
            $staticClassFilePath   = $staticClassReflection->getFileName();
            if (false !== $staticClassFilePath && !$staticClassReflection->isAbstract()) {
                $envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy(
                    bottommostDirectoryPath: dirname($staticClassFilePath),
                    throwOnException: $throwOnException,
                );
            }
        }

        $configBuilder = Parametizer::newConfig($envConfig, $throwOnException);
        static::setUpConfig($configBuilder);

        return $configBuilder;
    }


    public function __construct(protected readonly CliRequest $request) { }

    abstract public function execute(): void;
}
