<?php
/**
 * Cron Job Script for Automated Faculty Attendance
 * To be run every minute via cron (e.g. * * * * * php /path/to/backend/cron_auto_attendance.php)
 */

date_default_timezone_set('Asia/Manila');

// Get database connection (adjust path if needed when run via CLI)
$db_path = __DIR__ . '/db.php';
if (!file_exists($db_path)) {
    die("Database configuration not found.\n");
}
require_once $db_path;

$current_time = date('H:i');
$current_time_full = date('H:i:00');
$current_day = date('l'); // e.g. "Monday"
$current_date = date('Y-m-d');

// Use a 2-minute window to prevent missed attendance due to cron jitter
$window_start = date('H:i', strtotime('-2 minutes'));
$window_end = date('H:i', strtotime('+1 minute'));

echo "Running Auto Attendance Check for $current_day $current_time (window: $window_start - $window_end)\n";

try {
    // 1. Process Time In (Matches time_start within window)
    $stmtIn = $pdo->prepare("
        SELECT ss.id, ss.time_start, sl.faculty_id, sl.company_id
        FROM subject_schedules ss
        JOIN subject_loads sl ON ss.subject_load_id = sl.id
        JOIN employees e ON sl.faculty_id = e.id
        WHERE ss.day_of_week = ? AND ss.time_start BETWEEN ? AND ?
        AND e.status = 'Active'
    ");
    $stmtIn->execute([$current_day, $window_start, $window_end]);
    $start_schedules = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

    foreach ($start_schedules as $sched) {
        $faculty_id = $sched['faculty_id'];
        $company_id = $sched['company_id'];
        
        // Check if attendance record already exists for today
        $checkAtt = $pdo->prepare("SELECT id, check_in FROM attendance WHERE employee_id = ? AND log_date = ?");
        $checkAtt->execute([$faculty_id, $current_date]);
        $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAtt) {
            // First time checking in today
            $insert = $pdo->prepare("
                INSERT INTO attendance (company_id, employee_id, log_date, check_in, status, late_minutes) 
                VALUES (?, ?, ?, ?, 'On-Time', 0)
            ");
            $insert->execute([$company_id, $faculty_id, $current_date, $current_time_full]);
            echo "Auto Time In triggered for Faculty ID: $faculty_id at $current_time_full\n";
        } else if (empty($existingAtt['check_in'])) {
            // Record exists but no check_in (rare edge case)
            $update = $pdo->prepare("UPDATE attendance SET check_in = ?, status = 'On-Time' WHERE id = ?");
            $update->execute([$current_time_full, $existingAtt['id']]);
            echo "Auto Time In updated for Faculty ID: $faculty_id at $current_time_full\n";
        }
    }

    // 2. Process Time Out (Matches time_end within window)
    // Constraint: Time Out defaults to the end time of the FINAL subject load for that day.
    $stmtOut = $pdo->prepare("
        SELECT ss.id, ss.time_end, sl.faculty_id, sl.company_id
        FROM subject_schedules ss
        JOIN subject_loads sl ON ss.subject_load_id = sl.id
        JOIN employees e ON sl.faculty_id = e.id
        WHERE ss.day_of_week = ? AND ss.time_end BETWEEN ? AND ?
        AND e.status = 'Active'
    ");
    $stmtOut->execute([$current_day, $window_start, $window_end]);
    $end_schedules = $stmtOut->fetchAll(PDO::FETCH_ASSOC);

    foreach ($end_schedules as $sched) {
        $faculty_id = $sched['faculty_id'];
        
        // Check if this is the LAST schedule for the day for this faculty member
        $checkLast = $pdo->prepare("
            SELECT MAX(ss.time_end) as last_time
            FROM subject_schedules ss
            JOIN subject_loads sl ON ss.subject_load_id = sl.id
            WHERE ss.day_of_week = ? AND sl.faculty_id = ?
        ");
        $checkLast->execute([$current_day, $faculty_id]);
        $last_time = $checkLast->fetchColumn();
        
        // Convert both to HH:mm for safe comparison
        $sched_end = date('H:i', strtotime($sched['time_end']));
        $last_time_fmt = date('H:i', strtotime($last_time));
        
        if ($sched_end === $last_time_fmt) {
            // It is the final subject load for today, trigger Time Out
            $checkAtt = $pdo->prepare("SELECT id, check_in, lunch_out, lunch_in FROM attendance WHERE employee_id = ? AND log_date = ?");
            $checkAtt->execute([$faculty_id, $current_date]);
            $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingAtt && empty($existingAtt['check_out'])) {
                // Calculate total_hours
                $total_hours = null;
                if (!empty($existingAtt['check_in'])) {
                    $ci = new DateTime($existingAtt['check_in']);
                    $co = new DateTime($current_time_full);
                    $total_min = ($co->getTimestamp() - $ci->getTimestamp()) / 60;
                    if (!empty($existingAtt['lunch_out']) && !empty($existingAtt['lunch_in'])) {
                        $lo = new DateTime($existingAtt['lunch_out']);
                        $li = new DateTime($existingAtt['lunch_in']);
                        $lunch_min = ($li->getTimestamp() - $lo->getTimestamp()) / 60;
                        $total_min -= max(0, $lunch_min);
                    }
                    $total_hours = round(max(0, $total_min / 60), 2);
                }
                
                $update = $pdo->prepare("UPDATE attendance SET check_out = ?, total_hours = ? WHERE id = ?");
                $update->execute([$current_time_full, $total_hours, $existingAtt['id']]);
                echo "Auto Time Out triggered for Faculty ID: $faculty_id at $current_time_full (hours: $total_hours)\n";
            }
        } else {
            echo "Skipping Time Out for Faculty ID: $faculty_id (Not the final subject for today)\n";
        }
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
