<?php

use Cycle\Schema\Definition\Entity;
use Elazar\Phanua\Entity\TableResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

beforeEach(function () {
    $this->tableResolver = new TableResolver();
});

it('successfully resolves a table', function () {
    $componentName = 'foo';
    $schema = new Schema();
    $entity = new Entity();
    $entity->setRole('role');

    $result = $this->tableResolver->getTable(
        $componentName,
        $schema,
        $entity
    );

    expect($result)->toBe('role');
});
