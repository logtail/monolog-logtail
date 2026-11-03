<?php

// Router for PHP's built-in web server, standing in for a Better Stack ingesting host.
// The request path picks the status it answers with, e.g. POST /401.

\file_put_contents(\getenv('INGEST_STUB_REQUEST_LOG'), $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

$status = (int) \ltrim(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

\http_response_code($status);

echo match ($status) {
    401 => '{"error": "Unauthorized"}',
    503 => 'upstream connect error or disconnect/reset before headers. ' . \str_repeat('x', 500),
    default => '',
};
