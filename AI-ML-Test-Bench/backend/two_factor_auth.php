<?php
// Two-Factor Authentication (2FA) helper functions
// Uses TOTP (Time-based One-Time Password) compatible with Google Authenticator

/**
 * Generate a random base32 secret
 * 
 * @param int $length Secret length
 * @return string Base32 encoded secret
 */
function generate2FASecret($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

/**
 * Generate TOTP code
 * 
 * @param string $secret Base32 secret
 * @param int|null $timeStep Time step (30 seconds default)
 * @return string 6-digit TOTP code
 */
function generateTOTPCode($secret, $timeStep = null) {
    if ($timeStep === null) {
        $timeStep = floor(time() / 30);
    }
    
    $key = base32Decode($secret);
    $timeBytes = pack('N*', 0) . pack('N*', $timeStep);
    $hmac = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord(substr($hmac, -1)) & 0x0F;
    $hashPart = substr($hmac, $offset, 4);
    $code = unpack('N', $hashPart)['1'] & 0x7FFFFFFF;
    
    return str_pad($code % 1000000, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify TOTP code
 * 
 * @param string $secret Base32 secret
 * @param string $code User-provided code
 * @param int $window Verification window (±N time steps)
 * @return bool Verification result
 */
function verifyTOTPCode($secret, $code, $window = 1) {
    $currentTimeStep = floor(time() / 30);
    
    for ($i = -$window; $i <= $window; $i++) {
        $expectedCode = generateTOTPCode($secret, $currentTimeStep + $i);
        if (hash_equals($expectedCode, $code)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Decode base32 string
 * 
 * @param string $base32 Base32 encoded string
 * @return string Decoded binary data
 */
function base32Decode($base32) {
    $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32Map = array_flip(str_split($base32Chars));
    
    $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
    $binary = '';
    
    for ($i = 0; $i < strlen($base32); $i += 8) {
        $chunk = substr($base32, $i, 8);
        $bits = '';
        
        for ($j = 0; $j < strlen($chunk); $j++) {
            $bits .= str_pad(decbin($base32Map[$chunk[$j]]), 5, '0', STR_PAD_LEFT);
        }
        
        for ($j = 0; $j + 8 <= strlen($bits); $j += 8) {
            $binary .= chr(bindec(substr($bits, $j, 8)));
        }
    }
    
    return $binary;
}

/**
 * Generate QR code data URL for 2FA setup
 * 
 * @param string $userEmail User email
 * @param string $secret 2FA secret
 * @param string $issuer Issuer name
 * @return string QR code data URL
 */
function generate2FAQRCode($userEmail, $secret, $issuer = 'ALM Biometrics') {
    $otpAuthUrl = sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        urlencode($issuer),
        urlencode($userEmail),
        urlencode($secret),
        urlencode($issuer)
    );
    
    // Use Google Charts API for QR code generation
    $qrCodeUrl = sprintf(
        'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=%s',
        urlencode($otpAuthUrl)
    );
    
    return $qrCodeUrl;
}

/**
 * Enable 2FA for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array Secret and QR code URL
 */
function enable2FA($pdo, $userId) {
    $secret = generate2FASecret();
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET two_factor_enabled = FALSE, 
            two_factor_secret = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$secret, $userId]);
    
    // Get user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $issuer = getenv('2FA_ISSUER_NAME') ?: 'ALM Biometrics';
    $qrCodeUrl = generate2FAQRCode($user['email'], $secret, $issuer);
    
    return [
        'secret' => $secret,
        'qr_code' => $qrCodeUrl
    ];
}

/**
 * Verify and activate 2FA
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $code TOTP code
 * @return bool Activation status
 */
function activate2FA($pdo, $userId, $code) {
    $stmt = $pdo->prepare("SELECT two_factor_secret FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['two_factor_secret']) {
        return false;
    }
    
    if (verifyTOTPCode($user['two_factor_secret'], $code)) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET two_factor_enabled = TRUE 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return true;
    }
    
    return false;
}

/**
 * Disable 2FA for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $code TOTP code for verification
 * @return bool Success status
 */
function disable2FA($pdo, $userId, $code) {
    $stmt = $pdo->prepare("SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['two_factor_enabled']) {
        return false;
    }
    
    if (verifyTOTPCode($user['two_factor_secret'], $code)) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET two_factor_enabled = FALSE, 
                two_factor_secret = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return true;
    }
    
    return false;
}

/**
 * Verify 2FA code during login
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $code TOTP code
 * @return bool Verification result
 */
function verify2FALogin($pdo, $userId, $code) {
    $stmt = $pdo->prepare("SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['two_factor_enabled']) {
        return true; // 2FA not enabled
    }
    
    return verifyTOTPCode($user['two_factor_secret'], $code);
}

/**
 * Check if user has 2FA enabled
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return bool 2FA status
 */
function is2FAEnabled($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    return $user && $user['two_factor_enabled'];
}
?>
