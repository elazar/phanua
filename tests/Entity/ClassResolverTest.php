<?php

use Elazar\Phanua\Entity\ClassResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;
use Jane\Component\JsonSchema\Generator\Naming;

it('resolves a class with a namespace', function () {
    $resolver = new ClassResolver(new Naming(), '\\Foo\\Generated');
    $class = $resolver->getClass('bar');
    expect($class)->toBe('\\Foo\\Generated\\Model\\Bar');
});
