<?php

/**
 * Input Sanitization & Security Functions
 * Prevents SQL Injection, XSS, and other attacks
 */

/**
 * Sanitize string input - removes harmful characters
 */
function sanitizeString($input, $allowHtml = false) {
    if ($input === null) return null;
    
    $input = trim($input);
    
    if ($allowHtml) {
        // Allow safe HTML but strip scripts and event handlers
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        $input = preg_replace('/on\w+\s*=/i', '', $input);
    } else {
        // Plain text only - strip all HTML
        $input = strip_tags($input);
    }
    
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    
    return $input;
}

/**
 * Sanitize integer input
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : 0;
}

/**
 * Sanitize float/decimal input
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : 0.0;
}

/**
 * Sanitize email input
 */
function sanitizeEmail($input) {
    return filter_var($input, FILTER_VALIDATE_EMAIL) ?: null;
}

/**
 * Sanitize date input (YYYY-MM-DD format)
 */
function sanitizeDate($input) {
    $date = DateTime::createFromFormat('Y-m-d', $input);
    return ($date && $date->format('Y-m-d') === $input) ? $input : null;
}

/**
 * Sanitize datetime input
 */
function sanitizeDateTime($input) {
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $input);
    return ($datetime && $datetime->format('Y-m-d H:i:s') === $input) ? $input : null;
}

/**
 * Get sanitized GET parameter
 */
function getParam($key, $default = null, $type = 'string') {
    $value = $_GET[$key] ?? $default;
    
    if ($value === null) return $default;
    
    switch ($type) {
        case 'int':
            return sanitizeInt($value);
        case 'float':
            return sanitizeFloat($value);
        case 'email':
            return sanitizeEmail($value);
        case 'date':
            return sanitizeDate($value);
        case 'datetime':
            return sanitizeDateTime($value);
        case 'html':
            return sanitizeString($value, true);
        default:
            return sanitizeString($value);
    }
}

/**
 * Get sanitized POST parameter
 */
function postParam($key, $default = null, $type = 'string') {
    $value = $_POST[$key] ?? $default;
    
    if ($value === null) return $default;
    
    switch ($type) {
        case 'int':
            return sanitizeInt($value);
        case 'float':
            return sanitizeFloat($value);
        case 'email':
            return sanitizeEmail($value);
        case 'date':
            return sanitizeDate($value);
        case 'datetime':
            return sanitizeDateTime($value);
        case 'html':
            return sanitizeString($value, true);
        default:
            return sanitizeString($value);
    }
}

/**
 * Validate action parameter against whitelist
 */
function validateAction($action, $allowedActions) {
    return in_array($action, $allowedActions, true) ? $action : null;
}

/**
 * Escape output for HTML (prevent XSS)
 */
function escapeHtml($input) {
    return htmlspecialchars($input ?? '', ENT_QUOTES, 'UTF-8');
}

// ========================================
// EXISTING API HELPER FUNCTIONS
// ========================================

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