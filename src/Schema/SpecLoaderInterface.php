<?php

namespace Elazar\Phanua\Schema;

use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;

/**
 * Loader for OpenAPI specification files
 */
interface SpecLoaderInterface
{
    /**
     * @throws Exception
     */
    public function load(string $openApiSpecPath): OpenApi;
}
