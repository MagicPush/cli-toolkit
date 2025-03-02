<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script;

use FilesystemIterator;
use PhpToken;
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

                $fileNamespace            = null;
                $fileClassName            = null;
                $isTokenDetectedNamespace = false;
                $isTokenDetectedClass     = false;
                foreach (PhpToken::tokenize($fileContents) as $fileToken) {
                    if (T_ABSTRACT === $fileToken->id) {
                        break;
                    }

                    if ($fileToken->isIgnorable()) {
                        continue;
                    }

                    if (null === $fileNamespace) {
                        if ($isTokenDetectedNamespace && T_NAME_QUALIFIED === $fileToken->id) {
                            $fileNamespace = $fileToken->text;
                        } elseif (T_NAMESPACE === $fileToken->id) {
                            $isTokenDetectedNamespace = true;
                        }
                    }

                    if (null === $fileClassName) {
                        if ($isTokenDetectedClass && T_STRING === $fileToken->id) {
                            $fileClassName = $fileToken->text;

                            // Nothing useful for us below this token,
                            // e.g. 'namespace' can (should) not be defined below a class declaration.
                            break;
                        } elseif (T_CLASS === $fileToken->id) {
                            $isTokenDetectedClass = true;
                        }
                    }
                }
                if (null === $fileClassName) {
                    continue;
                }

                $fullyQualifiedName = '';
                if ($fileNamespace) {
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
