<?php
require_once 'config.php';
require_once 'header.php';

// Fetch budget vs actual data
$stmt = $pdo->query("SELECT * FROM `budget_actual` ORDER BY created_at DESC LIMIT 12");
$expenditures = $stmt->fetchAll() ?: [];
?>

<div class="row">
    <div class="col-md-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-chart-bar me-2"></i> Monthly Financial Performance</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">Budget Analysis</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th class="border-0">Month</th>
                            <th class="border-0">Allocated Budget</th>
                            <th class="border-0">Actual Payouts</th>
                            <th class="border-0">Variance</th>
                            <th class="border-0">Utilization</th>
                            <th class="border-0">Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php 
                        if (empty($expenditures)) {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No budget data available yet</td></tr>';
                        } else {
                            foreach ($expenditures as $exp): 
                                $variance = $exp['allocated_budget'] - $exp['actual_expenditure'];
                                $utilization = ($exp['actual_expenditure'] / $exp['allocated_budget']) * 100;
                                $status_class = ($variance >= 0) ? 'text-success' : 'text-danger';
                                $badge_class = ($variance >= 0) ? 'bg-success' : 'bg-danger';
                                $progress_class = ($utilization <= 100) ? 'bg-info' : 'bg-danger';
                        ?>
                        <tr>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($exp['month']); ?></td>
                            <td class="text-muted">₱<?php echo number_format($exp['allocated_budget'], 2); ?></td>
                            <td class="text-muted">₱<?php echo number_format($exp['actual_expenditure'], 2); ?></td>
                            <td class="<?php echo $status_class; ?> fw-bold">
                                <?php echo ($variance >= 0 ? '+' : ''); echo number_format($variance, 2); ?>
                            </td>
                            <td style="width: 250px;">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 8px; border-radius: 4px;">
                                        <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" style="width: <?php echo min($utilization, 100); ?>%;" aria-valuenow="<?php echo $utilization; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="fw-bold"><?php echo number_format($utilization, 1); ?>%</small>
                                </div>
                            </td>
                            <td>
                                <?php if ($utilization <= 100): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">On Track</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Exceeds Budget</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card p-4">
            <h6 class="fw-bold text-uppercase mb-3 small text-muted"><i class="fas fa-info-circle me-2"></i>Summary</h6>
            <div class="d-flex justify-content-between mb-2">
                <span>Total Allocated Budget:</span>
                <strong>₱<?php 
                    $total_budget = array_sum(array_column($expenditures, 'allocated_budget'));
                    echo number_format($total_budget, 2);
                ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Total Actual Expenditure:</span>
                <strong>₱<?php 
                    $total_actual = array_sum(array_column($expenditures, 'actual_expenditure'));
                    echo number_format($total_actual, 2);
                ?></strong>
            </div>
            <div class="d-flex justify-content-between text-success fw-bold">
                <span>Total Variance:</span>
                <span>₱<?php 
                    $total_variance = $total_budget - $total_actual;
                    echo number_format($total_variance, 2);
                ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4 bg-light">
            <h6 class="fw-bold text-uppercase mb-3 small text-muted"><i class="fas fa-lightbulb me-2"></i>Financial Insights</h6>
            <ul class="small text-muted" style="list-style: none; padding: 0;">
                <li><i class="fas fa-check text-success me-2"></i>Budget utilization is healthy</li>
                <li><i class="fas fa-chart-line text-info me-2"></i>Month-over-month expenses stable</li>
                <li><i class="fas fa-arrow-up text-warning me-2"></i>Q2 expenses trending upward</li>
                <li><i class="fas fa-target text-primary me-2"></i>FY target on pace</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
