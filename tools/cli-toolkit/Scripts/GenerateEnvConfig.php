<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Scripts;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use RuntimeException;
use Throwable;

class GenerateEnvConfig extends ScriptAbstract {

    public static function getConfiguration(): BuilderInterface {
        return static::newConfig()
            ->description('
                Generates an environment config file with all possible settings. All settings are set to default values.
                Then you may alter the settings you wish to affect your Parametizer-powered scripts.
            ')

            ->usage('/path/to/my-cool-project/')

            ->newFlag('--force', '-f')
            ->description('
                Do not throw an exception if a config file already exists.
                Replace the file with the generated one.
            ')

            ->newArgument('path')
            ->description('Location of the generated file.')
            ->default(getcwd())
            ->validatorCallback(
                function (&$value) {
                    $value = realpath(trim($value));

                    return false !== $value && is_readable($value) && is_dir($value);
                },
                'Path should be a readable directory.',
            );
    }

    public function execute(): void {
        set_exception_handler(function (Throwable $e) {
            fwrite(STDERR, ScriptFormatter::createForStdErr()->error($e->getMessage() . PHP_EOL));

            exit(Parametizer::ERROR_EXIT_CODE);
        });

        $isForced           = $this->request->getParamAsBool('force');
        $filePath           = $this->request->getParamAsString('path') . '/' . EnvironmentConfig::CONFIG_FILENAME;
        $executionFormatter = ScriptFormatter::createForStdOut();

        if (!$isForced && file_exists($filePath)) {
            throw new RuntimeException(
                "File '" . $executionFormatter->pathMentioned($filePath)
                    . "' already exists. Add '--force' ('-f') to overwrite it.",
            );
        }

        if (false === file_put_contents($filePath, (new EnvironmentConfig())->toJsonFileContent())) {
            throw new RuntimeException("Unable to write into '" . $executionFormatter->pathMentioned($filePath) . "'");
        }

        echo 'Environment config file is created: ' . $executionFormatter->success($filePath) . PHP_EOL;
    }
}
