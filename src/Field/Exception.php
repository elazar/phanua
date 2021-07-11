<?php

namespace Elazar\Phanua\Field;

use Elazar\Phanua\Exception as BaseException;

class Exception extends BaseException
{
    public const CODE_TYPE_RESOLUTION_FAILED = 1;

    public static function typeResolutionFailed(
        string $componentName,
        string $propertyName
    ): self {
        return new self(
            sprintf(
                'Could not resolve type for %s property of %s component',
                $propertyName,
                $componentName
            ),
            self::CODE_TYPE_RESOLUTION_FAILED
        );
    }
}
