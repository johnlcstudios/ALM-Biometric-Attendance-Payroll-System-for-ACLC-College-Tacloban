<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-certificate me-2"></i>Compliance Status & Management</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Compliance Score</div>
                    <div class="stat-value" style="color: #27ae60;">92%</div>
                    <small class="text-muted">Overall system health</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Policies in Effect</div>
                    <div class="stat-value">18</div>
                    <small class="text-muted">Active compliance policies</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Last Audit</div>
                    <div class="stat-value">2026-04-10</div>
                    <small class="text-muted">2 days ago</small>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Last Reviewed</th>
                        <th>Next Review</th>
                        <th>Compliance %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Data Protection Policy</strong></td>
                        <td>Privacy & Security</td>
                        <td><span class="badge bg-success">Compliant</span></td>
                        <td>2026-03-15</td>
                        <td>2026-09-15</td>
                        <td><span class="badge bg-success">100%</span></td>
                    </tr>
                    <tr>
                        <td><strong>Access Control Policy</strong></td>
                        <td>Security</td>
                        <td><span class="badge bg-success">Compliant</span></td>
                        <td>2026-03-20</td>
                        <td>2026-09-20</td>
                        <td><span class="badge bg-success">99%</span></td>
                    </tr>
                    <tr>
                        <td><strong>Payroll Regulations</strong></td>
                        <td>Regulatory</td>
                        <td><span class="badge bg-success">Compliant</span></td>
                        <td>2026-02-28</td>
                        <td>2026-08-28</td>
                        <td><span class="badge bg-success">98%</span></td>
                    </tr>
                    <tr>
                        <td><strong>User Authentication</strong></td>
                        <td>Security</td>
                        <td><span class="badge bg-warning">Minor Issues</span></td>
                        <td>2026-04-01</td>
                        <td>2026-10-01</td>
                        <td><span class="badge bg-warning">85%</span></td>
                    </tr>
                    <tr>
                        <td><strong>System Backup Policy</strong></td>
                        <td>Business Continuity</td>
                        <td><span class="badge bg-success">Compliant</span></td>
                        <td>2026-04-05</td>
                        <td>2026-10-05</td>
                        <td><span class="badge bg-success">100%</span></td>
                    </tr>
                    <tr>
                        <td><strong>Incident Response Plan</strong></td>
                        <td>Security</td>
                        <td><span class="badge bg-success">Compliant</span></td>
                        <td>2026-03-10</td>
                        <td>2026-09-10</td>
                        <td><span class="badge bg-success">96%</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Compliance Issues & Remediation</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-warning me-2"></i>Minor Issue Detected:</strong>
            User Authentication Policy - 85% compliant. Some users do not have multi-factor authentication enabled. 
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        
        <h6 class="mt-4 mb-3"><strong>Recommended Actions:</strong></h6>
        <ul>
            <li>Enable multi-factor authentication (MFA) for all administrative users by 2026-05-10</li>
            <li>Conduct quarterly security training for all staff</li>
            <li>Review and update access control policies by 2026-06-30</li>
            <li>Schedule next full system audit for 2026-10-10</li>
        </ul>
    </div>
</div>

<?php require_once 'footer.php'; ?>
