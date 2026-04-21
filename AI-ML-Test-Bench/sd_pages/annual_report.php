<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-file-alt me-2"></i>Annual Institutional Report</h5>
    </div>
    <div class="card-body">
        <form id="annualReportForm" onsubmit="event.preventDefault(); generateAnnualReport();">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Fiscal Year</strong></label>
                        <select class="form-control" name="fiscal_year" id="fiscal_year">
                            <option value="2026">2026 (Current)</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Report Type</strong></label>
                        <select class="form-control" name="report_type">
                            <option value="summary">Personnel & Financial Summary</option>
                            <option value="detailed">Detailed Audit Report</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sync me-2"></i>Generate Report
                    </button>
                </div>
            </div>
        </form>

        <div id="annual-report-results" style="display: none;">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Total Personnel</div>
                        <div class="stat-value" id="annual-total-staff">124</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Annual Gross Payroll</div>
                        <div class="stat-value" style="font-size: 1.5rem;">₱<span id="annual-total-salary">0</span></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Total Deductions</div>
                        <div class="stat-value" style="font-size: 1.5rem;">₱<span id="annual-total-deductions">0</span></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Net Disbursement</div>
                        <div class="stat-value" style="color: #27ae60; font-size: 1.5rem;">₱<span id="annual-total-net">0</span></div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6>Annual Salary Trend</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="annualTrendChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6>Cost Distribution by Department</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="costDistChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateAnnualReport() {
    document.getElementById('annual-report-results').style.display = 'block';
    
    // Initialize charts
    const trendCtx = document.getElementById('annualTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Gross Salary',
                data: [2500000, 2500000, 2600000, 2600000, 2650000, 2700000, 2750000, 2800000, 2850000, 2900000, 2950000, 3000000],
                borderColor: '#1e0178',
                backgroundColor: 'rgba(30, 1, 120, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    const distCtx = document.getElementById('costDistChart').getContext('2d');
    new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: ['Academics', 'Operations', 'Administration', 'Finance'],
            datasets: [{
                data: [1575000, 1610000, 1064000, 780000],
                backgroundColor: ['#1e0178', '#27ae60', '#f39c12', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}
</script>

<?php require_once 'footer.php'; ?>
