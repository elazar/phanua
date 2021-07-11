<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface PrimaryResolverInterface
{
    public function isPrimary(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): bool;
}
