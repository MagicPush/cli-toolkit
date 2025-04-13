<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\BuiltInSubcommand;

use LogicException;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

class ListScript extends ScriptAbstract {
    protected const string PADDING_BLOCK = '    ';

    protected const string HEADER_BUILT_IN = 'Built-in subcommands:';
    protected const string HEADER_MAIN     = '--';


    protected readonly HelpFormatter     $formatter;
    protected readonly EnvironmentConfig $environmentConfig;
    protected readonly Config            $parentConfig;

    protected readonly bool   $isSlim;
    protected readonly string $subcommandNamePart;


    public static function getConfiguration(): BuilderInterface {
        return static::newConfig()
            ->shortDescription('Shows available subcommands.')
            ->description('Shows the sorted list of available subcommands with their short descriptions.')

            ->newFlag('--slim', '-s')
            ->description('Outputs a simple sorted list without section headers.')

            ->newArgument('subcommand-name-part')
            ->description('Show subcommands with names containing this substring.')
            ->required(false);
    }

    public function __construct(CliRequest $request) {
        parent::__construct($request);

        $this->formatter         = HelpFormatter::createForStdOut();
        $this->parentConfig      = $request->config->getParent();
        $this->environmentConfig = $this->parentConfig->getEnvConfig();

        $this->isSlim             = $request->getParamAsBool('slim');
        $this->subcommandNamePart = $request->getParamAsString('subcommand-name-part');
    }

    public function execute(): void {
        $builtInSubcommands           = $this->parentConfig->getBuiltInSubcommands();
        $subcommandData               = [];
        $subcommandNameColumnWidthMax = 0;
        $padBlockWidth                = mb_strlen(static::PADDING_BLOCK);
        foreach ($this->parentConfig->getBranches() as $subcommandName => $subcommandConfig) {
            $isBuiltInSubcommand = array_key_exists($subcommandName, $builtInSubcommands);

            // Exclude ...
            if (
                // ... non-built-in subcommands
                !$isBuiltInSubcommand
                // ... if a filter is specified ...
                && '' !== $this->subcommandNamePart
                // ... and a subcommand name contains a substring from the filter.
                && !str_contains($subcommandName, $this->subcommandNamePart)
            ) {
                continue;
            }

            if ($this->isSlim) {
                $nodeLevel = 0;
            } elseif ($isBuiltInSubcommand) {
                $nodeLevel = 1;
            } else {
                $nodeLevel = mb_substr_count($subcommandName, static::NAME_SECTION_SEPARATOR);
            }
            $subcommandNameColumnWidth = $padBlockWidth * max($this->isSlim ? 0 : 1, $nodeLevel)
                + mb_strlen($subcommandName);
            if ($subcommandNameColumnWidthMax < $subcommandNameColumnWidth) {
                $subcommandNameColumnWidthMax = $subcommandNameColumnWidth;
            }

            // Slim list is treated in a much simpler way.
            // Built-in subcommand list is treated separately.
            if ($this->isSlim || $isBuiltInSubcommand) {
                if (!$isBuiltInSubcommand) {
                    $subcommandData[$subcommandName] = $subcommandConfig;
                }

                continue;
            }

            $nameParts       = explode(static::NAME_SECTION_SEPARATOR, $subcommandName);
            $nameNumberLast  = array_key_last($nameParts);
            $nameAccumulated = '';
            $elementLink     = &$subcommandData;
            foreach ($nameParts as $nameNumber => $namePart) {
                $nameAccumulated .= $namePart;
                if ($nameNumber === $nameNumberLast) {
                    if (0 === $nodeLevel) {
                        $elementLink[static::HEADER_MAIN][$subcommandName] = $subcommandConfig;
                    } else {
                        $elementLink[$nameAccumulated] = $subcommandConfig;
                    }
                } else {
                    $nameAccumulated .= static::NAME_SECTION_SEPARATOR;
                    $elementLink     = &$elementLink[$nameAccumulated];
                }
            }
        }

        if ($this->isSlim) {
            $this->outputNode($builtInSubcommands, $subcommandNameColumnWidthMax);
            $this->outputNode($subcommandData, $subcommandNameColumnWidthMax);

            return;
        }

        $this->outputNode([static::HEADER_BUILT_IN => $builtInSubcommands], $subcommandNameColumnWidthMax);
        echo PHP_EOL;
        $this->outputNode($subcommandData, $subcommandNameColumnWidthMax);
    }

    protected function outputNode(array $nodeData, int $subcommandNameColumnWidthMax, int $nodeLevel = 0): void {
        if ($nodeLevel < 0) {
            throw new LogicException(
                sprintf(
                    'Invalid node level "%d" for the node containing "%s"',
                    $nodeLevel,
                    array_key_first($nodeData),
                ),
            );
        }

        uksort($nodeData, function ($key1, $key2): int {
            $isSectionName1 = str_ends_with($key1, static::NAME_SECTION_SEPARATOR);
            $isSectionName2 = str_ends_with($key2, static::NAME_SECTION_SEPARATOR);

            if ($isSectionName1 !== $isSectionName2) {
                if ($isSectionName1) {
                    return 1;
                }
                if ($isSectionName2) {
                    return -1;
                }
            }

            return $key1 <=> $key2;
        });

        $firstElementKey = array_key_first($nodeData);
        foreach ($nodeData as $elementName => $elementValue) {
            $isFirstElement = ($firstElementKey === $elementName);
            if (is_array($elementValue) && !$isFirstElement) {
                echo PHP_EOL;
            }

            $subcommandNameOutput = str_repeat(static::PADDING_BLOCK, $nodeLevel) . $elementName;
            if ($elementValue instanceof Config) {
                $subcommandNameOutputFormatted = $subcommandNameOutput;
                if ('' !== $this->subcommandNamePart) {
                    $subcommandNameOutputFormatted = str_replace(
                        $this->subcommandNamePart,
                        $this->formatter->invert($this->subcommandNamePart),
                        $subcommandNameOutputFormatted,
                    );
                }
                echo $this->formatter->paramValue($subcommandNameOutputFormatted);
            } else {
                if (0 === $nodeLevel) {
                    echo ' ';
                }
                echo $this->formatter->helpNote($subcommandNameOutput);
            }

            if ($elementValue instanceof Config) {
                $shortDescription = HelpGenerator::getScriptShortDescription($elementValue, $this->environmentConfig);

                if ('' !== $shortDescription) {
                    echo mb_str_pad('', $subcommandNameColumnWidthMax - mb_strlen($subcommandNameOutput))
                        . static::PADDING_BLOCK . $shortDescription;
                }
            }
            echo PHP_EOL;

            if (is_array($elementValue)) {
                $this->outputNode($elementValue, $subcommandNameColumnWidthMax, $nodeLevel + 1);
            }
        }
    }
}
