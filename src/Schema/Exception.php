<?php

namespace Elazar\Phanua\Schema;

use Cycle\Schema\Exception\RegistryException;
use Elazar\Phanua\Exception as BaseException;
use Elazar\Phanua\Type\TypeResolverException;
use Jane\Component\OpenApiCommon\Exception\CouldNotParseException;
use Jane\Component\OpenApiCommon\Exception\OpenApiVersionSupportException;

class Exception extends BaseException
{
    public const CODE_OPENAPI_SPEC_PARSING_FAILED = 1;
    public const CODE_NO_RESOLVABLE_COMPONENTS = 2;
    public const CODE_NO_PRIMARY_KEY = 3;

    /**
     * @param CouldNotParseException|OpenApiVersionSupportException $e
     */
    public static function openApiSpecParsingFailed(string $openApiSpecPathNotSet, \Exception $previous): self
    {
        return new self(
            sprintf('OpenAPI spec could not be parsed: %s', $openApiSpecPathNotSet),
            self::CODE_OPENAPI_SPEC_PARSING_FAILED,
            $previous
        );
    }

    public static function noResolvableComponents(): self
    {
        return new self(
            'No resolvable components in OpenAPI specification',
            self::CODE_NO_RESOLVABLE_COMPONENTS
        );
    }

    public static function noPrimaryKey(string $componentName): self
    {
        return new self(
            sprintf('Component has no primary key: %s', $componentName),
            self::CODE_NO_PRIMARY_KEY
        );
    }
}
