<?php
// rate_limiter.php - File-based rate limiting for brute-force protection

/**
 * Check if an action is rate-limited
 * 
 * @param string $identifier Unique identifier (e.g., IP address + action)
 * @param int $max_attempts Maximum allowed attempts within the window
 * @param int $window_seconds Time window in seconds
 * @return array ['allowed' => bool, 'retry_after' => int (seconds)]
 */
function checkRateLimit($identifier, $max_attempts = 5, $window_seconds = 300)
{
    $rate_file = sys_get_temp_dir() . "/rate_limit_" . md5($identifier);
    $now = time();
    
    if (file_exists($rate_file)) {
        $data = json_decode(file_get_contents($rate_file), true);
        if (!$data) {
            $data = ['attempts' => []];
        }
        
        // Remove old attempts outside window
        $data['attempts'] = array_filter($data['attempts'], 
            function($t) use ($now, $window_seconds) {
                return ($now - $t) < $window_seconds;
            }
        );
        
        if (count($data['attempts']) >= $max_attempts) {
            $retry_after = $window_seconds - ($now - min($data['attempts']));
            return ['allowed' => false, 'retry_after' => max(0, $retry_after)];
        }
    } else {
        $data = ['attempts' => []];
    }
    
    $data['attempts'][] = $now;
    file_put_contents($rate_file, json_encode($data), LOCK_EX);
    return ['allowed' => true];
}

/**
 * Clear rate limit for an identifier (e.g., after successful login)
 */
function clearRateLimit($identifier)
{
    $rate_file = sys_get_temp_dir() . "/rate_limit_" . md5($identifier);
    if (file_exists($rate_file)) {
        unlink($rate_file);
    }
}
