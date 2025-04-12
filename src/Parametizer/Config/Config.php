<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config;

use LogicException;
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
use MagicPush\CliToolkit\Parametizer\Script\BuiltInSubcommand\HelpScript;
use MagicPush\CliToolkit\Parametizer\Script\BuiltInSubcommand\ListScript;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

class Config {
    public const string PARAMETER_NAME_LIST               = 'list';
    public const string OPTION_NAME_HELP                  = 'help';
    public const string OPTION_NAME_AUTOCOMPLETE_GENERATE = 'parametizer-internal-autocomplete-generate';
    public const string OPTION_NAME_AUTOCOMPLETE_EXECUTE  = 'parametizer-internal-autocomplete-execute';

    // Visibility bits:

    /** The param is displayed in usage template at the top of the help. */
    final public const int VISIBLE_USAGE_TEMPLATE = 1;

    /** The param is displayed in the help with its description. */
    final public const int VISIBLE_HELP = 2;

    /** Autocomplete will suggest the parameter value (and names for options). */
    final public const int VISIBLE_COMPLETION = 4;

    /** Parameter value is available in {@see CliRequest} inside of the script. */
    final public const int VISIBLE_REQUEST = 8;

    // Visibility bitmasks:

    /** Completely hidden (empty bitmask). */
    final public const int VISIBILITY_BITMASK_NONE = 0;

    /** Parameter is visible everywhere (full bitmask). */
    public const int VISIBILITY_BITMASK_ALL = 15;


    protected EnvironmentConfig $envConfig;

    protected string $description = '';

    /** @var CommandUsageExample[] */
    protected array $usageExamples = [];

    protected string $scriptName = '';

    /** @var array<string, ParameterAbstract> name => object */
    protected array $params = [];

    /** @var array<string, Argument> name => Argument */
    protected array $arguments = [];

    /** @var array<string, Option> long name => Option */
    protected array $options = [];

    /** @var array<string, Option> short name => Option */
    protected array $optionsByShortNames = [];

    /** @var array<string, Option> '--name' => Option / '-n' => Option */
    protected array $optionsByFormattedNamesAndShortNames = [];

    /** @var string[] */
    protected array $optionsWithValuesShortNames = [];

    protected ?string $subcommandSwitchName       = null;
    protected bool    $isSubcommandSwitchCommited = false;

    /**
     * @var array<string, ScriptAbstract|string> (string) subcommand name set in the config =>
     *                                           (string) Fully qualified class name that extends {@see ScriptAbstract}
     */
    protected array $builtInSubcommandClassBySubcommandName = [];

    /** Not obligatory but useful for subcommand usage template help ({@see HelpGenerator::getUsageTemplate()}) */
    protected ?self $parent = null;

    /** @var array<string, Config> (string) Subcommand value => (Config) branch config */
    protected array $branches = [];


    /**
     * @param bool $throwOnException Useful to debug automatic environment config creation, if `$envConfig` is `null`.
     */
    public function __construct(?EnvironmentConfig $envConfig = null, bool $throwOnException = false) {
        if (null === $envConfig) {
            $envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy(throwOnException: $throwOnException);
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
        if (!preg_match('/^[a-z][a-z0-9_\-:]+$/u', $subcommandValue)) {
            throw new ConfigException(
                "{$errorMessagePrefix} invalid characters in value '{$subcommandValueFormatted}'. Must start with"
                . ' a latin (lower); the rest symbols may be of latin (lower), digit, underscore, colon or hyphen.',
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

    protected function addBuiltInSubcommands(): static {
        $this->builtInSubcommandClassBySubcommandName[static::PARAMETER_NAME_LIST] = ListScript::class;
        $this->builtInSubcommandClassBySubcommandName[static::OPTION_NAME_HELP]    = HelpScript::class;

        foreach ($this->builtInSubcommandClassBySubcommandName as $subcommandName => $subcommandClass) {
            $this->newSubcommand($subcommandName, $subcommandClass::getConfiguration()->getConfig());
        }

        $this->arguments[$this->subcommandSwitchName]
            ->require(false)
            ->default(static::PARAMETER_NAME_LIST);

        return $this;
    }

    /**
     * @return ScriptAbstract|string|null Fully qualified class name that extends {@see ScriptAbstract}.
     */
    public function getBuiltInSubcommandClass(string $subcommandName): ?string {
        return $this->builtInSubcommandClassBySubcommandName[$subcommandName] ?? null;
    }

    /**
     * @return array<string, static> (string) subcommand name => (Config) subcommand config
     */
    public function getBuiltInSubcommands(): array {
        $builtInSubcommands = [];
        foreach ($this->builtInSubcommandClassBySubcommandName as $subcommandName => $notUsed) {
            $subcommandConfig = $this->getBranch($subcommandName);
            if (null !== $subcommandConfig) {
                $builtInSubcommands[$subcommandName] = $subcommandConfig;
            }
        }

        return $builtInSubcommands;
    }

    public function getSubcommandSwitchName(): ?string {
        return $this->subcommandSwitchName;
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

            $this->addBuiltInSubcommands();
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
     * Arguments (positional parameters).
     *
     * @return Argument[]
     */
    public function getArguments(): array {
        return array_values($this->arguments);
    }

    /**
     * Arguments (positional parameters) by their names as keys.
     *
     * @return array<string, Argument> name => Argument
     */
    public function getArgumentsByNames(): array {
        return $this->arguments;
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
     * Must be called once. Repeated calls will render {@see ConfigException}.
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
        $subcommandSwitch          = $this->arguments[$this->subcommandSwitchName];
        $subcommandConfigsByValues = $this->branches;

        // ==== VALIDATION ====

        $errorMessagePrefix = "'" . HelpFormatter::createForStdErr()->paramTitle($subcommandSwitch->getName())
            . "' >>> Config error:";

        if ($this->isSubcommandSwitchCommited) {
            throw new ConfigException("{$errorMessagePrefix} the subcommand switch was commited already.");
        }


        // ==== PROCESSING ====

        // Setting up the list of allowed values for 'help' built-in subcommand argument.
        $helpSubcommandConfig = $this->getBranch(static::OPTION_NAME_HELP);
        if (null === $helpSubcommandConfig) {
            throw new LogicException(sprintf('"%s" built-in subcommand was not registered', static::OPTION_NAME_HELP));
        }
        $helpSubcommandArgument = $helpSubcommandConfig->getParams()[HelpScript::ARGUMENT_SUBCOMMAND_NAME] ?? null;
        if (null === $helpSubcommandArgument) {
            throw new LogicException(
                sprintf(
                    '"%s" parameter was not registered in "%s" built-in subcommand',
                    HelpScript::ARGUMENT_SUBCOMMAND_NAME,
                    static::OPTION_NAME_HELP,
                ),
            );
        }
        $helpSubcommandArgument->allowedValues(array_fill_keys(array_keys($subcommandConfigsByValues), null), true);

        foreach ($subcommandConfigsByValues as $branchConfig) {
            $branchConfig->commitSubcommandSwitch();
        }

        // Setting up possible values for autocomplete and validation.
        $subcommandSwitch->allowedValues(array_fill_keys(array_keys($subcommandConfigsByValues), null));

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
     * @return array<string, Config> (string) Subcommand value => (Config) branch config
     */
    public function getBranches(): array {
        return $this->branches;
    }
}
