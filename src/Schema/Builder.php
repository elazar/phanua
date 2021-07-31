<?php

namespace Elazar\Phanua\Schema;

use ArrayObject;

use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
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

use Jane\Component\OpenApi3\JsonSchema\Model\Components;
use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;
use Jane\Component\OpenApi3\JsonSchema\Model\Reference;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema as JaneSchema;

use Psr\Log\LoggerInterface;

class Builder
{
    private ORM $orm;
    private SpecLoaderInterface $specLoader;
    private Registry $registry;
    private Compiler $compiler;
    private CompilerConfiguration $compilerConfiguration;
    private EntityResolverInterface $entityResolver;
    private NameResolverInterface $nameResolver;
    private FieldResolverInterface $fieldResolver;
    private TableResolverInterface $tableResolver;
    private ContextLogger $logger;
    private ?string $database;

    public function __construct(
        ORM $orm,
        SpecLoaderInterface $specLoader,
        Registry $registry,
        Compiler $compiler,
        CompilerConfiguration $compilerConfiguration,
        EntityResolverInterface $entityResolver,
        NameResolverInterface $nameResolver,
        FieldResolverInterface $fieldResolver,
        TableResolverInterface $tableResolver,
        LoggerInterface $logger,
        ?string $database
    ) {
        $this->orm = $orm;
        $this->specLoader = $specLoader;
        $this->registry = $registry;
        $this->compiler = $compiler;
        $this->compilerConfiguration = $compilerConfiguration;
        $this->entityResolver = $entityResolver;
        $this->nameResolver = $nameResolver;
        $this->fieldResolver = $fieldResolver;
        $this->tableResolver = $tableResolver;
        $this->logger = new ContextLogger($logger);
        $this->database = $database;
    }

    /**
     * @param string|string[] $openApiSpecPaths Path to one or more OpenAPI
     *        specification files
     */
    public function buildOrm($openApiSpecPaths): ORMInterface
    {
        return $this->orm->withSchema(
            $this->buildSchema($openApiSpecPaths)
        );
    }

    /**
     * @param string|string[] $openApiSpecPaths Path to one or more OpenAPI
     *        specification files
     */
    public function buildSchema($openApiSpecPaths): CycleSchema
    {
        if (is_string($openApiSpecPaths)) {
            $openApiSpecPaths = [ $openApiSpecPaths ];
        }
        $resolvedComponents = 0;

        foreach ($openApiSpecPaths as $openApiSpecPath) {
            $openApiSpec = $this->specLoader->load($openApiSpecPath);
            $componentSchemas = $this->getComponentSchemas($openApiSpec);

            foreach ($componentSchemas as $componentName => $componentSchema) {
                $this->logger->reset(['component' => $componentName]);

                $entity = $this->getEntity($componentName, $componentSchema);
                if ($entity === null) {
                    continue;
                }
                $resolvedComponents++;

                $fields = $entity->getFields();
                $foundPrimary = false;
                foreach ($componentSchema->getProperties() ?: [] as $propertyName => $propertySchema) {
                    $field = $this->getField($componentName, $propertyName, $propertySchema);
                    if ($field === null) {
                        continue;
                    }
                    $fieldName = $this->getName($componentName, $propertyName, $propertySchema);
                    $foundPrimary = $foundPrimary || $field->isPrimary();
                    $fields->set($fieldName, $field);
                }

                $this->checkPrimary($componentName, $foundPrimary);
                $this->registerEntity($entity);
                $this->linkEntity($componentName, $componentSchema, $entity);
            }
        }

        if ($resolvedComponents === 0) {
            $this->noResolvableComponents();
        }

        $schemaConfiguration = $this->getSchemaConfiguration();
        return new CycleSchema($schemaConfiguration);
    }

    /**
     * @return iterable<JaneSchema|Reference>
     * @throws Exception
     */
    private function getComponentSchemas(OpenApi $openApiSpec)
    {
        $components = $openApiSpec->getComponents();
        if ($components === null) {
            $this->noResolvableComponents();
        }
        return $components->getSchemas();
    }

    private function getEntity(
        string $componentName,
        JaneSchema $componentSchema
    ): ?Entity {
        $this->logger->debug('Resolving component to entity');
        $entity = $this->entityResolver->getEntity($componentName, $componentSchema);
        if ($entity === null) {
            $this->logger->debug('Component could not be resolved or was excluded');
            return null;
        }
        $this->logger->debug('Resolved component to entity', [
            'entity' => $entity,
        ]);
        return $entity;
    }

    private function getName(
        string $componentName,
        string $propertyName,
        JaneSchema $propertySchema
    ): string {
        $this->logger->debug('Resolving property to name', [
            'property' => $propertyName,
        ]);
        $fieldName = $this->nameResolver->getName(
            $componentName,
            $propertyName,
            $propertySchema
        );
        $this->logger->add(['name' => $fieldName]);
        return $fieldName;
    }

    private function getField(
        string $componentName,
        string $propertyName,
        JaneSchema $propertySchema
    ): ?Field {
        $this->logger->debug('Resolving property to field');
        $field = $this->fieldResolver->getField(
            $componentName,
            $propertyName,
            $propertySchema
        );
        if ($field === null) {
            $this->logger->debug('Property could not be resolved or was excluded');
            return null;
        }
        $this->logger->debug('Resolved property to field', [
            'field' => $field,
        ]);
        return $field;
    }

    /**
     * Check that a primary key was found for a component.
     *
     * Cycle\Schema\Compiler->compile() will silently drop any entity
     * that has no primary key, so each component must have one.
     *
     * @see https://github.com/cycle/schema-builder/blob/v1.2.0/src/Compiler.php#L68
     * @throws Exception
     */
    private function checkPrimary(
        string $componentName,
        bool $foundPrimary
    ): void {
        if (!$foundPrimary) {
            $this->logger->error('Component has no primary key');
            throw Exception::noPrimaryKey($componentName);
        }
    }

    private function registerEntity(
        Entity $entity
    ): void {
        $this->logger->debug('Registering entity');
        $this->registry->register($entity);
        $this->logger->add(['registry' => $this->registry]);
    }

    /**
     * @throws Exception
     */
    private function linkEntity(
        string $componentName,
        JaneSchema $componentSchema,
        Entity $entity
    ): void {
        $this->logger->debug('Linking entity to table');
        $table = $this->tableResolver->getTable(
            $componentName,
            $componentSchema,
            $entity
        );
        $this->logger->add(['table' => $table]);
        $this->registry->linkTable($entity, $this->database, $table);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function getSchemaConfiguration(): array
    {
        $this->logger->debug('Compiling registry to schema configuration', [
            'compiler' => $this->compilerConfiguration,
            'registry' => $this->registry,
        ]);
        $schema = $this->compiler->compile(
            $this->registry,
            $this->compilerConfiguration->getGenerators(),
            $this->compilerConfiguration->getDefaults()
        );
        $this->logger->debug('Compiled registry to schema configuration', [
            'schema' => $schema,
        ]);
        return $schema;
    }

    /**
     * @throws Exception
     */
    private function noResolvableComponents(): void
    {
        $this->logger->reset();
        $this->logger->error('No resolvable components in OpenAPI specification');
        throw Exception::noResolvableComponents();
    }
}
