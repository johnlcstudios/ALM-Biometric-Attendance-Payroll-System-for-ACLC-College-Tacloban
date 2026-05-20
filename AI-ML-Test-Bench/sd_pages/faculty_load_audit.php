<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-graduation-cap me-2"></i>Faculty Load Audit & Management</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="form-group">
                    <label><strong>Department</strong></label>
                    <select class="form-control">
                        <option value="">All Departments</option>
                        <option value="academics">Academics</option>
                        <option value="operations">Operations</option>
                        <option value="finance">Finance</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><strong>Academic Year</strong></label>
                    <select class="form-control">
                        <option value="2026-2027">2026-2027 (Current)</option>
                        <option value="2025-2026">2025-2026</option>
                        <option value="2024-2025">2024-2025</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <label>&nbsp;</label>
                <button class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Search</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Faculty ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Assigned Units</th>
                        <th>Teaching Hours</th>
                        <th>Administrative Hours</th>
                        <th>Total Load</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>FAC001</td>
                        <td>Dr. Maria Santos</td>
                        <td>Academics</td>
                        <td>3</td>
                        <td>12</td>
                        <td>3</td>
                        <td><strong>15</strong></td>
                        <td><span class="badge bg-success">Within Limit</span></td>
                    </tr>
                    <tr>
                        <td>FAC002</td>
                        <td>Prof. Juan Dela Cruz</td>
                        <td>Academics</td>
                        <td>4</td>
                        <td>16</td>
                        <td>2</td>
                        <td><strong>18</strong></td>
                        <td><span class="badge bg-warning">Exceeded</span></td>
                    </tr>
                    <tr>
                        <td>FAC003</td>
                        <td>Dr. Rosa Garcia</td>
                        <td>Academics</td>
                        <td>2</td>
                        <td>8</td>
                        <td>4</td>
                        <td><strong>12</strong></td>
                        <td><span class="badge bg-success">Within Limit</span></td>
                    </tr>
                    <tr>
                        <td>FAC004</td>
                        <td>Engr. Pedro Lopez</td>
                        <td>Academics</td>
                        <td>3</td>
                        <td>12</td>
                        <td>2</td>
                        <td><strong>14</strong></td>
                        <td><span class="badge bg-success">Within Limit</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Load Compliance Analysis</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Faculty Within Limits</div>
                    <div class="stat-value" style="color: #27ae60;">32</div>
                    <small class="text-muted">94% of total faculty</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Exceeded Load</div>
                    <div class="stat-value" style="color: #f39c12;">2</div>
                    <small class="text-muted">Requires review</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-label">Under-Utilized</div>
                    <div class="stat-value" style="color: #17a2b8;">1</div>
                    <small class="text-muted">Can take additional load</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
