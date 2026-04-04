<?php
require_once 'backend/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect Admin, HR, and Payroll roles
$session_role = trim($_SESSION['role'] ?? '');
if (in_array($session_role, ['Payroll', 'Payroll Officer'])) {
    header('Location: Payroll-Officer.php');
    exit;
}
if (in_array($session_role, ['Admin', 'HR'])) {
    header('Location: index.php');
    exit;
}

// Fetch employee profile on server-side
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();
$full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
$emp_id = $emp['employee_id'] ?? '---';
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - ALM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <style>
        body.bg-light { background: #fff; }
        .emp-dashboard { padding: 3rem; max-width: 1200px; margin: 0 auto; }
        .emp-topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; }
        .emp-welcome h1 { font-size: 2rem; font-weight: 700; color: #111; margin: 0; }
        .emp-welcome .meta { color: #666; margin-top: 0.3rem; font-size: 0.95rem; }
        .emp-actions { display: flex; gap: 0.75rem; align-items: center; }

        .emp-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 2rem; }
        .emp-card { background: #fff; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); padding: 1.5rem; min-height: 130px; }
        .emp-card-title { font-size: 0.75rem; font-weight: 800; letter-spacing: 0.8px; color: #111; text-transform: uppercase; margin-bottom: 1rem; }
        .emp-card-value { font-size: 1.3rem; font-weight: 800; color: #111; }
        .emp-card-sub { margin-top: 0.35rem; color: #666; font-size: 0.9rem; }

        .emp-table-card { background: #fff; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); margin-top: 2.5rem; overflow: hidden; }
        .emp-table-card-header { padding: 1.5rem; font-size: 1.2rem; font-weight: 800; color: #111; }
        .emp-table { width: 100%; border-collapse: collapse; }
        .emp-table thead th { background: #e57368; color: #fff; padding: 0.9rem 1.2rem; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .emp-table tbody td { padding: 0.9rem 1.2rem; border-top: 1px solid #eee; font-size: 0.9rem; color: #333; }
        .emp-table tbody tr:hover { background: #fafafa; }

        @media (max-width: 950px) {
            .emp-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="emp-dashboard">
        <div class="emp-topbar">
            <div class="emp-welcome">
                <h1>Welcome back!</h1>
                <div class="meta"><span id="emp-welcome-meta"><?php echo $emp_id; ?> | <?php echo $full_name; ?></span></div>
            </div>
            <div class="emp-actions">
                <button class="btn btn-danger btn-sm" onclick="logout()">Logout <i class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>

        <div class="emp-cards">
            <div class="emp-card">
                <div class="emp-card-title">Attendance</div>
                <div class="emp-card-value" id="emp-attendance-value">---</div>
                <div class="emp-card-sub" id="emp-attendance-sub">---</div>
            </div>
            <div class="emp-card">
                <div class="emp-card-title">Last Payroll</div>
                <div class="emp-card-value" id="emp-lastpayroll-value">---</div>
                <div class="emp-card-sub" id="emp-lastpayroll-sub">---</div>
            </div>
            <div class="emp-card">
                <div class="emp-card-title">Active Deductions</div>
                <div class="emp-card-value" id="emp-deductions-value">---</div>
                <div class="emp-card-sub" id="emp-deductions-sub">---</div>
            </div>
        </div>

        <div class="emp-table-card">
            <div class="emp-table-card-header">Recent Payroll Activity</div>
            <table class="emp-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Total Payout</th>
                    </tr>
                </thead>
                <tbody id="emp-payroll-activity-body">
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let essData = null;

        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(t => t.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            el.classList.add('active');
            
            document.getElementById('page-title').innerText = el.innerText.trim();
        }

        async function loadESS() {
            try {
                const response = await fetch('backend/api.php?action=get_ess_data');
                essData = await response.json();

                if (essData.error) throw new Error(essData.error);

                const p = essData.profile;
                const name = p.full_name || p.username || 'Employee';
                const empId = p.employee_id || '---';
                const meta = document.getElementById('emp-welcome-meta');
                if (meta) meta.innerText = `${empId} | ${name}`;

                const attendance = Array.isArray(essData.attendance) ? essData.attendance : [];
                const payroll = Array.isArray(essData.payroll) ? essData.payroll : [];

                const attendedDays = attendance.filter(a => a.check_in).length;
                const lastAttendance = attendance[0] || null;

                document.getElementById('emp-attendance-value').innerText = `${attendedDays} Logs`;
                document.getElementById('emp-attendance-sub').innerText = lastAttendance
                    ? `Last: ${lastAttendance.log_date} (${lastAttendance.status || '---'})`
                    : 'No attendance records yet';

                const lastPayroll = payroll[0] || null;
                if (lastPayroll) {
                    const net = typeof lastPayroll.net_pay !== 'undefined' ? parseFloat(lastPayroll.net_pay) : 0;
                    const ded = typeof lastPayroll.deductions !== 'undefined' ? parseFloat(lastPayroll.deductions) : 0;
                    document.getElementById('emp-lastpayroll-value').innerText = `₱${net.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    document.getElementById('emp-lastpayroll-sub').innerText = lastPayroll.period || '---';
                    document.getElementById('emp-deductions-value').innerText = `₱${ded.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    document.getElementById('emp-deductions-sub').innerText = 'From latest payroll';
                } else {
                    document.getElementById('emp-lastpayroll-value').innerText = '---';
                    document.getElementById('emp-lastpayroll-sub').innerText = 'No payroll yet';
                    document.getElementById('emp-deductions-value').innerText = '---';
                    document.getElementById('emp-deductions-sub').innerText = 'No payroll yet';
                }

                const tbody = document.getElementById('emp-payroll-activity-body');
                if (tbody) {
                    const recent = payroll.slice(0, 5);
                    tbody.innerHTML = recent.map(pr => {
                        const payout = parseFloat(pr.net_pay || 0);
                        return `
                            <tr>
                                <td>${pr.period || '---'}</td>
                                <td>${pr.status || '---'}</td>
                                <td>₱${payout.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            </tr>
                        `;
                    }).join('') || '<tr><td colspan="3" style="text-align:center; color:#666; padding: 1.5rem;">No payroll activity yet</td></tr>';
                }
            } catch (error) {
                console.error("Failed to load ESS data:", error);
                const meta = document.getElementById('emp-welcome-meta');
                if (meta) meta.innerText = 'Failed to load data';
            }
        }

        async function logout() {
            await fetch('backend/api.php?action=logout');
            window.location.href = 'login.php';
        }

        async function exportPayslip(id) {
            const response = await fetch(`backend/api.php?action=get_payslip&id=${id}`);
            const payslip = await response.json();

            if (payslip) {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                doc.setFontSize(20);
                doc.setTextColor(30, 42, 110);
                doc.text('OFFICIAL PAYSLIP', 105, 20, { align: 'center' });

                doc.setFontSize(10);
                doc.setTextColor(100);
                doc.text('Biometric Attendance & Payroll System', 105, 28, { align: 'center' });

                doc.autoTable({
                    startY: 40,
                    head: [['Field', 'Value']],
                    body: [
                        ['Employee ID', payslip.emp_code],
                        ['Name', payslip.full_name],
                        ['Period', payslip.period],
                        ['Basic Salary', `PHP ${parseFloat(payslip.basic_pay).toLocaleString()}`],
                        ['Deductions', `PHP ${parseFloat(payslip.deductions).toLocaleString()}`],
                        ['Net Pay', `PHP ${parseFloat(payslip.net_pay).toLocaleString()}`]
                    ],
                    theme: 'striped',
                    headStyles: { fillColor: [30, 42, 110] }
                });

                doc.save(`Payslip_${payslip.emp_code}_${payslip.period.replace('/', '-')}.pdf`);
            }
        }

        loadESS();
    </script>
</body>
</html>