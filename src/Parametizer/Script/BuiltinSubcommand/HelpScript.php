<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class HelpScript extends BuiltinSubcommandAbstract {
    public const string ARGUMENT_SUBCOMMAND_NAME = 'subcommand-name';


    protected readonly string $subcommandName;


    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $listSubcommandName = Config::PARAMETER_NAME_LIST;
        $formatter          = HelpFormatter::createForStdOut();

        $configBuilder->description('Outputs a help page for a specified subcommand.')

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
