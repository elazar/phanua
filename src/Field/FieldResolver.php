<?php

namespace Elazar\Phanua\Field;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Table\Column;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class FieldResolver implements FieldResolverInterface
{
    private ColumnResolverInterface $columnResolver;

    private PrimaryResolverInterface $primaryResolver;

    private TypeResolverInterface $typeResolver;

    /**
     * @var null|callable(string, string, Schema)
     */
    private $filterCallback;

    /**
     * @param null|callable(string, string, Schema): bool $filterCallback
     */
    public function __construct(
        ColumnResolverInterface $columnResolver,
        PrimaryResolverInterface $primaryResolver,
        TypeResolverInterface $typeResolver,
        $filterCallback = null
    ) {
        $this->columnResolver = $columnResolver;
        $this->primaryResolver = $primaryResolver;
        $this->typeResolver = $typeResolver;
        $this->filterCallback = $filterCallback;
    }

    /**
     * @throws Exception
     */
    public function getField(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?Field {
        if (is_callable($this->filterCallback)) {
            $include = ($this->filterCallback)(
                $componentName,
                $propertyName,
                $propertySchema
            );
            if ($include === false) {
                return null;
            }
        }

        $field = new Field();
        $options = $field->getOptions();

        $options->set(Column::OPT_NULLABLE, $propertySchema->getNullable());

        if ($default = $propertySchema->getDefault()) {
            $options->set(Column::OPT_DEFAULT, $default);
        }

        $column = $this->columnResolver->getColumn(
            $componentName,
            $propertyName,
            $propertySchema
        );
        $field->setColumn($column);

        $primary = $this->primaryResolver->isPrimary(
            $componentName,
            $propertyName,
            $propertySchema
        );
        $field->setPrimary($primary);

        $type = $this->typeResolver->getType(
            $componentName,
            $propertyName,
            $propertySchema
        );
        if ($type === null) {
            throw Exception::typeResolutionFailed(
                $componentName,
                $propertyName
            );
        }
        $field->setType($type);

        return $field;
    }
}
