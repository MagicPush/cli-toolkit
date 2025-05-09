<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCache;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCacheContext;

class ScriptLauncher {
    public readonly ScriptDetector $scriptDetector;
    public readonly ConfigBuilder $configBuilder;


    public function __construct(?ScriptDetector $scriptDetector = null, ?ConfigBuilder $configBuilder = null) {
        if (null === $scriptDetector) {
            $scriptDetector = new ScriptDetector();
            $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $launcherPath   = $debugBacktrace[array_key_last($debugBacktrace)]['file'] ?? '';
            if ('' !== $launcherPath) {
                $launcherDirectoryPath = dirname($launcherPath);
                $scriptDetector
                    ->searchClassPath($launcherDirectoryPath)
                    ->cacheFilePath($launcherDirectoryPath . '/' . basename($launcherPath, '.php') . '.json');
            }
        }
        $this->scriptDetector = $scriptDetector;

        if (null === $configBuilder) {
            $configBuilder = Parametizer::newConfig();
        }
        $this->configBuilder = $configBuilder;
    }

    public function execute(): void {
        $classNamesBySubcommandNames = $this->scriptDetector
            ->detect()
            ->getFQClassNamesByScriptNames();

        $this->configBuilder->newSubcommandSwitch('subcommand');
        foreach ($classNamesBySubcommandNames as $subcommandName => $className) {
            $this->configBuilder->newSubcommand($subcommandName, $className::getConfiguration());
        }

        if ($this->scriptDetector->doesCacheFileExist()) {
            $subcommandNameClearCache = ClearCache::getFullName();
            $contextClearCache        = new ClearCacheContext($this->scriptDetector->getCacheFilePath());

            $this->configBuilder->newSubcommand(
                $subcommandNameClearCache,
                ClearCache::getConfiguration($contextClearCache),
            );
        } else {
            $subcommandNameClearCache = null;
            $contextClearCache        = null;
        }

        $request = $this->configBuilder->run();

        $requestedSubcommandName = $request->getRequestedSubcommandName();
        if ($contextClearCache && $subcommandNameClearCache === $requestedSubcommandName) {
            $scriptClass = new ClearCache($request->getSubcommandRequest(), $contextClearCache);
        } else {
            $className   = $classNamesBySubcommandNames[$requestedSubcommandName];
            $scriptClass = new $className($request->getSubcommandRequest());
        }

        $scriptClass->execute();
    }
}
