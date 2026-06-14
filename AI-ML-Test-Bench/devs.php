<?php
session_start();
require_once 'backend/db.php';

// Simple password protection
$dev_password = 'devs2026';
$authenticated = false;

if (isset($_POST['password']) && $_POST['password'] === $dev_password) {
    $_SESSION['dev_auth'] = true;
    $authenticated = true;
}
if (isset($_SESSION['dev_auth']) && $_SESSION['dev_auth'] === true) {
    $authenticated = true;
}

// Handle delete actions
$message = '';
if ($authenticated && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_company') {
        $company_id = (int)($_POST['company_id'] ?? 0);
        if ($company_id > 0) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM attendance WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM payroll WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM leave_requests WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM loans WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM resignations WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM coe_requests WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM holidays WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM overtime_records WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM subject_schedules WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM subject_loads WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM employee_allowances WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM employee_deductions WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM deductions WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM allowance_categories WHERE company_id = ?")->execute([$company_id]);
                $employees = $pdo->prepare("SELECT id FROM employees WHERE company_id = ?");
                $employees->execute([$company_id]);
                $emp_ids = $employees->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($emp_ids)) {
                    $ph = implode(',', array_fill(0, count($emp_ids), '?'));
                    $pdo->prepare("DELETE FROM employees WHERE id IN ($ph)")->execute($emp_ids);
                }
                $pdo->prepare("DELETE FROM users WHERE company_id = ?")->execute([$company_id]);
                $pdo->prepare("DELETE FROM companies WHERE id = ?")->execute([$company_id]);
                $pdo->commit();
                $message = "Company #$company_id deleted successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_employee') {
        $emp_id = (int)($_POST['employee_id'] ?? 0);
        if ($emp_id > 0) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM attendance WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM payroll WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM leave_requests WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM loans WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM resignations WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM coe_requests WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM overtime_records WHERE employee_id = ?")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM subject_schedules WHERE subject_load_id IN (SELECT id FROM subject_loads WHERE faculty_id = ?)")->execute([$emp_id]);
                $pdo->prepare("DELETE FROM subject_loads WHERE faculty_id = ?")->execute([$emp_id]);
                $stmt_user = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
                $stmt_user->execute([$emp_id]);
                $user_id = $stmt_user->fetchColumn();
                $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$emp_id]);
                if ($user_id) $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                $pdo->commit();
                $message = "Employee #$emp_id deleted successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'logout_dev') {
        unset($_SESSION['dev_auth']);
        header('Location: devs.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Panel - ALM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #e0e0e0; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { color: #4facfe; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .subtitle { color: #888; margin-bottom: 2rem; font-size: 0.9rem; }
        .card { background: #16213e; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #0f3460; }
        .card h2 { color: #4facfe; font-size: 1.2rem; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #0f3460; font-size: 0.85rem; }
        th { color: #4facfe; font-weight: 600; background: #0f3460; }
        tr:hover { background: #1a1a3e; }
        .btn { padding: 6px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-primary { background: #4facfe; color: white; }
        .btn-primary:hover { background: #3a8fd4; }
        .btn-logout { background: #555; color: white; float: right; }
        .login-box { max-width: 400px; margin: 100px auto; background: #16213e; padding: 2rem; border-radius: 12px; border: 1px solid #0f3460; }
        .login-box input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #0f3460; border-radius: 8px; background: #1a1a2e; color: #e0e0e0; font-size: 0.95rem; }
        .login-box button { width: 100%; padding: 12px; }
        .msg { padding: 10px; border-radius: 8px; margin-bottom: 1rem; background: #1a3a2e; color: #4caf50; border: 1px solid #2e7d32; }
        .msg.error { background: #3a1a1a; color: #f44336; border: 1px solid #c62828; }
        a { color: #4facfe; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!$authenticated): ?>
    <div class="login-box">
        <h1 style="text-align:center; margin-bottom: 1.5rem;"><i class="fas fa-code"></i> Developer Panel</h1>
        <?php if (isset($_POST['password']) && $_POST['password'] !== $dev_password): ?>
            <div class="msg error">Invalid password.</div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Developer Password" required>
            <button type="submit" class="btn btn-primary" style="margin-top:10px;">Access Panel</button>
        </form>
        <div style="text-align:center; margin-top:1.5rem;">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
    <?php else: ?>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1><i class="fas fa-code"></i> Developer Panel</h1>
            <div class="subtitle">System Management & Data Cleanup</div>
        </div>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="logout_dev">
            <button type="submit" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Companies -->
    <div class="card">
        <h2><i class="fas fa-building"></i> Companies</h2>
        <table>
            <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Employees</th><th>Action</th></tr></thead>
            <tbody>
            <?php
            $companies = $pdo->query("SELECT c.id, c.company_code, c.name, (SELECT COUNT(*) FROM employees WHERE company_id = c.id) as emp_count FROM companies c ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($companies as $c):
            ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['company_code']); ?></td>
                <td><?php echo htmlspecialchars($c['name']); ?></td>
                <td><?php echo $c['emp_count']; ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete company <?php echo htmlspecialchars($c['name']); ?> and ALL its data? This cannot be undone!')">
                        <input type="hidden" name="action" value="delete_company">
                        <input type="hidden" name="company_id" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($companies)): ?>
            <tr><td colspan="5" style="text-align:center; color:#888;">No companies found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Employees -->
    <div class="card">
        <h2><i class="fas fa-users"></i> Employees</h2>
        <table>
            <thead><tr><th>ID</th><th>Employee Code</th><th>Name</th><th>Position</th><th>Company</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php
            $employees = $pdo->query("SELECT e.id, e.employee_id, e.full_name, e.position, e.status, c.name as company_name FROM employees e LEFT JOIN companies c ON e.company_id = c.id ORDER BY e.id")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($employees as $emp):
            ?>
            <tr>
                <td><?php echo $emp['id']; ?></td>
                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                <td><?php echo htmlspecialchars($emp['position']); ?></td>
                <td><?php echo htmlspecialchars($emp['company_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($emp['status']); ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete employee <?php echo htmlspecialchars($emp['full_name']); ?> and ALL their data? This cannot be undone!')">
                        <input type="hidden" name="action" value="delete_employee">
                        <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?>
            <tr><td colspan="7" style="text-align:center; color:#888;">No employees found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick Stats -->
    <div class="card">
        <h2><i class="fas fa-chart-bar"></i> Quick Stats</h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1rem;">
            <div style="text-align:center; padding:1rem; background:#0f3460; border-radius:8px;">
                <div style="font-size:2rem; color:#4facfe;"><?php echo $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(); ?></div>
                <div style="color:#888; font-size:0.8rem;">Companies</div>
            </div>
            <div style="text-align:center; padding:1rem; background:#0f3460; border-radius:8px;">
                <div style="font-size:2rem; color:#4facfe;"><?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></div>
                <div style="color:#888; font-size:0.8rem;">Users</div>
            </div>
            <div style="text-align:center; padding:1rem; background:#0f3460; border-radius:8px;">
                <div style="font-size:2rem; color:#4facfe;"><?php echo $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(); ?></div>
                <div style="color:#888; font-size:0.8rem;">Employees</div>
            </div>
            <div style="text-align:center; padding:1rem; background:#0f3460; border-radius:8px;">
                <div style="font-size:2rem; color:#4facfe;"><?php echo $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn(); ?></div>
                <div style="color:#888; font-size:0.8rem;">Attendance Logs</div>
            </div>
        </div>
    </div>

    <div style="text-align:center; margin-top:2rem; color:#555; font-size:0.8rem;">
        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
