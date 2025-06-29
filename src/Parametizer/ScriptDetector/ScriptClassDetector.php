<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use Override;
use RuntimeException;

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

    #[Override]
    protected function clearMemoryCache(): void {
        $this->detectedFQClassNames = [];
    }

    #[Override]
    protected function hasMinimalSearchSettings(): bool {
        return (bool) $this->searchedFQClassNames;
    }

    #[Override]
    protected function processDetectedFileContents(string $filePath, string $fileContents): void {
        if (preg_match('/' . PHP_EOL . 'abstract [a-z ]*class .+' . PHP_EOL . '?{/', $fileContents)) {
            return;
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
            return;
        }
        $fileClassName = $matches[1];

        if (null === $fileClassName) {
            return;
        }

        $fullyQualifiedName = '';
        if (null !== $fileNamespace) {
            $fullyQualifiedName .= "{$fileNamespace}\\";
        }
        $fullyQualifiedName .= $fileClassName;

        if (!is_subclass_of($fullyQualifiedName, ScriptAbstract::class)) {
            return;
        }

        $this->detectedFQClassNames[] = $fullyQualifiedName;
    }

    #[Override]
    protected function processCustomDetections(): void {
        foreach ($this->searchedFQClassNames as $fQClassName) {
            if (!is_subclass_of($fQClassName, ScriptAbstract::class)) {
                continue;
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
                if ($this->throwOnException) {
                    throw new RuntimeException("'{$fQClassName}' is not a subclass of " . ScriptAbstract::class);
                }

                continue;
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
    /**
     * @return array<string, ScriptAbstract|string> (string) script name => (string) Fully Qualified class name
     *                                              that extends {@see ScriptAbstract}
     */
    protected function getDataProcessedAfterDetection(): array {
        $detectedFQClassNamesByScriptNames = [];
        foreach ($this->detectedFQClassNames as $fullyQualifiedName) {
            $detectedFQClassNamesByScriptNames[$fullyQualifiedName::getFullName()] = $fullyQualifiedName;
        }

        return $detectedFQClassNamesByScriptNames;
    }
}
