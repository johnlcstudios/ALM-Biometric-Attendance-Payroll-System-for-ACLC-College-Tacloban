<?php
// Cash Advance Form Printable Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

$full_name = '';
$position = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT e.* FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch();
    
    if ($emp) {
        $full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
        $position = $emp['position'] ?? 'Staff';
    }
}

// Check GET variables
$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '';
$reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
$date_filed = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('F j, Y');
$ca_no = isset($_GET['ca_no']) ? htmlspecialchars($_GET['ca_no']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Cash Advance Forms</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #525659; /* PDF viewer typical background */
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #0056b3;
        }

        .page {
            width: 210mm;
            height: 297mm;
            padding: 10mm;
            margin: 20mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            box-sizing: border-box;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 10mm;
        }

        .form-container {
            border: 1px dashed #ccc; /* Cut guide */
            padding: 15px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .page {
                margin: 0;
                padding: 10mm;
                width: 210mm;
                height: 297mm;
                box-shadow: none;
                page-break-after: always;
            }
            .print-btn {
                display: none;
            }
            .form-container {
                border: 1px dashed #eee; /* Subtle cut line */
            }
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 15px;
        }

        .header-text {
            font-weight: bold;
            font-size: 13px;
            line-height: 1.2;
        }

        .form-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .label {
            width: 130px;
            font-weight: bold;
        }

        .colon {
            width: 15px;
            text-align: center;
            font-weight: bold;
        }

        .line {
            flex-grow: 1;
            border-bottom: 1px solid black;
            min-height: 15px;
        }

        .line-input {
            flex-grow: 1;
            border: none;
            border-bottom: 1px solid black;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            outline: none;
            padding: 0 5px;
            margin-bottom: -1px;
            font-weight: bold;
        }
        
        .line-group {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .line-group .line-input, .line-group .line {
            margin-top: 15px;
        }

        .signature-row {
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .signature-row .label {
            width: 70px;
        }
        
        .signature-row .line-group {
            padding-left: 10px;
        }

        .signatures-section {
            margin-top: 5px;
        }

        .sig {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .sig .label {
            width: 150px;
            font-weight: bold;
        }
        
        .sig .value {
            font-weight: bold;
            flex-grow: 1;
            padding-left: 5px;
        }

        .balance-row {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">Print Forms</button>

    <div class="page">
        <?php for($i=0; $i<4; $i++): ?>
        <div class="form-container">
            <div class="header">
                <img src="assets/logo.jpg" alt="Logo" class="logo">
                <div class="header-text">
                    ACLC COLLEGE<br>TACLOBAN
                </div>
            </div>
            
            <div class="form-title">CASH ADVANCE FORM</div>
            
            <div class="row">
                <div class="label">CA FORM NO.</div>
                <div class="colon">:</div>
                <input type="text" class="line-input" value="<?= htmlspecialchars($ca_no, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="row">
                <div class="label">Date Filed:</div>
                <div class="colon">:</div>
                <input type="text" class="line-input" value="<?= htmlspecialchars($date_filed, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="row">
                <div class="label">Name of Employee</div>
                <div class="colon">:</div>
                <input type="text" class="line-input" value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="row">
                <div class="label">Position</div>
                <div class="colon">:</div>
                <input type="text" class="line-input" value="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="row">
                <div class="label">Amount Loan</div>
                <div class="colon">:</div>
                <input type="text" class="line-input" value="<?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="row" style="align-items: flex-start;">
                <div class="label" style="margin-top: 2px;">Purpose</div>
                <div class="colon" style="margin-top: 2px;">:</div>
                <div class="line-group" style="margin-top: -13px;">
                    <input type="text" class="line-input" value="<?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="text" class="line-input">
                </div>
            </div>
            
            <div class="row signature-row" style="align-items: flex-start;">
                <div class="label" style="margin-top: 2px;">Signature</div>
                <div class="line-group" style="margin-top: -5px;">
                    <div class="line"></div>
                </div>
            </div>

            <div class="signatures-section">
                <div class="sig">
                    <div class="label">Verified by</div>
                    <div class="colon">:</div>
                    <div class="value">Accounting Clerk</div>
                </div>
                <div class="sig">
                    <div class="label">Recommending Approval</div>
                    <div class="colon">:</div>
                    <div class="value">Admin</div>
                </div>
                <div class="sig">
                    <div class="label">Approved By</div>
                    <div class="colon">:</div>
                    <div class="value">Managing Director</div>
                </div>
                <div class="sig">
                    <div class="label">Released By</div>
                    <div class="colon">:</div>
                    <div class="value">Cashier</div>
                </div>
            </div>

            <div class="row balance-row">
                <div class="label" style="width: 150px;">Balance</div>
                <div class="colon">:</div>
                <input type="text" class="line-input">
            </div>
        </div>
        <?php endfor; ?>
    </div>
</body>
</html>
