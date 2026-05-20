<?php
require_once 'config.php';
require_once 'header.php';

// Handle Authorization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id'])) {
    $batch_id = (int)$_POST['batch_id'];
    $director_name = "Director " . date('Ymd');
    try {
        $stmt = $pdo->prepare("UPDATE `payroll_batches` SET status = 'authorized', authorized_by = ?, authorized_at = NOW() WHERE id = ?");
        $stmt->execute([$director_name, $batch_id]);
        $msg = "Batch ID $batch_id has been successfully authorized.";
    } catch (Exception $e) {
        $msg_error = "Error: " . $e->getMessage();
    }
}

// Fetch Batches
$stmt = $pdo->query("SELECT * FROM `payroll_batches` ORDER BY created_at DESC LIMIT 50");
$batches = $stmt->fetchAll() ?: [];
?>

<?php if (isset($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
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
        <h5 class="fw-bold mb-0"><i class="fas fa-list-ul me-2 text-primary"></i> Payroll Batches Pending Approval</h5>
        <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">Director's Review Required</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-muted small text-uppercase">
                <tr>
                    <th class="border-0">Batch Name</th>
                    <th class="border-0">Created At</th>
                    <th class="border-0">Total Amount</th>
                    <th class="border-0">Status</th>
                    <th class="border-0">Authorized By</th>
                    <th class="border-0">Authorized At</th>
                    <th class="border-0 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php 
                if (empty($batches)) {
                    echo '<tr><td colspan="7" class="text-center text-muted py-4">No payroll batches found</td></tr>';
                } else {
                    foreach ($batches as $batch): 
                ?>
                <tr>
                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($batch['created_at'])); ?></td>
                    <td class="fw-bold">₱<?php echo number_format($batch['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <?php if ($batch['status'] === 'pending'): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i> Pending</span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Authorized</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?php echo $batch['authorized_by'] ? htmlspecialchars($batch['authorized_by']) : '-'; ?></td>
                    <td class="text-muted small"><?php echo $batch['authorized_at'] ? date('M d, Y H:i', strtotime($batch['authorized_at'])) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($batch['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">
                                    <i class="fas fa-signature me-1"></i> Authorize
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

<div class="alert alert-info bg-opacity-10 border-0 mt-4 rounded-4 p-4 d-flex align-items-center">
    <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
    <p class="mb-0 small text-muted"><strong>Note:</strong> Once a batch is authorized, it will be queued for disbursement by the finance department. This action is recorded with your digital signature.</p>
</div>

<?php require_once 'footer.php'; ?>
