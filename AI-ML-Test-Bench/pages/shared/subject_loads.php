<section id="subject_loads" class="page">
    <div class="payroll-header" style="margin-top: 3rem;">
        <div class="header-left">
            <h2>Master Subject List</h2>
            <p>Create and manage available subjects for the system.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddSubjectModal()" aria-label="Create new subject">
            <i class="fas fa-plus" aria-hidden="true"></i> Create New Subject
        </button>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>CODE</th>
                        <th>DESCRIPTION</th>
                        <th>UNITS</th>
                        <th>HOURS/WEEK</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="subjectsTableBody">
                </tbody>
            </table>
        </div>
    </div>
</section>
