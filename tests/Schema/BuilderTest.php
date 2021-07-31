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
use Elazar\Phanua\Field\PrimaryResolverInterface;
use Elazar\Phanua\Schema\Builder;
use Elazar\Phanua\Schema\Exception as SchemaException;
use Elazar\Phanua\Service\Exception as ServiceException;
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

/**
 * @return array<string, mixed>
 */
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

/**
 * @param string[] $values
 */
function getFilterCallback(array $values): callable
{
    return fn (string $value): bool => in_array($value, $values);
}

function getMinimalProvider(Provider $provider): Provider
{
    return $provider
        ->withNamespace('\\Foo')
        ->withDatabaseConfig(getDatabaseConfig())
        ->withComponentFilter(
            getFilterCallback(['Pet'])
        );
}

function getModifiedOpenApiSpec(callable $callback): string
{
    $rawContents = file_get_contents(PETSTORE_JSON_SPEC_PATH);
    if ($rawContents === false) {
        throw new RuntimeException(
            sprintf(
                'Failed to read file: %s',
                PETSTORE_JSON_SPEC_PATH
            )
        );
    }
    $rawSpec = json_decode($rawContents);
    if ($rawSpec === false) {
        throw new RuntimeException(
            sprintf(
                'Failed to decode file %s: error %s (see https://php.net/json_last_error)',
                PETSTORE_JSON_SPEC_PATH,
                json_last_error()
            )
        );
    }
    $contents = json_encode($callback($rawSpec));
    if ($contents === false) {
        throw new \RuntimeException(
            sprintf(
                'Failed to encode JSON: error %s (see https://php.net/json_last_error)',
                json_last_error()
            )
        );
    }
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
function buildSchema($providerOrContainer, $openApiSpecPaths = [PETSTORE_JSON_SPEC_PATH]): CycleSchema
{
    return getSchemaBuilder($providerOrContainer)
        ->buildSchema($openApiSpecPaths);
}

dataset('dependencies', function () {
    $container = new Container();
    (new Provider())->register($container);
    return $container->keys();
});

expect()->extend('toContainEntity', function (string $entity) {
    $schema = $this->value;
    expect($schema)->toBeInstanceOf(CycleSchema::class);
    expect($schema->defines($entity))->toBeTrue();
});

beforeEach(function () {
    $this->provider = new Provider();
});

it('fails to load without a namespace', function () {
    $provider = $this->provider
        ->withComponentFilter(
            getFilterCallback(['Pet'])
        );
    getSchemaBuilder($provider);
})
->throws(
    ServiceException::class,
    'No namespace provided for generated Jane model files'
);

it('fails to load without default dependencies', function ($key) {
    $container = $this->provider
        ->withNamespace('\\Foo')
        ->withComponentFilter(
            getFilterCallback(['Pet'])
        )
        ->getContainer();
    unset($container[$key]);
    getSchemaBuilder($container);
})
->with('dependencies')
->throws(UnknownIdentifierException::class);

it('fails to build without database configuration', function () {
    $provider = $this->provider
        ->withNamespace('\\Foo')
        ->withComponentFilter(
            getFilterCallback(['Pet'])
        );
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
    SchemaException::class,
    'No resolvable components in OpenAPI specification'
);

it('fails to build a specification with no resolvable components', function () {
    $provider = getMinimalProvider($this->provider)
        ->withComponentFilter(fn () => false);
    buildSchema($provider);
})
->throws(
    SchemaException::class,
    'No resolvable components in OpenAPI specification'
);

it('fails to build a specification if an expected primary key is missing', function () {
    $provider = $this->provider
        ->withNamespace('\\Foo')
        ->withDatabaseConfig(getDatabaseConfig());
    buildSchema($provider);
})
->throws(SchemaException::class);

it('builds a schema with required configuration', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider);
    expect($schema)->toContainEntity('Pet');
});

it('builds an ORM with required configuration', function () {
    $provider = getMinimalProvider($this->provider);
    $schemaBuilder = getSchemaBuilder($provider);
    $orm = $schemaBuilder->buildOrm([PETSTORE_JSON_SPEC_PATH]);
    expect($orm)->toBeInstanceOf(ORM::class);
    expect($orm->getSchema())->toContainEntity('Pet');
});

it('builds a schema with one file passed as a string', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider, PETSTORE_JSON_SPEC_PATH);
    expect($schema)->toContainEntity('Pet');
});

it('builds a schema for a YAML specification', function () {
    $provider = getMinimalProvider($this->provider);
    $schema = buildSchema($provider, PETSTORE_YAML_SPEC_PATH);
    expect($schema)->toContainEntity('Pet');
});

it('builds a schema with failed resolution of a relative property', function () {
    $container = getMinimalProvider($this->provider)->getContainer();
    $container[FieldResolverInterface::class] = fn () => getFieldResolver(
        fn (string $propertyName): bool => $propertyName === 'tag'
    );
    $container[PrimaryResolverInterface::class] = function () {
        $primaryResolver = mock(PrimaryResolverInterface::class);
        $primaryResolver
            ->shouldReceive('isPrimary')
            ->andReturnUsing(
                function (
                    string $componentName,
                    string $propertyName
                ): bool {
                    return in_array($propertyName, [
                        'id',
                        'code',
                    ]);
                }
            );
        return $primaryResolver;
    };
    $schema = buildSchema($container);
    expect($schema)->toContainEntity('Pet');
    $columns = array_values($schema->define('Pet', CycleSchema::COLUMNS));
    expect($columns)->toMatchArray(['id', 'name']);
});
