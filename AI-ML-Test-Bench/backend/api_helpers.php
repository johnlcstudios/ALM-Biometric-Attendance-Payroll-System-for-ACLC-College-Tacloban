<?php

function apiResponse($payload = null, $success = true, $message = '', $statusCode = 200)
{
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    $response = ['success' => $success];

    if ($message !== '') {
        $response['message'] = $message;
    }

    if ($payload !== null) {
        if (is_array($payload) && $payload !== [] && array_keys($payload) !== range(0, count($payload) - 1)) {
            $response = array_merge($response, $payload);
        } else {
            $response['data'] = $payload;
        }
    }

    http_response_code($statusCode);
    echo json_encode($response);
    exit;
}

function apiSuccess($payload = null, $message = '', $statusCode = 200)
{
    apiResponse($payload, true, $message, $statusCode);
}

function apiError($message = 'An error occurred', $errors = [], $statusCode = 400, $payload = null)
{
    $responsePayload = $payload;
    if (!empty($errors)) {
        $responsePayload = array_merge((array) $responsePayload, ['errors' => $errors]);
    }
    apiResponse($responsePayload, false, $message, $statusCode);
}

function apiData($data, $message = '', $statusCode = 200)
{
    apiResponse($data, true, $message, $statusCode);
}

function rejectInvalidPayload($errors)
{
    if (!empty($errors)) {
        apiError('Validation failed', $errors, 422);
    }
}