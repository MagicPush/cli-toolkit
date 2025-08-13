<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand;

use LogicException;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\Subcommand\ClearCache\ClearCache;
use Override;

class ListSubcommands extends BuiltinSubcommandAbstract {
    protected const string HEADER_DEFAULT = '--';


    protected readonly HelpFormatter     $formatter;
    protected readonly EnvironmentConfig $environmentConfig;
    protected readonly Config            $parentConfig;

    protected readonly bool   $isSlim;
    protected readonly string $subcommandNamePart;

    protected readonly int $paddingLeftMain;
    protected readonly int $paddingLeftCommand;
    protected readonly int $paddingLeftCommandDescription;

    #[Override]
    public static function getScriptInnerName(): string {
        return 'list';
    }

    #[Override]
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
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

        $this->paddingLeftMain               = $this->isSlim ? 0 : max(0, $this->environmentConfig->listPaddingLeftMain);
        $this->paddingLeftCommand            = max(0, $this->environmentConfig->listPaddingLeftCommand);
        $this->paddingLeftCommandDescription = max(0, $this->environmentConfig->listPaddingLeftCommandDescription);
    }

    public function execute(): void {
        $builtInSubcommands            = $this->parentConfig->getBuiltInSubcommands();
        $subcommandNameGroupsByHeaders = [
            'Built-in:'        => array_keys($builtInSubcommands),
            'Script launcher:' => [ClearCache::getScriptName()],
        ];

        /** @var array<string, string> $headersBySubcommandsLookup */
        $headersBySubcommandsLookup = [];
        foreach ($subcommandNameGroupsByHeaders as $header => $subcommandNames) {
            $headersBySubcommandsLookup = array_merge(
                $headersBySubcommandsLookup,
                array_fill_keys($subcommandNames, $header),
            );
        }

        // Init headered groups to ensure that those groups are listed in a particular order.
        // The rest will be sorted.
        /** @var array<string, Config|array> $subcommandGroupsByHeaders Headered groups */
        $subcommandGroupsByHeaders = array_fill_keys(array_keys($subcommandNameGroupsByHeaders), []);

        /**
         * @var array<string, Config|array> $subcommandGroupsAuto Groups without predefined positioned headers.
         *                                                        The headers for these groups are detected
         *                                                        automatically based on subcommand name sections.
         */
        $subcommandGroupsAuto = [];

        $subcommandNameColumnWidthMax = 0;
        foreach ($this->parentConfig->getBranches() as $subcommandName => $subcommandConfig) {
            $isBuiltInSubcommand = array_key_exists($subcommandName, $builtInSubcommands);

            if (
                !$isBuiltInSubcommand
                && '' !== $this->subcommandNamePart
                && !str_contains($subcommandName, $this->subcommandNamePart)
            ) {
                continue;
            }

            $subcommandGlobalHeader = $headersBySubcommandsLookup[$subcommandName] ?? null;
            $hasHeader              = null !== $subcommandGlobalHeader;

            if ($this->isSlim) {
                $nodeLevel = 0;
            } elseif ($hasHeader) {
                $nodeLevel = 1;
            } else {
                $nodeLevel = mb_substr_count($subcommandName, static::NAME_SECTION_SEPARATOR);
            }
            $subcommandNameColumnWidth = mb_strlen($subcommandName)
                + $this->paddingLeftCommand * max($this->isSlim ? 0 : 1, $nodeLevel);
            if ($subcommandNameColumnWidthMax < $subcommandNameColumnWidth) {
                $subcommandNameColumnWidthMax = $subcommandNameColumnWidth;
            }

            // Slim and headered lists are treated in a much simpler way.
            if ($hasHeader || $this->isSlim) {
                if ($hasHeader) {
                    $subcommandGroupsByHeaders[$subcommandGlobalHeader][$subcommandName] = $subcommandConfig;
                } elseif ($this->isSlim) {
                    $subcommandGroupsAuto[$subcommandName] = $subcommandConfig;
                }

                continue;
            }

            $nameParts         = explode(static::NAME_SECTION_SEPARATOR, $subcommandName);
            $namePartIndexLast = array_key_last($nameParts);
            $nameAccumulated   = '';
            $elementLink       = &$subcommandGroupsAuto;
            foreach ($nameParts as $namePartIndex => $namePart) {
                $nameAccumulated .= $namePart;
                if ($namePartIndex === $namePartIndexLast) {
                    // Here level 0 + last name part == a subcommand config without name sections.
                    if (0 === $nodeLevel) {
                        $elementLink[static::HEADER_DEFAULT][$subcommandName] = $subcommandConfig;
                    } else {
                        $elementLink[$nameAccumulated] = $subcommandConfig;
                    }

                    break;
                }

                $nameAccumulated .= static::NAME_SECTION_SEPARATOR;
                $elementLink     = &$elementLink[$nameAccumulated];
            }
            unset($elementLink);
        }

        if ($this->isSlim) {
            foreach ($subcommandGroupsByHeaders as $subcommandGroup) {
                if (!$subcommandGroup) {
                    continue;
                }

                $this->outputNode($subcommandGroup, $subcommandNameColumnWidthMax);
            }
            $this->outputNode($subcommandGroupsAuto, $subcommandNameColumnWidthMax);

            return;
        }

        foreach ($subcommandGroupsByHeaders as $header => $subcommandGroup) {
            if (!$subcommandGroup) {
                continue;
            }

            $this->outputNode(
                [$header => $subcommandGroup], // Pass groups with headers one by one
                                               // to prevent global (positioned) headers sorting.
                $subcommandNameColumnWidthMax,
            );
            echo PHP_EOL;
        }
        $this->outputNode($subcommandGroupsAuto, $subcommandNameColumnWidthMax);
    }

    /**
     * @param array<string, Config|array> $nodeData (string) node header => (Config) script config OR (array) subnode
     */
    protected function outputNode(
        array $nodeData,
        int $subcommandNameColumnWidthMax,
        int $nodeLevel = 0,
    ): void {
        if (!$nodeData) {
            return;
        }

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

            return strnatcmp($key1, $key2);
        });

        $firstElementKey = array_key_first($nodeData);
        foreach ($nodeData as $elementName => $elementValue) {
            if ($elementName !== $firstElementKey && is_array($elementValue)) {
                echo PHP_EOL;
            }

            $subcommandNameOutput = str_repeat(' ', $this->paddingLeftMain + $this->paddingLeftCommand * $nodeLevel)
                . $elementName;
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
                echo $this->formatter->helpNote($subcommandNameOutput);
            }

            if ($elementValue instanceof Config) {
                $shortDescription = HelpGenerator::getScriptShortDescription($elementValue, $this->environmentConfig);

                if ('' !== $shortDescription) {
                    echo str_repeat(
                            ' ',
                            $this->paddingLeftMain + $this->paddingLeftCommandDescription
                                + $subcommandNameColumnWidthMax - mb_strlen($subcommandNameOutput),
                        )
                        . $shortDescription;
                }
            }
            echo PHP_EOL;

            if (is_array($elementValue)) {
                $this->outputNode(
                    $elementValue,
                    $subcommandNameColumnWidthMax,
                    $nodeLevel + 1,
                );
            }
        }
    }
}
