<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

/**
 * Composes one or more type resolvers and applies resolution in the
 * order in which the type resolvers are passed to the constructor,
 * returning the first successfully resolved type, or NULL if
 * resolution fails.
 */
class CompositeTypeResolver implements TypeResolverInterface
{
    /**
     * @var TypeResolverInterface[]
     */
    private array $typeResolvers;

    public function __construct(
        TypeResolverInterface ...$typeResolvers
    ) {
        $this->typeResolvers = $typeResolvers;
    }

    public function getType(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?string {
        foreach ($this->typeResolvers as $typeResolver) {
            $type = $typeResolver->getType(
                $componentName,
                $propertyName,
                $propertySchema
            );
            if ($type !== null) {
                return $type;
            }
        }
        return null;
    }
}
