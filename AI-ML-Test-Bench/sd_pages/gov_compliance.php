<?php
require_once 'config.php';
require_once 'header.php';

// Handle Remittance Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remit_id'])) {
    $remit_id = (int)$_POST['remit_id'];
    try {
        $stmt = $pdo->prepare("UPDATE `compliance_remittances` SET status = 'remitted', remitted_at = NOW() WHERE id = ?");
        $stmt->execute([$remit_id]);
        $msg = "Remittance ID $remit_id has been successfully marked as remitted.";
    } catch (Exception $e) {
        $msg_error = "Error: " . $e->getMessage();
    }
}

// Fetch Remittances
$stmt = $pdo->query("SELECT * FROM `compliance_remittances` ORDER BY period DESC, agency ASC LIMIT 50");
$remittances = $stmt->fetchAll() ?: [];
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

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-white border-0 p-4">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <h6 class="text-muted text-uppercase fw-bold mb-2 small">Total SSS Remitted</h6>
                    <h3 class="text-primary fw-bold mb-0">₱<?php 
                        $sss_total = $pdo->query("SELECT SUM(amount) FROM compliance_remittances WHERE agency='SSS' AND status='remitted'")->fetchColumn() ?? 0;
                        echo number_format($sss_total, 2);
                    ?></h3>
                </div>
                <div class="col-md-4 border-end">
                    <h6 class="text-muted text-uppercase fw-bold mb-2 small">Total PhilHealth Remitted</h6>
                    <h3 class="text-success fw-bold mb-0">₱<?php 
                        $ph_total = $pdo->query("SELECT SUM(amount) FROM compliance_remittances WHERE agency='PhilHealth' AND status='remitted'")->fetchColumn() ?? 0;
                        echo number_format($ph_total, 2);
                    ?></h3>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-bold mb-2 small">Total Pag-IBIG Remitted</h6>
                    <h3 class="text-warning fw-bold mb-0">₱<?php 
                        $pagibig_total = $pdo->query("SELECT SUM(amount) FROM compliance_remittances WHERE agency='Pag-IBIG' AND status='remitted'")->fetchColumn() ?? 0;
                        echo number_format($pagibig_total, 2);
                    ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 text-dark fw-bold"><i class="fas fa-file-invoice me-2 text-primary"></i> Institutional Remittances Summary</h5>
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">100% Compliance Target</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-muted small text-uppercase">
                <tr>
                    <th class="border-0">Government Agency</th>
                    <th class="border-0">Applicable Period</th>
                    <th class="border-0">Amount Due</th>
                    <th class="border-0">Status</th>
                    <th class="border-0">Remitted At</th>
                    <th class="border-0 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php 
                if (empty($remittances)) {
                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No remittance records found</td></tr>';
                } else {
                    foreach ($remittances as $remit):
                ?>
                <tr>
                    <td class="fw-bold">
                        <?php if($remit['agency'] === 'SSS'): ?>
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                        <?php elseif($remit['agency'] === 'PhilHealth'): ?>
                            <i class="fas fa-heartbeat text-success me-2"></i>
                        <?php else: ?>
                            <i class="fas fa-home text-warning me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($remit['agency']); ?>
                    </td>
                    <td class="text-muted"><?php echo htmlspecialchars($remit['period'] ?? 'N/A'); ?></td>
                    <td class="fw-bold">₱<?php echo number_format($remit['amount'] ?? 0, 2); ?></td>
                    <td>
                        <?php if ($remit['status'] === 'pending'): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="fas fa-exclamation-triangle me-1"></i> Pending</span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fas fa-check-double me-1"></i> Remitted</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?php echo $remit['remitted_at'] ? date('M d, Y H:i', strtotime($remit['remitted_at'])) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($remit['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="remit_id" value="<?php echo $remit['id']; ?>">
                                <button type="submit" class="btn btn-outline-success rounded-pill btn-sm px-4">
                                    <i class="fas fa-check me-1"></i> Remit
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-light rounded-pill btn-sm px-4" disabled>
                                <i class="fas fa-print me-1"></i> Receipt
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

<div class="alert alert-warning bg-opacity-10 border-0 mt-4 rounded-4 p-4 d-flex align-items-center text-dark">
    <i class="fas fa-exclamation-circle fa-2x me-3 text-warning"></i>
    <div class="small">
        <strong>Government Mandate:</strong> All institutional remittances must be settled on or before the 15th of the following month to avoid penalties and legal implications.
    </div>
</div>

<?php require_once 'footer.php'; ?>
