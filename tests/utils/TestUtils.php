<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\utils;

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

final class TestUtils {
    public static function newConfig(?EnvironmentConfig $envConfig = null) {
        return Parametizer::newConfig(envConfig: $envConfig, throwOnException: true);
    }

    public static function getEnvironmentConfigPartJson(EnvironmentConfig $envConfig): string {
        $settings = [
            'optionHelpShortName'                                 => $envConfig->optionHelpShortName,
            'helpGeneratorShortDescriptionCharsMinBeforeFullStop' => $envConfig->helpGeneratorShortDescriptionCharsMinBeforeFullStop,
            'helpGeneratorShortDescriptionCharsMax'               => $envConfig->helpGeneratorShortDescriptionCharsMax,
        ];

        return json_encode(
            $settings,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_LINE_TERMINATORS
            | JSON_PRETTY_PRINT,
        );
    }
}
