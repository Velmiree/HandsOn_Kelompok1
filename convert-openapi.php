<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$data = Yaml::parseFile(__DIR__ . '/docs/openapi.yaml');

$json = json_encode(
    $data,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

file_put_contents(
    __DIR__ . '/resources/swagger/openapi.json',
    $json
);

echo "OpenAPI berhasil dikonversi ke JSON.\n";