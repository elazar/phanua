<?php

use Elazar\Phanua\Field\CompositeTypeResolver;
use Elazar\Phanua\Field\TypeResolverInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->subResolver1 = mock(TypeResolverInterface::class);
    $this->subResolver2 = mock(TypeResolverInterface::class);
    $this->resolver = new CompositeTypeResolver(
        $this->subResolver1,
        $this->subResolver2
    );
});

it('resolves in order and returns first non-null result', function () {
    $component = 'component';
    $property = 'property';
    $schema = new Schema();
    $type = 'type';

    $this->subResolver1
         ->allows()
         ->getType($component, $property, $schema)
         ->andReturn($type);

    $this->subResolver2
         ->shouldNotReceive('getType');

    $actual = $this->resolver->getType($component, $property, $schema);
    expect($actual)->toBe($type);
});

it('does not return the first result if it is null', function () {
    $component = 'component';
    $property = 'property';
    $schema = new Schema();
    $type = 'type';

    $this->subResolver1
         ->allows()
         ->getType($component, $property, $schema)
         ->andReturn(null);

    $this->subResolver2
         ->allows()
         ->getType($component, $property, $schema)
         ->andReturn($type);

    $actual = $this->resolver->getType($component, $property, $schema);
    expect($actual)->toBe($type);
});

it('returns null if no resolver returns a non-null result', function () {
    $component = 'component';
    $property = 'property';
    $schema = new Schema();

    $this->subResolver1
         ->allows()
         ->getType($component, $property, $schema)
         ->andReturn(null);

    $this->subResolver2
         ->allows()
         ->getType($component, $property, $schema)
         ->andReturn(null);

    $actual = $this->resolver->getType($component, $property, $schema);
    expect($actual)->toBeNull();
});
