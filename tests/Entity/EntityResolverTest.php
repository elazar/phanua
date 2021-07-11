<?php

use Elazar\Phanua\Entity\ClassResolverInterface;
use Elazar\Phanua\Entity\EntityResolver;
use Elazar\Phanua\Entity\RoleResolverInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

function getEntityResolver(array $exclude = []): EntityResolver
{
    $roleResolver = mock(RoleResolverInterface::class);
    $roleResolver
        ->allows()
        ->getRole(anyArgs())
        ->andReturn('role');

    $classResolver = mock(ClassResolverInterface::class);
    $classResolver
        ->allows()
        ->getClass(anyArgs())
        ->andReturn('class');

    return new EntityResolver(
        $roleResolver,
        $classResolver,
        $exclude
    );
}

it('resolves an entity', function () {
    $resolver = getEntityResolver();
    $entity = $resolver->getEntity('foo', new Schema());
    expect($entity->getRole())->toBe('role');
    expect($entity->getClass())->toBe('class');
});

it('does not resolve an excluded entity', function () {
    $resolver = getEntityResolver(['foo']);
    $entity = $resolver->getEntity('foo', new Schema());
    expect($entity)->toBeNull();
});
