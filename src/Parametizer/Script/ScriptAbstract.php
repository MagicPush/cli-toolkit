<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;

abstract class ScriptAbstract {
    /** @see Config::newSubcommand() - allowed characters validation */
    protected const string NAME_SECTION_SEPARATOR = ':';
    /** @see Config::newSubcommand() - allowed characters validation */
    protected const string NAME_PART_SEPARATOR = '-';


    public static function getLocalName(): string {
        $classNameParts = explode('\\', static::class);
        $classShortName = $classNameParts[array_key_last($classNameParts)];

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

    public static function getFullName(): string {
        $errorFormatter = HelpFormatter::createForStdErr();
        $classNameFormatted = $errorFormatter->helpNote(static::class);
        $errorMessagePrefix = "Script '{$classNameFormatted}' >>> Config error:";

        $localName = trim(static::getLocalName());
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

    protected static function newConfig(?EnvironmentConfig $envConfig = null): ConfigBuilder {
        return Parametizer::newConfig($envConfig);
    }


    public function __construct(protected CliRequest $request) { }


    abstract public static function getConfiguration(): BuilderInterface;

    abstract public function execute(): void;
}
