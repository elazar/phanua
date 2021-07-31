<?php

namespace Elazar\Phanua\Entity;

use Elazar\Phanua\Exception as BaseException;

class Exception extends BaseException
{
    public const CODE_TABLE_RESOLUTION_FAILED = 1;

    public static function tableResolutionFailed(
        string $componentName
    ): self {
        return new self(
            sprintf(
                'Could not resolve table for %s component',
                $componentName
            ),
            self::CODE_TABLE_RESOLUTION_FAILED
        );
    }
}
