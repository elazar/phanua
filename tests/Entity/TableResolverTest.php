<?php

use Cycle\Schema\Definition\Entity;
use Elazar\Phanua\Entity\Exception;
use Elazar\Phanua\Entity\TableResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->componentName = 'foo';
    $this->schema = new Schema();
    $this->entity = new Entity();
    $this->tableResolver = new TableResolver();
});

it('successfully resolves a table', function () {
    $this->entity->setRole('role');

    $result = $this->tableResolver->getTable(
        $this->componentName,
        $this->schema,
        $this->entity
    );

    expect($result)->toBe('role');
});

it('fails to resolve for an entity without a role', function () {
    $this->tableResolver->getTable(
        $this->componentName,
        $this->schema,
        $this->entity
    );
})
->throws(
    Exception::class,
    'Could not resolve table for foo component'
);
