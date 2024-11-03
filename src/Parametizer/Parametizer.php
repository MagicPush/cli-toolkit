<?php declare(strict_types=1);

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
    public const ERROR_EXIT_CODE = 1;

    /**
     * A shortcut for `new Config` for easy chaining.
     */
    public static function newConfig(): ConfigBuilder {
        return new ConfigBuilder();
    }

    /**
     * Read the description in {@see BuilderInterface::run()}.
     */
    public static function run(Config $config): CliRequest {
        static::setExceptionHandlerForParsing();

        if ('' === $config->getScriptName()) {
            $config->scriptName(basename($_SERVER['argv'][0]));
        }

        $requestProcessor = new CliRequestProcessor();

        $config->addDefaultOptions(true);
        $config->finalize();

        $request = $requestProcessor->load($config, new Parser());
        $requestProcessor->validate();

        return $request;
    }

    protected static function setExceptionHandlerForParsing(): void {
        set_exception_handler(
            function (Throwable $e) {
                $errorMessage = $e instanceof ParseErrorException ? $e->getMessage() : $e->__toString();
                fwrite(STDERR, $errorMessage . PHP_EOL);

                if ($e instanceof ParseErrorException) {
                    echo HelpGenerator::getUsageForParseErrorException($e);
                }

                exit(static::ERROR_EXIT_CODE);
            },
        );
    }
}
