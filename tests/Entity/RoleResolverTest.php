<?php

use Elazar\Phanua\Entity\RoleResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->resolver = new RoleResolver();
});

it('resolves a role', function () {
    $role = $this->resolver->getRole('foo');
    expect($role)->toBe('foo');
});
