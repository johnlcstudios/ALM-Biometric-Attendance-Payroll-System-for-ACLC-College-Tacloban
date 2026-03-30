<?php
require_once 'backend/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect Admin, HR, and Payroll roles
$session_role = trim($_SESSION['role'] ?? '');
if (strcasecmp($session_role, 'Payroll') === 0) {
    header('Location: Payroll-Officer.php');
    exit;
}
if (in_array($session_role, ['Admin', 'HR'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - ALM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .ess-dashboard { padding: 3rem; max-width: 1200px; margin: 0 auto; }
        .ess-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; }
        .ess-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .profile-card { background: white; padding: 2rem; border-radius: var(--border-radius); text-align: center; }
        .profile-card img { width: 120px; height: 120px; border-radius: 50%; margin-bottom: 1.5rem; border: 4px solid var(--accent-color); }
        .profile-card h3 { color: var(--primary-color); margin-bottom: 0.5rem; }
        .profile-card .tag { background: #eee; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; color: #666; }
        .ess-tabs { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .ess-tab { padding: 0.8rem 1.5rem; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .ess-tab.active { background: var(--accent-color); color: white; }
        .ess-content-card { background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--card-shadow); }
    </style>
</head>
<body class="bg-light">
    <div class="ess-dashboard">
        <header class="ess-header">
            <div class="logo"><i class="fas fa-fingerprint"></i> <span>ALM Biometrics</span></div>
            <button class="btn btn-danger btn-sm" onclick="logout()">Logout <i class="fas fa-sign-out-alt"></i></button>
        </header>

        <div class="ess-grid">
            <aside class="profile-card" id="profile-card">
                <img src="https://ui-avatars.com/api/?name=Employee&background=1e2a6e&color=fff" alt="Profile">
                <h3>---</h3>
                <p class="tag">Employee</p>
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">
                <div style="text-align: left;">
                    <p><strong>Employee ID:</strong> <span id="p-id">---</span></p>
                    <p><strong>Department:</strong> <span id="p-dept">---</span></p>
                    <p><strong>Position:</strong> <span id="p-pos">---</span></p>
                    <p><strong>Email:</strong> <span id="p-email">---</span></p>
                </div>
            </aside>

            <main>
                <div class="ess-tabs">
                    <button class="ess-tab active" onclick="switchTab('attendance')">Attendance</button>
                    <button class="ess-tab" onclick="switchTab('payroll')">Payroll</button>
                    <button class="ess-tab" onclick="switchTab('leave')">Leave</button>
                    <button class="ess-tab" onclick="switchTab('loan')">Loan</button>
                    <button class="ess-tab" onclick="switchTab('resignation')">Resignation</button>
                </div>

                <div class="ess-content-card" id="ess-content">
                    <!-- Dynamic Content -->
                </div>
            </main>
        </div>
    </div>

    <script>
        let essData = null;

        async function loadESS() {
            try {
                const response = await fetch('backend/api.php?action=get_ess_data');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                essData = await response.json();

                if (essData.error) throw new Error(essData.error);

                // Fill Profile
                const p = essData.profile;
                document.querySelector('.profile-card h3').innerText = p.full_name || p.username;
                document.querySelector('.profile-card .tag').innerText = p.role || 'Employee';
                document.getElementById('p-id').innerText = p.employee_id || 'N/A';
                document.getElementById('p-dept').innerText = p.department || 'N/A';
                document.getElementById('p-pos').innerText = p.position || p.role;
                document.getElementById('p-email').innerText = p.user_email || p.email;

                const avatarName = (p.full_name || p.username).split(' ').join('+');
                document.querySelector('.profile-card img').src = `https://ui-avatars.com/api/?name=${avatarName}&background=1e2a6e&color=fff`;

                switchTab('attendance');
            } catch (error) {
                console.error("Failed to load ESS data:", error);
                document.getElementById('ess-content').innerHTML = `<p style='color:red;text-align:center;'>Failed to load your data. Error: ${error.message}. Please try logging in again.</p>`;
                document.querySelector('.profile-card').style.display = 'none';
            }
        }

        function switchTab(tab) {
            document.querySelectorAll('.ess-tab').forEach(t => t.classList.toggle('active', t.innerText.toLowerCase().includes(tab)));
            
            const container = document.getElementById('ess-content');
            if (tab === 'attendance') {
                container.innerHTML = `
                    <h3>Recent Attendance Logs</h3>
                    <table style="width:100%; margin-top:1.5rem;">
                        <thead>
                            <tr><th>Date</th><th>Check-In</th><th>Check-Out</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            ${essData.attendance.map(a => `
                                <tr>
                                    <td>${a.log_date}</td>
                                    <td>${a.check_in || '---'}</td>
                                    <td>${a.check_out || '---'}</td>
                                    <td><span class="status-badge status-${a.status.toLowerCase()}">${a.status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else if (tab === 'payroll') {
                container.innerHTML = `
                    <h3>Payroll History</h3>
                    <table style="width:100%; margin-top:1.5rem;">
                        <thead>
                            <tr><th>Period</th><th>Net Pay</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            ${essData.payroll.map(p => `
                                <tr>
                                    <td>${p.period}</td>
                                    <td>₱${parseFloat(p.net_pay).toLocaleString()}</td>
                                    <td><span class="status-badge status-active">${p.status}</span></td>
                                    <td><button class="btn btn-primary btn-sm" onclick="exportPayslip('${p.id}')">Download PDF</button></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else if (tab === 'leave') {
                container.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                        <h3>Leave Requests</h3>
                        <button class="btn btn-primary btn-sm" onclick="showLeaveForm()">Apply for Leave</button>
                    </div>
                    <table style="width:100%;">
                        <thead>
                            <tr><th>Type</th><th>Duration</th><th>Reason</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            ${essData.leave ? essData.leave.map(l => `
                                <tr>
                                    <td>${l.type}</td>
                                    <td>${l.duration}</td>
                                    <td>${l.reason}</td>
                                    <td><span class="status-badge status-${l.status.toLowerCase()}">${l.status}</span></td>
                                </tr>
                            `).join('') : '<tr><td colspan="4">No requests found</td></tr>'}
                        </tbody>
                    </table>
                `;
            } else if (tab === 'loan') {
                container.innerHTML = `
                    <h3>Apply for a Loan</h3>
                    <form id="loanForm" style="margin-top:1.5rem;">
                        <div class="form-group">
                            <label>Amount (PHP)</label>
                            <input type="number" name="amount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Loan Application</button>
                    </form>
                `;
                document.getElementById('loanForm').onsubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    await fetch('backend/requests.php?action=apply_loan', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(Object.fromEntries(formData))
                    });
                    alert('Loan application submitted!');
                    loadESS();
                };
            } else if (tab === 'resignation') {
                container.innerHTML = `
                    <h3>Submit Resignation</h3>
                    <form id="resignationForm" style="margin-top:1.5rem;">
                        <div class="form-group">
                            <label>Effective Date</label>
                            <input type="date" name="effective_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Reason for Leaving</label>
                            <textarea name="reason" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">Submit Resignation</button>
                    </form>
                `;
                document.getElementById('resignationForm').onsubmit = async (e) => {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to submit your resignation? This action cannot be undone.')) return;
                    const formData = new FormData(e.target);
                    await fetch('backend/requests.php?action=apply_resignation', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(Object.fromEntries(formData))
                    });
                    alert('Resignation submitted. We are sorry to see you go.');
                    loadESS();
                };
            }
        }

        function showLeaveForm() {
            const container = document.getElementById('ess-content');
            container.innerHTML = `
                <h3>Apply for Leave</h3>
                <form id="leaveForm" style="margin-top:1.5rem;">
                    <div class="form-group">
                        <label>Leave Type</label>
                        <select name="type" class="form-control">
                            <option>Sick Leave</option>
                            <option>Vacation Leave</option>
                            <option>Emergency Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration (e.g. 2 days, 2026-04-01 to 2026-04-02)</label>
                        <input type="text" name="duration" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="switchTab('leave')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            `;

            document.getElementById('leaveForm').onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const response = await fetch('backend/api.php?action=apply_leave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(formData))
                });
                if ((await response.json()).success) {
                    alert('Leave request submitted!');
                    loadESS();
                }
            };
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
