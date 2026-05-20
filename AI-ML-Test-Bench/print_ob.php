<?php
// print_ob.php - Simple Official Business (OB) request printable form (client-side prefilled)
require_once 'backend/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$employee_name = $_GET['name'] ?? 'Employee';
$employee_id = $_GET['employee_id'] ?? 'N/A';
$position = $_GET['position'] ?? 'N/A';
$department = $_GET['department'] ?? 'N/A';
$company_name = $_GET['company_name'] ?? 'ALM COLLEGE TACLOBAN';
$company_code = $_GET['company_code'] ?? '';

$ob_type = $_GET['ob_type'] ?? 'OB';
$destination = $_GET['destination'] ?? 'N/A';
$purpose = $_GET['purpose'] ?? 'N/A';
$travel_date = $_GET['travel_date'] ?? date('F j, Y');
$time_out = $_GET['time_out'] ?? '';
$time_in = $_GET['time_in'] ?? '';

$date_filed = $_GET['date'] ?? date('F j, Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Official Business Request</title>
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
    <h2>OFFICIAL BUSINESS (OB) REQUEST</h2>

    <div class="box">
        <table class="meta">
            <tr><td class="label">OB Type</td><td><?php echo h($ob_type); ?></td></tr>
            <tr><td class="label">Date Filed</td><td><?php echo h($date_filed); ?></td></tr>
            <tr><td class="label">Travel Date</td><td><?php echo h($travel_date); ?></td></tr>
            <tr><td class="label">Employee</td><td><?php echo h($employee_name); ?> (ID: <?php echo h($employee_id); ?>)</td></tr>
            <tr><td class="label">Position / Department</td><td><?php echo h($position); ?> / <?php echo h($department); ?></td></tr>
            <tr><td class="label">Destination</td><td><?php echo h($destination); ?></td></tr>
            <tr><td class="label">Purpose</td><td><?php echo h($purpose); ?></td></tr>
            <tr><td class="label">Time Out</td><td><?php echo h($time_out); ?></td></tr>
            <tr><td class="label">Time In</td><td><?php echo h($time_in); ?></td></tr>
        </table>

        <p style="margin:0; line-height:1.5;">
            I hereby request approval for the conduct of official business as stated above.
        </p>
    </div>

    <div class="signature-row">
        <div class="sig">
            <div class="small">Employee</div>
            <div class="line"></div>
            <div class="name">__________________________</div>
        </div>
        <div class="sig">
            <div class="small">Approving Authority</div>
            <div class="line"></div>
            <div class="name">__________________________</div>
        </div>
    </div>

    <div class="small" style="margin-top:18px; text-align:center;">
        <?php echo h($company_code); ?>
    </div>
</body>
</html>

