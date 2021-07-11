<?php

use Elazar\Phanua\Field\ColumnResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->resolver = new ColumnResolver();
});

it('resolves a column name', function () {
    $column = $this->resolver->getColumn('foo', 'bar', new Schema());
    expect($column)->toBe('bar');
});
