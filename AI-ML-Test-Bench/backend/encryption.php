<?php
// Encryption helper for biometric data
// Uses AES-256-CBC encryption

/**
 * Load encryption key from environment
 */
function getEncryptionKey() {
    $key = getenv('ENCRYPTION_KEY');
    if (!$key) {
        // Fallback to .env file
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'ENCRYPTION_KEY=') === 0) {
                    $key = trim(substr($line, strlen('ENCRYPTION_KEY=')));
                    break;
                }
            }
        }
    }
    
    if (!$key || $key === 'change-this-to-a-random-32-character-key') {
        throw new Exception('Encryption key not configured. Set ENCRYPTION_KEY in .env file.');
    }
    
    // Ensure key is exactly 32 bytes for AES-256
    return hash('sha256', $key, true);
}

/**
 * Encrypt biometric descriptor data
 * 
 * @param array $descriptor 128-dimensional face descriptor array
 * @return array Encrypted data with IV
 */
function encryptBiometricData($descriptor) {
    $key = getEncryptionKey();
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    $jsonData = json_encode($descriptor);
    $encrypted = openssl_encrypt($jsonData, 'aes-256-cbc', $key, 0, $iv);
    
    if ($encrypted === false) {
        throw new Exception('Encryption failed');
    }
    
    return [
        'encrypted' => base64_encode($encrypted),
        'iv' => base64_encode($iv)
    ];
}

/**
 * Decrypt biometric descriptor data
 * 
 * @param string $encryptedData Base64 encoded encrypted data
 * @param string $iv Base64 encoded initialization vector
 * @return array Decrypted descriptor array
 */
function decryptBiometricData($encryptedData, $iv) {
    $key = getEncryptionKey();
    $encrypted = base64_decode($encryptedData);
    $ivBytes = base64_decode($iv);
    
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $ivBytes);
    
    if ($decrypted === false) {
        throw new Exception('Decryption failed');
    }
    
    $descriptor = json_decode($decrypted, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid decrypted data');
    }
    
    return $descriptor;
}

/**
 * Store encrypted biometric data for an employee
 * 
 * @param PDO $pdo Database connection
 * @param int $employeeId Employee ID
 * @param array $descriptor Face descriptor array
 * @return bool Success status
 */
function storeEncryptedBiometric($pdo, $employeeId, $descriptor) {
    $encrypted = encryptBiometricData($descriptor);
    
    $stmt = $pdo->prepare("
        UPDATE employees 
        SET face_descriptor_encrypted = ?, 
            encryption_iv = ?,
            face_descriptor = NULL
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $encrypted['encrypted'],
        $encrypted['iv'],
        $employeeId
    ]);
}

/**
 * Retrieve and decrypt biometric data for an employee
 * 
 * @param PDO $pdo Database connection
 * @param int $employeeId Employee ID
 * @return array|null Decrypted descriptor or null
 */
function getDecryptedBiometric($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT face_descriptor_encrypted, encryption_iv 
        FROM employees 
        WHERE id = ?
    ");
    
    $stmt->execute([$employeeId]);
    $row = $stmt->fetch();
    
    if (!$row || !$row['face_descriptor_encrypted'] || !$row['encryption_iv']) {
        return null;
    }
    
    return decryptBiometricData($row['face_descriptor_encrypted'], $row['encryption_iv']);
}

/**
 * Migrate existing plain text biometrics to encrypted format
 * 
 * @param PDO $pdo Database connection
 * @return int Number of records migrated
 */
function migrateBiometricsToEncryption($pdo) {
    $stmt = $pdo->query("
        SELECT id, face_descriptor 
        FROM employees 
        WHERE face_descriptor IS NOT NULL 
        AND face_descriptor_encrypted IS NULL
    ");
    
    $migrated = 0;
    
    while ($row = $stmt->fetch()) {
        try {
            $descriptor = json_decode($row['face_descriptor'], true);
            
            if (is_array($descriptor) && count($descriptor) === 128) {
                storeEncryptedBiometric($pdo, $row['id'], $descriptor);
                $migrated++;
            }
        } catch (Exception $e) {
            error_log("Failed to migrate employee {$row['id']}: " . $e->getMessage());
        }
    }
    
    return $migrated;
}
?>
