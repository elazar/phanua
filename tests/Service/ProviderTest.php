<?php

use Elazar\Phanua\Schema\Builder;
use Elazar\Phanua\Service\Exception;
use Elazar\Phanua\Service\Provider;
use Pimple\Container;
use Psr\Container\ContainerInterface;

function withConfiguration(Provider $provider): Provider
{
    return $provider
        ->withOpenApiSpecPath('path/to/spec.yaml')
        ->withNamespace('\\Foo')
        ->withDatabaseConfig([])
        ->withDatabase('foo');
}

expect()->extend('toBePimpleContainer', function () {
    $container = $this->value;
    $keys = $container->keys();
    expect($keys)->not->toBeEmpty();

    foreach ($keys as $key) {
        $result = $container[$key];
        expect($result)->toBeInstanceOf($key);
    }
});

beforeEach(function () {
    $this->provider = new Provider();
});

it('has no delegate container by default', function () {
    $default = $this->provider->getDelegateContainer();
    expect($default)->toBeNull();
});

it('accepts a Pimple delegate container', function () {
    $provider = $this->provider
         ->withDelegateContainer(new Container());
    $delegateContainer = $provider->getDelegateContainer();
    expect($delegateContainer)->toBeInstanceOf(ContainerInterface::class);
});

it('accepts a PSR-11 delegate container', function () {
    $container = mock(ContainerInterface::class);
    $provider = $this->provider
         ->withDelegateContainer($container);
    $delegateContainer = $provider->getDelegateContainer();
    expect($delegateContainer)->toBe($container);
});

it('rejects an invalid delegate container', function () {
    $this->provider->withDelegateContainer(null);
})
->throws(
    Exception::class,
    'Specified delegate has an unsupported type: NULL'
);

it('has no OpenAPI spec path by default', function () {
    $default = $this->provider->getOpenApiSpecPath();
    expect($default)->toBeNull();
});

it('accepts an OpenAPI spec path', function () {
    $expected = 'path/to/spec.yaml';
    $provider = $this->provider->withOpenApiSpecPath($expected);
    $openApiSpecPath = $provider->getOpenApiSpecPath();
    expect($openApiSpecPath)->toBe($expected);
});

it('has no namespace by default', function () {
    $default = $this->provider->getNamespace();
    expect($default)->toBeNull();
});

it('accepts a namespace', function () {
    $expected = '\\Foo';
    $provider = $this->provider->withNamespace($expected);
    $namespace = $provider->getNamespace();
    expect($namespace)->toBe($expected);
});

it('has no database configuration by default', function () {
    $default = $this->provider->getDatabaseConfig();
    expect($default)->toBeArray()->toBeEmpty();
});

it('accepts database configuration', function () {
    $expected = ['foo' => 'bar'];
    $provider = $this->provider->withDatabaseConfig($expected);
    $databaseConfig = $provider->getDatabaseConfig();
    expect($databaseConfig)->toBe($expected);
});

it('has no database by default', function () {
    $default = $this->provider->getDatabase();
    expect($default)->toBeNull();
});

it('accepts a database', function () {
    $expected = 'mydb';
    $provider = $this->provider->withDatabase($expected);
    $database = $provider->getDatabase();
    expect($database)->toBe($expected);
});

it('has no excluded components by default', function () {
    $default = $this->provider->getExcludedComponents();
    expect($default)->toBeArray()->toBeEmpty();
});

it('accepts excluded components', function () {
    $expected = ['foo'];
    $provider = $this->provider->withExcludedComponents($expected);
    $excludedComponents = $provider->getExcludedComponents();
    expect($excludedComponents)->toBe($expected);
});

it('has no excluded properties by default', function () {
    $default = $this->provider->getExcludedProperties();
    expect($default)->toBeArray()->toBeEmpty();
});

it('accepts excluded properties', function () {
    $expected = ['foo'];
    $provider = $this->provider->withExcludedProperties($expected);
    $excludedProperties = $provider->getExcludedProperties();
    expect($excludedProperties)->toBe($expected);
});

it('accepts Jane configuration', function () {
    $provider = $this->provider->withJaneConfiguration([]);
    expect($provider)->toEqualCanonicalizing($this->provider);

    $openApiFile = 'path/to/spec.yaml';
    $provider = $this->provider->withJaneConfiguration([
        'openapi-file' => $openApiFile,
    ]);
    expect($provider->getOpenApiSpecPath())->toBe($openApiFile);
    expect($provider->getNamespace())->toBeNull();

    $namespace = '\\Foo';
    $provider = $this->provider->withJaneConfiguration([
        'namespace' => $namespace,
    ]);
    expect($provider->getNamespace())->toBe($namespace);
    expect($provider->getOpenApiSpecPath())->toBeNull();

    $provider = $this->provider->withJaneConfiguration([
        'openapi-file' => $openApiFile,
        'namespace' => $namespace,
    ]);
    expect($provider->getOpenApiSpecPath())->toBe($openApiFile);
    expect($provider->getNamespace())->toBe($namespace);
});

it('registers dependencies in a container', function () {
    $provider = withConfiguration($this->provider);
    $container = new Container();
    $provider->register($container);
    expect($container)->toBePimpleContainer();
});

it('returns a schema builder with the default configuration', function () {
    $provider = withConfiguration($this->provider);
    $schemaBuilder = $provider->getSchemaBuilder();
    expect($schemaBuilder)->toBeInstanceOf(Builder::class);
});

it('returns a Pimple container', function () {
    $provider = withConfiguration($this->provider);
    $container = $provider->getContainer();
    expect($container)->toBePimpleContainer();
});

it('returns a PSR-11 container', function () {
    $provider = withConfiguration($this->provider);
    $container = $provider->getContainer();
    $psrContainer = $provider->getPsrContainer();

    foreach ($container->keys() as $key) {
        expect($psrContainer->has($key))->toBeTrue();
        expect($psrContainer->get($key))->toEqualCanonicalizing($container[$key]);
    }
});

it('allows overrides of defaults', function () {
    $delegate = new Container();
    $provider = withConfiguration($this->provider)
        ->withDelegateContainer($delegate);
    $container = $provider->getContainer();

    foreach ($container->keys() as $key) {
        $clone = clone $container[$key];
        $delegate[$key] = fn () => $clone;
        $configured = new Container();
        $provider->register($configured);
        expect($configured[$key])->toBe($delegate[$key]);
    }
});
