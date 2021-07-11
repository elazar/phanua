<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface NameResolverInterface
{
    public function getName(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): string;
}
