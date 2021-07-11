<?php

namespace Elazar\Phanua\Service;

use Elazar\Phanua\Exception as BaseException;

class Exception extends BaseException
{
    public const CODE_INVALID_DELEGATE = 1;

    /**
     * @param mixed $delegate
     */
    public static function invalidDelegate($delegate): self
    {
        $type = is_object($delegate) ? get_class($delegate) : gettype($delegate);
        return new self(
            sprintf('Specified delegate has an unsupported type: %s', $type),
            self::CODE_INVALID_DELEGATE
        );
    }
}
