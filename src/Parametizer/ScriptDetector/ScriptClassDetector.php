<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use Override;

/**
 * @method array<\string, ScriptAbstract|\string> getDetectedData() (string) script name => (string) Fully Qualified class name that extends {@see ScriptAbstract}
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ScriptClassDetector extends ScriptDetectorAbstract {
    /**
     * @var array<ScriptAbstract|string, ScriptAbstract|string> [both keys and values] (string) Fully Qualified class
     * name that extends {@see ScriptAbstract}.
     */
    protected array $searchedFQClassNames = [];

    /** @var ScriptAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptAbstract} */
    protected array $detectedFQClassNames = [];


    /**
     * @param string $fQClassName Fully qualified class name that extends {@see ScriptAbstract}
     */
    public function scriptClassName(string $fQClassName): static {
        if (array_key_exists($fQClassName, $this->searchedFQClassNames)) {
            if (!$this->throwOnException) {
                return $this;
            }

            throw new ScriptDetectorRuntimeException(
                "Duplicate fully qualified class name search requested: {$fQClassName}",
            );
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

    #[Override]
    protected function clearMemoryCache(): void {
        $this->detectedFQClassNames = [];
    }

    #[Override]
    protected function hasMinimalCustomSearchSettings(): bool {
        return (bool) $this->searchedFQClassNames;
    }

    #[Override]
    protected function processDetectedFileContents(string $filePath, string $fileContents): void {
        if (preg_match('/' . PHP_EOL . 'abstract [a-z ]*class .+' . PHP_EOL . '?{/', $fileContents)) {
            return;
        }

        $classNamespace = null;
        if (preg_match('/' . PHP_EOL . 'namespace\s+(\S+);' . PHP_EOL . '/', $fileContents, $matches)) {
            $classNamespace = $matches[1];
        }

        if (
            !preg_match(
                '/' . PHP_EOL . '[a-z ]*class (\S+) extends .+' . PHP_EOL . '?{/',
                $fileContents,
                $matches,
            )
        ) {
            return;
        }
        $fullyQualifiedClassName = $matches[1];

        if (null !== $classNamespace) {
            $fullyQualifiedClassName = "{$classNamespace}\\{$fullyQualifiedClassName}";
        }

        if (!is_subclass_of($fullyQualifiedClassName, ScriptAbstract::class)) {
            return;
        }

        $this->detectedFQClassNames[] = $fullyQualifiedClassName;
    }

    #[Override]
    protected function processCustomDetections(): void {
        $baseFQClassName = ScriptAbstract::class;

        foreach ($this->searchedFQClassNames as $fQClassName) {
            if (!is_subclass_of($fQClassName, $baseFQClassName)) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw new ScriptDetectorRuntimeException("'{$fQClassName}' must be a subclass of '{$baseFQClassName}'");
            }

            $this->detectedFQClassNames[] = $fQClassName;
        }
    }

    #[Override]
    /**
     * @param ScriptAbstract[]|string[] $dataFromCache (string) Fully Qualified class name
     *                                                 that extends {@see ScriptAbstract}
     */
    protected function loadDataFromCache(array $dataFromCache): void {
        foreach ($dataFromCache as $fQClassName) {
            if (!is_subclass_of($fQClassName, ScriptAbstract::class)) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw new ScriptDetectorRuntimeException(
                    "'{$fQClassName}' is not a subclass of " . ScriptAbstract::class,
                );
            }

            $this->detectedFQClassNames[] = $fQClassName;
        }
    }

    #[Override]
    /**
     * @return ScriptAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    protected function getDataToStoreInCache(): array {
        return $this->detectedFQClassNames;
    }

    #[Override]
    protected function getDataProcessedAfterDetection(): array {
        $detectedFQClassNamesByScriptNames = [];
        foreach ($this->detectedFQClassNames as $fullyQualifiedClassName) {
            $scriptName = $fullyQualifiedClassName::getFullName();
            if (array_key_exists($scriptName, $detectedFQClassNamesByScriptNames)) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw new ScriptDetectorRuntimeException(
                    "Duplicate script name '{$scriptName}' detected in class '{$fullyQualifiedClassName}'."
                        . " Already registered class: {$detectedFQClassNamesByScriptNames[$scriptName]}",
                );
            }

            $detectedFQClassNamesByScriptNames[$scriptName] = $fullyQualifiedClassName;
        }

        return $detectedFQClassNamesByScriptNames;
    }
}
