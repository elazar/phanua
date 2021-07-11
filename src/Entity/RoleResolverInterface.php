<?php

namespace Elazar\Phanua\Entity;

interface RoleResolverInterface
{
    public function getRole(string $entityName): string;
}
