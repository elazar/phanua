<?php

namespace Elazar\Phanua\Entity;

class RoleResolver implements RoleResolverInterface
{
    public function getRole(string $entityName): string
    {
        return $entityName;
    }
}
