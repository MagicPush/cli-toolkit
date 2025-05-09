<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script;

use FilesystemIterator;
use MagicPush\CliToolkit\Parametizer\Script\Subcommand\ScriptAbstract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class ScriptDetector {
    protected ?string $cacheFilePath = null;

    /** @var string[] */
    protected array $searchedClassPaths = [];

    /** @var ScriptAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptAbstract}. */
    protected array $detectedFQClassNames = [];

    /**
     * @var array<string, ScriptAbstract|string> (string) script name =>
     *                                           (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    protected array $detectedFQClassNamesByScriptNames = [];


    protected function clearMemoryCache(): static {
        $this->detectedFQClassNames              = [];
        $this->detectedFQClassNamesByScriptNames = [];

        return $this;
    }


    public function __construct(protected bool $throwOnException = false) { }

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

    protected function validateSearchPath(?string $path): ?string {
        $pathTrimmed   = trim($path);
        $pathValidated = realpath($pathTrimmed);

        if (
            '' === $pathTrimmed             // realpath('') renders current working directory.
            || false === $pathValidated
            || !is_readable($pathValidated)
        ) {
            if ($this->throwOnException) {
                throw new RuntimeException('Search path is unreadable: ' . var_export($path, true));
            }

            $pathValidated = null;
        }

        return $pathValidated;
    }

    public function searchClassPath(?string $path): static {
        $pathValidated = $this->validateSearchPath($path);
        if (null !== $pathValidated) {
            $this->searchedClassPaths[] = $pathValidated;
        }

        return $this;
    }

    public function searchClassPaths(array $paths): static {
        foreach ($paths as $path) {
            $this->searchClassPath($path);
        }

        return $this;
    }

    public function detect(): static {
        $this->clearMemoryCache();

        if ($this->doesCacheFileExist()) {
            return $this->detectFromCache();
        }

        return $this->detectFromPaths();
    }

    protected function detectFromPaths(): static {
        foreach ($this->searchedClassPaths as $searchClassPath) {
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchClassPath, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($files as $file) {
                if ('php' !== $file->getExtension()) {
                    continue;
                }

                $filePath = $file->getRealPath();
                if (false === $filePath || !$file->isReadable()) {
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

        return $this->storeDetectedToCache();
    }

    protected function detectFromCache(): static {
        $cacheFileContents = is_readable($this->cacheFilePath) ? (string) file_get_contents($this->cacheFilePath) : '';
        if ('' === $cacheFileContents) {
            if ($this->throwOnException) {
                throw new RuntimeException(
                    'Could not read scripts cache file: ' . var_export($this->cacheFilePath, true),
                );
            }

            return $this;
        }

        $jsonDecodeFlags = 0;
        if ($this->throwOnException) {
            $jsonDecodeFlags |= JSON_THROW_ON_ERROR;
        }

        $rawFQClassNames = (array) json_decode(
            json: file_get_contents($this->cacheFilePath),
            associative: true,
            flags: $jsonDecodeFlags,
        );
        foreach ($rawFQClassNames as $fqClassName) {
            if (!is_subclass_of($fqClassName, ScriptAbstract::class)) {
                if ($this->throwOnException) {
                    throw new RuntimeException("'{$fqClassName}' is not a subclass of " . ScriptAbstract::class);
                }

                continue;
            }

            $this->detectedFQClassNames[] = $fqClassName;
        }

        return $this;
    }

    protected function storeDetectedToCache(): static {
        if (null === $this->cacheFilePath) {
            return $this;
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

                return $this;
            }
        }

        $jsonEncodeFlags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        if ($this->throwOnException) {
            $jsonEncodeFlags |= JSON_THROW_ON_ERROR;
        }
        $cacheFileContents = json_encode($this->detectedFQClassNames, flags: $jsonEncodeFlags);

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
                "Unable to get the realpath from just created cache file: {$this->cacheFilePath}",
            );
        }
        $this->cacheFilePath = $cacheFilePathReal;

        return $this;
    }

    /**
     * @return array<string, ScriptAbstract|string> (string) script name =>
     *                                              (string) Fully Qualified class name
     *                                              that extends {@see ScriptAbstract}
     */
    public function getFQClassNamesByScriptNames(): array {
        if ($this->detectedFQClassNamesByScriptNames) {
            return $this->detectedFQClassNamesByScriptNames;
        }

        foreach ($this->detectedFQClassNames as $fullyQualifiedName) {
            $this->detectedFQClassNamesByScriptNames[$fullyQualifiedName::getFullName()] = $fullyQualifiedName;
        }

        return $this->detectedFQClassNamesByScriptNames;
    }
}
