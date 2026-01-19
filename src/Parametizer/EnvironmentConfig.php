<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer;

use Exception;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ShowHelpPage;
use MagicPush\CliToolkit\Utils;
use RuntimeException;
use TypeError;

class EnvironmentConfig {
    public const string CONFIG_FILENAME = 'parametizer.env.json';


    /* AVAILABLE PROPERTIES -> */

    /**
     * @var int Padding size for each line from the left in {@see ListSubcommands} output.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $listPaddingLeftMain               = 1;
    /**
     * @var int Padding size each command or commands header from the left in {@see ListSubcommands} output.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $listPaddingLeftCommand            = 2;
    /**
     * @var int Length of a gap between a command name and its description in {@see ListSubcommands} output.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $listPaddingLeftCommandDescription = 4;

    /**
     * @var int Padding size for almost each line (except headers) from the left in {@see ShowHelpPage} output.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $helpGeneratorPaddingLeftMain                        = 2;
    /**
     * @var int Minimal length of a gap between a parameter name and its description
     * in {@see ShowHelpPage} output.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $helpGeneratorPaddingLeftParameterDescription        = 4;
    /**
     * @var int Minimum length of a short description before ". " (to trim after the whole sentence);
     * mainly affects {@see ListSubcommands} output for subcommands.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $helpGeneratorShortDescriptionCharsMinBeforeFullStop = 40;
    /**
     * @var int Maximum length of a short description; mainly affects {@see ListSubcommands} output for subcommands.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $helpGeneratorShortDescriptionCharsMax               = 70;
    /**
     * @var int Maximum amount of non-required options allowed to be shown in {@see ShowHelpPage} usage template(s)
     * (instead of a single "[options]" substring).
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public int $helpGeneratorUsageNonRequiredOptionsMax             = 5;

    /**
     * @var string|null Short name for `--help` ({@see Config::PARAMETER_NAME_HELP}) parameter.
     * `null` disables a short name.
     * <br />See {@see ../../docs/features-manual.md} for details about each available setting.
     */
    public ?string $optionHelpShortName = null;

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
     *                                             where a script config might be located.
     * @param string|null $topmostDirectoryPath    The method will not search config files above this directory.
     *                                             If `null`, will try to detect a path via
     *                                             {@see Utils::detectTopmostProjectRootDirectory()}.
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
            $topmostDirectoryPath = Utils::detectTopmostProjectRootDirectory();
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

            $previousDirPath = $currentDirPath;
            $currentDirPath  = dirname($currentDirPath);
            // Prevents a possible endless loop, if `$topmostDirectoryPath` is unreachable:
            if ($currentDirPath === $previousDirPath) {
                return $envConfig;
            }
        }
    }

    protected static function detectBottommostDirectoryPath(): ?string {
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return $debugBacktrace[array_key_last($debugBacktrace)]['file'] ?? null;
    }
}
