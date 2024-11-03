<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Exception;

use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use RuntimeException;
use Throwable;

final class ParseErrorException extends RuntimeException {
    public const E_NO_PARAM = 1;

    public const E_TOO_MANY_ARGUMENTS = 101;
    public const E_WRONG_ARGUMENT     = 102;

    public const E_NO_OPTION       = 201;
    public const E_WRONG_OPTION    = 202;
    public const E_NO_OPTION_VALUE = 203;

    /** @var ParameterAbstract[] */
    protected array $invalidParams;

    /**
     * @param ParameterAbstract[] $invalidParams
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        array $invalidParams = [],
        ?Throwable $previous = null,
    ) {
        /*
         * Add base formatting here for all such exceptions.
         * Do not add such formatting at the place the exception message is printed -
         * some formatting is lost if called by autocomplete.
         */
        parent::__construct(HelpFormatter::createForStdErr()->error($message), $code, $previous);

        $this->invalidParams = $invalidParams;
    }

    /**
     * @return ParameterAbstract[]
     */
    public function getInvalidParams(): array {
        return $this->invalidParams;
    }
}
