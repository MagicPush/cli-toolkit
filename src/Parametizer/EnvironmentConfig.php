<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer;

use Exception;
use RuntimeException;
use TypeError;

class EnvironmentConfig {
    public const string CONFIG_FILENAME = 'parametizer.env.json';


    /* AVAILABLE PROPERTIES -> */

    public ?string $optionHelpShortName = null;

    public int $helpGeneratorShortDescriptionCharsMinBeforeFullStop = 40;
    public int $helpGeneratorShortDescriptionCharsMax               = 70;

    /* <- AVAILABLE PROPERTIES */


    /** @var bool[] (string) property name => (values do not matter) */
    protected array $propertiesNotYetInitializedFromFiles;


    public function __construct() {
        // Initialize the list or properties settable from config files:
        $this->propertiesNotYetInitializedFromFiles = array_fill_keys(
            array_keys(get_object_vars(...)->__invoke($this)),
            true,
        );
    }

    public function toJsonFileContent(): string {
        return json_encode(
            $this,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_LINE_TERMINATORS
            | JSON_PRETTY_PRINT,
        ) . PHP_EOL;
    }

    protected function haveFilesInitializedAllProperties(): bool {
        return empty($this->propertiesNotYetInitializedFromFiles);
    }

    /**
     * Fills the instance properties with a JSON config file contents.
     *
     * Affects only the properties mentioned in a file, the rest are kept unchanged.
     */
    public function fillFromJsonConfigFile(string $jsonConfigPath, bool $throwOnException = false): void {
        $configAbsolutePath = realpath($jsonConfigPath);
        if (false === $configAbsolutePath || !is_readable($configAbsolutePath)) {
            if (!$throwOnException) {
                return;
            }

            throw new RuntimeException('Invalid path or the file does not exist: ' . var_export($jsonConfigPath, true));
        }

        try {
            $parsedConfig = json_decode(file_get_contents($configAbsolutePath), true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            if (!$throwOnException) {
                return;
            }

            throw new RuntimeException(
                "Unable to read the environment config '{$configAbsolutePath}': {$e->getMessage()}",
            );
        }

        foreach ($this->propertiesNotYetInitializedFromFiles as $propertyName => $notUsed) {
            if (array_key_exists($propertyName, $parsedConfig)) {
                try {
                    $this->$propertyName = $parsedConfig[$propertyName];
                } catch (Exception|TypeError $e) {
                    if (!$throwOnException) {
                        continue;
                    }

                    throw new RuntimeException(
                        "Unable to set '{$propertyName}' environment config setting to the value: "
                            . var_export($parsedConfig[$propertyName], true)
                            . "; source file '{$configAbsolutePath}'; error: {$e->getMessage()}",
                    );
                }

                unset($this->propertiesNotYetInitializedFromFiles[$propertyName]);
            }
        }
    }

    /**
     * Creates an {@see EnvironmentConfig} instance with default values and tries to fill it from config files
     * {@see CONFIG_FILENAME} found along the way from `$bottommostDirectoryPath` to `$topmostDirectoryPath`.
     *
     * @param string      $bottommostDirectoryPath Should be filled with a readable path to a directory
     *                                             where a script config is being created.
     * @param string|null $topmostDirectoryPath    The method will not search config files above this directory.
     *                                             If `null`, will try to detect a path via
     *                                             {@see static::detectTopmostDirectoryPath()}.
     */
    public static function createFromConfigsBottomUpHierarchy(
        ?string $bottommostDirectoryPath = null,
        ?string $topmostDirectoryPath = null,
        bool $throwOnException = false,
    ): static {
        $envConfig = new EnvironmentConfig();

        if (null === $bottommostDirectoryPath) {
            $bottommostDirectoryPath = static::detectBottommostDirectoryPath();
        }

        $bottommostDirectoryPathValidated = null !== $bottommostDirectoryPath
            ? realpath($bottommostDirectoryPath)
            : false;
        if (false === $bottommostDirectoryPathValidated || !is_readable($bottommostDirectoryPathValidated)) {
            if (!$throwOnException) {
                return $envConfig;
            }

            throw new RuntimeException(
                'Unable to read the bottommost directory: ' . var_export($bottommostDirectoryPath, true),
            );
        }

        if (null === $topmostDirectoryPath) {
            $topmostDirectoryPath = static::detectTopmostDirectoryPath($bottommostDirectoryPathValidated);
        }

        $topmostDirectoryPathValidated = realpath($topmostDirectoryPath);
        if (false === $topmostDirectoryPathValidated || !is_readable($topmostDirectoryPathValidated)) {
            if (!$throwOnException) {
                return $envConfig;
            }

            throw new RuntimeException(
                'Unable to read the topmost directory: ' . var_export($topmostDirectoryPath, true),
            );
        }

        $currentDirPath = $bottommostDirectoryPathValidated;
        while (true) {
            $configPath = $currentDirPath . '/' . static::CONFIG_FILENAME;
            if (file_exists($configPath)) {
                $envConfig->fillFromJsonConfigFile($configPath, $throwOnException);

                // Values from "closer" config files are prioritized over "farther" config files.
                // Thus, if all properties are initialized from already detected files, we should stop the search.
                if ($envConfig->haveFilesInitializedAllProperties()) {
                    return $envConfig;
                }
            }

            if ($currentDirPath === $topmostDirectoryPath) {
                return $envConfig;
            }

            $currentDirPath = dirname($currentDirPath);
        }
    }

    protected static function detectBottommostDirectoryPath(): ?string {
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return $debugBacktrace[array_key_last($debugBacktrace)]['file'] ?? null;
    }

    /**
     * Performs bottom-up search for a path to a `vendor` directory located closest to `/`. If fails to find one,
     * eventually returns a topmost directory path in a file system.
     *
     * For example: the function will return `/home/user/cool-project`, if starts searching from
     * `/home/user/cool-project/vendor/sup-project/vendor/MagicPush/cli-tool/src/Parametizer/EnvironmentConfig`
     */
    protected static function detectTopmostDirectoryPath(string $bottommostDirectoryPath): string {
        // Here $bottommostDirectoryPath should have been validated earlier and transformed into an absolute path.

        $currentDirPath            = $bottommostDirectoryPath;
        $highestDirPathAboveVendor = null;
        while (true) {
            if (file_exists($currentDirPath . '/vendor')) {
                $highestDirPathAboveVendor = $currentDirPath;
            }

            $previousDirPath = $currentDirPath;
            $currentDirPath  = dirname($previousDirPath);
            // We can't go higher than a filesystem's top:
            if ($currentDirPath === $previousDirPath) {
                return $highestDirPathAboveVendor ?? $currentDirPath;
            }
        }
    }
}
