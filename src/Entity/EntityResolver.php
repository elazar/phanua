<?php

namespace Elazar\Phanua\Entity;

use Cycle\Schema\Definition\Entity;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class EntityResolver implements EntityResolverInterface
{
    private RoleResolverInterface $roleResolver;

    private ClassResolverInterface $classResolver;

    /**
     * @var string[]
     */
    private array $exclude;

    /**
     * @param string[] $exclude
     */
    public function __construct(
        RoleResolverInterface $roleResolver,
        ClassResolverInterface $classResolver,
        array $exclude = []
    ) {
        $this->roleResolver = $roleResolver;
        $this->classResolver = $classResolver;
        $this->exclude = $exclude;
    }

    public function getEntity(
        string $componentName,
        Schema $componentSchema
    ): ?Entity {
        if (in_array($componentName, $this->exclude)) {
            return null;
        }

        $entity = new Entity();
        $entity->setRole($this->roleResolver->getRole($componentName));
        $entity->setClass($this->classResolver->getClass($componentName));
        return $entity;
    }
}
