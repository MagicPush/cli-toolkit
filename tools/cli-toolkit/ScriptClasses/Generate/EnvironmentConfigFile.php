<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tools\CliToolkit\Classes\ScriptFormatter;
use RuntimeException;
use Throwable;

class EnvironmentConfigFile extends CliToolkitGenerateScriptAbstract {
    protected static function setUpConfig(ConfigBuilder $configBuilder): void {
        parent::setUpConfig($configBuilder);

        $configBuilder
            ->description('
                Generates an environment config file with all possible settings. All settings are set to default values.
                Then you may alter the settings you wish to affect your Parametizer-powered scripts.
            ')

            ->usage('/path/to/my-cool-project/')

            ->newFlag('--force', '-f')
            ->description('
                Do not throw an exception if a config file already exists.
                Instead replace the file with the generated one.
            ')

            ->newArgument('directory-path')
            ->description('
                The generated file directory path.
                If the directory does not exist, the script will attempt to create it.
            ');
    }


    public function execute(): void {
        set_exception_handler(function (Throwable $e) {
            fwrite(STDERR, ScriptFormatter::createForStdErr()->error($e->getMessage() . PHP_EOL));

            exit(Parametizer::ERROR_EXIT_CODE);
        });

        $isForced           = $this->request->getParamAsBool('force');
        $directoryPath      = $this->request->getParamAsString('directory-path');
        $executionFormatter = ScriptFormatter::createForStdOut();

        if (!is_dir($directoryPath)) {
            if (!mkdir($directoryPath, recursive: true)) {
                throw new RuntimeException('Unable to create a directory: ' . var_export($directoryPath, true));
            }
            echo 'A directory has been created: '
                . $executionFormatter->success($directoryPath)
                . PHP_EOL;
        }

        $directoryPath = realpath($directoryPath);
        $filePath      = $directoryPath . '/' . EnvironmentConfig::CONFIG_FILENAME;

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
