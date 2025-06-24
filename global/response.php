<?php
function responseCode(int $code): void
{
    http_response_code($code);
}

function errorMessage($message, array $options = []): string|bool
{
    $response = array_merge(["success" => false, "message" => $message], $options);
    return json_encode($response);
}

function successMessage($message, array $options = []): string|bool
{
    $response = array_merge(["success" => true, "message" => $message], $options);
    return json_encode($response);
}

function sendError(string $message, int $code = 400, array $options = []): void
{
    responseCode($code);
    echo errorMessage($message, $options);
    exit;
}

function sendSuccess(string $message,int $code = 200, array $options = []): void
{
    responseCode($code);
    echo successMessage($message, $options);
    exit;
}