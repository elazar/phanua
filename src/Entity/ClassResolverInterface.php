<?php

namespace Elazar\Phanua\Entity;

interface ClassResolverInterface
{
    public function getClass(string $entityName): string;
}
