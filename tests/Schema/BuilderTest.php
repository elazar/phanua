<?php

use Cycle\ORM\ORM;
use Cycle\ORM\Schema as CycleSchema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Registry;

use Elazar\Phanua\ContextLogger;
use Elazar\Phanua\Entity\EntityResolverInterface;
use Elazar\Phanua\Entity\TableResolverInterface;
use Elazar\Phanua\Field\FieldResolverInterface;
use Elazar\Phanua\Field\NameResolverInterface;
use Elazar\Phanua\Schema\Builder;
use Elazar\Phanua\Schema\Exception;
use Elazar\Phanua\Service\Provider;

use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema as JaneSchema;

use Pimple\Container;
use Pimple\Exception\UnknownIdentifierException;

use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Spiral\Database\Exception\DBALException;

function getFieldResolver(callable $callback): FieldResolverInterface
{
    $fieldResolver = mock(FieldResolverInterface::class);
    $fieldResolver
        ->shouldReceive('getField')
        ->andReturnUsing(
            function (
                string $componentName,
                string $propertyName,
                JaneSchema $propertySchema
            ) use ($callback): ?Field {
                if ($callback($propertyName, $componentName)) {
                    return null;
                }
                $field = new Field();
                $field->setColumn($propertyName);
                $field->setPrimary($propertyName === 'id');
                return $field;
            }
        );
    return $fieldResolver;
}

function getDatabaseConfig(): array
{
    return [
        'databases' => [
            'default' => ['connection' => 'default'],
        ],
        'connections' => [
            'default' => [
                'driver' => SQLiteDriver::class,
                'connection' => 'sqlite::memory:',
            ],
        ],
    ];
}

function getMinimalProvider(Provider $provider): Provider
{
    return $provider
        ->withNamespace('\\Foo')
        ->withDatabaseConfig(getDatabaseConfig())
        ->withExcludedComponents(['Pets', 'Error']);
}

function getModifiedOpenApiSpec(callable $callback): string
{
    $rawSpec = json_decode(file_get_contents(JSON_OPENAPI_SPEC_PATH));
    $contents = json_encode($callback($rawSpec));
    return createTempFile($contents);
}

/**
 * @param Provider|Container $providerOrContainer
 */
function getSchemaBuilder($providerOrContainer): Builder
{
    $container = $providerOrContainer instanceof Provider
        ? $providerOrContainer->getContainer()
        : $providerOrContainer;
    return $container[Builder::class];
}

/**
 * @param Provider|Container $providerOrContainer
 * @param string|string[] $openApiSpecPaths
 */
function buildSchema($providerOrContainer, $openApiSpecPaths = [JSON_OPENAPI_SPEC_PATH]): CycleSchema
{
    return getSchemaBuilder($providerOrContainer)
        ->buildSchema($openApiSpecPaths);
}

dataset('dependencies', function () {
    $container = new Container();
    (new Provider())->register($container);
    return $container->keys();
});

expect()->extend('toBeValidSchema', function () {
    $schema = $this->value;
    expect($schema)->toBeInstanceOf(CycleSchema::class);
    expect($schema->defines('Pet'))->toBeTrue();
});

beforeEach(function () {
    $this->provider = new Provider();
});

it('fails to load without a namespace', function () {
    $provider = $this->provider
        ->withExcludedComponents(['Pets', 'Error']);
    getSchemaBuilder($provider);
})
->throws(TypeError::class);

it('fails to load without default dependencies', function ($key) {
    $container = $this->provider
        ->withNamespace('\\Foo')
        ->withExcludedComponents(['Pets', 'Error'])
        ->getContainer();
    unset($container[$key]);
    getSchemaBuilder($container);
})
->with('dependencies')
->throws(UnknownIdentifierException::class);

it('fails to build without database configuration', function () {
    $provider = $this->provider
        ->withNamespace('\\Foo')
        ->withExcludedComponents(['Pets', 'Error']);
    buildSchema($provider);
})
->throws(DBALException::class);

it('fails to build a specification without components', function () {
    $openApiSpecPath = getModifiedOpenApiSpec(
        function (object $spec): object {
            $clone = clone $spec;
            unset($clone->components);
            return $clone;
        }
    );
    $provider = getMinimalProvider($this->provider);
    buildSchema($provider, $openApiSpecPath);
})
->throws(
    Exception::class,
    'No resolvable components in OpenAPI specification'
);

it('fails to build a specification with no resolvable components', function () {
    $provider = getMinimalProvider($this->provider)
        ->withExcludedComponents(['Pet', 'Pets', 'Error']);
    buildSchema($provider);
})
->throws(
    Exception::class,
    'No resolvable components in OpenAPI specification'
);

it('fails to build a specification if an expected primary key is missing', function () {
    $provider = $this->provider
        ->withNamespace('\\Foo')
        ->withDatabaseConfig(getDatabaseConfig());
    buildSchema($provider);
})
->throws(Exception::class);

it('builds a schema with required configuration', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider);
    expect($schema)->toBeValidSchema();
});

it('builds an ORM with required configuration', function () {
    $provider = getMinimalProvider($this->provider);
    $schemaBuilder = getSchemaBuilder($provider);
    $orm = $schemaBuilder->buildOrm([JSON_OPENAPI_SPEC_PATH]);
    expect($orm)->toBeInstanceOf(ORM::class);
    expect($orm->getSchema())->toBeValidSchema();
});

it('builds a schema with one file passed as a string', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider, JSON_OPENAPI_SPEC_PATH);
    expect($schema)->toBeValidSchema();
});

it('builds a schema for a YAML specification', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider, YAML_OPENAPI_SPEC_PATH);
    expect($schema)->toBeValidSchema();
});

it('builds a schema with failed resolution of a relative property', function () {
    $primaryResolver = mock(PrimaryResolverInterface::class);
    $primaryResolver
        ->shouldReceive('isPrimary')
        ->andReturnUsing(
            function (
                string $componentName,
                string $propertyName,
                JaneSchema $propertySchema
            ): bool {
                return in_array($propertyName, [
                    'id',
                    'code',
                ]);
            }
        );
    $container = getMinimalProvider($this->provider)
        ->getContainer();
    $container[FieldResolverInterface::class] = fn () => getFieldResolver(
        fn (string $propertyName): bool => $propertyName === 'tag'
    );
    $container[PrimaryResolverInterface::class] = fn () => $primaryResolver;
    $schema = buildSchema($container);
    expect($schema)->toBeValidSchema();
    $columns = array_values($schema->define('Pet', CycleSchema::COLUMNS));
    expect($columns)->toMatchArray(['id', 'name']);
});
