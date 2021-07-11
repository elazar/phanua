<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface ColumnResolverInterface
{
    public function getColumn(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): string;
}
