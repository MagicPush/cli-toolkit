<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

class HelpScript extends ScriptAbstract {
    public const string ARGUMENT_SUBCOMMAND_NAME = 'subcommand-name';


    protected readonly string $subcommandName;


    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        $listSubcommandName = Config::PARAMETER_NAME_LIST;
        $formatter          = HelpFormatter::createForStdOut();

        return static::newConfig(envConfig: $envConfig, throwOnException: $throwOnException)
            ->description('Outputs a help page for a specified subcommand.')

            ->newArgument(static::ARGUMENT_SUBCOMMAND_NAME)
            ->description("
                Name of any registered subcommand.
                See '{$formatter->paramValue($listSubcommandName)}' subcommand for the list of possible values.
            ")
            ->default(Config::OPTION_NAME_HELP);
    }

    public function __construct(CliRequest $request) {
        parent::__construct($request);

        $this->subcommandName = $request->getParamAsString(static::ARGUMENT_SUBCOMMAND_NAME);
    }

    public function execute(): void {
        $subcommandConfig = $this->request->config->getParent()->getBranch($this->subcommandName);

        echo (new HelpGenerator($subcommandConfig))->getFullHelp();
    }
}
