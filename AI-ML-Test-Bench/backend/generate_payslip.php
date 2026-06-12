<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$payroll_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$employee_id = filter_var($_GET['employee_id'] ?? '', FILTER_VALIDATE_INT);
$period = $_GET['period'] ?? '';

if (!$payroll_id && (!$employee_id || !$period)) {
    http_response_code(400);
    die('Missing required parameters. Provide either id or employee_id + period.');
}

$company_id = $_SESSION['company_id'] ?? null;

if ($payroll_id) {
    if ($company_id) {
        $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, e.sss, e.philhealth, e.pagibig, e.tin, e.basic_salary, c.name as company_name, c.address as company_address, c.company_code FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.id = ? AND p.company_id = ?");
        $stmt->execute([$payroll_id, $company_id]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, e.sss, e.philhealth, e.pagibig, e.tin, e.basic_salary, c.name as company_name, c.address as company_address, c.company_code FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.id = ?");
        $stmt->execute([$payroll_id]);
    }
} else {
    if ($company_id) {
        $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, e.sss, e.philhealth, e.pagibig, e.tin, e.basic_salary, c.name as company_name, c.address as company_address, c.company_code FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.employee_id = ? AND p.period = ? AND p.company_id = ?");
        $stmt->execute([$employee_id, $period, $company_id]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, e.sss, e.philhealth, e.pagibig, e.tin, e.basic_salary, c.name as company_name, c.address as company_address, c.company_code FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.employee_id = ? AND p.period = ?");
        $stmt->execute([$employee_id, $period]);
    }
}

$payroll = $stmt->fetch();
if (!$payroll) {
    http_response_code(404);
    die('Payroll record not found.');
}

$breakdown = json_decode($payroll['breakdown'] ?? '{}', true) ?: [];
$tax_breakdown = $breakdown['tax_breakdown'] ?? null;
$employee_deductions_details = $breakdown['employee_deductions_details'] ?? [];

$company_name = $payroll['company_name'] ?? 'Company Name';
$company_address = $payroll['company_address'] ?? '';
$company_code = $payroll['company_code'] ?? '';

$emp_name = $payroll['full_name'] ?? 'N/A';
$emp_code = $payroll['emp_code'] ?? $payroll['employee_id'] ?? 'N/A';
$emp_position = $payroll['position'] ?? 'N/A';
$emp_department = $payroll['department'] ?? 'N/A';

$gross_pay = (float)($payroll['basic_pay'] ?? 0);
$allowances = (float)($breakdown['total_allowances'] ?? 0);
$overtime_pay = (float)($payroll['overtime_pay'] ?? $breakdown['overtime'] ?? 0);
$holiday_pay = (float)($payroll['holiday_pay'] ?? $breakdown['ot_holiday'] ?? 0);
$night_diff = (float)($payroll['night_diff'] ?? $breakdown['differential'] ?? 0);
$load_pay = (float)($breakdown['load_pay'] ?? 0);
$substitution_pay = (float)($payroll['substitution_pay'] ?? $breakdown['substitution'] ?? 0);
$honorarium = (float)($breakdown['honorarium'] ?? 0);

$total_earnings = $gross_pay + $allowances + $overtime_pay + $holiday_pay + $night_diff + $load_pay + $substitution_pay + $honorarium;

$sss_employee = (float)($tax_breakdown['sss_employee'] ?? 0);
$philhealth_employee = (float)($tax_breakdown['philhealth_employee'] ?? 0);
$pagibig_employee = (float)($tax_breakdown['pagibig_employee'] ?? 0);
$bir_tax = (float)($tax_breakdown['bir_tax'] ?? 0);
$absences = (float)($breakdown['absences'] ?? 0);
$late_ut = (float)($breakdown['late_ut'] ?? 0);
$hdmf_cont = (float)($breakdown['hdmf_cont'] ?? 0);
$hdmf_loans = (float)($breakdown['hdmf_loans'] ?? 0);
$hdmf_mp2 = (float)($breakdown['hdmf_mp2'] ?? 0);
$employee_specific_deductions = (float)($breakdown['employee_deductions'] ?? 0);
$adj_minus = (float)($breakdown['adj_minus'] ?? 0);
$cash_advance = (float)($breakdown['cash_advance'] ?? 0);

$total_deductions = (float)($payroll['deductions'] ?? 0);
$net_pay = (float)($payroll['net_pay'] ?? 0);

$sss_employer = (float)($tax_breakdown['sss_employer'] ?? 0);
$philhealth_employer = (float)($tax_breakdown['philhealth_employer'] ?? 0);
$pagibig_employer = (float)($tax_breakdown['pagibig_employer'] ?? 0);

function fmt($amount) {
    return number_format((float)$amount, 2);
}

$html = generatePayslipHTML($payroll, $breakdown, $tax_breakdown, $employee_deductions_details, [
    'company_name' => $company_name,
    'company_address' => $company_address,
    'company_code' => $company_code,
    'emp_name' => $emp_name,
    'emp_code' => $emp_code,
    'emp_position' => $emp_position,
    'emp_department' => $emp_department,
    'gross_pay' => $gross_pay,
    'allowances' => $allowances,
    'overtime_pay' => $overtime_pay,
    'holiday_pay' => $holiday_pay,
    'night_diff' => $night_diff,
    'load_pay' => $load_pay,
    'substitution_pay' => $substitution_pay,
    'honorarium' => $honorarium,
    'total_earnings' => $total_earnings,
    'sss_employee' => $sss_employee,
    'philhealth_employee' => $philhealth_employee,
    'pagibig_employee' => $pagibig_employee,
    'bir_tax' => $bir_tax,
    'absences' => $absences,
    'late_ut' => $late_ut,
    'hdmf_cont' => $hdmf_cont,
    'hdmf_loans' => $hdmf_loans,
    'hdmf_mp2' => $hdmf_mp2,
    'employee_specific_deductions' => $employee_specific_deductions,
    'adj_minus' => $adj_minus,
    'cash_advance' => $cash_advance,
    'total_deductions' => $total_deductions,
    'net_pay' => $net_pay,
    'sss_employer' => $sss_employer,
    'philhealth_employer' => $philhealth_employer,
    'pagibig_employer' => $pagibig_employer,
]);

$dompdf_available = class_exists('Dompdf\Dompdf');

if ($dompdf_available) {
    $autoload_paths = [
        __DIR__ . '/../vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php',
    ];
    $loaded = false;
    foreach ($autoload_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }

    if ($loaded && class_exists('Dompdf\Dompdf')) {
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultPaperSize', 'Letter');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        $filename = "payslip_{$payroll['emp_code']}_{$payroll['period']}.pdf";
        $dompdf->stream($filename, ['Attachment' => false]);
        exit;
    }
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
exit;

function generatePayslipHTML($payroll, $breakdown, $tax_breakdown, $emp_deductions, $d) {
    $period = htmlspecialchars($payroll['period'] ?? 'N/A');
    $company_name = htmlspecialchars($d['company_name']);
    $company_address = htmlspecialchars($d['company_address']);
    $company_code = htmlspecialchars($d['company_code']);
    $emp_name = htmlspecialchars($d['emp_name']);
    $emp_code = htmlspecialchars($d['emp_code']);
    $emp_position = htmlspecialchars($d['emp_position']);
    $emp_department = htmlspecialchars($d['emp_department']);
    $sss_id = htmlspecialchars($payroll['sss'] ?? 'N/A');
    $philhealth_id = htmlspecialchars($payroll['philhealth'] ?? 'N/A');
    $pagibig_id = htmlspecialchars($payroll['pagibig'] ?? 'N/A');
    $tin = htmlspecialchars($payroll['tin'] ?? 'N/A');
    $payroll_type = htmlspecialchars($payroll['payroll_type'] ?? 'General');
    $date_printed = date('F d, Y h:i A');
    $days_present = $breakdown['days_present'] ?? '-';
    $absent_days = $breakdown['absent_days'] ?? '-';
    $late_minutes = $breakdown['late_minutes'] ?? '-';

    $f = function($amount) {
        return number_format((float)$amount, 2);
    };

    $earning_rows = '';
    $earning_rows .= '<tr><td>Basic Pay</td><td class="amount">' . $f($d['gross_pay']) . '</td></tr>';
    if ($d['load_pay'] > 0) {
        $earning_rows .= '<tr><td>Load/Subject Pay</td><td class="amount">' . $f($d['load_pay']) . '</td></tr>';
    }
    if ($d['allowances'] > 0) {
        $earning_rows .= '<tr><td>Allowances</td><td class="amount">' . $f($d['allowances']) . '</td></tr>';
    }
    if ($d['overtime_pay'] > 0) {
        $earning_rows .= '<tr><td>Overtime Pay</td><td class="amount">' . $f($d['overtime_pay']) . '</td></tr>';
    }
    if ($d['holiday_pay'] > 0) {
        $earning_rows .= '<tr><td>Holiday Pay</td><td class="amount">' . $f($d['holiday_pay']) . '</td></tr>';
    }
    if ($d['night_diff'] > 0) {
        $earning_rows .= '<tr><td>Night Differential</td><td class="amount">' . $f($d['night_diff']) . '</td></tr>';
    }
    if ($d['substitution_pay'] > 0) {
        $earning_rows .= '<tr><td>Substitution Pay</td><td class="amount">' . $f($d['substitution_pay']) . '</td></tr>';
    }
    if ($d['honorarium'] > 0) {
        $earning_rows .= '<tr><td>Honorarium</td><td class="amount">' . $f($d['honorarium']) . '</td></tr>';
    }
    $earning_rows .= '<tr class="total-row"><td><strong>Total Gross Pay</strong></td><td class="amount"><strong>' . $f($d['total_earnings']) . '</strong></td></tr>';

    $deduction_rows = '';
    if ($d['absences'] > 0) {
        $deduction_rows .= '<tr><td>Absences</td><td class="amount">' . $f($d['absences']) . '</td></tr>';
    }
    if ($d['late_ut'] > 0) {
        $deduction_rows .= '<tr><td>Late / Undertime</td><td class="amount">' . $f($d['late_ut']) . '</td></tr>';
    }
    if ($d['sss_employee'] > 0) {
        $deduction_rows .= '<tr><td>SSS Contribution</td><td class="amount">' . $f($d['sss_employee']) . '</td></tr>';
    }
    if ($d['philhealth_employee'] > 0) {
        $deduction_rows .= '<tr><td>PhilHealth Contribution</td><td class="amount">' . $f($d['philhealth_employee']) . '</td></tr>';
    }
    if ($d['pagibig_employee'] > 0) {
        $deduction_rows .= '<tr><td>Pag-IBIG Contribution</td><td class="amount">' . $f($d['pagibig_employee']) . '</td></tr>';
    }
    if ($d['bir_tax'] > 0) {
        $deduction_rows .= '<tr><td>BIR Withholding Tax</td><td class="amount">' . $f($d['bir_tax']) . '</td></tr>';
    }
    if ($d['hdmf_cont'] > 0 && $d['pagibig_employee'] == 0) {
        $deduction_rows .= '<tr><td>Pag-IBIG (HDMF)</td><td class="amount">' . $f($d['hdmf_cont']) . '</td></tr>';
    }
    if ($d['hdmf_loans'] > 0) {
        $deduction_rows .= '<tr><td>Pag-IBIG Loan</td><td class="amount">' . $f($d['hdmf_loans']) . '</td></tr>';
    }
    if ($d['hdmf_mp2'] > 0) {
        $deduction_rows .= '<tr><td>Pag-IBIG MP2</td><td class="amount">' . $f($d['hdmf_mp2']) . '</td></tr>';
    }
    if ($d['adj_minus'] > 0) {
        $deduction_rows .= '<tr><td>Adjustments</td><td class="amount">' . $f($d['adj_minus']) . '</td></tr>';
    }
    if ($d['cash_advance'] > 0) {
        $deduction_rows .= '<tr><td>Cash Advance</td><td class="amount">' . $f($d['cash_advance']) . '</td></tr>';
    }
    if (!empty($emp_deductions)) {
        foreach ($emp_deductions as $ed) {
            $ed_name = htmlspecialchars($ed['name'] ?? 'Other Deduction');
            $ed_amount = $f($ed['amount'] ?? 0);
            if ((float)($ed['amount'] ?? 0) > 0) {
                $deduction_rows .= '<tr><td>' . $ed_name . '</td><td class="amount">' . $ed_amount . '</td></tr>';
            }
        }
    }
    $deduction_rows .= '<tr class="total-row"><td><strong>Total Deductions</strong></td><td class="amount"><strong>' . $f($d['total_deductions']) . '</strong></td></tr>';

    $has_contributions = ($d['sss_employer'] > 0 || $d['philhealth_employer'] > 0 || $d['pagibig_employer'] > 0);
    $contributions_section = '';
    if ($has_contributions) {
        $contributions_section = '
        <div class="section contributions-section">
            <h3>Government Contributions (Employer Share)</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Contribution</th><th class="amount">Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>SSS (Employer)</td><td class="amount">' . $f($d['sss_employer']) . '</td></tr>
                    <tr><td>PhilHealth (Employer)</td><td class="amount">' . $f($d['philhealth_employer']) . '</td></tr>
                    <tr><td>Pag-IBIG (Employer)</td><td class="amount">' . $f($d['pagibig_employer']) . '</td></tr>
                </tbody>
            </table>
        </div>';
    }

    $attendance_section = '';
    if ($days_present !== '-' || $absent_days !== '-' || $late_minutes !== '-') {
        $attendance_section = '
        <div class="section">
            <h3>Attendance Summary</h3>
            <table class="data-table attendance-table">
                <tbody>
                    <tr><td>Days Present</td><td class="amount">' . htmlspecialchars((string)$days_present) . '</td></tr>
                    <tr><td>Absent Days</td><td class="amount">' . htmlspecialchars((string)$absent_days) . '</td></tr>
                    <tr><td>Late Minutes</td><td class="amount">' . htmlspecialchars((string)$late_minutes) . '</td></tr>
                </tbody>
            </table>
        </div>';
    }

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - ' . $emp_name . ' - ' . $period . '</title>
    <style>
        @page {
            margin: 0.5in;
            size: letter;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            background: #fff;
        }
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1a237e;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .company-info h1 {
            font-size: 18px;
            color: #1a237e;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .company-info p {
            font-size: 10px;
            color: #555;
            line-height: 1.3;
        }
        .payslip-title {
            text-align: right;
        }
        .payslip-title h2 {
            font-size: 22px;
            color: #1a237e;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 800;
        }
        .payslip-title .pay-period {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }

        /* Employee Info */
        .employee-info {
            display: flex;
            justify-content: space-between;
            background: #f5f7ff;
            border: 1px solid #d5daf5;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 10.5px;
        }
        .employee-info .info-group {
            flex: 1;
        }
        .employee-info .info-group p {
            margin-bottom: 2px;
        }
        .employee-info .info-label {
            font-weight: 700;
            color: #1a237e;
            display: inline-block;
            min-width: 80px;
        }
        .employee-info .info-value {
            color: #333;
        }

        /* Sections */
        .section {
            margin-bottom: 12px;
        }
        .section h3 {
            font-size: 12px;
            color: #1a237e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .data-table thead th {
            background: #1a237e;
            color: #fff;
            padding: 6px 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table thead th.amount {
            text-align: right;
        }
        .data-table tbody td {
            padding: 5px 10px;
            border-bottom: 1px solid #e8e8e8;
        }
        .data-table tbody td.amount {
            text-align: right;
            font-family: "Consolas", "Courier New", monospace;
        }
        .data-table tbody tr:hover {
            background: #f9f9f9;
        }
        .data-table tbody tr.total-row {
            background: #eef1fa;
            border-top: 2px solid #1a237e;
        }
        .data-table tbody tr.total-row td {
            padding: 7px 10px;
        }

        /* Net Pay */
        .net-pay-section {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #fff;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .net-pay-section .net-pay-label {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .net-pay-section .net-pay-amount {
            font-size: 22px;
            font-weight: 800;
            font-family: "Consolas", "Courier New", monospace;
        }

        /* Two-column layout */
        .two-columns {
            display: flex;
            gap: 15px;
        }
        .two-columns .column {
            flex: 1;
        }

        /* Contributions */
        .contributions-section {
            background: #f0faf0;
            border: 1px solid #c5e1c5;
            border-radius: 6px;
            padding: 10px 15px;
        }
        .contributions-section h3 {
            color: #2e7d32;
            border-bottom-color: #2e7d32;
        }

        /* Attendance */
        .attendance-table tbody td {
            padding: 4px 10px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            color: #888;
        }
        .footer .signature-line {
            text-align: center;
            min-width: 150px;
        }
        .footer .signature-line .line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 9px;
            color: #555;
        }
        .footer .print-date {
            text-align: right;
        }

        /* Print styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #fff;
            }
            .payslip-container {
                padding: 0;
                max-width: 100%;
            }
            .net-pay-section {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .data-table thead th {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-btn {
                display: none !important;
            }
        }

        /* Browser print button */
        .print-btn {
            display: block;
            margin: 10px auto;
            padding: 8px 25px;
            background: #1a237e;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .print-btn:hover {
            background: #283593;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print();">Print Payslip</button>

    <div class="payslip-container">

        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>' . $company_name . '</h1>
                ' . ($company_address ? '<p>' . $company_address . '</p>' : '') . '
                ' . ($company_code ? '<p>Company Code: ' . $company_code . '</p>' : '') . '
            </div>
            <div class="payslip-title">
                <h2>Payslip</h2>
                <div class="pay-period">Pay Period: <strong>' . $period . '</strong></div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="employee-info">
            <div class="info-group">
                <p><span class="info-label">Employee Name:</span> <span class="info-value">' . $emp_name . '</span></p>
                <p><span class="info-label">Employee ID:</span> <span class="info-value">' . $emp_code . '</span></p>
                <p><span class="info-label">Position:</span> <span class="info-value">' . $emp_position . '</span></p>
            </div>
            <div class="info-group">
                <p><span class="info-label">Department:</span> <span class="info-value">' . $emp_department . '</span></p>
                <p><span class="info-label">Payroll Type:</span> <span class="info-value">' . $payroll_type . '</span></p>
                <p><span class="info-label">TIN:</span> <span class="info-value">' . $tin . '</span></p>
            </div>
            <div class="info-group">
                <p><span class="info-label">SSS No.:</span> <span class="info-value">' . $sss_id . '</span></p>
                <p><span class="info-label">PhilHealth No.:</span> <span class="info-value">' . $philhealth_id . '</span></p>
                <p><span class="info-label">Pag-IBIG No.:</span> <span class="info-value">' . $pagibig_id . '</span></p>
            </div>
        </div>

        ' . $attendance_section . '

        <!-- Earnings & Deductions -->
        <div class="two-columns">
            <div class="column">
                <div class="section">
                    <h3>Earnings</h3>
                    <table class="data-table">
                        <thead>
                            <tr><th>Description</th><th class="amount">Amount (PHP)</th></tr>
                        </thead>
                        <tbody>
                            ' . $earning_rows . '
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="column">
                <div class="section">
                    <h3>Deductions</h3>
                    <table class="data-table">
                        <thead>
                            <tr><th>Description</th><th class="amount">Amount (PHP)</th></tr>
                        </thead>
                        <tbody>
                            ' . $deduction_rows . '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="net-pay-section">
            <div class="net-pay-label">Net Pay</div>
            <div class="net-pay-amount">PHP ' . $f($d['net_pay']) . '</div>
        </div>

        ' . $contributions_section . '

        <!-- Footer -->
        <div class="footer">
            <div class="signature-line">
                <div class="line">Authorized Signature</div>
            </div>
            <div class="signature-line">
                <div class="line">Employee Signature</div>
            </div>
            <div class="print-date">
                <p>Date Printed: ' . $date_printed . '</p>
                <p>This is a computer-generated payslip.</p>
                <p>No signature is required if printed.</p>
            </div>
        </div>

    </div>
</body>
</html>';
}
