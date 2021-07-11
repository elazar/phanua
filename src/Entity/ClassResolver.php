<?php

namespace Elazar\Phanua\Entity;

use Jane\Component\JsonSchema\Generator\Naming;

class ClassResolver implements ClassResolverInterface
{
    private Naming $naming;

    private string $namespace;

    public function __construct(
        Naming $naming,
        string $namespace
    ) {
        $this->namespace = $namespace;
        $this->naming = $naming;
    }

    public function getClass(string $entityName): string
    {
        $name = $this->naming->getClassName($entityName);
        return $this->namespace . '\\Model\\' . $name;
    }
}
