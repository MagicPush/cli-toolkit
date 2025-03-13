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
    protected array $searchClassPaths = [];

    /** @var array<string, ScriptAbstract> (string) file path => (string) Fully Qualified Class name */
    protected array $detectedFQClassNames = [];

    /** @var array<string, ScriptAbstract> (string) script name => (string) Fully Qualified Class name */
    protected array $detectedFQClassNamesByScriptNames = [];


    protected function clearCache(): static {
        $this->detectedFQClassNames              = [];
        $this->detectedFQClassNamesByScriptNames = [];

        return $this;
    }


    public function __construct(protected bool $throwOnException = false) { }

    public function addSearchClassPath(string $path): static {
        $pathValidated = realpath(trim($path));
        if (false !== $pathValidated && is_readable($pathValidated)) {
            $this->searchClassPaths[] = $pathValidated;
        } elseif ($this->throwOnException) {
            throw new RuntimeException('Script detector >>> Path is unreadable: ' . var_export($path, true));
        }

        return $this;
    }

    public function addSearchClassPaths(array $paths): static {
        foreach ($paths as $path) {
            $this->addSearchClassPath($path);
        }

        return $this;
    }

    public function detect(): static {
        $this->clearCache();

        foreach ($this->searchClassPaths as $searchClassPath) {
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchClassPath, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($files as $file) {
                if ('php' !== $file->getExtension()) {
                    continue;
                }

                $filePath = $file->getRealPath();
                if (false === $filePath) {
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
     * @return array<string, ScriptAbstract> (string) script name => (string) Fully Qualified Class name
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
