<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class PrimaryResolver implements PrimaryResolverInterface
{
    public function isPrimary(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): bool {
        return $propertyName === 'id';
    }
}
