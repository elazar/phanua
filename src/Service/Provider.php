<?php

namespace Elazar\Phanua\Service;

use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\Schema\Compiler;
use Cycle\Schema\Registry;

use Elazar\Phanua\Entity\ClassResolver;
use Elazar\Phanua\Entity\ClassResolverInterface;
use Elazar\Phanua\Entity\EntityResolver;
use Elazar\Phanua\Entity\EntityResolverInterface;
use Elazar\Phanua\Entity\RoleResolver;
use Elazar\Phanua\Entity\RoleResolverInterface;
use Elazar\Phanua\Entity\TableResolver;
use Elazar\Phanua\Entity\TableResolverInterface;
use Elazar\Phanua\Field\ColumnResolver;
use Elazar\Phanua\Field\ColumnResolverInterface;
use Elazar\Phanua\Field\FieldResolver;
use Elazar\Phanua\Field\FieldResolverInterface;
use Elazar\Phanua\Field\NameResolver;
use Elazar\Phanua\Field\NameResolverInterface;
use Elazar\Phanua\Field\PrimaryResolver;
use Elazar\Phanua\Field\PrimaryResolverInterface;
use Elazar\Phanua\Field\TypeResolver;
use Elazar\Phanua\Field\TypeResolverInterface;
use Elazar\Phanua\Immutable;
use Elazar\Phanua\Schema\Builder as SchemaBuilder;
use Elazar\Phanua\Schema\CompilerConfiguration;
use Elazar\Phanua\Schema\SpecLoader;
use Elazar\Phanua\Schema\SpecLoaderInterface;

use Jane\Component\JsonSchema\Generator\Naming;
use Jane\Component\OpenApi3\JsonSchema\Normalizer\JaneObjectNormalizer;
use Jane\Component\OpenApi3\SchemaParser\SchemaParser;

use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Pimple\ServiceProviderInterface;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\DatabaseProviderInterface;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class Provider implements ServiceProviderInterface
{
    use Immutable;

    private ?ContainerInterface $delegateContainer = null;

    private ?string $openApiSpecPath = null;

    private ?string $namespace = null;

    /**
     * This property defaults to an empty array to make the resulting thrown
     * exception more informative about the root cause.
     */
    private array $databaseConfig = [];

    private ?string $database = null;

    /**
     * @var callable|null
     */
    private $componentFilter = null;

    /**
     * @var callable|null
     */
    private $propertyFilter = null;

    public function getDelegateContainer(): ?ContainerInterface
    {
        return $this->delegateContainer;
    }

    /**
     * @param Container|ContainerInterface $delegateContainer Pimple or other
     *        PSR-11 container used to override Phanua dependencies
     */
    public function withDelegateContainer($delegateContainer): self
    {
        if ($delegateContainer instanceof Container) {
            $delegateContainer = new PsrContainer($delegateContainer);
        } elseif (!$delegateContainer instanceof ContainerInterface) {
            throw Exception::invalidDelegate($delegateContainer);
        }
        return $this->with('delegateContainer', $delegateContainer);
    }

    public function getOpenApiSpecPath(): ?string
    {
        return $this->openApiSpecPath;
    }

    /**
     * @param string $openApiSpecPath Path to an OpenAPI 3 specification file
     *        in JSON or YAML format
     */
    public function withOpenApiSpecPath(string $openApiSpecPath): self
    {
        return $this->with('openApiSpecPath', $openApiSpecPath);
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace Namespace used by Jane
     * @see https://jane.readthedocs.io/en/latest/documentation/OpenAPI.html#configuration-file
     */
    public function withNamespace(string $namespace): self
    {
        return $this->with('namespace', $namespace);
    }

    public function getDatabaseConfig(): array
    {
        return $this->databaseConfig;
    }

    /**
     * @param array $databaseConfig Cycle ORM DBAL configuration
     * @see https://cycle-orm.dev/docs/basic-connect#instantiate-dbal
     */
    public function withDatabaseConfig(array $databaseConfig): self
    {
        return $this->with('databaseConfig', $databaseConfig);
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * @param string $database Name of a database connection defined in Cycle
     *        ORM configuration
     * @see https://cycle-orm.dev/docs/basic-connect#connections
     */
    public function withDatabase(string $database): self
    {
        return $this->with('database', $database);
    }

    /**
     * @return callable|null
     */
    public function getComponentFilter()
    {
        return $this->componentFilter;
    }

    /**
     * @param callable $componentFilter Callback that accepts a string
     *        containing the name of a component and returns a boolean value
     *        indicating whether the component should be included in the schema
     */
    public function withComponentFilter(callable $componentFilter): self
    {
        return $this->with('componentFilter', $componentFilter);
    }

    /**
     * @return callable|null
     */
    public function getPropertyFilter()
    {
        return $this->propertyFilter;
    }

    /**
     * @param callable $componentFilter Callback that accepts two strings
     *        containing the names of a component and property and returns a
     *        boolean value indicating whether the property should be included
     *        in the schema
     */
    public function withPropertyFilter(callable $propertyFilter): self
    {
        return $this->with('propertyFilter', $propertyFilter);
    }

    /**
     * @param string|array $config Array containing Jane configuration or a
     *        string containing the path to a Jane configuration file
     */
    public function withJaneConfiguration($config): self
    {
        // @codeCoverageIgnoreStart
        if (is_string($config)) {
            $config = require $config;
        }
        // @codeCoverageIgnoreEnd

        $modified = clone $this;

        if (isset($config['openapi-file'])) {
            $modified = $modified->withOpenApiSpecPath($config['openapi-file']);
        }

        if (isset($config['namespace'])) {
            $modified = $modified->withNamespace($config['namespace']);
        }

        return $modified;
    }

    /**
     * Implements \Pimple\ServiceProviderInterface->register() to add Phanua
     * dependencies to a \Pimple\Container instance.
     *
     * @see https://github.com/silexphp/Pimple#extending-a-container
     */
    public function register(Container $c)
    {
        // cycle/orm

        $c[DatabaseConfig::class] =
            fn () => new DatabaseConfig($this->getDatabaseConfig());

        $c[DatabaseManager::class] =
            fn () => new DatabaseManager($c[DatabaseConfig::class]);

        $c[DatabaseProviderInterface::class] =
            fn () => $c[DatabaseManager::class];

        $c[FactoryInterface::class] =
            fn () => new Factory($c[DatabaseProviderInterface::class]);

        $c[ORM::class] =
            fn () => new ORM($c[FactoryInterface::class]);

        // cycle/schema-builder

        $c[Compiler::class] =
            fn () => new Compiler();

        $c[Registry::class] =
            fn () => new Registry($c[DatabaseProviderInterface::class]);

        // elazar/phanua

        $c[ClassResolverInterface::class] =
            fn () => new ClassResolver(
                $c[Naming::class],
                $this->getNamespace()
            );

        $c[ColumnResolverInterface::class] =
            fn () => new ColumnResolver();

        $c[CompilerConfiguration::class] =
            fn () => new CompilerConfiguration();

        $c[EntityResolverInterface::class] =
            fn () => new EntityResolver(
                $c[RoleResolverInterface::class],
                $c[ClassResolverInterface::class],
                $this->getComponentFilter()
            );

        $c[FieldResolverInterface::class] =
            fn () => new FieldResolver(
                $c[ColumnResolverInterface::class],
                $c[PrimaryResolverInterface::class],
                $c[TypeResolverInterface::class],
                $this->getPropertyFilter()
            );

        $c[NameResolverInterface::class] =
            fn () => new NameResolver();

        $c[PrimaryResolverInterface::class] =
            fn () => new PrimaryResolver();

        $c[RoleResolverInterface::class] =
            fn () => new RoleResolver();

        $c[SchemaBuilder::class] =
            fn () => new SchemaBuilder(
                $c[ORM::class],
                $c[SpecLoaderInterface::class],
                $c[Registry::class],
                $c[Compiler::class],
                $c[CompilerConfiguration::class],
                $c[EntityResolverInterface::class],
                $c[NameResolverInterface::class],
                $c[FieldResolverInterface::class],
                $c[TableResolverInterface::class],
                $c[LoggerInterface::class],
                $this->getDatabase()
            );

        $c[SpecLoader::class] =
            fn () => new SpecLoader(
                $c[SchemaParser::class],
                $c[LoggerInterface::class]
            );

        $c[SpecLoaderInterface::class] =
            fn () => $c[SpecLoader::class];

        $c[TableResolverInterface::class] =
            fn () => new TableResolver();

        $c[TypeResolver::class] =
            fn () => new TypeResolver();

        $c[TypeResolverInterface::class] =
            fn () => $c[TypeResolver::class];

        // jane-php/json-schema

        $c[JaneObjectNormalizer::class] =
            fn () => new JaneObjectNormalizer();

        $c[Naming::class] =
            fn () => new Naming();

        // jane-php/open-api-3

        $c[SchemaParser::class] =
            fn () => new SchemaParser($c[SerializerInterface::class]);

        // psr/log

        $c[LoggerInterface::class] =
            fn () => new NullLogger();

        // symfony/serializer

        $c[ArrayDenormalizer::class] =
            fn () => new ArrayDenormalizer();

        $c[JsonEncoder::class] =
            fn () => new JsonEncoder();

        $c[SerializerInterface::class] =
            fn () => new Serializer(
                [
                    $c[ArrayDenormalizer::class],
                    $c[JaneObjectNormalizer::class],
                ],
                [
                    $c[JsonEncoder::class],
                    $c[YamlEncoder::class],
                ]
            );

        $c[YamlEncoder::class] =
            fn () => new YamlEncoder();

        // Overrides

        if ($this->delegateContainer === null) {
            return;
        }

        $overrides = array_filter(
            $c->keys(),
            fn ($key): bool => $this->delegateContainer->has($key)
        );
        foreach ($overrides as $key) {
            $c[$key] = $this->delegateContainer->get($key);
        }
    }

    /**
     * Returns the Phanua schema builder used to generate a Cycle ORM schema
     * from an OpenAPI 3 specification.
     */
    public function getSchemaBuilder(): SchemaBuilder
    {
        return $this->getContainer()[SchemaBuilder::class];
    }

    /**
     * Returns the Pimple container used internally by Phanua.
     *
     * @see https://github.com/silexphp/Pimple#usage
     */
    public function getContainer(): Container
    {
        $container = new Container();
        $this->register($container);
        return $container;
    }

    /**
     * Returns a PSR-11 compatible instance of the Pimple container used
     * internally by Phanua.
     *
     * @see https://www.php-fig.org/psr/psr-11/
     */
    public function getPsrContainer(): ContainerInterface
    {
        return new PsrContainer($this->getContainer());
    }
}
