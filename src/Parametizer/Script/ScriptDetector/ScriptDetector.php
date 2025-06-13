<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptDetector;

use Exception;
use FilesystemIterator;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class ScriptDetector {
    protected ?string $cacheFilePath = null;

    /**
     * @var array<ScriptAbstract|string, ScriptAbstract|string> [both keys and values] (string) Fully Qualified class
     * name that extends {@see ScriptAbstract}.
     */
    protected array $searchedFQClassNames = [];

    /** @var array<string, SearchDirectoryContext> (string) searching real path => {@see SearchDirectoryContext} */
    protected array $searchingDirectories = [];

    /** @var string[] */
    protected array $excludedDirectoryPaths = [];

    /** @var ScriptAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptAbstract} */
    protected array $detectedFQClassNames = [];

    /**
     * @var array<string, ScriptAbstract|string> (string) script name =>
     *                                           (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    protected array $detectedFQClassNamesByScriptNames = [];


    protected function clearMemoryCache(): void {
        $this->detectedFQClassNames              = [];
        $this->detectedFQClassNamesByScriptNames = [];
    }


    /**
     * @param bool $throwOnException Useful to debug issues with paths (read / write).
     */
    public function __construct(protected readonly bool $throwOnException = false) { }

    public function cacheFilePath(?string $cacheFilePath): static {
        $cacheFilePathReal = null !== $cacheFilePath ? realpath($cacheFilePath) : false;

        $this->cacheFilePath = $cacheFilePathReal ?: $cacheFilePath;

        return $this;
    }

    public function getCacheFilePath(): ?string {
        return $this->cacheFilePath;
    }

    public function doesCacheFileExist(): bool {
        return null !== $this->cacheFilePath && file_exists($this->cacheFilePath);
    }

    /**
     * @param string $fQClassName Fully qualified class name that extends {@see ScriptAbstract}
     */
    public function scriptClassName(string $fQClassName): static {
        if (array_key_exists($fQClassName, $this->searchedFQClassNames)) {
            if ($this->throwOnException) {
                throw new RuntimeException("Duplicate fully qualified class name search requested: {$fQClassName}");
            }

            return $this;
        }

        $this->searchedFQClassNames[$fQClassName] = $fQClassName;

        return $this;
    }

    /**
     * @param ScriptAbstract|string[] $fQClassNames (string) Fully Qualified class name
     *                                              that extends {@see ScriptAbstract}
     */
    public function scriptClassNames(array $fQClassNames): static {
        foreach ($fQClassNames as $fQClassName) {
            $this->scriptClassName($fQClassName);
        }

        return $this;
    }

    protected function getValidatedRealPath(string $path): ?string {
        $pathTrimmed   = trim($path);
        $pathValidated = realpath($pathTrimmed);

        if (
            '' === $pathTrimmed             // realpath('') renders current working directory.
            || false === $pathValidated
            || !is_readable($pathValidated)
            || !is_dir($pathValidated)
        ) {
            if ($this->throwOnException) {
                throw new RuntimeException('Path should be a readable directory: ' . var_export($path, true));
            }

            return null;
        }

        return $pathValidated;
    }

    public function searchDirectory(string $path, bool $isRecursive = true): static {
        $normalizedPath = $this->getValidatedRealPath($path);
        if (null === $normalizedPath) {
            return $this;
        }

        if (array_key_exists($normalizedPath, $this->searchingDirectories)) {
            if ($this->throwOnException) {
                throw new RuntimeException(
                    sprintf(
                        "Duplicate searching directory path: %s (raw value: '%s')",
                        $normalizedPath,
                        $path,
                    ),
                );
            }

            return $this;
        }

        foreach ($this->searchingDirectories as $index => $searchDirectoryContext) {
            // Let's compare paths from both sides:
            if (
                $isRecursive
                && str_starts_with($searchDirectoryContext->normalizedPath, "{$normalizedPath}/")
            ) {
                if ($this->throwOnException) {
                    throw new RuntimeException(
                        sprintf(
                            "Just added directory's searching recursive scope '%s' (raw value: '%s')"
                                . " includes previously added directory path '%s'",
                            $normalizedPath,
                            $path,
                            $searchDirectoryContext->normalizedPath,
                        ),
                    );
                }

                // "Wider" paths should be added instead of more "narrow" ones.
                unset($this->searchingDirectories[$index]);
                if (!array_key_exists($normalizedPath, $this->searchingDirectories)) {
                    $this->searchingDirectories[$normalizedPath] = new SearchDirectoryContext(
                        $normalizedPath,
                        $isRecursive,
                    );
                }

                // Let's continue the loop and remove other possible "narrow" paths...
            } elseif (
                $searchDirectoryContext->isRecursive
                && str_starts_with($normalizedPath, "{$searchDirectoryContext->normalizedPath}/")
            ) {
                if ($this->throwOnException) {
                    throw new RuntimeException(
                        sprintf(
                            "Previously added directory's searching recursive scope '%s'"
                                . " includes just added directory path '%s' (raw value: '%s')",
                            $searchDirectoryContext->normalizedPath,
                            $normalizedPath,
                            $path,
                        ),
                    );
                }

                return $this;
            }
        }

        $this->searchingDirectories[$normalizedPath] = new SearchDirectoryContext($normalizedPath, $isRecursive);

        return $this;
    }

    /**
     * @param string[] $paths
     */
    public function searchDirectories(array $paths, bool $isRecursive = true): static {
        foreach ($paths as $path) {
            $this->searchDirectory($path, $isRecursive);
        }

        return $this;
    }

    public function excludeDirectory(string $path): static {
        $normalizedPath = $this->getValidatedRealPath($path);
        if (null === $normalizedPath) {
            return $this;
        }

        if (array_key_exists($normalizedPath, $this->excludedDirectoryPaths)) {
            if ($this->throwOnException) {
                throw new RuntimeException(
                    sprintf(
                        "Duplicate excluded directory path: %s (raw value: '%s')",
                        $normalizedPath,
                        $path,
                    ),
                );
            }

            return $this;
        }

        foreach ($this->excludedDirectoryPaths as $index => $alreadyExcludedDirectoryPath) {
            // Let's compare paths from both sides:
            if (str_starts_with($alreadyExcludedDirectoryPath, "{$normalizedPath}/")) {
                if ($this->throwOnException) {
                    throw new RuntimeException(
                        sprintf(
                            "Just excluded directory '%s' (raw value: '%s')"
                                . " incorporates previously excluded directory path '%s'",
                            $normalizedPath,
                            $path,
                            $alreadyExcludedDirectoryPath,
                        ),
                    );
                }

                // "Wider" paths should be added instead of more "narrow" ones.
                unset($this->excludedDirectoryPaths[$index]);
                if (!array_key_exists($normalizedPath, $this->excludedDirectoryPaths)) {
                    $this->excludedDirectoryPaths[$normalizedPath] = $normalizedPath;
                }

                // Let's continue the loop and remove other possible "narrow" paths...
            } elseif (str_starts_with($normalizedPath, "{$alreadyExcludedDirectoryPath}/")) {
                if ($this->throwOnException) {
                    throw new RuntimeException(
                        sprintf(
                            "Previously excluded directory '%s'"
                                . " incorporates just excluded directory path '%s' (raw value: '%s')",
                            $alreadyExcludedDirectoryPath,
                            $normalizedPath,
                            $path,
                        ),
                    );
                }

                return $this;
            }
        }

        $this->excludedDirectoryPaths[$normalizedPath] = $normalizedPath;

        return $this;
    }

    /**
     * @param string[] $paths
     */
    public function excludeDirectories(array $paths): static {
        foreach ($paths as $path) {
            $this->excludeDirectory($path);
        }

        return $this;
    }

    protected function detect(): void {
        $this->clearMemoryCache();

        if ($this->doesCacheFileExist()) {
            $this->detectFromCache();

            return;
        }

        $this->detectBySettings();
    }

    protected function validateSearchingAndExcludedPathsIntersections(): void {
        foreach ($this->excludedDirectoryPaths as $excludedPath) {
            $excludedPathWithSlash   = "{$excludedPath}/";
            $isExcludedPathUnrelated = true;

            foreach ($this->searchingDirectories as $searchIndex => $searchDirectoryContext) {
                $searchingPathWithSlash = "{$searchDirectoryContext->normalizedPath}/";

                // Searching path is completely excluded:
                if (str_starts_with($searchingPathWithSlash, $excludedPathWithSlash)) {
                    if ($this->throwOnException) {
                        throw new RuntimeException(
                            "Excluded path '{$excludedPath}' fully excludes"
                            . " searching path '{$searchDirectoryContext->normalizedPath}'",
                        );
                    }

                    /**
                     * Here goes the tricky part.
                     *
                     * In the edge case when throwing is disabled, and some searching paths are fully excluded
                     * by respective exclusion paths, no files should be found by logic.
                     * Unfortunately, {@see RecursiveCallbackFilterIterator} does not process the very parent (starting)
                     * directory, so in such a case the files located inside the starting directory will still be detected.
                     *
                     * To counter it (and disable files detection in that directory),
                     * the corresponding searching directory should be removed.
                     */
                    unset($this->searchingDirectories[$searchIndex]);
                }

                if (
                    $searchDirectoryContext->isRecursive
                    && str_starts_with($excludedPathWithSlash, $searchingPathWithSlash)
                ) {
                    $isExcludedPathUnrelated = false;
                }
            }

            if ($isExcludedPathUnrelated && $this->throwOnException) {
                throw new RuntimeException(
                    "Excluded path '{$excludedPath}' is not related to any of specified searching paths.",
                );
            }
        }
    }

    protected function detectBySettings(): void {
        if (!$this->searchedFQClassNames && !$this->searchingDirectories) {
            if ($this->throwOnException) {
                throw new RuntimeException('There are no search settings specified.');
            }

            return;
        }

        $this->validateSearchingAndExcludedPathsIntersections();

        foreach ($this->searchedFQClassNames as $fQClassName) {
            if (!is_subclass_of($fQClassName, ScriptAbstract::class)) {
                continue;
            }

            $this->detectedFQClassNames[] = $fQClassName;
        }

        foreach ($this->searchingDirectories as $searchDirectoryContext) {
            $directoryIterator = new RecursiveDirectoryIterator(
                $searchDirectoryContext->normalizedPath,
                FilesystemIterator::SKIP_DOTS,
            );

            if ($searchDirectoryContext->isRecursive) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveCallbackFilterIterator(
                        $directoryIterator,
                        function (SplFileInfo $item): bool {
                            if (!$item->isDir()) {
                                return true;
                            }

                            if (in_array($item->getRealPath(), $this->excludedDirectoryPaths)) {
                                return false;
                            }

                            return true;
                        },
                    ),
                );
            } else {
                $files = $directoryIterator;
            }

            /** @var SplFileInfo[] $files */
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                if (false === $filePath || !$file->isReadable()) {
                    continue;
                }

                if ('php' !== $file->getExtension()) {
                    continue;
                }

                $fileContents = file_get_contents($filePath);
                if (!$fileContents) {
                    continue;
                }

                if (preg_match('/' . PHP_EOL . 'abstract [a-z ]*class .+' . PHP_EOL . '?{/', $fileContents)) {
                    continue;
                }

                $fileNamespace = null;
                if (preg_match('/' . PHP_EOL . 'namespace\s+(\S+);' . PHP_EOL . '/', $fileContents, $matches)) {
                    $fileNamespace = $matches[1];
                }

                if (
                    !preg_match(
                        '/' . PHP_EOL . '[a-z ]*class (.+) extends .+' . PHP_EOL . '?{/',
                        $fileContents,
                        $matches,
                    )
                ) {
                    continue;
                }
                $fileClassName = $matches[1];

                if (null === $fileClassName) {
                    continue;
                }

                $fullyQualifiedName = '';
                if (null !== $fileNamespace) {
                    $fullyQualifiedName .= "{$fileNamespace}\\";
                }
                $fullyQualifiedName .= $fileClassName;

                if (!is_subclass_of($fullyQualifiedName, ScriptAbstract::class)) {
                    continue;
                }

                $this->detectedFQClassNames[] = $fullyQualifiedName;
            }
        }

        $this->storeDetectedToCache();
    }

    protected function detectFromCache(): void {
        $cacheFileContents = is_readable($this->cacheFilePath) ? (string) file_get_contents($this->cacheFilePath) : '';
        if ('' === $cacheFileContents) {
            if ($this->throwOnException) {
                throw new RuntimeException(
                    'Could not read the cache file: ' . var_export($this->cacheFilePath, true),
                );
            }

            return;
        }

        try {
            $rawFQClassNames = json_decode(
                json: file_get_contents($this->cacheFilePath),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (Exception $e) {
            if (!$this->throwOnException) {
                return;
            }

            throw new RuntimeException(
                "Unable to read the cache file '" . var_export($this->cacheFilePath, true) . "': {$e->getMessage()}",
            );
        }
        foreach ($rawFQClassNames as $fQClassName) {
            if (!is_subclass_of($fQClassName, ScriptAbstract::class)) {
                if ($this->throwOnException) {
                    throw new RuntimeException("'{$fQClassName}' is not a subclass of " . ScriptAbstract::class);
                }

                continue;
            }

            $this->detectedFQClassNames[] = $fQClassName;
        }
    }

    protected function storeDetectedToCache(): void {
        if (null === $this->cacheFilePath) {
            return;
        }

        $cacheDirPath = dirname($this->cacheFilePath);
        if (!is_dir($cacheDirPath)) {
            if (!mkdir($cacheDirPath, recursive: true)) {
                if ($this->throwOnException) {
                    throw new RuntimeException(
                        "Unable to create a directory '{$cacheDirPath}' for the cache file: "
                            . var_export($this->cacheFilePath, true),
                    );
                }

                return;
            }
        }

        try {
            $cacheFileContents = json_encode(
                value: $this->detectedFQClassNames,
                flags: JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
            );
        } catch (Exception $e) {
            if (!$this->throwOnException) {
                return;
            }

            throw new RuntimeException(
                "Unable to create JSON contents for the cache file '" . var_export($this->cacheFilePath, true)
                    . "': {$e->getMessage()}",
            );
        }

        if (
            false === file_put_contents($this->cacheFilePath, $cacheFileContents, LOCK_EX)
            && $this->throwOnException
        ) {
            throw new RuntimeException(
                'Unable to write data into the cache file: ' . var_export($this->cacheFilePath, true),
            );
        }

        $cacheFilePathReal = realpath($this->cacheFilePath);
        if (false === $cacheFilePathReal) {
            throw new RuntimeException(
                "Unable to get the realpath from the just created cache file: {$this->cacheFilePath}",
            );
        }
        $this->cacheFilePath = $cacheFilePathReal;
    }

    /**
     * @return array<string, ScriptAbstract|string> (string) script name => (string) Fully Qualified class name
     *                                              that extends {@see ScriptAbstract}
     */
    public function getClassNamesByScriptNames(): array {
        $this->detect();

        $detectedFQClassNamesByScriptNames = [];
        foreach ($this->detectedFQClassNames as $fullyQualifiedName) {
            $detectedFQClassNamesByScriptNames[$fullyQualifiedName::getFullName()] = $fullyQualifiedName;
        }

        return $detectedFQClassNamesByScriptNames;
    }
}
