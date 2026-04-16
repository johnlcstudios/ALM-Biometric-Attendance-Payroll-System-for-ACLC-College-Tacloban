<?php
// helpers.php - Shared functions, validation, rate limiting, auth helpers

// Biometric Constants
define('BIOMETRIC_MATCH_THRESHOLD', 0.60);
define('BIOMETRIC_DUPLICATE_THRESHOLD', 0.40);
define('BIOMETRIC_AMBIGUITY_RATIO', 1.30);

/**
 * Send standardized error response with HTTP status code
 */
function sendError($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $data));
    exit;
}

/**
 * Check for HR or Admin role
 */
function isAdminOrHR() {
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll', 'Payroll Officer']);
}

/**
 * Check for Payroll role or higher
 */
function isPayrollOrHigher() {
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll', 'Payroll Officer']);
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $errors[] = "Field '$field' is required";
        }
    }
    return $errors;
}

/**
 * Validate date format
 */
function validateDate($date, $fieldName) {
    if (empty($date)) return [];
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return ["Invalid date format for '$fieldName'. Expected YYYY-MM-DD"];
    }
    return [];
}

/**
 * Validate numeric amount
 */
function validateAmount($amount, $fieldName, $min = 0) {
    if (!is_numeric($amount)) {
        return ["'$fieldName' must be a valid number"];
    }
    if ((float)$amount < $min) {
        return ["'$fieldName' must be at least $min"];
    }
    return [];
}

/**
 * Validate positive integer ID
 */
function validateId($id, $fieldName) {
    if (!is_numeric($id) || (int)$id <= 0) {
        return ["'$fieldName' must be a positive integer"];
    }
    return [];
}

/**
 * Validate date range
 */
function validateDateRange($startDate, $endDate) {
    $errors = array_merge(validateDate($startDate, 'start_date'), validateDate($endDate, 'end_date'));
    if (empty($errors) && strtotime($startDate) > strtotime($endDate)) {
        $errors[] = 'Start date cannot be after end date';
    }
    return $errors;
}

/**
 * Exit with 400 if errors exist
 */
function rejectInvalidPayload($errors) {
    if (!empty($errors)) {
        sendError(400, 'Validation failed', ['errors' => $errors]);
    }
}

/**
 * File-based rate limiter
 */
function checkRateLimit($action) {
    $ip         = $_SERVER['REMOTE_ADDR'];
    $cache_file = sys_get_temp_dir() . '/rate_' . md5($ip . $action) . '.json';
    $now        = time();
    $data       = ['count' => 0, 'reset' => $now];

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true) ?: $data;
        if ($data['reset'] < $now - 60) {
            $data = ['count' => 0, 'reset' => $now];
        }
    }

    if ($data['count'] >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
        exit;
    }

    $data['count']++;
    file_put_contents($cache_file, json_encode($data));
    return true;
}

/**
 * Get server time with timezone awareness
 */
function getServerTime($companyId = null, $dbConnection = null) {
    global $pdo;
    $timezone = 'UTC';

    if ($companyId && ($dbConnection || isset($pdo))) {
        try {
            $db   = $dbConnection ?? $pdo;
            $stmt = $db->prepare("SELECT timezone FROM companies WHERE id = ?");
            $stmt->execute([$companyId]);
            $result = $stmt->fetchColumn();
            if ($result) $timezone = $result;
        } catch (Exception $e) {}
    }

    $utcNow     = new DateTime('now', new DateTimeZone('UTC'));
    $displayNow = clone $utcNow;
    $displayNow->setTimezone(new DateTimeZone($timezone));

    return [
        'utc'          => $utcNow->format('Y-m-d H:i:s'),
        'display'      => $displayNow->format('Y-m-d H:i:s'),
        'date'         => $displayNow->format('Y-m-d'),
        'time'         => $displayNow->format('H:i:s'),
        'display_time' => $displayNow->format('h:i A'),
        'timezone'     => $timezone,
        'server_ms'    => (int)round(microtime(true) * 1000)
    ];
}

function getCurrentUTCTimestamp() {
    return date('Y-m-d H:i:s', time());
}

function convertUTCToTimezone($utcTimestamp, $timezone = 'UTC') {
    try {
        $dt = new DateTime($utcTimestamp, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $utcTimestamp;
    }
}

function convertTimestampToUTC($timestamp, $timezone = 'UTC') {
    try {
        $dt = new DateTime($timestamp, new DateTimeZone($timezone));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return getCurrentUTCTimestamp();
    }
}
