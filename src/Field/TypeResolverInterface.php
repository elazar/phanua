<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface TypeResolverInterface
{
    /**
     * This method allows a null return value so that type resolvers can be
     * composed and allow the composing resolver to handle type resolution if
     * the composed resolver fails to produce a type.
     */
    public function getType(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?string;
}
