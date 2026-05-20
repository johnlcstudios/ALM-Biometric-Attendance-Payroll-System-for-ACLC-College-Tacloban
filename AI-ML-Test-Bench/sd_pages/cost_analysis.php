<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-money-bill-wave me-2"></i>Departmental Cost Analysis</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Total Payroll Cost</div>
                    <div class="stat-value">₱33.0M</div>
                    <small class="text-muted">Annual FY2026</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Avg Cost per Employee</div>
                    <div class="stat-value">₱266.1K</div>
                    <small class="text-muted">Includes benefits</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Highest Cost Dept</div>
                    <div class="stat-value">Operations</div>
                    <small class="text-muted">₱12.6M (38%)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Cost Trend YoY</div>
                    <div class="stat-value" style="color: #f39c12;">+3.2%</div>
                    <small class="text-muted">Growth rate</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Cost Distribution by Department</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="costDistChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Cost Breakdown Analysis</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="costBreakdownChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6>Departmental Cost Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Headcount</th>
                                <th>Total Cost</th>
                                <th>% of Total</th>
                                <th>Avg Cost/Employee</th>
                                <th>Cost Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Operations</strong></td>
                                <td>46</td>
                                <td>₱12,600,000</td>
                                <td>38.2%</td>
                                <td>₱273,913</td>
                                <td><span class="badge bg-info">+2.5%</span></td>
                            </tr>
                            <tr>
                                <td><strong>Academics</strong></td>
                                <td>35</td>
                                <td>₱11,000,000</td>
                                <td>33.3%</td>
                                <td>₱314,286</td>
                                <td><span class="badge bg-info">+3.8%</span></td>
                            </tr>
                            <tr>
                                <td><strong>Administration</strong></td>
                                <td>28</td>
                                <td>₱6,050,000</td>
                                <td>18.3%</td>
                                <td>₱216,071</td>
                                <td><span class="badge bg-info">+2.1%</span></td>
                            </tr>
                            <tr>
                                <td><strong>Finance</strong></td>
                                <td>15</td>
                                <td>₱3,350,000</td>
                                <td>10.2%</td>
                                <td>₱223,333</td>
                                <td><span class="badge bg-success">+0.5%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cost Distribution Pie Chart
    const distCtx = document.getElementById('costDistChart').getContext('2d');
    new Chart(distCtx, {
        type: 'pie',
        data: {
            labels: ['Operations', 'Academics', 'Administration', 'Finance'],
            datasets: [{
                data: [12600000, 11000000, 6050000, 3350000],
                backgroundColor: ['#1e0178', '#27ae60', '#f39c12', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // Cost Breakdown Bar Chart
    const breakdownCtx = document.getElementById('costBreakdownChart').getContext('2d');
    new Chart(breakdownCtx, {
        type: 'bar',
        data: {
            labels: ['Salaries', 'Benefits', 'SSS/PhilHealth', 'Pag-IBIG', 'Other'],
            datasets: [{
                label: 'Cost (₱)',
                data: [20000000, 7000000, 3500000, 1500000, 1000000],
                backgroundColor: '#1e0178'
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y' }
    });
});
</script>

<?php require_once 'footer.php'; ?>
