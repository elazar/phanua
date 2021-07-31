<?php

namespace Elazar\Phanua\Schema;

use Psr\Log\LoggerInterface;
use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;
use Jane\Component\OpenApi3\SchemaParser\SchemaParser;
use Jane\Component\OpenApiCommon\Exception\CouldNotParseException;
use Jane\Component\OpenApiCommon\Exception\OpenApiVersionSupportException;

class SpecLoader implements SpecLoaderInterface
{
    private SchemaParser $schemaParser;
    private LoggerInterface $logger;

    public function __construct(
        SchemaParser $schemaParser,
        LoggerInterface $logger
    ) {
        $this->schemaParser = $schemaParser;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $openApiSpecPath): OpenApi
    {
        $this->logger->debug('Parsing OpenAPI spec', [
            'path' => $openApiSpecPath,
        ]);

        try {
            $spec = $this
                ->schemaParser
                ->parseSchema($openApiSpecPath);

            $this->logger->debug('OpenAPI spec parsed', [
                'path' => $openApiSpecPath,
            ]);

            return $spec;
        } catch (CouldNotParseException $e) {
        } catch (OpenApiVersionSupportException $e) {
        }

        $error = Exception::openApiSpecParsingFailed($openApiSpecPath, $e);
        $this->logger->error('OpenAPI specification cannot be parsed', [
            'path' => $openApiSpecPath,
            'error' => $error,
        ]);
        throw $error;
    }
}
