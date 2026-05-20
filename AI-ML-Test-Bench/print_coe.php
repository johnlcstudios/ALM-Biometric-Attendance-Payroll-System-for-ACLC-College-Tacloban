<?php
// print_coe.php - Simple COE printable form (client-side prefilled via query params)
require_once 'backend/db.php';

$employee_name = $_GET['name'] ?? 'Employee';
$employee_id = $_GET['employee_id'] ?? 'N/A';
$position = $_GET['position'] ?? 'N/A';
$department = $_GET['department'] ?? 'N/A';
$hire_date = $_GET['hire_date'] ?? 'N/A';
$work_status = $_GET['work_status'] ?? 'N/A';
$company_name = $_GET['company_name'] ?? 'ALM COLLEGE TACLOBAN';
$company_code = $_GET['company_code'] ?? '';
$date_filed = $_GET['date'] ?? date('F j, Y');

// Keep output safe
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Certificate of Employment</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 24px; color:#111; }
    .header { display:flex; align-items:center; gap:16px; justify-content:center; }
    .logo { width:70px; height:70px; object-fit:contain; }
    h1 { text-align:center; font-size:20px; margin: 8px 0 2px; }
    h2 { text-align:center; font-size:16px; margin: 0 0 18px; font-weight:normal; }
    .meta { width: 100%; border-collapse: collapse; margin: 8px 0 18px; }
    .meta td { padding: 6px 8px; }
    .meta td.label { width: 180px; color:#444; font-weight:bold; }
    .box { border:1px solid #ddd; padding:18px; border-radius:10px; }
    .signature-row { display:flex; justify-content:space-between; margin-top: 44px; }
    .sig { width: 45%; text-align:center; }
    .sig .line { border-bottom:1px solid #111; height: 36px; }
    .sig .name { margin-top: 8px; font-weight:bold; }
    .small { font-size: 12px; color:#444; }
    @media print { .no-print{display:none;} body{margin:0;} }
</style>
</head>
<body>
    <div class="no-print" style="text-align:right;margin-bottom:12px;">
        <button onclick="window.print()" style="padding:10px 16px;cursor:pointer;">Print</button>
    </div>

    <div class="header">
        <img class="logo" src="assets/logo.jpg" alt="Logo" />
    </div>
    <h1><?php echo h($company_name); ?></h1>
    <h2>CERTIFICATE OF EMPLOYMENT</h2>

    <div class="box">
        <p style="margin:0 0 14px; line-height:1.5;">
            This is to certify that <b><?php echo h($employee_name); ?></b> (Employee ID: <b><?php echo h($employee_id); ?></b>)
            is employed with <b><?php echo h($company_name); ?></b> as a <b><?php echo h($position); ?></b>
            under the <b><?php echo h($department); ?></b>.
        </p>

        <table class="meta">
            <tr>
                <td class="label">Employment Status</td>
                <td><?php echo h($work_status); ?></td>
            </tr>
            <tr>
                <td class="label">Date Hired</td>
                <td><?php echo h($hire_date); ?></td>
            </tr>
            <tr>
                <td class="label">Date Issued</td>
                <td><?php echo h($date_filed); ?></td>
            </tr>
        </table>

        <p style="margin:0; line-height:1.5;">
            This certification is issued upon request for whatever legal purpose it may serve.
        </p>
    </div>

    <div class="signature-row">
        <div class="sig">
            <div class="small">HR / Records</div>
            <div class="line"></div>
            <div class="name">__________________________</div>
        </div>
        <div class="sig">
            <div class="small">Authorized Signatory</div>
            <div class="line"></div>
            <div class="name">__________________________</div>
        </div>
    </div>

    <div class="small" style="margin-top:18px; text-align:center;">
        <?php echo h($company_code); ?>
    </div>

</body>
</html>

