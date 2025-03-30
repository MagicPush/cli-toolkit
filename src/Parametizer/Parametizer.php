<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\Exception\ParseErrorException;
use MagicPush\CliToolkit\Parametizer\Parser\Parser;
use Throwable;

class Parametizer {
    /** This exit code will be used if an uncaught exception occurs. */
    public const int ERROR_EXIT_CODE = 1;

    /**
     * A handy shortcut for easy chaining.
     *
     * If `$envConfig` is `null`, loads an {@see EnvironmentConfig} instance automatically from config files.
     *
     * @param bool $throwOnException Useful to debug automatic environment config creation, if `$envConfig` is `null`.
     */
    public static function newConfig(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): ConfigBuilder {
        return new ConfigBuilder($envConfig, $throwOnException);
    }

    /**
     * Read the description in {@see BuilderInterface::run()}.
     */
    public static function run(Config $config): CliRequest {
        if ('' === $config->getScriptName()) {
            $config->scriptName(basename($_SERVER['argv'][0]));
        }
        $config->addDefaultOptions(true);
        $config->finalize();

        $requestProcessor = new CliRequestProcessor($config);
        static::setExceptionHandlerForParsing($requestProcessor);

        $request = $requestProcessor->load(new Parser());
        $requestProcessor->validate();

        $request->executeBuiltInSubcommandIfRequested();

        return $request;
    }

    protected static function setExceptionHandlerForParsing(CliRequestProcessor $cliRequestProcessor): void {
        set_exception_handler(
            function (Throwable $e) use ($cliRequestProcessor) {
                $errorMessage = $e instanceof ParseErrorException ? $e->getMessage() : $e->__toString();
                fwrite(STDERR, $errorMessage . PHP_EOL);

                if ($e instanceof ParseErrorException) {
                    fwrite(
                        STDERR,
                        HelpGenerator::getUsageForParseErrorException(
                            $e,
                            $cliRequestProcessor->getInnermostBranchConfig(),
                        ),
                    );
                }

                exit(static::ERROR_EXIT_CODE);
            },
        );
    }
}
