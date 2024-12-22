<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\Exception\ParseErrorException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class HelpGenerator {
    protected const PAD_LEFT_MAIN              = 2;
    protected const PAD_LEFT_PARAM_DESCRIPTION = 3;

    protected const USAGE_MAX_OPTIONS = 5;

    protected const SHORT_DESCRIPTION_MIN_CHARS = 30;
    protected const SHORT_DESCRIPTION_MAX_CHARS = 70;

    protected readonly HelpFormatter $formatter;


    public function __construct(protected readonly Config $config) {
        $this->formatter = HelpFormatter::createForStdOut();
    }

    public function getFullHelp(): string {
        return $this->getDescriptionBlock()
            . $this->getUsagesBlock()
            . static::getParamsBlock($this->formatter, $this->config->getOptions(), 'OPTIONS')
            . static::getParamsBlock($this->formatter, $this->config->getArguments(), 'ARGUMENTS')
            . $this->getSubcommandsBlock()
            . PHP_EOL;
    }

    /**
     * @param bool $isSubcommandSwitchNameOmitted Useful when printing a subcommand template - subcommand switch name
     *                                            is replaced with an actual value (subcommand config "script name").
     */
    public static function getUsageTemplate(Config $config, bool $isSubcommandSwitchNameOmitted = false): string {
        $usageTemplate = '';

        $parentConfig = $config->getParent();
        if ($parentConfig) {
            $usageTemplate .= static::getUsageTemplate($parentConfig, true) . ' ';
        }

        $usageTemplate .= $parentConfig
            ? HelpFormatter::createForStdOut()->paramValue($config->getScriptName())
            : $config->getScriptName();

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
        if (!$description) {
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
        foreach ($descriptionLines as $line) {
            // Do not count empty lines:
            if (!$line) {
                continue;
            }

            $headingSpacesCount = mb_strlen($line) - mb_strlen(ltrim($line, ' '));
            if (null === $minHeadingSpacesCount || $minHeadingSpacesCount > $headingSpacesCount) {
                $minHeadingSpacesCount = $headingSpacesCount;
            }
        }

        // 4. Delete that many heading spaces from each line.
        if ($minHeadingSpacesCount) {
            foreach ($descriptionLines as &$line) {
                // 5. Trim space-only lines.
                if (!trim($line)) {
                    $line = '';
                }

                if (!$line) {
                    continue;
                }

                $line = mb_substr($line, $minHeadingSpacesCount);
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
        $output .= static::getUsageTemplate($this->config);

        $usageExamples  = $this->config->getUsageExamples();
        $baseScriptName = static::getBaseScriptName($this->config);

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
            // Place 'help' option at the top, required options under 'help' and then the rest of options:
            usort(
                $options,
                function (Option $a, Option $b) {
                    return (Config::OPTION_NAME_HELP === $b->getName()) <=> (Config::OPTION_NAME_HELP === $a->getName())
                        ?: $b->isRequired() <=> $a->isRequired();
                },
            );
        }

        /** @var Option[]|Argument[] $paramsSorted */
        $paramsSorted = array_merge($options, $arguments);

        $lines = [];
        foreach ($paramsSorted as $param) {
            if ($param instanceof Option) {
                $paramTitle = implode(', ', array_reverse(static::getOptionTemplates($param)));
            } else {
                $paramTitle = $param->getTitleForHelp();
            }
            $paramTitle = $formatter->paramTitle($paramTitle);

            if ($param->isRequired()) {
                $paramTitle .= PHP_EOL . $formatter->paramRequired('(required)');
            }

            $description = static::makeParamDescription($formatter, $param);

            $lines[] = [$paramTitle, $description];
        }

        return static::makeDefinitionList($formatter, $lines, $sectionTitle);
    }

    /**
     * Returns script's help part based on exception's relation.
     */
    public static function getUsageForParseErrorException(
        ParseErrorException $exception,
        bool $isForStdErr = false,
    ): string {
        $invalidParams = [Config::createHelpOption(), ...$exception->getInvalidParams()];

        $formatter = $isForStdErr ? HelpFormatter::createForStdErr() : HelpFormatter::createForStdOut();

        // Print a script's help block only for invalid params + 'help' option for full help hint:
        return static::getParamsBlock($formatter, $invalidParams);
    }

    protected function getSubcommandsBlock(): string {
        $lines = [];
        foreach ($this->config->getBranches() as $config) {
            $title   = static::getShortDescription($config->getDescription());
            $lines[] = [HelpGenerator::getUsageTemplate($config), $title];
        }

        return static::makeDefinitionList($this->formatter, $lines, 'COMMANDS');
    }

    protected static function makeParamDescription(HelpFormatter $formatter, ParameterAbstract $param): string {
        $description = $param->getDescription();
        if ($description) {
            $description = static::unindent($description);
        }

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
            $description .= ($description !== '') ? PHP_EOL : '';
            if ($isLongFormat) {
                $maxLength   = max(array_map('mb_strlen', array_keys($stringValues)));
                $description .= $formatter->helpNote('Allowed values:');
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
                $description .= $formatter->helpNote('Allowed values: ')
                    . implode(', ', static::getPossibleValuesFormatted($formatter, array_keys($stringValues)));
            }
        }

        if ($param->isSubcommandSwitch()) {
            $description .= PHP_EOL . $formatter->helpNote('Subcommand help: ')
                . '<script_name> ... <subcommand value> --help';
        }

        if ($param->isArray()) {
            $description .= ($description !== '') ? PHP_EOL : '';
            $description .= $formatter->helpImportant("(multiple values allowed)");
        }

        $flagValue = ($param instanceof Option) ? $param->getFlagValue() : null;

        // Print parameter's default value in readable form:
        $default = $param->getDefault();
        if (!$flagValue && null !== $default && [] !== $default && '' !== $default) {
            $defaultValue = static::convertValueToString($default);
            if (null !== $defaultValue) {
                $defaultValue = $formatter->paramValue($defaultValue);
                $description  .= ($description !== '') ? PHP_EOL : '';
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
     * @param array[] $titleAndDescriptionLines
     */
    protected static function makeDefinitionList(
        HelpFormatter $formatter,
        array $titleAndDescriptionLines,
        string $title,
    ): string {
        if (empty($titleAndDescriptionLines)) {
            return '';
        }

        $text = PHP_EOL;
        if ($title !== '') {
            $text .= $formatter->section($title) . PHP_EOL;
        }

        // Firstly determine max padding in a specific section:
        $paramTitleMaxLength          = 0;
        $titleLinesByParamLines       = [];
        $descriptionLinesByParamLines = [];
        foreach ($titleAndDescriptionLines as $i => $row) {
            $paramTitle = static::padTextBlock($row[0], static::PAD_LEFT_MAIN, true);
            foreach (explode(PHP_EOL, $paramTitle) as $titleLine) {
                $titleLinesByParamLines[$i][] = $titleLine;
                $titleLength                  = $formatter::mbStrlenNoFormat($titleLine);

                if ($titleLength > $paramTitleMaxLength) {
                    $paramTitleMaxLength = $titleLength;
                }
            }

            if ($row[1]) {
                $descriptionLinesByParamLines[$i] = explode(PHP_EOL, $row[1], count($titleLinesByParamLines[$i]));
            }
        }

        // ... And now we can print the section itself properly padded.
        $textLinesByParamLines = [];
        foreach ($titleLinesByParamLines as $i => $titleLines) {
            foreach ($titleLines as $j => $titleLine) {
                if (empty($descriptionLinesByParamLines[$i][$j])) {
                    $descriptionLine = '';
                } else {
                    /**
                     * Do not use {@see str_pad()} / {@see mb_str_pad()} here:
                     * font escape sequences are visually invisible in text but affect text length.
                     */
                    $descriptionLine = str_repeat(
                        ' ',
                        $paramTitleMaxLength + static::PAD_LEFT_PARAM_DESCRIPTION - $formatter::mbStrlenNoFormat(
                            $titleLine,
                        ),
                    );
                    $descriptionLine .= static::padTextBlock(
                        $descriptionLinesByParamLines[$i][$j],
                        $paramTitleMaxLength + static::PAD_LEFT_PARAM_DESCRIPTION,
                    );
                }

                $textLinesByParamLines[$i][] = $titleLine . $descriptionLine;
            }

            $text .= PHP_EOL . implode(PHP_EOL, $textLinesByParamLines[$i]) . PHP_EOL;
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
     * Returns main script name.
     *
     * Useful when provided config is related to a subcommand.
     */
    protected static function getBaseScriptName(Config $config): string {
        $mainConfig = $config;
        while ($parentConfig = $mainConfig->getParent()) {
            $mainConfig = $parentConfig;
        }

        return $mainConfig->getScriptName();
    }

    /**
     * Until there is a {@see wordwrap()} UTF8-compatible analogue, we cut a string gracefully the manual way.
     */
    protected static function getShortDescription(string $description): string {
        [$firstLine, ] = explode(PHP_EOL, static::unindent($description), 2);
        if (mb_strlen($firstLine) <= static::SHORT_DESCRIPTION_MAX_CHARS) {
            return $firstLine;
        }
        $firstLineShort = mb_substr($firstLine, 0, static::SHORT_DESCRIPTION_MAX_CHARS);

        $lastSentencePosition = mb_strrpos($firstLineShort, '. ');
        if ($lastSentencePosition) {
            $lastSentence = mb_substr($firstLineShort, 0, $lastSentencePosition);
            if (mb_strlen($lastSentence) >= static::SHORT_DESCRIPTION_MIN_CHARS) {
                return $lastSentence . '.';
            }
        }

        $lastSpacePosition = mb_strrpos($firstLineShort, ' ');
        if (false === $lastSpacePosition) {
            return $firstLineShort;
        }

        return mb_substr($firstLineShort, 0, $lastSpacePosition);
    }
}
