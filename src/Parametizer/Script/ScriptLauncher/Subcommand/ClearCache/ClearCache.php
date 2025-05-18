<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ScriptLauncherScriptAbstract;
use RuntimeException;

class ClearCache extends ScriptLauncherScriptAbstract {
    protected readonly HelpFormatter $formatterOutput;
    protected readonly HelpFormatter $formatterError;

    protected readonly bool $isVerbose;


    /**
     * @param ClearCacheContext|null $context The actual value must not be `null`.
     *                                        The availability of a default value is made solely for the compliance
     *                                        with the parent abstract method signature.
     */
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
        ?ClearCacheContext $context = null,
    ): BuilderInterface {
        if (null === $context) {
            throw new RuntimeException(static::getClassLastName(ClearCacheContext::class) . ' is not set');
        }

        $formatter = HelpFormatter::createForStdOut();

        $detectorClassLastNameFormatted = $formatter->helpNote(static::getClassLastName(ScriptDetector::class));

        return static::newConfig(envConfig: $envConfig, throwOnException: $throwOnException)
            ->shortDescription("Clears {$detectorClassLastNameFormatted}'s cache file.")
            ->description("
                Clears {$detectorClassLastNameFormatted}'s cache file: "
                    . $formatter->paramValue($context->cacheFilePath) . "
            ")

            ->newFlag('--verbose', '-v')
            ->description('Print various messages during the subcommand execution.');
    }

    protected static function getClassLastName(string $classFQName): string {
        $lastBackSlashPosition = mb_strrpos($classFQName, '\\');
        if (false === $lastBackSlashPosition) {
            return $classFQName;
        }

        return mb_substr($classFQName, $lastBackSlashPosition + 1);
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
            . $this->formatterOutput->paramValue($this->context->cacheFilePath)
            . '...',
        );

        if (unlink($this->context->cacheFilePath)) {
            $this->logOutput(' OK' . PHP_EOL);
        } else {
            $this->logOutput(PHP_EOL)
                ->logError(
                    $this->formatterError->error('Unable to delete the script detector cache file: ')
                    . $this->formatterError->paramValue($this->context->cacheFilePath)
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
