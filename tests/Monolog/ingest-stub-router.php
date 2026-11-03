<?php

// Router for PHP's built-in web server, standing in for a Better Stack ingesting host.
// The request path picks the answer, e.g. POST /401 or POST /401-paused; it starts with the status.

\file_put_contents(\getenv('INGEST_STUB_REQUEST_LOG'), $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

$answer = \ltrim(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

\http_response_code((int) $answer);

echo match ($answer) {
    '401' => '{"error": "Unauthorized"}',
    '401-paused' => '{"error": "This source has been paused, will not accept new data"}',
    '402' => '{"error": "Quota exceeded"}',
    '503' => 'upstream connect error or disconnect/reset before headers. ' . \str_repeat('x', 500),
    default => '',
};
