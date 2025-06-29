<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCache;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCacheContext;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

class ScriptLauncher {
    protected readonly ScriptClassDetector $scriptClassDetector;
    protected readonly ConfigBuilder       $configBuilder;

    protected bool $useParentEnvConfigForSubcommands = false;
    protected bool $throwOnException                 = false;


    public function __construct(
        ?ScriptClassDetector $scriptClassDetector = null,
        ?ConfigBuilder $configBuilder = null,
    ) {
        if (null !== $scriptClassDetector) {
            $this->scriptClassDetector = $scriptClassDetector;
        }
        if (null !== $configBuilder) {
            $this->configBuilder = $configBuilder;
        }
    }

    /**
     * The flag does not affect built-in subcommands - those always utilize a parent environment config.
     */
    public function useParentEnvConfigForSubcommands(bool $isEnabled = true): static {
        $this->useParentEnvConfigForSubcommands = $isEnabled;

        return $this;
    }

    /**
     * Always affects detected subcommands. Affects {@see ScriptClassDetector} and {@see ConfigBuilder}
     * instances only if those are created automatically - if `null` is passed instead of an instance
     * to {@see static::__construct()}.
     *
     * @see ScriptClassDetector::__construct()
     * @see Parametizer::newConfig()
     */
    public function throwOnException(bool $isEnabled = true): static {
        $this->throwOnException = $isEnabled;

        return $this;
    }

    public function execute(): void {
        if (!isset($this->scriptClassDetector)) {
            $this->scriptClassDetector = (new ScriptClassDetector(throwOnException: $this->throwOnException))
                ->searchDirectory(dirname($_SERVER['SCRIPT_FILENAME']));
        }
        if (!isset($this->configBuilder)) {
            $this->configBuilder = Parametizer::newConfig(throwOnException: $this->throwOnException);
        }

        $envConfigForSubcommands = $this->useParentEnvConfigForSubcommands
            ? $this->configBuilder->getConfig()->getEnvConfig()
            : null;

        $classNamesBySubcommandNames = $this->scriptClassDetector
            ->getDetectedData();

        // Init a subcommand switch - ensure built-in subcommands are added even if no custom subcommands are detected.
        $this->configBuilder->newSubcommandSwitch('subcommand-name');

        foreach ($classNamesBySubcommandNames as $subcommandName => $className) {
            $this->configBuilder->newSubcommand(
                $subcommandName,
                $className::getConfiguration(
                    envConfig: $envConfigForSubcommands,
                    throwOnException: $this->throwOnException,
                ),
            );
        }

        if ($this->scriptClassDetector->doesCacheFileExist()) {
            $subcommandNameClearCache = ClearCache::getFullName();
            $contextClearCache        = new ClearCacheContext($this->scriptClassDetector->getCacheFilePath());

            $this->configBuilder->newSubcommand(
                $subcommandNameClearCache,
                ClearCache::getConfiguration(
                    envConfig: $envConfigForSubcommands,
                    throwOnException: $this->throwOnException,
                    context: $contextClearCache,
                ),
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
