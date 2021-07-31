<?php

use Elazar\Phanua\Schema\Exception;
use Elazar\Phanua\Schema\SpecLoader;
use Elazar\Phanua\Service\Provider;
use Jane\Component\OpenApi3\JsonSchema\Model\OpenApi;

beforeEach(function () {
    $container = (new Provider())->getContainer();
    $this->loader = $container[SpecLoader::class];
});

it('fails to parse an invalid specification', function () {
    $rawSpec = substr(file_get_contents(PETSTORE_JSON_SPEC_PATH), 0, -1);
    $openApiSpecPath = createTempFile($rawSpec);
    $this->loader->load($openApiSpecPath);
})
->throws(Exception::class);

it('parses a valid specification', function () {
    $openApiSpec = $this->loader->load(PETSTORE_JSON_SPEC_PATH);
    expect($openApiSpec)->toBeInstanceOf(OpenApi::class);
});
