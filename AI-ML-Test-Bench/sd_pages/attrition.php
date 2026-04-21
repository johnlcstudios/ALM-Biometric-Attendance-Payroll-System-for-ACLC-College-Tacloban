<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-chart-line me-2"></i>Attrition Analysis & Trends</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Annual Turnover Rate</div>
                    <div class="stat-value" style="color: #f39c12;">3.2%</div>
                    <small class="text-muted">4 employees left FY2026</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Retention Rate</div>
                    <div class="stat-value" style="color: #27ae60;">96.8%</div>
                    <small class="text-muted">High retention strength</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Avg Service Duration</div>
                    <div class="stat-value">5.2 yrs</div>
                    <small class="text-muted">At resignation</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Cost of Attrition</div>
                    <div class="stat-value" style="color: #dc3545;">₱1.8M</div>
                    <small class="text-muted">Estimated impact</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Resignation Trends</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="resignationTrendChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Reasons for Attrition</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="reasonsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6>Recent Resignations</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Resignation Date</th>
                                <th>Effective Date</th>
                                <th>Reason</th>
                                <th>Tenure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Roberto Santos</td>
                                <td>Operations</td>
                                <td>2026-02-15</td>
                                <td>2026-03-15</td>
                                <td>Career Advancement</td>
                                <td>4 yrs 2 mo</td>
                            </tr>
                            <tr>
                                <td>Cristina Reyes</td>
                                <td>Finance</td>
                                <td>2026-01-10</td>
                                <td>2026-02-10</td>
                                <td>Relocation</td>
                                <td>6 yrs 8 mo</td>
                            </tr>
                            <tr>
                                <td>Miguel Torres</td>
                                <td>Academics</td>
                                <td>2025-12-05</td>
                                <td>2026-01-05</td>
                                <td>Health Concerns</td>
                                <td>3 yrs 5 mo</td>
                            </tr>
                            <tr>
                                <td>Angela Flores</td>
                                <td>Administration</td>
                                <td>2025-11-20</td>
                                <td>2025-12-20</td>
                                <td>Higher Education</td>
                                <td>5 yrs 11 mo</td>
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
    // Resignation Trends
    const trendCtx = document.getElementById('resignationTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: ['2022', '2023', '2024', '2025', '2026'],
            datasets: [{
                label: 'Resignations',
                data: [6, 5, 4, 5, 4],
                backgroundColor: '#dc3545'
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // Reasons for Attrition
    const reasonsCtx = document.getElementById('reasonsChart').getContext('2d');
    new Chart(reasonsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Career Advancement', 'Relocation', 'Health', 'Education', 'Other'],
            datasets: [{
                data: [8, 5, 3, 2, 2],
                backgroundColor: ['#1e0178', '#27ae60', '#f39c12', '#dc3545', '#17a2b8']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
});
</script>

<?php require_once 'footer.php'; ?>
