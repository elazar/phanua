<?php

namespace Elazar\Phanua\Entity;

use Cycle\Schema\Definition\Entity;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class EntityResolver implements EntityResolverInterface
{
    private RoleResolverInterface $roleResolver;

    private ClassResolverInterface $classResolver;

    /**
     * @var null|callable(string, Schema): bool
     */
    private $filterCallback;

    /**
     * @param null|callable(string, Schema): bool $filterCallback
     */
    public function __construct(
        RoleResolverInterface $roleResolver,
        ClassResolverInterface $classResolver,
        $filterCallback = null
    ) {
        $this->roleResolver = $roleResolver;
        $this->classResolver = $classResolver;
        $this->filterCallback = $filterCallback;
    }

    public function getEntity(
        string $componentName,
        Schema $componentSchema
    ): ?Entity {
        if (is_callable($this->filterCallback)) {
            $include = ($this->filterCallback)(
                $componentName,
                $componentSchema
            );
            if ($include === false) {
                return null;
            }
        }

        $entity = new Entity();
        $entity->setRole($this->roleResolver->getRole($componentName));
        $entity->setClass($this->classResolver->getClass($componentName));
        return $entity;
    }
}
