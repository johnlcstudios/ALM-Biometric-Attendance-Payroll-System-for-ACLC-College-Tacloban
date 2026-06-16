<?php
/**
 * Cron Job Script for Automated Attendance (Faculty, Staff, Utility)
 * To be run every minute via cron (e.g. * * * * * php /path/to/backend/cron_auto_attendance.php)
 */

date_default_timezone_set('Asia/Manila');

$db_path = __DIR__ . '/db.php';
if (!file_exists($db_path)) {
    die("Database configuration not found.\n");
}
require_once $db_path;

$current_time = date('H:i');
$current_time_full = date('H:i:00');
$current_day = date('l');
$current_date = date('Y-m-d');

$window_start = date('H:i', strtotime('-2 minutes'));
$window_end = date('H:i', strtotime('+1 minute'));

echo "Running Auto Attendance Check for $current_day $current_time (window: $window_start - $window_end)\n";

try {
    // 1. Process Faculty Time In (Matches subject schedule time_start within window)
    $stmtIn = $pdo->prepare("
        SELECT ss.id, ss.time_start, sl.faculty_id as employee_id, sl.company_id
        FROM subject_schedules ss
        JOIN subject_loads sl ON ss.subject_load_id = sl.id
        JOIN employees e ON sl.faculty_id = e.id
        WHERE ss.day_of_week = ? AND ss.time_start BETWEEN ? AND ?
        AND e.status = 'Active'
    ");
    $stmtIn->execute([$current_day, $window_start, $window_end]);
    $start_schedules = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

    // Pre-fetch grace periods for all companies
    $grace_periods = [];
    $stmtGrace = $pdo->prepare("SELECT id, COALESCE(grace_period, 0) as grace_period FROM companies");
    $stmtGrace->execute();
    while ($g = $stmtGrace->fetch(PDO::FETCH_ASSOC)) {
        $grace_periods[$g['id']] = (int) $g['grace_period'];
    }

    foreach ($start_schedules as $sched) {
        $employee_id = $sched['employee_id'];
        $company_id = $sched['company_id'];

        $checkAtt = $pdo->prepare("SELECT id, check_in FROM attendance WHERE employee_id = ? AND log_date = ?");
        $checkAtt->execute([$employee_id, $current_date]);
        $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);

        // Calculate late status based on schedule time_start
        $schedule_start = date('H:i:s', strtotime($sched['time_start']));
        $actual_start = $current_time_full;
        $late_minutes = 0;
        $status = 'On-Time';

        $grace = $grace_periods[$company_id] ?? 0;
        $sched_ts = strtotime($current_date . ' ' . $schedule_start);
        $actual_ts = strtotime($current_date . ' ' . $actual_start);
        $diff_minutes = ($actual_ts - $sched_ts) / 60;

        if ($diff_minutes > $grace) {
            $late_minutes = (int) round($diff_minutes - $grace);
            $status = 'Late';
        }

        if (!$existingAtt) {
            $insert = $pdo->prepare("
                INSERT INTO attendance (company_id, employee_id, log_date, check_in, status, late_minutes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$company_id, $employee_id, $current_date, $actual_start, $status, $late_minutes]);
            echo "Auto Time In triggered for Faculty ID: $employee_id at $actual_start ($status, $late_minutes min late)\n";
        } else if (empty($existingAtt['check_in'])) {
            $update = $pdo->prepare("UPDATE attendance SET check_in = ?, status = ?, late_minutes = ? WHERE id = ?");
            $update->execute([$actual_start, $status, $late_minutes, $existingAtt['id']]);
            echo "Auto Time In updated for Faculty ID: $employee_id at $actual_start ($status)\n";
        }
    }

    // 2. Process Faculty Time Out (Matches subject schedule time_end within window)
    $stmtOut = $pdo->prepare("
        SELECT ss.id, ss.time_end, sl.faculty_id as employee_id, sl.company_id
        FROM subject_schedules ss
        JOIN subject_loads sl ON ss.subject_load_id = sl.id
        JOIN employees e ON sl.faculty_id = e.id
        WHERE ss.day_of_week = ? AND ss.time_end BETWEEN ? AND ?
        AND e.status = 'Active'
    ");
    $stmtOut->execute([$current_day, $window_start, $window_end]);
    $end_schedules = $stmtOut->fetchAll(PDO::FETCH_ASSOC);

    foreach ($end_schedules as $sched) {
        $employee_id = $sched['employee_id'];

        // Check if this is the LAST schedule for the day for this faculty member
        $checkLast = $pdo->prepare("
            SELECT MAX(ss.time_end) as last_time
            FROM subject_schedules ss
            JOIN subject_loads sl ON ss.subject_load_id = sl.id
            WHERE ss.day_of_week = ? AND sl.faculty_id = ?
        ");
        $checkLast->execute([$current_day, $employee_id]);
        $last_time = $checkLast->fetchColumn();

        $sched_end = date('H:i', strtotime($sched['time_end']));
        $last_time_fmt = date('H:i', strtotime($last_time));

        if ($sched_end === $last_time_fmt) {
            $checkAtt = $pdo->prepare("SELECT id, check_in, lunch_out, lunch_in FROM attendance WHERE employee_id = ? AND log_date = ?");
            $checkAtt->execute([$employee_id, $current_date]);
            $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);

            if ($existingAtt && empty($existingAtt['check_out'])) {
                // Use schedule time_end instead of current time for accurate hours
                $checkout_time = date('H:i:s', strtotime($sched['time_end']));

                $total_hours = null;
                if (!empty($existingAtt['check_in'])) {
                    $ci = new DateTime($existingAtt['check_in']);
                    $co = new DateTime($checkout_time);
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
                $update->execute([$checkout_time, $total_hours, $existingAtt['id']]);
                echo "Auto Time Out triggered for Faculty ID: $employee_id at $checkout_time (hours: $total_hours)\n";
            }
        }
    }

    // 3. Process Staff and Utility Time In (based on company work_start)
    $stmtStaffIn = $pdo->prepare("
        SELECT e.id as employee_id, e.company_id, c.work_start, c.work_end, c.grace_period
        FROM employees e
        JOIN companies c ON e.company_id = c.id
        WHERE e.position IN ('Staff', 'Utility') AND e.status = 'Active'
        AND c.work_start BETWEEN ? AND ?
    ");
    $stmtStaffIn->execute([$window_start, $window_end]);
    $staff_start = $stmtStaffIn->fetchAll(PDO::FETCH_ASSOC);

    foreach ($staff_start as $emp) {
        $employee_id = $emp['employee_id'];
        $company_id = $emp['company_id'];

        $checkAtt = $pdo->prepare("SELECT id, check_in FROM attendance WHERE employee_id = ? AND log_date = ?");
        $checkAtt->execute([$employee_id, $current_date]);
        $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);

        $schedule_start = date('H:i:s', strtotime($emp['work_start']));
        $actual_start = $current_time_full;
        $late_minutes = 0;
        $status = 'On-Time';

        $grace = (int) ($emp['grace_period'] ?? 0);
        $sched_ts = strtotime($current_date . ' ' . $schedule_start);
        $actual_ts = strtotime($current_date . ' ' . $actual_start);
        $diff_minutes = ($actual_ts - $sched_ts) / 60;

        if ($diff_minutes > $grace) {
            $late_minutes = (int) round($diff_minutes - $grace);
            $status = 'Late';
        }

        if (!$existingAtt) {
            $insert = $pdo->prepare("
                INSERT INTO attendance (company_id, employee_id, log_date, check_in, status, late_minutes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$company_id, $employee_id, $current_date, $actual_start, $status, $late_minutes]);
            echo "Auto Time In triggered for Staff/Utility ID: $employee_id at $actual_start ($status)\n";
        } else if (empty($existingAtt['check_in'])) {
            $update = $pdo->prepare("UPDATE attendance SET check_in = ?, status = ?, late_minutes = ? WHERE id = ?");
            $update->execute([$actual_start, $status, $late_minutes, $existingAtt['id']]);
            echo "Auto Time In updated for Staff/Utility ID: $employee_id at $actual_start ($status)\n";
        }
    }

    // 4. Process Staff and Utility Time Out (based on company work_end)
    $stmtStaffOut = $pdo->prepare("
        SELECT e.id as employee_id, e.company_id, c.work_end
        FROM employees e
        JOIN companies c ON e.company_id = c.id
        WHERE e.position IN ('Staff', 'Utility') AND e.status = 'Active'
        AND c.work_end BETWEEN ? AND ?
    ");
    $stmtStaffOut->execute([$window_start, $window_end]);
    $staff_end = $stmtStaffOut->fetchAll(PDO::FETCH_ASSOC);

    foreach ($staff_end as $emp) {
        $employee_id = $emp['employee_id'];

        $checkAtt = $pdo->prepare("SELECT id, check_in, lunch_out, lunch_in FROM attendance WHERE employee_id = ? AND log_date = ?");
        $checkAtt->execute([$employee_id, $current_date]);
        $existingAtt = $checkAtt->fetch(PDO::FETCH_ASSOC);

        if ($existingAtt && empty($existingAtt['check_out'])) {
            $checkout_time = date('H:i:s', strtotime($emp['work_end']));

            $total_hours = null;
            if (!empty($existingAtt['check_in'])) {
                $ci = new DateTime($existingAtt['check_in']);
                $co = new DateTime($checkout_time);
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
            $update->execute([$checkout_time, $total_hours, $existingAtt['id']]);
            echo "Auto Time Out triggered for Staff/Utility ID: $employee_id at $checkout_time (hours: $total_hours)\n";
        }
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
