<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ScriptLauncherSubcommandAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\ToolBelt;
use RuntimeException;

class ClearCache extends ScriptLauncherSubcommandAbstract {
    protected readonly HelpFormatter $formatterOutput;
    protected readonly HelpFormatter $formatterError;

    protected readonly bool $isVerbose;


    /**
     * @param ClearCacheContext|null $context The actual value must not be `null`.
     *                                        The availability of a default value is made solely for the compliance
     *                                        with the parent abstract method signature.
     */
    public static function getConfigBuilder(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
        ?ClearCacheContext $context = null,
    ): ConfigBuilder {
        $configBuilder = parent::getConfigBuilder($envConfig, $throwOnException);

        if (null === $context) {
            throw new RuntimeException(ClearCacheContext::class . ' instance is not set');
        }

        $formatter                      = HelpFormatter::createForStdOut();
        $detectorClassLastNameFormatted = $formatter->helpNote(ToolBelt::getClassShortName(ScriptClassDetector::class));

        $configBuilder
            ->shortDescription("Removes {$detectorClassLastNameFormatted}'s cache file.")
            ->description("
                Removes {$detectorClassLastNameFormatted}'s cache file: "
                    . $formatter->paramValue($context->detectorCacheFilePath) . "
            ")

            ->newFlag('--verbose', '-v')
            ->description('Print various messages during the subcommand execution.');

        return $configBuilder;
    }

    public function __construct(CliRequest $request, protected readonly ClearCacheContext $context) {
        parent::__construct($request);

        $this->formatterOutput = HelpFormatter::createForStdOut();
        $this->formatterError  = HelpFormatter::createForStdErr();

        $this->isVerbose = $request->getParamAsBool('verbose');
    }

    public function execute(): void {
        $this->logOutput(
            'Deleting the script detector cache file '
                . $this->formatterOutput->paramValue($this->context->detectorCacheFilePath)
                . '...',
        );

        if (unlink($this->context->detectorCacheFilePath)) {
            $this->logOutput(' OK' . PHP_EOL);
        } else {
            $this->logOutput(PHP_EOL)
                ->logError(
                    $this->formatterError->error('Unable to delete the script detector cache file: ')
                        . $this->formatterError->paramValue($this->context->detectorCacheFilePath)
                        . PHP_EOL,
                );
        }
    }

    protected function logOutput(string $message): static {
        if ($this->isVerbose) {
            echo $message;
        }

        return $this;
    }

    protected function logError(string $message): static {
        fwrite(STDERR, $message);

        return $this;
    }
}
