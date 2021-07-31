<?php

use Elazar\Phanua\Schema\Exception;
use Elazar\Phanua\Schema\SpecLoader;
use Elazar\Phanua\Service\Provider;
use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;

beforeEach(function () {
    $container = (new Provider())->getContainer();
    $this->loader = $container[SpecLoader::class];

    $this->rawSpec = file_get_contents(PETSTORE_JSON_SPEC_PATH);
    if ($this->rawSpec === false) {
        throw new \RuntimeException(
            sprintf(
                'Failed to read file: %s',
                PETSTORE_JSON_SPEC_PATH
            )
        );
    }
});

it('fails to parse an invalid specification', function () {
    $rawSpec = substr($this->rawSpec, 0, -1);
    $openApiSpecPath = createTempFile($rawSpec);
    $this->loader->load($openApiSpecPath);
})
->throws(Exception::class);

it('fails to parse a specification with an invalid version', function () {
    $rawSpec = str_replace('3.0.0', '2.0.0', $this->rawSpec);
    $openApiSpecPath = createTempFile($rawSpec);
    $this->loader->load($openApiSpecPath);
})
->throws(Exception::class);

it('parses a valid specification', function () {
    $openApiSpec = $this->loader->load(PETSTORE_JSON_SPEC_PATH);
    expect($openApiSpec)->toBeInstanceOf(OpenApi::class);
});
