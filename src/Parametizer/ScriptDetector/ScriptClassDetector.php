<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use Override;

/**
 * @method array<\string, ScriptClassAbstract|\string> getDetectedData() (string) script name => (string) Fully Qualified class name that extends {@see ScriptClassAbstract}
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ScriptClassDetector extends ScriptDetectorAbstract {
    /**
     * @var array<ScriptClassAbstract|string, ScriptClassAbstract|string> [both keys and values]
     * (string) Fully Qualified class name that extends {@see ScriptClassAbstract}.
     */
    protected array $searchedFQClassNames = [];

    /**
     * @var ScriptClassAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptClassAbstract}
     */
    protected array $detectedFQClassNames = [];


    /**
     * @param string $fQClassName Fully qualified class name that extends {@see ScriptClassAbstract}
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
     * @param ScriptClassAbstract|string[] $fQClassNames (string) Fully Qualified class name
     *                                              that extends {@see ScriptClassAbstract}
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

        if (
            !is_subclass_of($fullyQualifiedClassName, ScriptClassAbstract::class)
            || !$fullyQualifiedClassName::isAvailableByDetector()
        ) {
            return;
        }

        $this->detectedFQClassNames[] = $fullyQualifiedClassName;
    }

    #[Override]
    protected function processCustomDetections(): void {
        $baseFQClassName = ScriptClassAbstract::class;

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
     * @param ScriptClassAbstract[]|string[] $dataFromCache (string) Fully Qualified class name
     *                                                 that extends {@see ScriptClassAbstract}
     */
    protected function loadDataFromCache(array $dataFromCache): void {
        foreach ($dataFromCache as $fQClassName) {
            if (!is_subclass_of($fQClassName, ScriptClassAbstract::class)) {
                if (!$this->throwOnException) {
                    continue;
                }

                throw new ScriptDetectorRuntimeException(
                    "'{$fQClassName}' is not a subclass of " . ScriptClassAbstract::class,
                );
            }

            $this->detectedFQClassNames[] = $fQClassName;
        }
    }

    #[Override]
    /**
     * @return ScriptClassAbstract[]|string[] (string) Fully Qualified class name
     * that extends {@see ScriptClassAbstract}
     */
    protected function getDataToStoreInCache(): array {
        return $this->detectedFQClassNames;
    }

    #[Override]
    protected function getDataProcessedAfterDetection(): array {
        $detectedFQClassNamesByScriptNames = [];
        foreach ($this->detectedFQClassNames as $fullyQualifiedClassName) {
            $scriptName = $fullyQualifiedClassName::getScriptName();
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
