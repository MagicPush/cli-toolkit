<?php

namespace MagicPush\CliToolkit\Parametizer\Config\Builder;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Config;

interface BuilderInterface {
    /**
     * New argument (positional parameter).
     */
    public function newArgument(string $name): ArgumentBuilder;

    /**
     * New argument that can have multiple values with identical settings ({@see newArgument()}).
     *
     * **Warning!** This kind of an argument must be defined the last,
     * because it consumes all input params starting from its position.
     */
    public function newArrayArgument(string $name): ArrayArgumentBuilder;

    /**
     * New option (`--option=value / -o value / -ovalue`).
     *
     * You can force treating params as arguments by using `--`:
     * `my-script some-argument-value --some-option=optionvalue -- definitely-argument-value`
     * This is useful if you want to pass argument value that starts with a dash,
     * like 'rm -- -r' will remove a file named '-r'.
     */
    public function newOption(string $name, ?string $shortName = null): OptionBuilder;

    /**
     * New option that allows several values with identical settings ({@see newOption()}):
     * `--option=value1 --option=value2 -ovalue3 -o value4 ...`
     */
    public function newArrayOption(string $name, ?string $shortName = null): ArrayOptionBuilder;

    /**
     * New flag: an option that has no input value (`--flag / -f`).
     */
    public function newFlag(string $name, ?string $shortName = null): FlagBuilder;

    /**
     * New argument (positional parameter) that switches subcommands.
     *
     * Call {@see newSubcommand()} for each possible switch value to configure connected subcommands.
     */
    public function newSubcommandSwitch(string $name): SubcommandSwitchBuilder;

    /**
     * New subcommand (config branch). Make sure to use {@see newSubcommandSwitch()} beforehand.
     *
     * When an input parameter is processed, if it is a subcommand switch, all following parameters
     * are processed with the subcommand config.
     */
    public function newSubcommand(string $subcommandName, BuilderInterface $builder): ConfigBuilder;

    /**
     * Processes the input parameters, validates options and other configured things,
     * runs callbacks, and returns the structured data corresponding to configured args/options/etc.
     */
    public function run(): CliRequest;

    /**
     * Config object that we're building here.
     */
    public function getConfig(): Config;
}
