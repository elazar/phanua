<?php

namespace Elazar\Phanua\Entity;

use Cycle\Schema\Definition\Entity;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class TableResolver implements TableResolverInterface
{
    public function getTable(
        string $componentName,
        Schema $componentSchema,
        Entity $entity
    ): string {
        $role = $entity->getRole();
        if ($role === null) {
            throw Exception::tableResolutionFailed($componentName);
        }
        return $role;
    }
}
