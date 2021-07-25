# Phanua

[![PHP Version Support](https://img.shields.io/static/v1?label=php&message=%3E=%207.4.0&color=blue)](https://packagist.org/packages/elazar/phanua)
[![Packagist Version](https://img.shields.io/static/v1?label=packagist&message=0.1.0&color=blue)](https://packagist.org/packages/elazar/phanua)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![Buy Me a Cofee](https://img.shields.io/badge/buy%20me%20a%20coffee-donate-blue.svg)](https://www.buymeacoffee.com/DIkm1qe)
[![Patreon](https://img.shields.io/badge/patreon-donate-blue.svg)](https://patreon.com/matthewturland)

[OpenAPI 3](https://swagger.io/specification/) + [Jane](https://jane.readthedocs.io/en/latest/) + [Cycle ORM](https://cycle-orm.dev) = 🔥

Phanua builds Cycle ORM schemas from OpenAPI 3 component schemas.

Released under the [MIT License](https://en.wikipedia.org/wiki/MIT_License).

**WARNING: This project is in an alpha state of development and may be subject to changes that break backward compatibility. It may contain bugs, lack features or extension points, or be otherwise unsuitable for production use. User discretion is advised.**

## Requirements

* PHP 7.4+
* [PDO](https://www.php.net/pdo) and [driver](https://www.php.net/manual/en/pdo.drivers.php) for desired database

## Installation

Use [Composer](https://getcomposer.org/).

```sh
composer require elazar/phanua
```

## Usage

```php
<?php

/**
 * 1. Configure the Phanua service provider.
 *
 * The next section covers this step in more detail.
 */

$provider = new \Elazar\Phanua\Service\Provider;

// Configure $provider as needed here.

/**
 * 2. Use the Phanua service provider to create a Phanua schema builder.
 */

$schemaBuilder = $provider->getSchemaBuilder();

/**
 * 3. Use the Phanua schema builder to generate a Cycle schema or ORM instance.
 */

// To configure an existing ORM instance to use the generated schema:
$orm = new \Cycle\ORM\ORM(/* ... */);
// ...
$schema = $schemaBuilder->buildSchema();
$orm = $orm->withSchema($schema);

// To have Phanua create a new ORM instance with the generated schema:
$orm = $schemaBuilder->buildOrm();
```

## Configuration

For its most basic use, Phanua needs three parameters:

1. the path to an OpenAPI 3 specification file in JSON or YAML format;
2. a PHP namespace used by [generated](https://jane.readthedocs.io/en/latest/documentation/OpenAPI.html#generating) [Jane model class files](https://jane.readthedocs.io/en/latest/documentation/OpenAPI.html#using) (without the `Model` subnamespace added by Jane); and
3. a Cycle ORM database configuration.

You can pass these parameters to Phanua by using an instance of the Phanua service provider class `Elazar\Phanua\Service\Provider`.

You can provide the path and namespace provided using your [Jane configuration file](https://jane.readthedocs.io/en/latest/documentation/OpenAPI.html#configuration-file) or by passing them directly to the service provider instance.

```php
<?php

use Elazar\Phanua\Service\Provider;

// To load the Jane configuration file, provide the path
$provider = (new Provider)
    ->withJaneConfiguration('/path/to/.jane-openapi.php');

// If file is already loaded, provide the contained array
$janeConfig = require '.jane-openapi.php';
$provider = (new Provider)
    ->withJaneConfiguration($janeConfig);

// To pass the same values directly:
$provider = (new Provider)
    ->withOpenApiSpecPath('path/to/openapi.json')
    ->withNamespace('\\Foo\\Generated');
```

You can provide the database configuration by specifying the same array passed to `Spiral\Database\Config\DatabaseConfig`. See [related Cycle documentation](https://cycle-orm.dev/docs/basic-connect#instantiate-dbal) for an example of this array.

```php
<?php

$databaseConfig = [ /* ... */ ];
$provider = (new \Elazar\Phanua\Service\Provider)
    ->withDatabaseConfig($databaseConfig);
```

Providing the database configuration in other ways requires an explanation of how Phanua handles its dependencies.

### Overriding Dependencies

To overriding a dependency of Phanua, you must provide a dependency injection container that includes that dependency.

Phanua can use any container that implements the [PSR-11](https://www.php-fig.org/psr/psr-11/) standard. An example of such a container is [Pimple](https://packagist.org/packages/pimple/pimple), which Phanua uses internally. Pimple is the recommended container to use if you aren't already using a different one.

```php
<?php

// Create a Pimple or PSR-11 container instance
$container = new \Pimple\Container;

// Then configure Phanua to use it
$provider = (new \Elazar\Phanua\Service\Provider)
    ->withDelegateContainer($container);
```

Phanua expects this container to use [fully-qualified class names](https://www.php.net/manual/en/language.namespaces.rules.php) as entry identifiers. Below are examples of alternate methods of configuring Pimple to supply the database configuration.

```php
<?php

$container = new \Pimple\Container;

// Compared to the earlier example of passing the database configuration to
// Phanua as an array, this is the next easiest / most low-level method of doing
// so if you're not already using a container in your application.
use Spiral\Database\Config\DatabaseConfig;
$container[DatabaseConfig::class] = fn() => new DatabaseConfig(
    // Pass the same array passed to Provider->withDatabaseConfig() in the earlier
    // example here.
);

// If you're already using a container and it includes a configured instance of
// the DatabaseManager class used by Cycle ORM, you can specify that instead.
use Spiral\Database\Config\DatabaseManager;
$container[DatabaseManager::class] = fn() => new DatabaseManager(
    new DatabaseConfig(/* ... */)
);

// Or you can specify an implementation of Cycle\ORM\FactoryInterface, such as
// the Cycle\ORM\Factory class.
use Spiral\Database\Config\{Factory, FactoryInterface};
$container[FactoryInterface::class] = fn() => new Factory(
    new DatabaseManager(/* ... */)
);

// Or you can specify an instance of Cycle\ORM\ORM.
use Cycle\ORM\ORM;
$container[ORM::class] = fn() => new ORM(
    new Factory(/* ... */)
);
```

If you're already using Pimple and want Phanua to use dependencies you've registered in your container, you can register your configured Phanua service provider instance with your container as a [provider](https://github.com/silexphp/Pimple#extending-a-container).

```php
<?php

$provider = new \Elazar\Phanua\Service\Provider;

// Configure $provider as needed here.

$container = new \Pimple\Container;
$container->register($provider);
```

Once you've configured the Phanua service provider with the necessary parameters, it's possible to use the container it builds independently.

```php
<?php

$provider = new \Elazar\Phanua\Service\Provider;

// Configure $provider as needed here.

// Pimple
$container = $provider->getContainer();

// PSR-11
$psrContainer = $provider->getPsrContainer();

// To get the schema builder:

use Elazar\Phanua\Schema\Builder;

// Pimple
$schemaBuilder = $container[Builder::class];

// PSR-11
$schemaBuilder = $container->get(Builder::class);
```

### Role Resolver

Cycle ORM uses the term ["role"](https://cycle-orm.dev/docs/advanced-schema#schema-properties) to refer to a unique name for an entity.

Phanua defines the interface `Elazar\Phanua\Entity\RoleResolverInterface` for classes used to determine the role for a given entity.

This interface contains a single method `getRole()` which receives a single parameter: a `string` containing the name of the OpenAPI component corresponding to the entity.

`getRole()` must return a `string` containing the determined role for the entity.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Entity\RoleResolver`. Its `getRole()` method returns the OpenAPI component name it receives so that the component name and role are the same.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Entity\RoleResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Class Resolver

Entities in Cycle ORM schemas have corresponding [classes](https://cycle-orm.dev/docs/basic-crud#create-entity).

Phanua defines the interface `Elazar\Phanua\Entity\ClassResolverInterface` for classes used to determine the class for a given entity.

This interface contains a single method, `getClass()`, which receives a single parameter: a `string` containing the name of the OpenAPI component corresponding to the entity.

`getClass()` must return a `string` containing the [fully-qualified name](https://www.php.net/manual/en/language.namespaces.rules.php) of the class for the entity.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Entity\ClassResolver`. Its `getClass()` method returns a class name constructed with the namespace used by Jane from the Phanua service provider configuration and the OpenAPI component name it receives.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Entity\ClassResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Table Resolver

Each Cycle ORM entity has a corresponding [table](https://cycle-orm.dev/docs/advanced-declaration#to-start).

Phanua defines the interface `Elazar\Phanua\Entity\TableResolverInterface` for classes used to determine the table associated with a given entity.

This interface contains a single method, `getTable()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity;
2. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the component schema; and
3. an instance of `Cycle\Schema\Definition\Entity` representing the entity.

`getTable()` must return a `string` containing the name of the table for the entity.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Entity\TableResolver`. Its `getTable()` method returns the entity role, as determined by the instance of `Elazar\Phanua\Entity\RoleResolverInterface` in use, so that the role and table name are the same. See the ["Role Resolver"](#role-resolver) section above for further details.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Entity\TableResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Name Resolver

Entities in Cycle ORM contain [fields](https://cycle-orm.dev/docs/advanced-dynamic-schema#using-schema-builder) that have names.

Phanua defines the interface `Elazar\Phanua\Field\NameResolverInterface` for classes used to determine the name of a field.

This interface contains a single method, `getName()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity containing the field;
2. a `string` containing the name of the component property corresponding to the field; and
3. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the property schema.

`getName()` must return a `string` containing the name to assign to the field.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Field\NameResolver`. Its `getName()` method returns the OpenAPI property name it receives so that the property name and field name are the same.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Field\NameResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Column Resolver

Cycle ORM entity fields have corresponding table [columns](https://cycle-orm.dev/docs/advanced-declaration#columns-and-abstract-types).

Phanua defines the interface `Elazar\Phanua\Field\ColumnResolverInterface` for classes used to determine the name of the column for a field.

This interface contains a single method, `getColumn()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity containing the field;
2. a `string` containing the name of the component property corresponding to the field; and
3. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the property schema.

`getColumn()` must return a `string` containing the name to assign to the column.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Field\ColumnResolver`. Its `getColumn()` method returns the OpenAPI property name it receives so that the property name and column name are the same.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Field\ColumnResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Type Resolver

Each Cycle ORM entity field has a corresponding [abstract type](https://cycle-orm.dev/docs/advanced-declaration#columns-and-abstract-types-abstract-types).

Phanua defines the interface `Elazar\Phanua\Field\TypeResolverInterface` for classes used to determine the type for a field.

This interface contains a single method, `getType()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity containing the field;
2. a `string` containing the name of the component property corresponding to the field; and
3. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the property schema.

`getType()` may return a `string` containing the type to assign to the column or `null` if it's unable to resolve the column to a type.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Field\TypeResolver`. Its `getType()` method returns a type it derives from the [property type and format](https://swagger.io/specification/) as well as [validation constraints](https://swagger.io/specification/#schemaObject) on the property value (e.g. `minimum`, `exclusiveMinimum`, `maximum`, or `exclusiveMaximum` for numeric values, `minLength` and `maxLength` for string / text values). Lacking these constraints, `getType()` returns the largest available type to allow for maximal possible values.

**NOTE: This implementation does not handle nested objects, arrays, or references to other component schemas. This may change in the future.**

In the event of being unable to resolve to a more specific type, `getType()` supports falling back to a specified type. To reduce configuration needed for common use cases, the default value for this fallback type is `'string'`.

You may want change this fallback type if, for example, you want to compose `TypeResolver` with your own resolver implementation to resolve types that `TypeResolver` doesn't cover. In that case, you would set the fallback type to `null` so that your resolver knows when `TypeResolver` can't resolve a given type. Below is an example of overriding the fallback type using a Pimple container.

```php
<?php

use Elazar\Phanua\Field\TypeResolver;
$container[TypeResolver::class] = new TypeResolver(
    null // fallback value goes here
);
```

To override the default type resolver implementation with your own, add an entry to your container using the key `Elazar\Phanua\Field\TypeResolverInterface` and have it resolve to an instance of a class that implements the interface.

If you want to use the default type resolver, your own resolver, or another resolver together, you can compose them using `Elazar\Phanua\Field\CompositeFieldResolver`, which will use resolvers in turn until one returns a type.

```php
<?php

use Elazar\Phanua\Field\CompositeTypeResolver;
use Elazar\Phanua\Field\TypeResolver;
use Elazar\Phanua\Field\TypeResolverInterface;
use My\CustomTypeResolver;

// If you want your resolver to be tried first:
$container[TypeResolverInterface::class] = new CompositeTypeResolver(
    new CustomTypeResolver(),
    new TypeResolver(),
);

// If you want the default resolver to be tried first:
$container[TypeResolverInterface::class] = new CompositeTypeResolver(
    new TypeResolver(
        null // Required for CompositeTypeResolver to fall back to your resolver
    ),
    new CustomTypeResolver(),
);
```

### Primary Resolver

Each table must have a [primary index](https://cycle-orm.dev/docs/advanced-declaration#primary-index) on one or more columns for a compiled Cycle ORM schema to include it.

This circumstance is a limitation of the compiler that handles [compiling entities from the registry](https://cycle-orm.dev/docs/advanced-schema-builder#manually-define-entity) into a schema: it [skips entities without a primary index](https://github.com/cycle/schema-builder/blob/v1.2.0/src/Compiler.php#L68).

To draw attention to this behavior, Phanua throws an exception if primary index resolution fails for a given entity. To avoid this, you must explicitly exclude any entities in your OpenAPI specification that you do not want to include in the generated Cycle ORM schema. The [next section](#entity-resolver) discusses this further.

Phanua defines the interface `Elazar\Phanua\Field\PrimaryResolverInterface` for classes used to determine whether a given field should be part of the primary index of the table containing its respective column.

This interface contains a single method, `isPrimary()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity containing the field;
2. a `string` containing the name of the component property corresponding to the field; and
3. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the property schema.

`isPrimary()` must return a `boolean` value where a value of `true` indicates that the given field is part of the primary index for the associated table and a value of `false` indicates that it's not.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Field\PrimaryResolver`. Its `isPrimary()` method returns `true` if the given property name is `id` or `false` otherwise. This class assumes that the property and column names are the same, which is the case when using the default implementation of `Elazar\Phanua\Field\ColumnResolverInterface` detailed in the ["Column Resolver"](#column-resolver) section above.

If your property and column names are not consistent with each other, or if any of your tables have a primary index that includes more than one column, create your own implementation of this interface to suit the needs of your database.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Field\PrimaryResolverInterface` and have it resolve to an instance of a class that implements the interface.

### Entity Resolver

Phanua represents the process of converting an OpenAPI component to a Cycle ORM entity with the interface `Elazar\Phanua\Entity\EntityResolverInterface`.

This interface contains a single method, `getEntity()`, which receives two parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity; and
2. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the component schema.

`getEntity()` must return a populated instance of `Cycle\Schema\Definition\Entity` representing the entity corresponding to the given component or `null` if it fails to resolve the component to an entity.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Entity\EntityResolver`. It composes an implementation of each of `Elazar\Phanua\Entity\RoleResolverInterface` and `Elazar\Phanua\Entity\ClassResolverInterface`, which it uses to determine the entity [role](#role-resolver) and [class](#class-resolver) respectively.

It also provides for the exclusion of specific components from having an entity in the generated schema, such as those components where the corresponding entity would not have a [primary index](#primary-resolver). The easiest way to exclude one or more components is by using the service provider: by default, it handles injecting a list of components to exclude into `EntityResolver`.

```php
<?php

$excludedComponents = [
    'component1',
    'component2',
    // ...
];

$provider = (new \Elazar\Phanua\Service\Provider)
    ->withExcludedComponents($excludedComponents);
```

You can also exclude components by [overriding](#overriding-dependencies) the `EntityResolver` instance used by default with one that includes the names of components to exclude in the `$exclude` constructor parameter of `EntityResolver` or does something similar for your own entity resolver implementation. The example below does this using a Pimple container.

```php
<?php

use Elazar\Phanua\{
    Entity\ClassResolverInterface,
    Entity\EntityResolver,
    Entity\EntityResolverInterface,
    Entity\RoleResolverInterface,
    Service\Provider
};

$provider = new Provider;

/**
 * 1. Get a container from the Phanua service provider to override some of the
 *    default dependencies it defines.
 */

// Pimple
$phanuaContainer = $provider->getContainer();

// PSR-11
$phanuaContainer = $provider->getPsrContainer();

/**
 * 2. In your own container, use the key EntityResolverInterface::class and
 *    assign it an instance of EntityResolver or your own entity resolver
 *    implementation, injecting any default Phanua dependencies that you need.
 *    Below is an example of doing this using Pimple containers.
 */

$yourContainer = new \Pimple\Container;
$yourContainer[EntityResolverInterface::class] = fn() => new EntityResolver(
    $phanuaContainer[RoleResolverInterface::class],
    $phanuaContainer[ClassResolverInterface::class],
    [ /* strings containing names of components to exclude go here */ ]
);

/**
 * 3. Set your container as the delegate container in the Phanua service
 *    provider.
 */

$provider = $provider->withDelegateContainer($yourContainer);

/**
 * 4. Get the schema builder as normal. It will use the custom dependencies
 *    you've defined.
 */

$schemaBuilder = $provider->getSchemaBuilder();
```

It's entirely optional to have your entity resolver use other Phanua dependencies, as the default Phanua implementation does; your entity resolver can function in whatever way you like.

Another approach to consider is having your entity resolver implementation compose the default implementation that Phanua uses. By doing so, you can use the default implementation for entities that are compatible with it and your own implementation for those that are not. See the example of this below.

```php
<?php

/**
 * 1. Define your entity resolver implementation.
 */

use Cycle\Schema\Definition\Entity;
use Elazar\Phanua\Entity\EntityResolverInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class YourEntityResolver implements EntityResolverInterface
{
    private EntityResolverInterface $entityResolver;

    // Have the constructor accept another resolver implementation as a
    // parameter.

    public function __construct(
        EntityResolverInterface $entityResolver,
        /* ... */
    ) {
        $this->entityResolver = $entityResolver;
        /* ... */
    }

    // Then, in the implementation of the interface method...

    public function getEntity(
        string $componentName,
        Schema $componentSchema
    ): ?Entity
    {
        // $entity = ...

        // ... if your implementation can't resolve an entity...
        if ($entity === null) {

            // ... then have it defer to the injected resolver.
            $entity = $this->entityResolver->getEntity(
                $componentName,
                $componentSchema
            );

        }

        return $entity;
    }
}

/**
 * 2. Inject your implementation to have Phanua use it.
 */

// Get the Phanua container and configure your container as normal...
$phanuaContainer = $provider->getContainer();
$yourContainer = new \Pimple\Container;
$yourContainer[EntityResolverInterface::class] = fn() => new YourEntityResolver(

    // ... then inject the entity resolver implementation from the Phanua
    // container into your own implementation.
    $phanuaContainer[EntityResolverInterface::class]

);
```

### Field Resolver

Phanua includes a resolver for fields as it does for entities, represented by the `Elazar\Phanua\Field\FieldResolverInterface`.

This interface contains a single method, `getField()`, which receives three parameters:

1. a `string` containing the name of the OpenAPI component corresponding to the entity containing the field;
2. a `string` containing the name of the component property corresponding to the field; and
3. an instance of `Jane\Component\OpenApi3\JsonSchema\Model\Schema` representing the property schema.

`getField()` must return a populated instance of `Cycle\Schema\Definition\Field` representing the field corresponding to the given property or `null` if it fails to resolve the component to a field.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Field\FieldResolver`. It composes an implementation of each of `Elazar\Phanua\Field\ColumnResolverInterface`, `Elazar\Phanua\Field\TypeResolverInterface`, and `Elazar\Phanua\Field\PrimaryResolverInterface`, which it uses to determine the field [column](#column-resolver), [type](#type-resolver), and presence in the [primary index](#primary-resolver) respectively.

It also handles setting other options for the field, such as its default value and whether it's nullable based on corresponding [`default`](https://swagger.io/specification/#properties) and [`nullable`](https://swagger.io/specification/#fixed-fields-20) values from the given property schema.

`FieldResolver` also provides for the exclusion of specific properties from having a field in the generated schema. The easiest way to exclude one or more properties is by using the service provider: by default, it handles injecting a list of properties to exclude into `FieldResolver`.

```php
<?php

$excludedProperties = [
    'propertyName',
    'component.propertyName',
    // ...
];

$provider = (new \Elazar\Phanua\Service\Provider)
    ->withExcludedProperties($excludedProperties);
```

You can also exclude properties by [overriding](#overriding-dependencies) the `FieldResolver` instance used by default with one that includes the names of properties to exclude in the `$exclude` constructor parameter of `FieldResolver`. The example below does this using a Pimple container.

```php
<?php

use Elazar\Phanua\{
    Field\ColumnResolverInterface,
    Field\FieldResolver,
    Field\FieldResolverInterface,
    Field\PrimaryResolverInterface,
    Field\TypeResolverInterface,
    Service\Provider
};

$provider = new Provider;

/**
 * 1. Get a container from the Phanua service provider to override some of the
 *    default dependencies it defines.
 */

// Pimple
$phanuaContainer = $provider->getContainer();

// PSR-11
$phanuaContainer = $provider->getPsrContainer();

/**
 * 2. In your own container, use the key FieldResolverInterface::class and
 *    assign it an instance of your entity resolver implementation, injecting
 *    any default Phanua dependencies that you need. Below is an example of
 *    doing this using Pimple containers.
 */

$yourContainer = new \Pimple\Container;
$yourContainer[FieldResolverInterface::class] = fn() => new FieldResolver(
    $phanuaContainer[ColumnResolverInterface::class],
    $phanuaContainer[PrimaryResolverInterface::class],
    $phanuaContainer[TypeResolverInterface::class],
    [ // names of properties to exclude go here, e.g.
        'propertyName',
        'componentName.propertyName',
        // ...
    ]
);

/**
 * 3. Set your container as the delegate container in the Phanua service
 *    provider.
 */

$provider = $provider->withDelegateContainer($yourContainer);

/**
 * 4. Get the schema builder as normal. It will use the custom dependencies
 *    you've defined.
 */

$schemaBuilder = $provider->getSchemaBuilder();
```

It's entirely optional to have your field resolver use other Phanua dependencies, as the default Phanua implementation does; your field resolver can function in whatever way you like.

Another approach to consider is having your field resolver implementation compose the default implementation that Phanua uses. By doing so, you can use the default implementation for fields that are compatible with it and your own implementation for those that are not. See the example of this below.

```php
<?php

/**
 * 1. Define your field resolver implementation.
 */

use Cycle\Schema\Definition\Field;
use Elazar\Phanua\Field\FieldResolverInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class YourFieldResolver implements FieldResolverInterface
{
    private FieldResolverInterface $fieldResolver;

    // Have the constructor accept another resolver implementation as a
    // parameter.

    public function __construct(
        FieldResolverInterface $fieldResolver,
        /* ... */
    ) {
        $this->fieldResolver = $fieldResolver;
        /* ... */
    }

    // Then, in the implementation of the interface method...

    public function getField(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?Field
    {
        // $field = ...

        // ... if your implementation can't resolve an entity...
        if ($field === null) {

            // ... then have it defer to the injected resolver.
            $field = $this->fieldResolver->getField(
                $componentName,
                $propertyName,
                $propertySchema
            );

        }

        return $field;
    }
}

/**
 * 2. Inject your implementation to have Phanua use it.
 */

// Get the Phanua container and configure your container as normal...
$phanuaContainer = $provider->getContainer();
$yourContainer = new \Pimple\Container;
$yourContainer[FieldResolverInterface::class] = fn() => new YourFieldResolver(

    // ... then inject the field resolver implementation from the Phanua
    // container into your own implementation.
    $phanuaContainer[FieldResolverInterface::class]

);
```

### Logger

Phanua supports any [PSR-3 logger](https://www.php-fig.org/psr/psr-3/). By default, it uses `Psr\Logger\NullLogger`, which discards any logged entries. To store these entries, you must override the default logger with one that does something with them. Below is an example of using the Pimple container to override the default logger with one from the [Monolog](https://github.com/Seldaek/monolog/) library.

```php
<?php

use Elazar\Phanua\Service\Provider;
use Monolog\Logger;
use Pimple\Container;
use Psr\Log\LoggerInterface;

$yourContainer = new Container;
$yourContainer[LoggerInterface::class] = function () {
    $logger = new Logger;
    // Configure $logger as needed here
    return $logger;
};

$provider = (new Provider)
    ->withDelegateContainer($yourContainer);
```

### Specification Loader

To convert an OpenAPI specification to a Cycle ORM schema, Phanua must first load and parse that specification.

Phanua defines the interface `Elazar\Phanua\Schema\SpecLoaderInterface` for classes used to load and parse OpenAPI specification files.

This interface contains a single method `load()` which receives a single parameter: a `string` containing the path to an OpenAPI specification file.

`load()` must return an instance of `Jane\Component\OpenApi3\JsonSchema\Model\OpenApi` containing the parsed specification or throw an instance of `Elazar\Phanua\Schema\Exception` if loading or parsing the specification fails.

The implementation of this interface that Phanua uses by default is `Elazar\Phanua\Schema\SpecLoader`. It handles logging events related to loading and parsing the specification.

To override the default implementation, add an entry to your container using the key `Elazar\Phanua\Schema\SpecLoaderInterface` and have it resolve to an instance of a class that implements the interface.

### Registry

As Phanua converts OpenAPI components to Cycle ORM entities, it uses an instance of `Cycle\Schema\Registry` to register the entities and link them to tables. This registry is ultimately used to compile the entities into an instance of `Cycle\ORM\Schema`.

By default, Phanua uses an empty `Registry` instance configured for your database. You want to use a different instance if, for example, you're compiling entities generated from other sources into the same schema with entities generated by Phanua.

In such situations, add an entry to your container using the key `Cycle\Schema\Registry` and have it resolve to your desired `Registry` instance to have Phanua use it. Note that you can use the Phanua container to either use a modified version of its default registry instance or to supply your registry's required implementation of `Spiral\Database\DatabaseProviderInterface` using the same instance of `Spiral\Database\DatabaseManager` that Phanua does. Below is an example of doing each with a Pimple container.

```php
<?php
use Cycle\Schema\Registry;
use Phanua\Service\Provider;
use Pimple\Container;
use Spiral\Database\DatabaseProviderInterface;

$provider = new Provider;

// If you're using Phanua\Service\Provider as a Pimple provider:
$yourContainer = new Container;
$yourContainer->register($provider);
$yourContainer->extend(Registry::class, function (Registry $registry) {
    // Change $registry as needed here
});

// If you're not using Phanua\Service\Provider as a Pimple provider:
$phanuaContainer = $provider->getContainer();
$yourContainer = new Container;
$yourContainer[Registry::class] = function () use ($phanuaContainer) {
    $databaseProvider = $phanuaContainer[DatabaseProviderInterface::class];
    $registry = new Registry($databaseProvider);
    // Configure $registry here as needed
    return $registry;
};
$phanuaContainer[Registry::class] = fn() => $yourContainer[Registry::class];
```

### Compiler Configuration

Phanua uses an instance of `Cycle\Schema\Compiler` to compile a registry of entities into a schema. This compiler can use [custom generators](https://cycle-orm.dev/docs/advanced-schema-builder#custom-generators) and [defaults](https://cycle-orm.dev/docs/advanced-default-classes). By default, Phanua uses default values for each of these (i.e. empty arrays).

If you want to use custom values for either of these, Phanua provides `Elazar\Phanua\Schema\CompilerConfiguration` to override them. Add an entry for this class to your container with a configured instance. Below is an example of doing this with a Pimple container.

```php
<?php

use Elazar\Phanua\Schema\CompilerConfiguration;
use Elazar\Phanua\Service\Provider;
use Pimple\Container;

$yourContainer = new Container;
$yourContainer[CompilerConfiguration::class] = fn() => (new CompilerConfiguration)
    ->withGenerators(/* ... */)
    ->withDefaults(/* ... */);

$provider = (new Provider)
    ->withDelegateContainer($yourContainer);
```

## FAQ

### Why did you build this?

I was already using OpenAPI and Jane in some projects and wanted to use Cycle ORM as well. I was already using model classes generated by Jane and didn't want to have to manually annotate or otherwise change them to be usable with Cycle. I built this so I wouldn't have to.

### Why the name "Phanua?"

It's taken from the latter half of the Swahili word *kufafanua*, which means "to define," and adapted to replace the "f" with "ph" as is common with PHP project names.

### How do you pronounce "Phanua?"

Fah-NOO-ah.
