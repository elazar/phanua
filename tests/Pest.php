<?php

$vendor = __DIR__ . '/../vendor';

require "$vendor/mockery/mockery/library/helpers.php";

require "$vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php";

/**
 * @see https://github.com/OAI/OpenAPI-Specification/blob/main/examples/v3.0/petstore.json
 */
define('PETSTORE_JSON_SPEC_PATH', __DIR__ . '/_files/petstore.json');

/**
 * @see https://github.com/OAI/OpenAPI-Specification/blob/main/examples/v3.0/petstore.yaml
 */
define('PETSTORE_YAML_SPEC_PATH', __DIR__ . '/_files/petstore.yaml');

function createTempFile(string $contents): string
{
    $tempDir = sys_get_temp_dir();
    $specFile = tempnam($tempDir, (string) time());
    if ($specFile === false) {
        throw new \RuntimeException(
            "Unable to create temporary file in $tempDir"
        );
    }
    file_put_contents($specFile, $contents);
    register_shutdown_function(fn () => unlink($specFile));
    return $specFile;
}
