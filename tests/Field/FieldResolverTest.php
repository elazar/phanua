<?php

use Cycle\Schema\Table\Column;
use Elazar\Phanua\Field\ColumnResolverInterface;
use Elazar\Phanua\Field\Exception;
use Elazar\Phanua\Field\FieldResolver;
use Elazar\Phanua\Field\PrimaryResolverInterface;
use Elazar\Phanua\Field\TypeResolverInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

function getPrimaryResolver(bool $return = false): PrimaryResolverInterface
{
    $primaryResolver = mock(PrimaryResolverInterface::class);
    $primaryResolver->allows([
        'isPrimary' => $return,
    ]);
    return $primaryResolver;
}

function getTypeResolver($type = 'string')
{
    $typeResolver = mock(TypeResolverInterface::class);
    $typeResolver
        ->allows()
        ->getType(anyArgs())
        ->andReturn($type);
    return $typeResolver;
}

beforeEach(function () {
    $this->columnResolver = mock(ColumnResolverInterface::class);
    $this->columnResolver
        ->allows()
        ->getColumn(anyArgs())
        ->andReturnUsing(fn ($cn, $pn, $ps) => $pn);
});

it('resolves a field name', function (bool $primary) {
    $resolver = new FieldResolver(
        $this->columnResolver,
        getPrimaryResolver($primary),
        getTypeResolver()
    );

    $field = $resolver->getField('foo', 'bar', new Schema());
    expect($field->getColumn())->toBe('bar');
    expect($field->isPrimary())->toBe($primary);
    expect($field->getType())->toBe('string');
})
->with([
    true,
    false,
]);

it('resolves a default value', function () {
    $resolver = new FieldResolver(
        $this->columnResolver,
        getPrimaryResolver(),
        getTypeResolver()
    );

    $schema = new Schema();
    $field = $resolver->getField('foo', 'bar', $schema);
    expect($field->getOptions()->has(Column::OPT_DEFAULT))->toBeFalse();

    $schema->setDefault(1);
    $field = $resolver->getField('foo', 'bar', $schema);
    expect($field->getOptions()->has(Column::OPT_DEFAULT))->toBeTrue();
    expect($field->getOptions()->get(Column::OPT_DEFAULT))->toBe(1);
});

it('resolves a nullable status', function () {
    $resolver = new FieldResolver(
        $this->columnResolver,
        getPrimaryResolver(),
        getTypeResolver()
    );

    $schema = new Schema();
    $field = $resolver->getField('foo', 'bar', $schema);
    expect($field->getOptions()->get(Column::OPT_NULLABLE))->toBeFalse();

    $schema->setNullable(true);
    $field = $resolver->getField('foo', 'bar', $schema);
    expect($field->getOptions()->get(Column::OPT_NULLABLE))->toBeTrue();
});

it('does not resolve an excluded field', function ($field) {
    $resolver = new FieldResolver(
        $this->columnResolver,
        getPrimaryResolver(),
        getTypeResolver(),
        fn (string $property): bool => $property === $field
    );

    $schema = new Schema();
    $field = $resolver->getField('foo', 'bar', $schema);
    expect($field)->toBeNull();
})
->with([
    'bar',
    'foo.bar',
]);

it('fails to resolve if type resolution fails', function () {
    $resolver = new FieldResolver(
        $this->columnResolver,
        getPrimaryResolver(),
        getTypeResolver(null)
    );

    $resolver->getField('foo', 'bar', new Schema());
})
->throws(
    Exception::class,
    'Could not resolve type for bar property of foo component'
);
