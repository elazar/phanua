<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class NameResolver implements NameResolverInterface
{
    public function getName(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): string {
        return $propertyName;
    }
}
