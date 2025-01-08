<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Argument;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\Option;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class Config {
    public const OPTION_NAME_HELP                  = 'help';
    public const OPTION_NAME_AUTOCOMPLETE_GENERATE = 'parametizer-internal-autocomplete-generate';
    public const OPTION_NAME_AUTOCOMPLETE_EXECUTE  = 'parametizer-internal-autocomplete-execute';

    // Visibility bits:

    /** The param is displayed in usage template at the top of the help. */
    public final const VISIBLE_USAGE_TEMPLATE = 1;

    /** The param is displayed in the help with its description. */
    public final const VISIBLE_HELP = 2;

    /** Autocomplete will suggest the parameter value (and names for options). */
    public final const VISIBLE_COMPLETION = 4;

    /** Parameter value is available in {@see CliRequest} inside of the script. */
    public final const VISIBLE_REQUEST = 8;

    // Visibility bitmasks:

    /** Completely hidden (empty bitmask). */
    public final const VISIBILITY_BITMASK_NONE = 0;

    /** Parameter is visible everywhere (full bitmask). */
    public const VISIBILITY_BITMASK_ALL = 15;


    protected EnvironmentConfig $envConfig;

    protected string $description = '';

    /** @var CommandUsageExample[] */
    protected array $usageExamples = [];

    protected string $scriptName = '';

    /** @var ParameterAbstract[] name => object */
    protected array $params = [];

    /** @var Argument[] name => Argument (positional parameters) */
    protected array $arguments = [];

    /** @var Option[] long name => Option */
    protected array $options = [];

    /** @var Option[] short name => Option */
    protected array $optionsByShortNames = [];

    /** @var Option[] '--name' => Option / '-n' => Option */
    protected array $optionsByFormattedNamesAndShortNames = [];

    /** @var string[] */
    protected array $optionsWithValuesShortNames = [];

    protected ?string $subcommandSwitchName       = null;
    protected bool    $isSubcommandSwitchCommited = false;

    /** Not obligatory but useful for subcommand usage template help ({@see HelpGenerator::getUsageTemplate()}) */
    protected ?self $parent = null;

    /** @var Config[] Subcommand value => config object */
    protected array $branches = [];


    public function __construct(?EnvironmentConfig $envConfig = null) {
        if (null === $envConfig) {
            $envConfig = new EnvironmentConfig();
        }
        $this->envConfig = $envConfig;
    }

    public function getEnvConfig(): EnvironmentConfig {
        return $this->envConfig;
    }

    /**
     * Read the description in {@see ConfigBuilder::description()}.
     */
    public function description(string $description): static {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Read the description in {@see ConfigBuilder::usage()}.
     */
    public function usage(string $example, ?string $description = null): static {
        $this->usageExamples[] = new CommandUsageExample($example, $description);

        return $this;
    }

    /**
     * @return CommandUsageExample[]
     */
    public function getUsageExamples(): array {
        return $this->usageExamples;
    }

    /**
     * Sets a script name for help output.
     */
    public function scriptName(string $scriptName): static {
        $this->scriptName = $scriptName;

        return $this;
    }

    public function getScriptName(): string {
        return $this->scriptName;
    }

    /**
     * Makes sure the config does not yet have any type of param with the same name.
     * If there is such duplicate, a detailed exception will be thrown.
     */
    protected function ensureNoDuplicateName(ParameterAbstract $param): void {
        if (!array_key_exists($param->getName(), $this->params)) {
            return;
        }

        $errorFormatter         = HelpFormatter::createForStdErr();
        $paramNameFormatted     = $errorFormatter->paramTitle($param->getName());
        $paramTitleFormatted    = $errorFormatter->paramTitle($param->getTitleForHelp());
        $original               = $this->params[$param->getName()];
        $originalTitleFormatted = $errorFormatter->paramTitle($original->getTitleForHelp());

        $originalErrorPart = $original instanceof Option
            ? "option '{$originalTitleFormatted}' already exists"
            : "argument {$originalTitleFormatted} already exists";

        if ($param instanceof Option) {
            throw new ConfigException(
                "Duplicate option '{$paramNameFormatted}' ({$paramTitleFormatted}) declaration: {$originalErrorPart}.",
            );
        }

        throw new ConfigException("Duplicate argument {$paramTitleFormatted} declaration: {$originalErrorPart}.");
    }

    /**
     * Makes sure the config does not yet have an option with the same short name.
     * If there is such duplicate, a detailed exception will be thrown.
     */
    protected function ensureNoDuplicateShortName(Option $option): void {
        $shortName = $option->getShortName();

        if (!array_key_exists($shortName, $this->optionsByShortNames)) {
            return;
        }

        $original       = $this->optionsByShortNames[$shortName];
        $errorFormatter = HelpFormatter::createForStdErr();

        throw new ConfigException(
            "Duplicate option short name '-" . $errorFormatter->paramTitle($shortName)
            . "' (" . $errorFormatter->paramTitle($option->getTitleForHelp()) . ") declaration:"
            . " already used for the option '" . $errorFormatter->paramTitle($original->getTitleForHelp()) . "'.",
        );
    }

    protected function ensureArrayArgumentIsLastAndOnlyOne(): void {
        $arrayArgumentName = null;
        foreach ($this->getArguments() as $argument) {
            $argumentName = $argument->getName();

            if (null !== $arrayArgumentName) {
                $errorFormatter             = HelpFormatter::createForStdErr();
                $argumentNameFormatted      = $errorFormatter->paramTitle($argumentName);
                $arrayArgumentNameFormatted = $errorFormatter->paramTitle($arrayArgumentName);

                throw new ConfigException(
                    "'{$argumentNameFormatted}' >>> Config error: extra arguments are not allowed after already registered"
                    . " array argument ('{$arrayArgumentNameFormatted}') due to ambiguous parsing."
                    . " Register '{$arrayArgumentNameFormatted}' argument as the last one.",
                );
            }

            if ($argument->isArray()) {
                $arrayArgumentName = $argumentName;
            }
        }
    }

    /**
     * Read the description in {@see BuilderInterface::newSubcommand()}.
     */
    public function newSubcommand(string $subcommandValue, Config $config): static {
        $errorFormatter           = HelpFormatter::createForStdErr();
        $subcommandValueFormatted = $errorFormatter->paramValue($subcommandValue);

        if (null === $this->subcommandSwitchName) {
            throw new ConfigException(
                "subcommand value '{$subcommandValueFormatted}' >>> Config error:"
                . ' a subcommand switch must be specified first.',
            );
        }

        $subcommandSwitchNameFormatted = $errorFormatter->paramTitle($this->subcommandSwitchName);
        $errorMessagePrefix            = "'{$subcommandSwitchNameFormatted}' subcommand >>> Config error:";

        if (mb_strlen($subcommandValue) < 1) {
            throw new ConfigException("{$errorMessagePrefix} empty value; must contain at least 1 symbol.");
        }
        if (!preg_match('/^[a-z][a-z0-9_\-]+$/u', $subcommandValue)) {
            throw new ConfigException(
                "{$errorMessagePrefix} invalid characters in value '{$subcommandValueFormatted}'. Must start with"
                . ' latin (lower); the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            );
        }

        if (isset($this->branches[$subcommandValue])) {
            throw new ConfigException(
                "'{$subcommandSwitchNameFormatted}' subcommand >>> Config error: duplicate value '{$subcommandValueFormatted}'.",
            );
        }

        $this->branches[$subcommandValue] = $config
            ->parent($this)
            ->scriptName($subcommandValue);
        $config->addDefaultOptions();

        return $this;
    }

    /**
     * Registering new argument (positional parameter).
     */
    public function registerArgument(Argument $argument): void {
        $argumentName = $argument->getName();

        if (null !== $this->subcommandSwitchName) {
            $errorFormatter                = HelpFormatter::createForStdErr();
            $argumentNameFormatted         = $errorFormatter->paramTitle($argumentName);
            $subcommandSwitchNameFormatted = $errorFormatter->paramTitle($this->subcommandSwitchName);

            throw new ConfigException(
                "'{$argumentNameFormatted}' >>> Config error: extra arguments are not allowed on the same level AFTER"
                . " a subcommand switch ('{$subcommandSwitchNameFormatted}') is registered;"
                . " you should add arguments BEFORE '{$subcommandSwitchNameFormatted}' or to subcommands.",
            );
        }

        $this->ensureNoDuplicateName($argument);
        $this->params[$argumentName]    = $argument;
        $this->arguments[$argumentName] = $argument;

        if ($argument->isSubcommandSwitch()) {
            $this->subcommandSwitchName = $argumentName;
        }
    }

    /**
     * Registering new option (named parameter).
     */
    public function registerOption(Option $option): void {
        $optionName = $option->getName();

        $this->ensureNoDuplicateName($option);
        $this->params[$optionName]  = $option;
        $this->options[$optionName] = $option;

        $this->optionsByFormattedNamesAndShortNames["--{$optionName}"] = $option;

        $optionShortName = $option->getShortName();
        if (null !== $optionShortName) {
            $this->ensureNoDuplicateShortName($option);
            $this->optionsByShortNames[$optionShortName]                       = $option;
            $this->optionsByFormattedNamesAndShortNames["-{$optionShortName}"] = $option;

            if ($option->isValueRequired()) {
                $this->optionsWithValuesShortNames[] = $optionShortName;
            }
        }
    }

    public function addDefaultOptions(bool $isTopConfig = false): void {
        if ($isTopConfig) {
            $this->registerOption(
                (new Option(static::OPTION_NAME_AUTOCOMPLETE_GENERATE))
                    ->visibilityBitmask(static::VISIBILITY_BITMASK_NONE)
                    ->callback(function ($shellAlias) {
                        echo Completion::generateAutocompleteScript($shellAlias);

                        exit;
                    }),
            );

            $this->registerOption(
                (new Option(static::OPTION_NAME_AUTOCOMPLETE_EXECUTE))
                    ->flagValue(true)
                    ->default(false)
                    ->visibilityBitmask(static::VISIBILITY_BITMASK_NONE)
                    ->callback(function () {
                        Completion::executeAutocomplete($this);

                        exit;
                    }),
            );
        }

        $this->registerOption(
            (new Option(static::OPTION_NAME_HELP))
                ->shortName($this->getEnvConfig()->optionHelpShortName)
                ->flagValue(true)
                ->default(false)
                ->description('Show full help page.')
                ->visibilityBitmask(static::VISIBLE_HELP | static::VISIBLE_COMPLETION)
                ->callback(function () {
                    echo (new HelpGenerator($this))->getFullHelp();

                    exit;
                }),
        );
    }

    /**
     * Params of any type.
     *
     * @return ParameterAbstract[] name => ParameterAbstract
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * Arguments (positional params).
     *
     * @return Argument[]
     */
    public function getArguments(): array {
        return array_values($this->arguments);
    }

    /**
     * Options (named params).
     *
     * @return Option[] long name => Option
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * All options indexed by their names.
     *
     * An option may have both long and short names, so the same option object may appear twice here.
     *
     * ```
     *  `--name` => Option,
     *  `-n`     => Option,
     * ```
     *
     * @return Option[]
     */
    public function getOptionsByFormattedNamesAndShortNames(): array {
        return $this->optionsByFormattedNamesAndShortNames;
    }

    /**
     * Completes setup of parameter that defines subcommands. Runs recursively for all branches.
     *
     * Warning! Do not call this more than once! The second call will not do anything.
     *
     * This is called before processing script input parameters.
     * Manual call to this method may be needed in a rare case:
     * 1. Config has subcommands;
     * 2. You want for some reason to use command switch ({@see ConfigBuilder::newSubcommandSwitch()})
     * without processing input params.
     *
     * Do not call this method until all subcommands are configured!
     */
    protected function commitSubcommandSwitch(): void {
        if (null === $this->subcommandSwitchName) {
            return;
        }

        // Technically subcommand switch can be done via option/flag.
        // Any class of $subcommandSwitch will do.
        $subcommandSwitch          = $this->params[$this->subcommandSwitchName];
        $subcommandConfigsByValues = $this->branches;

        // ==== VALIDATION ====

        $errorMessagePrefix = "'" . HelpFormatter::createForStdErr()->paramTitle($subcommandSwitch->getName())
            . "' >>> Config error:";

        if ($this->isSubcommandSwitchCommited) {
            throw new ConfigException("{$errorMessagePrefix} the subcommand switch was commited already.");
        }

        if (count($subcommandConfigsByValues) < 2) {
            throw new ConfigException("{$errorMessagePrefix} you must specify at least 2 subcommand configs.");
        }


        // ==== PROCESSING ====

        $subcommandValues = array_keys($subcommandConfigsByValues);

        foreach ($subcommandConfigsByValues as $branchConfig) {
            $branchConfig->commitSubcommandSwitch();
        }

        // Setting up possible values for autocomplete and validation.
        $subcommandSwitch->allowedValues(array_fill_keys($subcommandValues, null));

        // Restricting further attempts to commit again.
        $this->isSubcommandSwitchCommited = true;
    }

    /**
     * Final touches before starting to process script input params.
     */
    public function finalize(): void {
        $this->ensureArrayArgumentIsLastAndOnlyOne();
        $this->commitSubcommandSwitch();
    }

    /**
     * The list of short names for options that require values (are not flags).
     *
     * @return string[]
     */
    public function getOptionsWithValuesShortNames(): array {
        return $this->optionsWithValuesShortNames;
    }

    public function parent(?Config $config): static {
        $this->parent = $config;

        return $this;
    }

    public function getParent(): ?static {
        return $this->parent;
    }

    /**
     * Returns branch config if it exists
     *
     * @param string $name proper name, not an alias!
     */
    public function getBranch(string $name): ?static {
        return $this->branches[$name] ?? null;
    }

    /**
     * Returns all configured branches
     *
     * @return Config[]
     */
    public function getBranches(): array {
        return $this->branches;
    }
}
