<?php
// Rate limiting helper functions
function checkRateLimit($pdo, $identifier, $maxAttempts = 5, $lockoutMinutes = 15) {
    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE ip_address = ? AND (locked_until IS NULL OR locked_until > NOW())");
    $stmt->execute([$identifier]);
    $attempt = $stmt->fetch();
    
    if ($attempt) {
        if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()) {
            $remaining = ceil((strtotime($attempt['locked_until']) - time()) / 60);
            return ['blocked' => true, 'message' => "Too many attempts. Please try again in $remaining minutes."];
        }
        
        if ($attempt['attempt_count'] >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60));
            $stmt = $pdo->prepare("UPDATE login_attempts SET locked_until = ? WHERE ip_address = ?");
            $stmt->execute([$lockedUntil, $identifier]);
            return ['blocked' => true, 'message' => "Too many attempts. Account locked for $lockoutMinutes minutes."];
        }
    }
    
    return ['blocked' => false];
}

function recordFailedAttempt($pdo, $identifier, $username = null) {
    $stmt = $pdo->prepare("SELECT id, attempt_count FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$identifier]);
    $attempt = $stmt->fetch();
    
    if ($attempt) {
        $stmt = $pdo->prepare("UPDATE login_attempts SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE ip_address = ?");
        $stmt->execute([$identifier]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_count) VALUES (?, ?, 1)");
        $stmt->execute([$identifier, $username]);
    }
}

function resetFailedAttempts($pdo, $identifier) {
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$identifier]);
}
?>
