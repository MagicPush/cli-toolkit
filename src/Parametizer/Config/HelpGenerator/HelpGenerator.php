<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Exception\ParseErrorException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class HelpGenerator {
    protected const int PAD_LEFT_MAIN              = 2;
    protected const int PAD_LEFT_PARAM_DESCRIPTION = 3;

    protected const int USAGE_MAX_OPTIONS = 5;

    protected readonly HelpFormatter $formatter;


    public function __construct(protected readonly Config $config) {
        $this->formatter = HelpFormatter::createForStdOut();
    }

    public function getFullHelp(): string {
        return $this->getDescriptionBlock()
            . $this->getUsagesBlock()
            . static::getParamsBlock($this->formatter, $this->config->getOptions(), 'OPTIONS')
            . static::getParamsBlock($this->formatter, $this->config->getArguments(), 'ARGUMENTS')
            . PHP_EOL;
    }

    /**
     * @param bool $isSubcommandSwitchNameOmitted Useful when printing a subcommand template - subcommand switch name
     *                                            is replaced with an actual value (subcommand config "script name").
     */
    protected function getUsageTemplate(Config $config, bool $isSubcommandSwitchNameOmitted = false): string {
        $usageTemplate = '';

        $parentConfig = $config->getParent();
        if ($parentConfig) {
            $usageTemplate .= $this->getUsageTemplate($parentConfig, true)
                . ' ' . $this->formatter->paramValue($config->getScriptName());
        } else {
            $usageTemplate .= $config->getScriptName();
        }

        $optionTemplateStrings         = [];
        $requiredOptionTemplateStrings = [];
        $flagShortNames                = '';

        foreach ($config->getOptions() as $option) {
            if (!$option->isVisibleIn(Config::VISIBLE_USAGE_TEMPLATE)) {
                continue;
            }

            $optionNameTemplates = static::getOptionTemplates($option);
            if ($option->isRequired()) {
                $requiredOptionTemplateStrings[] = implode(' | ', $optionNameTemplates);
            } else {
                if (!$option->isValueRequired() && null !== $option->getShortName()) {
                    $flagShortNames .= $option->getShortName();
                    unset($optionNameTemplates[1]);
                }

                $optionTemplateStrings[] = implode(' | ', $optionNameTemplates);
            }
        }

        if ($flagShortNames) {
            $usageTemplate .= " [-{$flagShortNames}]";
        }
        if ($optionTemplateStrings) {
            $usageTemplate .= count($optionTemplateStrings) > static::USAGE_MAX_OPTIONS
                ? ' [options]'
                : ' [' . implode('] [', $optionTemplateStrings) . ']';
        }
        if ($requiredOptionTemplateStrings) {
            $usageTemplate .= ' ' . implode(' ', $requiredOptionTemplateStrings);
        }

        foreach ($config->getArguments() as $argument) {
            if (!$argument->isVisibleIn(Config::VISIBLE_USAGE_TEMPLATE)) {
                continue;
            }

            if ($isSubcommandSwitchNameOmitted && $argument->isSubcommandSwitch()) {
                continue;
            }

            $argumentUsage = $argument->getTitleForHelp();

            $usageTemplate .= ' ';
            $usageTemplate .= $argument->isRequired() ? $argumentUsage : "[{$argumentUsage}]";
        }

        return $usageTemplate;
    }

    public function getDescriptionBlock(): string {
        $description = $this->config->getDescription();
        if ('' === $description) {
            $description = $this->config->getShortDescription();
        }
        if ('' === $description) {
            return '';
        }

        /*
         * We need to trim heading spaces. But we also should retain the leading spaces added intentionally,
         * for instance, for indenting numbered list lines.
         * This is needed when description text is placed inside code as a paragraph, without any strings concatenation
         * and explicit line breaks.
         * It's done in a few steps below:
         */

        // 1. Remove heading and tail "blank" lines.
        $patternBlankLines = sprintf('/^( *%1$s+)*%1$s*/u', PHP_EOL);
        $description       = preg_replace([$patternBlankLines, '/\s*$/u'], '', $description);
        // 2. Replace tabs with spaces (for exact spaces counting - we'll need it later).
        $description = preg_replace('/\t/u', str_repeat(' ', 8), $description);

        // 3. Determine the minimum heading spaces count in all lines of description.
        $descriptionLines      = explode(PHP_EOL, $description);
        $minHeadingSpacesCount = null;
        foreach ($descriptionLines as $lineIndex => $line) {
            // 4. Trim space-only lines.
            // Ignore empty and space-only lines:
            if (!trim($line)) {
                $descriptionLines[$lineIndex] = '';

                continue;
            }

            $headingSpacesCount = mb_strlen($line) - mb_strlen(ltrim($line, ' '));
            if (null === $minHeadingSpacesCount || $minHeadingSpacesCount > $headingSpacesCount) {
                $minHeadingSpacesCount = $headingSpacesCount;
            }
        }

        // 5. Delete that many heading spaces from each line.
        if ($minHeadingSpacesCount) {
            foreach ($descriptionLines as $lineIndex => $line) {
                if (!$line) {
                    continue;
                }

                $descriptionLines[$lineIndex] = mb_substr($line, $minHeadingSpacesCount);
            }
            unset($line);

            $description = implode(PHP_EOL, $descriptionLines);
        }

        return PHP_EOL
            . static::padTextBlock($description, static::PAD_LEFT_MAIN, true)
            . PHP_EOL;
    }

    /**
     * Returns the whole USAGE text block containing usage template and examples.
     */
    public function getUsagesBlock(): string {
        $output = $this->formatter->section('USAGE') . PHP_EOL . PHP_EOL;

        // Print general usage template:
        $output .= $this->getUsageTemplate($this->config);

        $branchConfig    = $this->config;
        $scriptNameParts = [];
        do {
            $scriptNameParts[] = $branchConfig->getScriptName();
        } while ($branchConfig = $branchConfig->getParent());
        $baseScriptName = implode(' ', array_reverse($scriptNameParts));

        $usageExamples = $this->config->getUsageExamples();

        // Firstly print usage examples with no description:
        foreach ($usageExamples as $usageExample) {
            if (!empty($usageExample->description)) {
                continue;
            }
            $output .= PHP_EOL . $this->formatter->command("{$baseScriptName} {$usageExample->example}");
        }

        // Then print the rest usage examples which have descriptions:
        foreach ($usageExamples as $usageExample) {
            if (empty($usageExample->description)) {
                continue;
            }

            $output .= PHP_EOL . PHP_EOL . $this->formatter->italic($usageExample->description) . ':';
            $output .= PHP_EOL . $this->formatter->command("{$baseScriptName} {$usageExample->example}");
        }

        return PHP_EOL
            . static::padTextBlock($output, static::PAD_LEFT_MAIN)
            . PHP_EOL;
    }

    /**
     * @param ParameterAbstract[] $params
     */
    public static function getParamsBlock(HelpFormatter $formatter, array $params, string $sectionTitle = ''): string {
        $arguments = [];
        $options   = [];
        foreach ($params as $param) {
            if (!$param->isVisibleIn(Config::VISIBLE_HELP)) {
                continue;
            }

            if ($param instanceof Option) {
                $options[] = $param;
            } else {
                $arguments[] = $param;
            }
        }

        if (count($options) > 1) {
            usort(
                $options,
                function (Option $a, Option $b) {
                    // Place 'help' option at the top:
                    $comparisonIsHelp =
                        (Config::PARAMETER_NAME_HELP === $b->getName())
                        <=>
                        (Config::PARAMETER_NAME_HELP === $a->getName());
                    if (0 !== $comparisonIsHelp) {
                        return $comparisonIsHelp;
                    }

                    // Required options are more important:
                    $comparisonIsRequired = $b->isRequired() <=> $a->isRequired();
                    if (0 !== $comparisonIsRequired) {
                        return $comparisonIsRequired;
                    }

                    // Otherwise sort alphabetically:
                    return $a->getName() <=> $b->getName();
                },
            );
        }

        /** @var Option[]|Argument[] $paramsSorted */
        $paramsSorted         = array_merge($options, $arguments);
        $parameterDefinitions = [];
        foreach ($paramsSorted as $param) {
            $paramNames = $param instanceof Option
                ? static::getOptionTemplates($param)
                : [$param->getTitleForHelp()];

            $parameterDefinitions[] = new HelpParameterDefinition(
                name: $formatter->paramTitle($paramNames[0]),
                shortName: isset($paramNames[1]) ? $formatter->paramTitle($paramNames[1]) : '',
                description: static::makeParamDescription($formatter, $param),
                required: $param->isRequired() ? $formatter->paramRequired('(required)') : '',
            );
        }

        return static::makeDefinitionList($formatter, $sectionTitle, $parameterDefinitions);
    }

    /**
     * Returns script's help part based on exception's relation + 'help' option for full help hint.
     */
    public static function getUsageForParseErrorException(
        ParseErrorException $exception,
        Config $config,
    ): string {
        $invalidParams = [];
        $helpOption    = $config->getOptions()[Config::PARAMETER_NAME_HELP] ?? null;
        if (null !== $helpOption) {
            $invalidParams[] = $helpOption;
        }
        $invalidParams = [...$invalidParams, ...$exception->getInvalidParams()];

        return static::getParamsBlock(HelpFormatter::createForStdErr(), $invalidParams);
    }

    protected static function makeParamDescription(HelpFormatter $formatter, ParameterAbstract $param): string {
        $description = $param->getDescription();
        if ($description) {
            $description = static::unindent($description);
        }

        $allowedValuesHeaderFormatted = $formatter->helpNote('Allowed values:');

        if ($param->isSubcommandSwitch()) {
            $description .= ('' !== $description) ? PHP_EOL : '';

            $subcommandListFormatted = $formatter->paramValue(Config::PARAMETER_NAME_LIST);

            $description .= $allowedValuesHeaderFormatted
                . ' ' . $formatter->paramRequired((string) count($param->getAllowedValues())) . ' subcommands available'
                . " (see '{$subcommandListFormatted}' subcommand output)";

            $subcommandDescriptionHeader    = 'Subcommand help:';
            $subcommandDescriptionHeaderAlt =
                mb_str_pad('... or:', mb_strlen($subcommandDescriptionHeader), ' ', STR_PAD_LEFT);

            $description .= PHP_EOL . $formatter->helpNote($subcommandDescriptionHeader)
                . " <{$param->getName()}> " . $formatter->paramValue('--' . Config::PARAMETER_NAME_HELP);
            $description .= PHP_EOL . $formatter->helpNote($subcommandDescriptionHeaderAlt)
                . ' ' . $formatter->paramValue(Config::PARAMETER_NAME_HELP) . " <{$param->getName()}>";
        } elseif (!$param->areAllowedValuesHiddenFromHelp()) {
            // Print allowed values list.
            // Print in long format if there is a description for at least one value. Otherwise, print values in one line.
            $stringValues = [];
            $isLongFormat = false;
            foreach ($param->getAllowedValues() as $value => $valueDescription) {
                $stringValue = static::convertValueToString($value);
                if (null !== $stringValue) {
                    $stringValues[$stringValue] = $valueDescription;
                    if ($valueDescription) {
                        $isLongFormat = true;
                    }
                }
            }
            if ($stringValues) {
                $description .= ('' !== $description) ? PHP_EOL : '';
                $description .= $allowedValuesHeaderFormatted;

                if ($isLongFormat) {
                    $maxLength   = max(array_map('mb_strlen', array_keys($stringValues)));
                    foreach ($stringValues as $value => $valueDescription) {
                        $description .= PHP_EOL . ' - '
                            . $formatter->paramValue(
                                $valueDescription ? mb_str_pad((string) $value, $maxLength + 1) : $value
                            );
                        if ($valueDescription) {
                            $description .= $valueDescription;
                        }
                    }
                } else {
                    $description .= ' '
                        . implode(', ', static::getPossibleValuesFormatted($formatter, array_keys($stringValues)));
                }
            }
        }

        if ($param->isArray()) {
            $description .= ('' !== $description) ? PHP_EOL : '';
            $description .= $formatter->helpImportant("(multiple values allowed)");
        }

        $flagValue = ($param instanceof Option) ? $param->getFlagValue() : null;

        // Print parameter's default value in readable form:
        $default = $param->getDefault();
        if (!$flagValue && null !== $default && [] !== $default && '' !== $default) {
            $defaultValue = static::convertValueToString($default);
            if (null !== $defaultValue) {
                $defaultValue = $formatter->paramValue($defaultValue);
                $description  .= ('' !== $description) ? PHP_EOL : '';
                $description  .= $formatter->helpNote("Default: {$defaultValue}");
            }
        }

        return $description;
    }

    public static function convertValueToString(mixed $value): ?string {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return json_encode($value);
        }

        if (is_array($value)) {
            $stringValues = [];
            foreach ($value as $v) {
                $stringValue = static::convertValueToString($v);
                if (null !== $stringValue) {
                    $stringValues[] = $stringValue;
                }
            }
            if ($stringValues) {
                return '[' . implode(', ', $stringValues) . ']';
            }
        }

        return null;
    }

    /**
     * @param HelpParameterDefinition[] $parameterDefinitions
     */
    protected static function makeDefinitionList(
        HelpFormatter $formatter,
        string $sectionTitle,
        array $parameterDefinitions,
    ): string {
        if (empty($parameterDefinitions)) {
            return '';
        }

        // Firstly, determine max padding in a specific section:
        $nameMaxLength      = 0;
        $shortNameMaxLength = 0;
        $definitionsTable   = [];
        foreach ($parameterDefinitions as $paramIndex => $definition) {
            $definitionsTable[$paramIndex][0] = [];

            $shortName = $definition->shortName;
            if ('' !== $definition->shortName) {
                $shortName       .= ', ';
                $shortNameLength = $formatter::mbStrlenNoFormat($shortName);
                if ($shortNameMaxLength < $shortNameLength) {
                    $shortNameMaxLength = $shortNameLength;
                }
                // |X|.|.|
                // |.|.|.|
                $definitionsTable[$paramIndex][0][0] = [
                    'value'  => $shortName,
                    'length' => $shortNameLength,
                ];
            }

            $nameLength = $formatter::mbStrlenNoFormat($definition->name);
            if ($nameMaxLength < $nameLength) {
                $nameMaxLength = $nameLength;
            }
            // 0: |.|X|.|
            // 1: |.|.|.|
            $definitionsTable[$paramIndex][0][1] = [
                'value'  => $definition->name,
                'length' => $nameLength,
            ];

            if ('' !== $definition->required) {
                $nameLength = $formatter::mbStrlenNoFormat($definition->required);
                if ($nameMaxLength < $nameLength) {
                    $nameMaxLength = $nameLength;
                }
                // 0: |.|.|.|
                // 1: |.|X|.|
                $definitionsTable[$paramIndex][1][1] = [
                    'value'  => $definition->required,
                    'length' => $nameLength,
                ];
            }

            if ('' !== $definition->description) {
                foreach (explode(PHP_EOL, $definition->description) as $lineIndex => $descriptionLine) {
                    // $lineIndex: |.|.|X|
                    $definitionsTable[$paramIndex][$lineIndex][2] = ['value' => $descriptionLine];
                }
            }
        }

        // ... And now we can print the section itself properly padded.
        $text = PHP_EOL;
        if ('' !== $sectionTitle) {
            $text .= $formatter->section($sectionTitle) . PHP_EOL;
        }

        foreach ($definitionsTable as $paramTable) {
            // Extra empty line between parameter definitions:
            $text .= PHP_EOL;

            foreach ($paramTable as $row) {
                /**
                 * Do not use {@see str_pad()} / {@see mb_str_pad()} in this block:
                 * font escape sequences are visually invisible in text, but affect text length.
                 */

                // 1. Main padding:
                $text .= str_repeat(' ', static::PAD_LEFT_MAIN);
                // 2. Short name (|X|.|.|), left padding:
                if ($shortNameMaxLength > 0) {
                    $text .= ($row[0]['value'] ?? '')
                        . str_repeat(' ', $shortNameMaxLength - ($row[0]['length'] ?? 0));
                }
                // 3. Long name (|.|X|.|):
                if (isset($row[1])) {
                    $text .= $row[1]['value'];
                }
                // 4. Description (|.|.|X), left padding:
                if (isset($row[2])) {
                    $text .= str_repeat(
                            ' ',
                            0                                           // |X|.|.|, 1st column is already padded.
                            + $nameMaxLength - ($row[1]['length'] ?? 0) // |.|X|.|
                            + static::PAD_LEFT_PARAM_DESCRIPTION,       // |.|.|X|
                        )
                        . $row[2]['value'];
                }

                // End of a row:
                $text .= PHP_EOL;
            }
        }

        return $text;
    }

    /**
     * Returns formatted text block.
     *
     * Specifically:
     * * adds head padding (spaces) for each line of `$text` except the first line
     * (`$text` is split into "lines" by {@see PHP_EOL});
     *     * if `$padFirstLine` is true, then the padding is added to the first line too;
     * * trims trailing spaces, tabs and new line markers.
     */
    protected static function padTextBlock(string $text, int $paddingLeft = 0, bool $padFirstLine = false): string {
        $out     = '';
        $padding = str_repeat(' ', $paddingLeft);

        foreach (explode(PHP_EOL, $text) as $i => $line) {
            // Pad a line if it is not blank and not first (or the first line padding is enabled).
            if (trim($line) && ($i || $padFirstLine)) {
                $out .= $padding;
            }
            $out .= $line . PHP_EOL;
        }

        return rtrim($out, PHP_EOL . " \t");
    }

    /**
     * Returns a list of option names formatted for a help page (usage and options blocks).
     *
     * Always contains '0' element - full (long) name.
     * Contains '1' element if an option has a short (one-letter) name alias.
     *
     * @return string[]
     */
    protected static function getOptionTemplates(Option $option): array {
        $name = "--{$option->getName()}";
        if ($option->isValueRequired()) {
            $name .= '=…';
        }

        $result = [$name];

        if (null !== $option->getShortName()) {
            $shortName = "-{$option->getShortName()}";
            if ($option->isValueRequired()) {
                $shortName .= ' …';
            }

            $result[] = $shortName;
        }

        return $result;
    }

    /**
     * Strips extra indentation from a string.
     *
     * The method finds a common indent in your string and strips it away,
     * so you can write descriptions for config params without having to
     * break indentation. It also trims the string.
     */
    protected static function unindent(string $text): string {
        $indent       = '';
        $indentLength = 0;
        $lines        = explode(PHP_EOL, $text);
        foreach ($lines as $i => $line) {
            if ('' == $line) {
                continue;
            }

            // We grab the first non-empty string prefix, except for the first line.
            if ('' === $indent && '' !== trim($line) && preg_match('/^[\t ]+/', $line, $matches)) {
                // If the first line does not have an indent, we ignore it and look for the next line.
                if (!mb_strlen($matches[0]) && 0 === $i) {
                    continue;
                }

                $indent       = $matches[0];
                $indentLength = mb_strlen($indent);
            }

            if (mb_substr($line, 0, $indentLength) == $indent) {
                $lines[$i] = mb_substr($line, $indentLength);
            }
        }

        return trim(implode(PHP_EOL, $lines));
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    protected static function getPossibleValuesFormatted(HelpFormatter $formatter, array $values): array {
        return array_map(
            function ($value) use ($formatter) {
                return $formatter->paramValue((string) $value);
            },
            $values,
        );
    }

    /**
     * Until there is a {@see wordwrap()} UTF8-compatible analogue, we cut a string gracefully the manual way.
     *
     * 1. The method cuts an input string by `$charsMax`.
     * 2. Then tries to detect the last complete sentence (ends with a full stop symbol '.') in a substring.
     * 3. If there is no complete sentence, or a substring with a sentence (or several in a row) is too short
     * (shorter than `$charsMinBeforeFullStop`), the rest of a cut substring is added too.
     * 4. Next the method cuts a substring by the last space character, so there is no trailing part of a word.
     */
    public static function getShortDescription(
        string $description,
        int $charsMinBeforeFullStop,
        int $charsMax,
    ): string {
        [$firstLine, ] = explode(PHP_EOL, static::unindent($description), 2);
        if (mb_strlen($firstLine) <= $charsMax) {
            return $firstLine;
        }

        // At first, let's add +1 to MAX in case the +1 character is a space.
        // This way we will not cut a whole word from the end of a substring.
        $firstLineShort = mb_substr($firstLine, 0, $charsMax + 1);

        $lastSentencePosition = mb_strrpos($firstLineShort, '. ');
        if ($lastSentencePosition) {
            // Compare the length of a sentence with a possible trailing space:
            // do not count a trailing space as a beginning of the next sentence.
            $lastSentence = mb_substr($firstLineShort, 0, $lastSentencePosition) . '. ';
            if (mb_strlen($lastSentence) >= $charsMinBeforeFullStop) {
                return rtrim($lastSentence, ' '); // Now let's remove a possible trailing space.
            }
        }

        $lastSpacePosition = mb_strrpos($firstLineShort, ' ');
        if (false === $lastSpacePosition) {
            // Previously we cut a substring with +1. Now we just want a substring of a maximum allowed length.
            return mb_substr($firstLine, 0, $charsMax);
        }

        return mb_substr($firstLineShort, 0, $lastSpacePosition);
    }

    /**
     * Returns {@see Config::getShortDescription()} as is (if present), otherwise returns a shortened version
     * of {@see Config::getDescription()} (see {@see static::getShortDescription()} for details).
     *
     * {@see EnvironmentConfig} object is not read from `$config`, but specified explicitly by design - different
     * environment configs may be used for different cases.
     * Examples:
     *  * You should use a common parent env config while outputting short descriptions for subcommands.
     *  * You may want to output somewhere descriptions of particular scripts (not necessarily subcommands)
     *    based on some other custom env config or the scripts' own configs.
     */
    public static function getScriptShortDescription(Config $config, EnvironmentConfig $envConfig): string {
        $shortDescription = trim($config->getShortDescription());
        if ('' !== $shortDescription) {
            return $shortDescription;
        }

        $description = $config->getDescription();
        if ('' === $description) {
            return $description;
        }

        return static::getShortDescription(
            $description,
            $envConfig->helpGeneratorShortDescriptionCharsMinBeforeFullStop,
            $envConfig->helpGeneratorShortDescriptionCharsMax,
        );
    }
}
