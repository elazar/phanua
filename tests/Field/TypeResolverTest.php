<?php

use Elazar\Phanua\Field\TypeResolver;
use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

/**
 * @param array<string, mixed> $options
 */
function getPropertySchema(string $type, array $options): Schema
{
    $schema = new Schema();
    $schema->setType($type);

    $keys = [
        'format',
        'minimum',
        'exclusiveMinimum',
        'maximum',
        'exclusiveMaximum',
        'minLength',
        'maxLength',
    ];
    foreach ($keys as $key) {
        if (isset($options[$key])) {
            $method = "set$key";
            $schema->$method($options[$key]);
        }
    }

    return $schema;
}

beforeEach(function () {
    $this->resolver = new TypeResolver();
});

it('returns correct types', function (
    string $inputType,
    string $outputType,
    array $options = []
) {
    $schema = getPropertySchema($inputType, $options);
    $result = $this->resolver->getType('', '', $schema);
    expect($result)->toBe($outputType);
})
->with([

    ['boolean', 'boolean'],

    ['number', 'double', [
        'format' => 'double',
    ]],

    ['number', 'float', [
        'format' => 'float',
    ]],

    ['number', 'double'],

    ['integer', 'bigInteger', [
        'format' => 'int64',
    ]],

    ['integer', 'integer', [
        'format' => 'int32',
    ]],

    ['string', 'date', [
        'format' => 'date',
    ]],

    ['string', 'datetime', [
        'format' => 'date-time',
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => TypeResolver::INT1_MIN,
        'maximum' => TypeResolver::INT1_MAX,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => 0,
        'maximum' => TypeResolver::UINT1_MAX,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => TypeResolver::INT1_MIN - 1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::INT1_MAX,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => -1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::UINT1_MAX,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => TypeResolver::INT1_MIN,
        'maximum' => TypeResolver::INT1_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => 0,
        'maximum' => TypeResolver::UINT1_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => TypeResolver::INT1_MIN - 1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::INT1_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'tinyInteger', [
        'minimum' => -1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::UINT1_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'integer', [
        'minimum' => TypeResolver::INT32_MIN,
        'maximum' => TypeResolver::INT32_MAX,
    ]],

    ['integer', 'integer', [
        'minimum' => 0,
        'maximum' => TypeResolver::UINT32_MAX,
    ]],

    ['integer', 'integer', [
        'minimum' => TypeResolver::INT32_MIN - 1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::INT32_MAX,
    ]],

    ['integer', 'integer', [
        'minimum' => -1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::UINT32_MAX,
    ]],

    ['integer', 'integer', [
        'minimum' => TypeResolver::INT32_MIN,
        'maximum' => TypeResolver::INT32_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'integer', [
        'minimum' => 0,
        'maximum' => TypeResolver::UINT32_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'integer', [
        'minimum' => TypeResolver::INT32_MIN - 1,
        'exclusiveMinimum' => true,
        'maximum' => TypeResolver::INT32_MAX + 1,
        'exclusiveMaximum' => true,
    ]],

    ['integer', 'bigInteger'],

    ['string', 'tinyBinary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'tinyBinary', [
        'format' => 'binary',
        'maxLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'tinyBinary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT1_MAX,
        'maxLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'binary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT1_MAX + 1,
    ]],

    ['string', 'binary', [
        'format' => 'binary',
        'maxLength' => TypeResolver::UINT1_MAX + 1,
    ]],

    ['string', 'binary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT1_MAX + 1,
        'maxLength' => TypeResolver::UINT1_MAX + 1,
    ]],

    ['string', 'bigBinary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'bigBinary', [
        'format' => 'binary',
        'maxLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'bigBinary', [
        'format' => 'binary',
        'minLength' => TypeResolver::UINT32_MAX + 1,
        'maxLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'tinyText', [
        'minLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'tinyText', [
        'maxLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'tinyText', [
        'minLength' => TypeResolver::UINT1_MAX,
        'maxLength' => TypeResolver::UINT1_MAX,
    ]],

    ['string', 'text', [
        'minLength' => TypeResolver::UINT32_MAX,
    ]],

    ['string', 'text', [
        'maxLength' => TypeResolver::UINT32_MAX,
    ]],

    ['string', 'text', [
        'minLength' => TypeResolver::UINT32_MAX,
        'maxLength' => TypeResolver::UINT32_MAX,
    ]],

    ['string', 'bigText', [
        'minLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'bigText', [
        'maxLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'bigText', [
        'minLength' => TypeResolver::UINT32_MAX + 1,
        'maxLength' => TypeResolver::UINT32_MAX + 1,
    ]],

    ['string', 'string'],

]);
