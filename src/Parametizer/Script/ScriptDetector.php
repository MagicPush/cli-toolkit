<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class ScriptDetector {
    /** @var string[] */
    protected array $searchedClassPaths = [];

    /**
     * @var array<string, ScriptAbstract|string> (string) file path =>
     *                                           (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    protected array $detectedFQClassNames = [];

    /**
     * @var array<string, ScriptAbstract|string> (string) script name =>
     *                                           (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    protected array $detectedFQClassNamesByScriptNames = [];


    protected function clearCache(): static {
        $this->detectedFQClassNames              = [];
        $this->detectedFQClassNamesByScriptNames = [];

        return $this;
    }


    public function __construct(protected bool $throwOnException = false) { }

    protected function validateSearchPath(string $path): ?string {
        $pathValidated = realpath(trim($path));
        if (false === $pathValidated || !is_readable($pathValidated)) {
            if ($this->throwOnException) {
                throw new RuntimeException('Script detector >>> Path is unreadable: ' . var_export($path, true));
            }

            $pathValidated = null;
        }

        return $pathValidated;
    }

    public function searchClassPath(string $path): static {
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
        $this->clearCache();

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
                if (false === mb_strpos($fileContents, ' extends ')) {
                    continue;
                }

                if (preg_match('/' . PHP_EOL . 'abstract [a-z ]*class .+' . PHP_EOL . '?{/', $fileContents)) {
                    continue;
                }

                $fileNamespace = null;
                if (preg_match('/' . PHP_EOL . 'namespace\s+(\S+);' . PHP_EOL . '/', $fileContents, $matches)) {
                    $fileNamespace = $matches[1];
                }

                if (!preg_match('/' . PHP_EOL . '[a-z ]*class (.+) extends .+' . PHP_EOL . '?{/', $fileContents, $matches)) {
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

                $this->detectedFQClassNames[$filePath] = $fullyQualifiedName;
            }
        }

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
        ksort($this->detectedFQClassNamesByScriptNames, SORT_NATURAL);

        return $this->detectedFQClassNamesByScriptNames;
    }
}
