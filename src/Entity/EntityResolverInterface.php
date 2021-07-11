<?php

namespace Elazar\Phanua\Entity;

use Cycle\Schema\Definition\Entity;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

interface EntityResolverInterface
{
    public function getEntity(
        string $componentName,
        Schema $componentSchema
    ): ?Entity;
}
