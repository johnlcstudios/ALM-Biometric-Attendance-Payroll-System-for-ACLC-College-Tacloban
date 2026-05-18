<?php
require_once 'config.php';
require_once 'header.php';

// Handle Loan Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id']) && isset($_POST['action'])) {
    $loan_id = (int)$_POST['loan_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $director_name = "Director " . date('Ymd');
    try {
        $stmt = $pdo->prepare("UPDATE `loans_advances` SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$action, $director_name, $loan_id]);
        $msg = "Cash advance request ID $loan_id has been successfully $action.";
    } catch (Exception $e) {
        $msg_error = "Error: " . $e->getMessage();
    }
}

// Fetch Loans
$stmt = $pdo->query("SELECT * FROM `loans_advances` ORDER BY created_at DESC LIMIT 50");
$loans = $stmt->fetchAll() ?: [];
?>

<?php if (isset($msg)): ?>
    <div class="alert alert-info alert-dismissible fade show rounded-pill px-4 shadow-sm" role="alert">
        <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($msg_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-pill px-4 shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $msg_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 text-danger fw-bold"><i class="fas fa-hand-holding-usd me-2"></i> Cash Advance Requests</h5>
        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Pending Review: <?php 
            $pending = $pdo->query("SELECT COUNT(*) FROM loans_advances WHERE status='pending'")->fetchColumn() ?? 0;
            echo $pending;
        ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-muted small text-uppercase">
                <tr>
                    <th class="border-0">Employee Name</th>
                    <th class="border-0">Type</th>
                    <th class="border-0">Requested Amount</th>
                    <th class="border-0">Request Date</th>
                    <th class="border-0">Status</th>
                    <th class="border-0">Reviewed By</th>
                    <th class="border-0 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php 
                if (empty($loans)) {
                    echo '<tr><td colspan="7" class="text-center text-muted py-4">No cash advance requests found</td></tr>';
                } else {
                    foreach ($loans as $loan):
                ?>
                <tr>
                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($loan['employee_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if($loan['type'] === 'Loan'): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">Cash Advance</span>
                        <?php else: ?>
                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-1 rounded-pill">Advance</span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold">₱<?php echo number_format($loan['amount'] ?? 0, 2); ?></td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></td>
                    <td>
                        <?php if ($loan['status'] === 'pending'): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i> Pending</span>
                        <?php elseif ($loan['status'] === 'approved'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Approved</span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill"><i class="fas fa-times-circle me-1"></i> Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?php echo $loan['reviewed_by'] ? htmlspecialchars($loan['reviewed_by']) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($loan['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success rounded-pill btn-sm px-3 me-1">
                                    <i class="fas fa-thumbs-up me-1"></i> Approve
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline-danger rounded-pill btn-sm px-3">
                                    <i class="fas fa-thumbs-down me-1"></i> Reject
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-light rounded-pill btn-sm px-4" disabled>
                                <i class="fas fa-lock me-1"></i> Locked
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                    endforeach;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card bg-light bg-opacity-50 border-0 p-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3 small"><i class="fas fa-info-circle me-2"></i> Cash Advance Review Policy</h6>
            <ul class="small text-muted mb-0">
                <li><strong>High-Value Threshold:</strong> Cash advances exceeding ₱50,000.00 require secondary approval from the Board of Trustees.</li>
                <li><strong>Cash Advance Limit:</strong> Limited to 50% of the employee's monthly basic salary.</li>
                <li><strong>Delinquency Rule:</strong> Employees with existing unpaid cash advances are not eligible for new requests.</li>
                <li><strong>Interest Rate:</strong> Follows institutional policy as per employee handbook.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
