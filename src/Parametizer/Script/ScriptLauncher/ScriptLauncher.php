<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCache;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCacheContext;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

class ScriptLauncher {
    protected readonly ConfigBuilder $configBuilder;

    protected bool $useParentEnvConfigForSubcommands = false;
    protected bool $throwOnException                 = false;


    public function __construct(
        protected readonly ScriptClassDetector $scriptClassDetector,
        ?ConfigBuilder $configBuilder = null,
    ) {
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
     * Always affects subcommands. Also, affects main {@see ConfigBuilder} instance only if the latter is created
     * automatically - if `null` (default value) is passed to {@see static::__construct()} as the relevant parameter.
     *
     * By default (without this method call), the corresponding internal property value is `false`.
     *
     * @see Parametizer::newConfig()
     */
    public function throwOnException(bool $isEnabled = true): static {
        $this->throwOnException = $isEnabled;

        return $this;
    }

    public function execute(): void {
        if (!isset($this->configBuilder)) {
            $this->configBuilder = Parametizer::newConfig(null, $this->throwOnException);
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
                $className::getConfigBuilder(
                    $envConfigForSubcommands,
                    $this->throwOnException,
                ),
            );
        }

        if ($this->scriptClassDetector->doesCacheFileExist()) {
            $subcommandNameClearCache = ClearCache::getScriptName();
            $contextClearCache        = new ClearCacheContext($this->scriptClassDetector->getCacheFilePath());

            $this->configBuilder->newSubcommand(
                $subcommandNameClearCache,
                // Like other built-in subcommands, this one should utilize a parent (launcher's) config.
                ClearCache::getConfigBuilder(
                    $this->configBuilder->getConfig()->getEnvConfig(),
                    $this->throwOnException,
                    $contextClearCache,
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
