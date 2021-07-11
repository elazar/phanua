<?php

namespace Elazar\Phanua\Field;

use Cycle\Schema\Definition\Field;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface FieldResolverInterface
{
    public function getField(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?Field;
}
