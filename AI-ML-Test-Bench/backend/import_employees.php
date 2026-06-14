<?php
// Bulk Employee Import from CSV
// Requires: admin or HR role

if (!isset($_SESSION['company_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../backend/db.php';
require_once '../backend/audit.php';
require_once '../backend/encryption.php';

$companyId = $_SESSION['company_id'];
$userId = $_SESSION['user_id'];

if (!isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

// Validate file type
$allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Read CSV file
$handle = fopen($file['tmp_name'], 'r');
if ($handle === false) {
    echo json_encode(['success' => false, 'message' => 'Cannot open file']);
    exit;
}

// Read header row
$headers = fgetcsv($handle);
if (!$headers) {
    echo json_encode(['success' => false, 'message' => 'Empty CSV file']);
    fclose($handle);
    exit;
}

// Validate required columns
$requiredColumns = ['employee_id', 'full_name', 'email', 'position', 'basic_salary'];
$missingColumns = array_diff($requiredColumns, $headers);

if (!empty($missingColumns)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required columns: ' . implode(', ', $missingColumns)
    ]);
    fclose($handle);
    exit;
}

// Map column names to indices
$columnMap = array_flip($headers);

$imported = 0;
$failed = 0;
$errors = [];
$rowNumber = 1;

$pdo->beginTransaction();

try {
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        if (count($row) !== count($headers)) {
            $errors[] = "Row $rowNumber: Column count mismatch";
            $failed++;
            continue;
        }
        
        // Extract data
        $employeeId = trim($row[$columnMap['employee_id']] ?? '');
        $fullName = trim($row[$columnMap['full_name']] ?? '');
        $email = trim($row[$columnMap['email']] ?? '');
        $position = trim($row[$columnMap['position']] ?? '');
        $basicSalary = trim($row[$columnMap['basic_salary']] ?? '0');
        $department = trim($row[$columnMap['department'] ?? ''] ?? '');
        $sss = trim($row[$columnMap['sss'] ?? ''] ?? '');
        $tin = trim($row[$columnMap['tin'] ?? ''] ?? '');
        $philhealth = trim($row[$columnMap['philhealth'] ?? ''] ?? '');
        $pagibig = trim($row[$columnMap['pagibig'] ?? ''] ?? '');
        $dob = trim($row[$columnMap['dob'] ?? ''] ?? '');
        $status = trim($row[$columnMap['status'] ?? ''] ?? 'Active');
        
        // Validate required fields
        if (empty($employeeId) || empty($fullName) || empty($email)) {
            $errors[] = "Row $rowNumber: Missing required fields";
            $failed++;
            continue;
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $rowNumber: Invalid email format";
            $failed++;
            continue;
        }
        
        // Validate salary
        if (!is_numeric($basicSalary) || $basicSalary < 0) {
            $errors[] = "Row $rowNumber: Invalid salary amount";
            $failed++;
            continue;
        }
        
        // Check if employee_id already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ? AND company_id = ?");
        $stmt->execute([$employeeId, $companyId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Row $rowNumber: Employee ID $employeeId already exists";
            $failed++;
            continue;
        }
        
        // Create user account
        $username = strtolower(str_replace(' ', '.', $fullName)) . '.' . substr($employeeId, -3);
        $defaultPassword = password_hash('Welcome123!', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'Employee', ?)");
        $stmt->execute([$companyId, $username, $defaultPassword, $email]);
        $user_id = $pdo->lastInsertId();
        
        // Create employee record
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                company_id, employee_id, full_name, email, position, department, 
                basic_salary, sss, tin, philhealth, pagibig, dob, status, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $companyId,
            $employeeId,
            $fullName,
            $email,
            $position,
            $department,
            $basicSalary,
            $sss,
            $tin,
            $philhealth,
            $pagibig,
            $dob ?: null,
            $status,
            $user_id
        ]);
        
        $imported++;
    }
    
    fclose($handle);
    
    $pdo->commit();
    
    // Log import
    logAudit($pdo, $companyId, $userId, AUDIT_IMPORT_EMPLOYEES, null, null, [
        'imported' => $imported,
        'failed' => $failed,
        'file' => $file['name']
    ]);
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'failed' => $failed,
        'errors' => $errors,
        'message' => "Successfully imported $imported employees. $failed failed."
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);
    
    echo json_encode([
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage(),
        'imported' => $imported,
        'row' => $rowNumber
    ]);
}
?>
