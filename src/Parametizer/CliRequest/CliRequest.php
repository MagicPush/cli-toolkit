<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\CliRequest;

use LogicException;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class CliRequest {
    /**
     * @param mixed[] $params
     */
    public function __construct(protected readonly Config $config, protected readonly array $params = []) { }

    /**
     * @return mixed[]
     */
    public function getParams(): array {
        return $this->params;
    }

    public function getParam(string $paramName): mixed {
        if (!array_key_exists($paramName, $this->params)) {
            throw new LogicException(
                "Parameter '" . HelpFormatter::createForStdErr()->paramTitle($paramName) . "' not found in the request."
                    . ' The parameters being parsed: ' . implode(', ', array_keys($this->params)),
            );
        }

        return $this->params[$paramName];
    }

    /**
     * Returns a subcommand request by a name specified for a subcommand switch parameter.
     */
    public function getSubcommandRequest(string $subcommandName): static {
        $subcommandConfig = $this->config->getBranch($subcommandName);
        if (!$subcommandConfig) {
            throw new LogicException(
                "Subcommand '" . HelpFormatter::createForStdErr()->paramTitle($subcommandName) . "' not found",
            );
        }

        return new static($subcommandConfig, $this->getParam($subcommandName));
    }

    private static function validateValueIsArray(string $paramName, mixed $paramValue): void {
        if (is_array($paramValue)) {
            return;
        }

        throw new LogicException(
            "Parameter '" . HelpFormatter::createForStdErr()->paramTitle($paramName) . "' contains a single value",
        );
    }

    private static function validateValueNotArray(string $paramName, mixed $paramValue): void {
        if (!is_array($paramValue)) {
            return;
        }

        throw new LogicException(
            "Parameter '" . HelpFormatter::createForStdErr()->paramTitle($paramName) . "' contains an array",
        );
    }

    public function getParamAsBool(string $paramName): bool {
        $paramValue = $this->getParam($paramName);
        self::validateValueNotArray($paramName, $paramValue);

        return (bool) $paramValue;
    }

    public function getParamAsInt(string $paramName): int {
        $paramValue = $this->getParam($paramName);
        self::validateValueNotArray($paramName, $paramValue);

        return (int) $paramValue;
    }

    /**
     * @return int[]
     */
    public function getParamAsIntList(string $paramName): array {
        $paramValue = $this->getParam($paramName);
        self::validateValueIsArray($paramName, $paramValue);

        array_walk(
            $paramValue,
            function (&$elementValue) {
                $elementValue = (int) $elementValue;
            },
        );

        return $paramValue;
    }

    public function getParamAsFloat(string $paramName): float {
        $paramValue = $this->getParam($paramName);
        self::validateValueNotArray($paramName, $paramValue);

        return (float) $paramValue;
    }

    /**
     * @return float[]
     */
    public function getParamAsFloatList(string $paramName): array {
        $paramValue = $this->getParam($paramName);
        self::validateValueIsArray($paramName, $paramValue);

        array_walk(
            $paramValue,
            function (&$elementValue) {
                $elementValue = (float) $elementValue;
            },
        );

        return $paramValue;
    }

    public function getParamAsString(string $paramName): string {
        $paramValue = $this->getParam($paramName);
        self::validateValueNotArray($paramName, $paramValue);

        return (string) $paramValue;
    }

    /**
     * @return string[]
     */
    public function getParamAsStringList(string $paramName): array {
        $paramValue = $this->getParam($paramName);
        self::validateValueIsArray($paramName, $paramValue);

        array_walk(
            $paramValue,
            function (&$elementValue) {
                $elementValue = (string) $elementValue;
            },
        );

        return $paramValue;
    }
}
