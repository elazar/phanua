<?php

use Elazar\Phanua\Field\PrimaryResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->resolver = new PrimaryResolver();
});

it('resolves a column name', function (string $propertyName, bool $result) {
    $primary = $this->resolver->isPrimary('foo', $propertyName, new Schema());
    expect($primary)->toBe($result);
})
->with([
    ['id', true],
    ['bar', false],
]);
