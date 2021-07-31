<?php

namespace Elazar\Phanua\Field;

use Jane\Component\OpenApi3\JsonSchema\Model\Schema;

class TypeResolver implements TypeResolverInterface
{
    public const INT1_MIN = -128;
    public const INT1_MAX = 127;
    public const UINT1_MAX = 255;
    public const INT32_MIN = -2_147_483_648;
    public const INT32_MAX = 2_147_483_647;
    public const UINT32_MAX = 4_294_967_295;
    public const UINT64_MAX = 18_446_744_073_709_551_615;

    private ?string $defaultType;

    public function __construct(
        ?string $defaultType = 'string'
    ) {
        $this->defaultType = $defaultType;
    }

    public function getType(
        string $componentName,
        string $propertyName,
        Schema $propertySchema
    ): ?string {
        $type = $propertySchema->getType();
        $format = $propertySchema->getFormat();

        if ($type === 'boolean') {
            return 'boolean';
        }

        if ($type === 'integer') {
            return $this->getIntegerType($format, $propertySchema);
        }

        if ($type === 'number') {
            if ($format === 'float') {
                return 'float';
            }
            return 'double';
        }

        if ($format === 'date') {
            return 'date';
        }

        if ($format === 'date-time') {
            return 'datetime';
        }

        $lengthAnalyzer = $this->getLengthAnalyzer($propertySchema);

        if ($format === 'binary') {
            return $this->getBinaryFormat($lengthAnalyzer);
        }

        if ($textType = $this->getTextType($lengthAnalyzer)) {
            return $textType;
        }

        return $this->defaultType;
    }

    private function getIntegerType(?string $format, Schema $propertySchema): string
    {
        if ($format === 'int64') {
            return 'bigInteger';
        }

        if ($format === 'int32') {
            return 'integer';
        }

        $bounds = [
            [
                'tinyInteger',
                self::INT1_MIN,
                self::INT1_MAX,
                self::UINT1_MAX,
            ],
            [
                'integer',
                self::INT32_MIN,
                self::INT32_MAX,
                self::UINT32_MAX,
            ]
        ];

        $minimum = $propertySchema->getMinimum();
        $maximum = $propertySchema->getMaximum();

        /**
         * "An undefined value is the same as false."
         *
         * @see https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.3
         * @see https://datatracker.ietf.org/doc/html/draft-wright-json-schema-validation-00#section-5.5
         */
        $exclusiveMinimum = $propertySchema->getExclusiveMinimum() ?: false;
        $exclusiveMaximum = $propertySchema->getExclusiveMaximum() ?: false;

        foreach ($bounds as $bound) {
            [$type, $signedLower, $signedUpper, $unsignedUpper] = $bound;

            $meetsBound = $this->getIntegerAnalyzer(
                $signedLower,
                $signedUpper,
                $unsignedUpper
            );

            $meetsMinimum = $meetsBound($minimum, $exclusiveMinimum);
            $meetsMaximum = $meetsBound($maximum, $exclusiveMaximum);

            if ($meetsMinimum && $meetsMaximum) {
                return $type;
            }
        }

        return 'bigInteger';
    }

    private function getBinaryFormat(callable $meetsBound): string
    {
        if ($meetsBound(self::UINT1_MAX)) {
            return 'tinyBinary';
        }

        if ($meetsBound(self::UINT32_MAX)) {
            return 'binary';
        }

        return 'bigBinary';
    }

    private function getTextType(callable $meetsBound): ?string
    {
        $bounds = [
            ['tinyText', self::UINT1_MAX],
            ['text', self::UINT32_MAX],
            ['bigText', self::UINT64_MAX],
        ];

        foreach ($bounds as $bound) {
            [$type, $upperBound] = $bound;

            if ($meetsBound($upperBound)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return callable(int|float): bool
     */
    private function getLengthAnalyzer(Schema $propertySchema): callable
    {
        $minLength = $propertySchema->getMinLength();
        $maxLength = $propertySchema->getMaxLength();
        return fn ($upperBound): bool =>
            ((int) $minLength !== 0 || $maxLength !== null)
            && ((int) $minLength === 0 || $minLength <= $upperBound)
            && ($maxLength === null || $maxLength <= $upperBound);
    }

    /**
     * @return callable(int|float|null, bool): bool
     */
    private function getIntegerAnalyzer(
        int $signedLower,
        int $signedUpper,
        int $unsignedUpper
    ): callable {
        /**
         * @param int|float|null $value
         */
        return fn ($value, bool $exclusive): bool =>
            $value !== null
            && (($value >= $signedLower - (int) $exclusive && $value <= $signedUpper + (int) $exclusive)
            || ($value >= 0 - (int) $exclusive && $value <= $unsignedUpper + (int) $exclusive));
    }
}
