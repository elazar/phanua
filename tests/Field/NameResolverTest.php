<?php

use Elazar\Phanua\Field\NameResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->resolver = new NameResolver();
});

it('resolves a field name', function () {
    $column = $this->resolver->getName('foo', 'bar', new Schema());
    expect($column)->toBe('bar');
});
