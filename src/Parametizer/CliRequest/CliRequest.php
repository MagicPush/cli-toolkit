<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\CliRequest;

use LogicException;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class CliRequest {
    /**
     * @param mixed[] $params
     */
    public function __construct(
        public readonly Config $config,
        protected readonly array $params,
        public readonly ?self $parent = null,
    ) {
    }

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
     * Returns a subcommand name specified in a subcommand switch parameter,
     * or `null` if there is no subcommand switch.
     */
    public function getSubcommandRequestName(): ?string {
        $subcommandSwitchName = $this->config->getSubcommandSwitchName();
        if (null === $subcommandSwitchName) {
            return null;
        }

        return $this->getParamAsString($subcommandSwitchName);
    }

    /**
     * Returns a subcommand request by a name specified in a subcommand switch parameter
     * or `null` if there is no subcommand switch.
     */
    public function getSubcommandRequest(): ?static {
        $subcommandName = $this->getSubcommandRequestName();
        if (null === $subcommandName) {
            return null;
        }

        $subcommandConfig = $this->config->getBranch($subcommandName);
        if (null === $subcommandConfig) {
            throw new LogicException(
                "Subcommand '" . HelpFormatter::createForStdErr()->paramTitle($subcommandName) . "' not found",
            );
        }

        $subcommandParameterValues = $this->getParam($subcommandName);
        self::validateValueIsArray(
            $this->config->getSubcommandSwitchName() . ' > ' . $subcommandName,
            $subcommandParameterValues,
        );

        return new static($subcommandConfig, $subcommandParameterValues, $this);
    }

    public function executeBuiltInSubcommandIfRequested(): void {
        $innermostSubcommandName          = null;
        $innermostSubcommandParentRequest = null;
        $innermostSubcommandRequest       = $this;
        while ($subcommandName = $innermostSubcommandRequest->getSubcommandRequestName()) {
            $innermostSubcommandName          = $subcommandName;
            $innermostSubcommandParentRequest = $innermostSubcommandRequest;
            $innermostSubcommandRequest       = $innermostSubcommandParentRequest->getSubcommandRequest();
        }

        // No parent request means there was no subcommand request.
        if (null === $innermostSubcommandParentRequest) {
            return;
        }

        $builtInSubcommandClass = $innermostSubcommandParentRequest->config
            ->getBuiltInSubcommandClass($innermostSubcommandName);
        if (null === $builtInSubcommandClass) {
            return;
        }

        (new $builtInSubcommandClass($innermostSubcommandRequest))->execute();

        // For a built-in subcommand we should not execute any code outside of that subcommand.
        // So, for instance, a subcommand launcher script will not try to execute a built-in subcommand twice.
        exit;
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
