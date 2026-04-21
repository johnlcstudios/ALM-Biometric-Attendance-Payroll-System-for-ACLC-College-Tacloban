<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-certificate me-2"></i>Accreditation Report</h5>
    </div>
    <div class="card-body">
        <form id="accreditationForm" onsubmit="event.preventDefault(); generateAccreditationReport();">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Accrediting Body</strong></label>
                        <select class="form-control" name="accrediting_body" id="accrediting_body">
                            <option value="PACUCOA">PACUCOA (Philippine Association of Colleges and Universities Commission on Accreditation)</option>
                            <option value="PAASCU">PAASCU (Philippine Accrediting Association of Schools, Colleges and Universities)</option>
                            <option value="CHED">CHED Compliance (Commission on Higher Education)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Academic Year</strong></label>
                        <select class="form-control" name="academic_year" id="academic_year">
                            <option value="2027-2028">2027-2028 (Current)</option>
                            <option value="2026-2027">2026-2027</option>
                            <option value="2025-2026">2025-2026</option>
                            <option value="2024-2025">2024-2025</option>
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

        <div id="accreditation-results" style="display: none;">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Accreditation Criteria</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Last Evaluated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Faculty Qualifications</strong></td>
                            <td><span class="badge bg-success">Compliant</span></td>
                            <td>95%</td>
                            <td>2026-03-15</td>
                        </tr>
                        <tr>
                            <td><strong>Infrastructure & Facilities</strong></td>
                            <td><span class="badge bg-success">Compliant</span></td>
                            <td>88%</td>
                            <td>2026-02-20</td>
                        </tr>
                        <tr>
                            <td><strong>Curriculum Standards</strong></td>
                            <td><span class="badge bg-warning">Needs Improvement</span></td>
                            <td>78%</td>
                            <td>2026-01-10</td>
                        </tr>
                        <tr>
                            <td><strong>Student Support Services</strong></td>
                            <td><span class="badge bg-success">Compliant</span></td>
                            <td>92%</td>
                            <td>2026-02-28</td>
                        </tr>
                        <tr>
                            <td><strong>Research & Development</strong></td>
                            <td><span class="badge bg-warning">Needs Improvement</span></td>
                            <td>72%</td>
                            <td>2025-12-15</td>
                        </tr>
                        <tr>
                            <td><strong>Administrative Systems</strong></td>
                            <td><span class="badge bg-success">Compliant</span></td>
                            <td>91%</td>
                            <td>2026-03-05</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3">
                <strong><i class="fas fa-info-circle me-2"></i>Overall Assessment:</strong> Your institution is on track for full accreditation with minor improvements needed in curriculum standards and research initiatives.
            </div>
        </div>
    </div>
</div>

<script>
function generateAccreditationReport() {
    document.getElementById('accreditation-results').style.display = 'block';
}
</script>

<?php require_once 'footer.php'; ?>
