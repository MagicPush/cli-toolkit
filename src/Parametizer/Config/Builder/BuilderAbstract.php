<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Parametizer;

abstract class BuilderAbstract implements BuilderInterface {
    protected ConfigBuilder     $configBuilder;
    protected ParameterAbstract $param;


    // === Parameter settings ===

    public function description(string $description): static {
        $this->param->description($description);

        return $this;
    }

    /**
     * Set a callback for a param (null = disable callback).
     *
     * Callback is called after validation
     * ({@see VariableBuilderAbstract::validatorPattern()} and {@see VariableBuilderAbstract::validatorCallback()}).
     * If there is no validator, callback is called immediately. If the param allows multiple values,
     * callback is called for each value (as soon as the parser reads it).
     *
     * Callback receives 1 argument (parameter's value), its result is not used.
     */
    public function callback(?callable $callback): static {
        $this->param->callback($callback);

        return $this;
    }

    /**
     * Changes param visibility (see `VISIB*` constants in {@see Config}).
     * Default is full visibility {@see Config::VISIBILITY_BITMASK_ALL}.
     */
    public function visibilityBitmask(int $visibilityBitmask): static {
        $this->param->visibilityBitmask($visibilityBitmask);

        return $this;
    }


    // === Chain call helpers ===

    public function newArgument(string $name): ArgumentBuilder {
        return $this->configBuilder->newArgument($name);
    }

    public function newArrayArgument(string $name): ArrayArgumentBuilder {
        return $this->configBuilder->newArrayArgument($name);
    }

    public function newOption(string $name, ?string $shortName = null): OptionBuilder {
        return $this->configBuilder->newOption($name, $shortName);
    }

    public function newArrayOption(string $name, ?string $shortName = null): ArrayOptionBuilder {
        return $this->configBuilder->newArrayOption($name, $shortName);
    }

    public function newFlag(string $name, ?string $shortName = null): FlagBuilder {
        return $this->configBuilder->newFlag($name, $shortName);
    }

    public function newSubcommandSwitch(string $name): SubcommandSwitchBuilder {
        return $this->configBuilder->newSubcommandSwitch($name);
    }

    public function newSubcommand(string $subcommandName, BuilderInterface $builder): ConfigBuilder {
        return $this->configBuilder->newSubcommand($subcommandName, $builder);
    }

    public function run(): CliRequest {
        return Parametizer::run($this->getConfig());
    }

    public function getConfig(): Config {
        return $this->configBuilder->getConfig();
    }


    // === Misc ===

    protected static function getValidatedOptionName(string $name): string {
        if (!str_starts_with($name, '--')) {
            $errorFormatter = HelpFormatter::createForStdErr();

            throw new ConfigException(
                "'" . $errorFormatter->paramTitle($name) . "' >>> Config error: the option must have prefix"
                . " '--' (example: '" . $errorFormatter->italic('--name') . "').",
            );
        }

        return ltrim($name, '-');
    }

    protected static function getValidatedOptionShortName(?string $shortName): ?string {
        if (null === $shortName) {
            return null;
        }

        if (!str_starts_with($shortName, '-')) {
            $errorFormatter = HelpFormatter::createForStdErr();

            throw new ConfigException(
                "'" . $errorFormatter->paramTitle($shortName) . "' >>> Config error: the option's short name must have"
                . " prefix '-' (example: '" . $errorFormatter->italic('-n') . "').",
            );
        }

        return ltrim($shortName, '-');
    }
}
