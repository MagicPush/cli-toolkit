<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptDetector;

use Override;

/**
 * @method array<\string, \string> getDetectedData() (string) alias => (string) absolute path
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ScriptFileDetector extends ScriptDetectorAbstract {
    /** @see Parametizer::newConfig() */
    protected const string SUBSTR_PARAMETIZER_CONSTRUCT = 'Parametizer::newConfig(';
    /** @see Parametizer::run() */
    protected const string SUBSTR_PARAMETIZER_EXEC = '->run()';
    /** @see ScriptClassLauncher::__construct() */
    protected const string SUBSTR_LAUNCHER_CONSTRUCT = 'ScriptClassLauncher(';
    /** @see ScriptClassLauncher::execute() */
    protected const string SUBSTR_LAUNCHER_EXEC = '->execute()';


    /** @var array<string, string> (string) alias => (string) absolute path */
    protected array $detectedFilePathsByAliases = [];

    /** @var array<string, string> [both keys and values] Script file absolute paths */
    protected array $searchedScriptPaths = [];


    public function scriptPath(string $scriptPath): static {
        $realPath = realpath($scriptPath);
        try {
            if (false === $realPath) {
                throw new ScriptDetectorRuntimeException("Unable to retrieve the absolute path for '{$scriptPath}'");
            }

            if (array_key_exists($realPath, $this->searchedScriptPaths)) {
                throw new ScriptDetectorRuntimeException("Duplicate script path requested: {$realPath}");
            }
        } catch (ScriptDetectorRuntimeException $e) {
            if (!$this->throwOnException) {
                return $this;
            }

            throw $e;
        }

        $this->searchedScriptPaths[$realPath] = $realPath;

        return $this;
    }

    public function scriptPaths(array $scriptPaths): static {
        foreach ($scriptPaths as $scriptPath) {
            $this->scriptPath($scriptPath);
        }

        return $this;
    }

    /**
     * @param bool $isForExactFile If file contents do not meet certain requirements:
     *                             * `false`: the file will be skipped;
     *                             * `true`: {@see ScriptDetectorRuntimeException} may be thrown,
     *                             if {@see static::$throwOnException} is enabled.
     */
    protected function processDetectedFileContentsInternal(
        string $filePath,
        string $fileContents,
        bool $isForExactFile,
    ): void {
        try {
            $isParametizerScriptDetected =
                (
                    str_contains($fileContents, static::SUBSTR_PARAMETIZER_CONSTRUCT)
                    && str_contains($fileContents, static::SUBSTR_PARAMETIZER_EXEC)
                )
                || (
                    str_contains($fileContents, static::SUBSTR_LAUNCHER_CONSTRUCT)
                    && str_contains($fileContents, static::SUBSTR_LAUNCHER_EXEC)
                );
            if (!$isParametizerScriptDetected) {
                throw new ScriptDetectorRuntimeException(
                    sprintf(
                        "Script file '{$filePath}' should contain one of these pairs of substrings:"
                        . " '%s' + '%s' OR '%s' + '%s'",
                        static::SUBSTR_PARAMETIZER_CONSTRUCT,
                        static::SUBSTR_PARAMETIZER_EXEC,
                        static::SUBSTR_LAUNCHER_CONSTRUCT,
                        static::SUBSTR_LAUNCHER_EXEC,
                    ),
                );
            }
        } catch (ScriptDetectorRuntimeException $e) {
            if (!$isForExactFile || !$this->throwOnException) {
                return;
            }

            throw $e;
        }

        $alias = basename($filePath, '.' . static::FILE_EXTENSION);
        if (array_key_exists($alias, $this->detectedFilePathsByAliases)) {
            if (!$this->throwOnException) {
                return;
            }

            throw new ScriptDetectorRuntimeException(
                "Duplicate script basename '{$alias}' for path '{$filePath}'."
                    . " Already detected: {$this->detectedFilePathsByAliases[$alias]}",
            );
        }

        $this->detectedFilePathsByAliases[$alias] = $filePath;
    }

    #[Override]
    protected function clearMemoryCache(): void {
        $this->detectedFilePathsByAliases = [];
    }

    #[Override]
    protected function hasMinimalCustomSearchSettings(): bool {
        return (bool) $this->searchedScriptPaths;
    }

    #[Override]
    protected function processDetectedFileContents(string $filePath, string $fileContents): void {
        $this->processDetectedFileContentsInternal($filePath, $fileContents, isForExactFile: false);
    }

    #[Override]
    protected function processCustomDetections(): void {
        foreach ($this->searchedScriptPaths as $scriptPath) {
            try {
                if (!is_readable($scriptPath)) {
                    throw new ScriptDetectorRuntimeException("Script file is not readable: {$scriptPath}");
                }

                if (!str_ends_with($scriptPath, '.' . static::FILE_EXTENSION)) {
                    throw new ScriptDetectorRuntimeException(
                        sprintf("Missing extension '%s' for the script file: {$scriptPath}", static::FILE_EXTENSION),
                    );
                }

                $scriptContents = file_get_contents($scriptPath);
                if (false === $scriptContents) {
                    throw new ScriptDetectorRuntimeException("Unable to read script file contents: {$scriptPath}");
                }

                $this->processDetectedFileContentsInternal($scriptPath, $scriptContents, isForExactFile: true);
            } catch (ScriptDetectorRuntimeException $e) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw $e;
            }
        }
    }

    #[Override]
    /**
     * @param array<string, string> $dataFromCache (string) alias => (string) absolute path,
     *                                             same as {@see static::$detectedFilePathsByAliases}
     */
    protected function loadDataFromCache(array $dataFromCache): void {
        foreach ($dataFromCache as $alias => $filePath) {
            if (!is_readable($filePath)) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw new ScriptDetectorRuntimeException("Script file is not readable or does not exist: {$filePath}");
            }

            $this->detectedFilePathsByAliases[$alias] = $filePath;
        }
    }

    #[Override]
    protected function getDataToStoreInCache(): array {
        return $this->detectedFilePathsByAliases;
    }

    #[Override]
    /**
     * @return array<string, string> getDetectedData() (string) alias => (string) absolute path
     */
    protected function getDataProcessedAfterDetection(): array {
        return $this->detectedFilePathsByAliases;
    }
}
