<?php
// DTR (Daily Time Record) Generation for ESS Portal
// Generates PDF DTR for employees

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix path: need to go up 2 levels from pages/shared/ to reach backend/
require_once __DIR__ . '/../../backend/db.php';
require_once __DIR__ . '/../../backend/api_helpers.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$employeeId = $_GET['employee_id'] ?? 0;
$month = $_GET['month'] ?? date('Y-m');
$userId = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];
$userRole = $_SESSION['role'];

// Employees can only view their own DTR
if ($userRole === 'Employee') {
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$userId, $companyId]);
    $emp = $stmt->fetch();
    if (!$emp) {
        die(json_encode(['success' => false, 'message' => 'Employee record not found']));
    }
    $employeeId = $emp['id'];
}

// Get employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND company_id = ?");
$stmt->execute([$employeeId, $companyId]);
$employee = $stmt->fetch();

if (!$employee) {
    die(json_encode(['success' => false, 'message' => 'Employee not found']));
}

// Get attendance records for the month
$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = ? 
    AND company_id = ?
    AND log_date LIKE ?
    ORDER BY log_date ASC
");
$stmt->execute([$employeeId, $companyId, $month . '%']);
$attendanceRecords = $stmt->fetchAll();

// Generate HTML for DTR
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Time Record - ' . htmlspecialchars($employee['full_name']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .employee-info {
            margin-bottom: 20px;
        }
        .employee-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .employee-info td {
            padding: 5px;
        }
        .employee-info td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .dtr-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .dtr-table th, .dtr-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .dtr-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .signature {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DAILY TIME RECORD</h1>
        <h2>' . date('F Y', strtotime($month . '-01')) . '</h2>
    </div>
    
    <div class="employee-info">
        <table>
            <tr>
                <td>Employee Name:</td>
                <td>' . htmlspecialchars($employee['full_name']) . '</td>
                <td>Employee ID:</td>
                <td>' . htmlspecialchars($employee['employee_id']) . '</td>
            </tr>
            <tr>
                <td>Position:</td>
                <td>' . htmlspecialchars($employee['position'] ?? 'N/A') . '</td>
                <td>Department:</td>
                <td>' . htmlspecialchars($employee['department'] ?? 'N/A') . '</td>
            </tr>
        </table>
    </div>
    
    <table class="dtr-table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Lunch Out</th>
                <th>Lunch In</th>
                <th>Time Out</th>
                <th>Hours Worked</th>
                <th>Late (min)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
';

$totalHours = 0;
$totalLate = 0;

foreach ($attendanceRecords as $record) {
    $date = strtotime($record['log_date']);
    $dayName = date('l', $date);
    $dayNum = date('d', $date);
    
    $timeIn = $record['check_in'] ? date('h:i A', strtotime($record['check_in'])) : '-';
    $lunchOut = $record['lunch_out'] ? date('h:i A', strtotime($record['lunch_out'])) : '-';
    $lunchIn = $record['lunch_in'] ? date('h:i A', strtotime($record['lunch_in'])) : '-';
    $timeOut = $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : '-';
    
    // Calculate hours worked
    $hoursWorked = 0;
    if ($record['check_in'] && $record['check_out']) {
        $checkIn = new DateTime($record['check_in']);
        $checkOut = new DateTime($record['check_out']);
        $interval = $checkIn->diff($checkOut);
        $hoursWorked = $interval->h + ($interval->i / 60);
        
        // Subtract lunch break if applicable
        if ($record['lunch_out'] && $record['lunch_in']) {
            $lunchOut = new DateTime($record['lunch_out']);
            $lunchIn = new DateTime($record['lunch_in']);
            $lunchInterval = $lunchOut->diff($lunchIn);
            $lunchHours = $lunchInterval->h + ($lunchInterval->i / 60);
            $hoursWorked -= $lunchHours;
        }
    }
    
    $totalHours += $hoursWorked;
    $totalLate += $record['late_minutes'] ?? 0;
    
    $html .= '
            <tr>
                <td>' . $dayName . '</td>
                <td>' . $dayNum . '</td>
                <td>' . $timeIn . '</td>
                <td>' . $lunchOut . '</td>
                <td>' . $lunchIn . '</td>
                <td>' . $timeOut . '</td>
                <td>' . number_format($hoursWorked, 2) . '</td>
                <td>' . ($record['late_minutes'] ?? 0) . '</td>
                <td>' . htmlspecialchars($record['status'] ?? '-') . '</td>
            </tr>
    ';
}

$html .= '
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td colspan="6">TOTAL</td>
                <td>' . number_format($totalHours, 2) . '</td>
                <td>' . $totalLate . '</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <div class="signature">
        <div class="signature-line">
            Employee Signature
        </div>
        <div class="signature-line">
            Supervisor/Manager Signature
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
';

echo $html;
?>
