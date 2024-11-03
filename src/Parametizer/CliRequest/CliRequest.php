<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\CliRequest;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;

class CliRequest {
    /**
     * @param mixed[] $params
     */
    public function __construct(protected readonly Config $config, protected readonly array $params = []) { }

    public function getParam(string $key): mixed {
        if (!array_key_exists($key, $this->params)) {
            throw new ConfigException(
                "Parameter '" . HelpFormatter::createForStdErr()->paramTitle($key) . "' not found in the request",
            );
        }

        return $this->params[$key];
    }

    public function getCommandRequest(string $command): static {
        $subcommandConfig = $this->config->getBranch($command);
        if (!$subcommandConfig) {
            throw new ConfigException(
                "Subcommand '" . HelpFormatter::createForStdErr()->paramTitle($command) . "' not found",
            );
        }

        return new static($subcommandConfig, $this->getParam($command));
    }

    /**
     * @return mixed[]
     */
    public function getParams(): array {
        return $this->params;
    }
}
