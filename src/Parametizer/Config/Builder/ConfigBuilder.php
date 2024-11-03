<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Parametizer;

class ConfigBuilder implements BuilderInterface {
    protected readonly Config $config;


    public function __construct() {
        $this->config = new Config();
    }


    // === Config settings ===

    /**
     * Description of the whole script (for help)
     */
    public function description(string $description): static {
        $this->config->description($description);

        return $this;
    }

    /**
     * Usage example (for help).
     *
     * Examples with no descriptions are displayed first.
     * Then described examples are displayed.
     */
    public function usage(string $example, ?string $description = null): static {
        $this->config->usage($example, $description);

        return $this;
    }

    public function newSubcommand(string $subcommandValue, BuilderInterface $builder): static {
        $this->config->newSubcommand($subcommandValue, $builder->getConfig());

        return $this;
    }


    // === Parameter builders ===

    public function newArgument(string $name): ArgumentBuilder {
        return new ArgumentBuilder($this, $name);
    }

    public function newArrayArgument(string $name): ArrayArgumentBuilder {
        return new ArrayArgumentBuilder($this, $name);
    }

    public function newOption(string $name, ?string $shortName = null): OptionBuilder {
        return new OptionBuilder($this, $name, $shortName);
    }

    public function newArrayOption(string $name, ?string $shortName = null): ArrayOptionBuilder {
        return new ArrayOptionBuilder($this, $name, $shortName);
    }

    public function newFlag(string $name, ?string $shortName = null): FlagBuilder {
        return new FlagBuilder($this, $name, $shortName);
    }

    public function newSubcommandSwitch(string $name): SubcommandSwitchBuilder {
        return new SubcommandSwitchBuilder($this, $name);
    }


    // === Chain call helpers ===

    public function run(): CliRequest {
        return Parametizer::run($this->getConfig());
    }


    // === Misc ===

    public function getConfig(): Config {
        return $this->config;
    }
}
