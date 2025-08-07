<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use Override;

class ShowHelpPage extends BuiltinSubcommandAbstract {
    public const string ARGUMENT_SUBCOMMAND_NAME = 'subcommand-name';


    protected readonly string $subcommandName;

    #[Override]
    public static function getScriptInnerName(): string {
        return Config::PARAMETER_NAME_HELP;
    }

    #[Override]
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $listSubcommandName = ListSubcommands::getScriptName();
        $formatter          = HelpFormatter::createForStdOut();

        $configBuilder->description('Outputs a help page for a specified subcommand.')

            ->newArgument(static::ARGUMENT_SUBCOMMAND_NAME)
            ->description("
                Name of any registered subcommand.
                See '{$formatter->paramValue($listSubcommandName)}' subcommand for the list of possible values.
            ")
            ->default(static::getScriptName());
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
