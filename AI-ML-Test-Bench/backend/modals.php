<!-- Modals -->
<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 100001; pointer-events: none;"></div>

<div id="employeeModal" class="modal">
    <div class="modal-content large-modal">
        <div class="modal-header bg-dark-purple text-white">
            <h3 id="employeeModalTitle">Add New Employee</h3>
            <span class="close" onclick="closeModal('employeeModal')">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Step Indicator -->
            <div class="stepper-container">
                <div class="stepper-item active" id="step1-indicator">
                    <div class="step-counter">1</div>
                    <div class="step-name">Personal</div>
                </div>
                <div class="stepper-item" id="step2-indicator">
                    <div class="step-counter">2</div>
                    <div class="step-name">Employment</div>
                </div>
                <div class="stepper-item" id="step3-indicator">
                    <div class="step-counter">3</div>
                    <div class="step-name">Government</div>
                </div>
                <div class="stepper-item" id="step4-indicator">
                    <div class="step-counter">4</div>
                    <div class="step-name">Subjects</div>
                </div>
            </div>

            <form id="employeeForm" class="modern-form">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step1">
                    <div class="step-header">
                        <i class="fas fa-user-circle"></i>
                        <h4>Personal Information</h4>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> First Name <span class="required text-danger">*</span></label>
                            <input type="text" name="firstName" placeholder="Enter first name" required>
                            <div class="error-msg text-danger small mt-1" style="display:none;">First name is required.</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Last Name <span class="required text-danger">*</span></label>
                            <input type="text" name="lastName" placeholder="Enter last name" required>
                            <div class="error-msg text-danger small mt-1" style="display:none;">Last name is required.</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Middle Initial</label>
                            <input type="text" name="middleInitial" placeholder="e.g. A" maxlength="1">
                        </div>
                        <div class="form-group full-width">
                            <label><i class="fas fa-user-tag"></i> Full Name (Auto-generated)</label>
                            <input type="text" name="fullName" id="fullNameDisplay" readonly style="background: rgba(0,0,0,0.2); cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Date of Birth <span class="required text-danger">*</span></label>
                            <input type="date" name="dob" required>
                            <div class="error-msg text-danger small mt-1" style="display:none;">Valid DOB required.</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address <span class="required text-danger">*</span></label>
                            <input type="email" name="email" placeholder="email@example.com" required>
                            <div class="error-msg text-danger small mt-1" style="display:none;">Valid email required.</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Contact Number</label>
                            <input type="text" name="contactNo" placeholder="09XX XXX XXXX">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender</label>
                            <select name="gender">
                                <option value="">Select a Gender</option>
                                <option value="Male">Male</option>

                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Employment Details -->
                <div class="form-step" id="step2">
                    <div class="step-header">
                        <i class="fas fa-briefcase"></i>
                        <h4>Employment Details</h4>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-id-badge"></i> Position <span class="required text-danger">*</span></label>
                            <select name="position" onchange="toggleSubjectStep()" required>
                                <option value="">Select a Position</option>
                                <option value="Faculty">Full-Time Faculty</option>
                                <option value="Staff">Regular Staff</option>
                                <option value="Utility">Utility Staff</option>
                                <option value="Payroll Officer">Payroll Officer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chalkboard-teacher"></i> Work Position (Academic Rank)</label>
                            <input type="text" name="work_position" placeholder="e.g. Assistant Professor, Lecturer, Instructor">
                        </div>
                        <div class="form-group" id="facultyLevelGroup" style="display:none;">
                            <label><i class="fas fa-graduation-cap"></i> Faculty Level <span class="required text-danger">*</span></label>
                            <select name="faculty_level">
                                <option value="">Select a Faculty Level</option>
                                <option value="SHS">Senior High School (SHS)</option>
                                <option value="College">College</option>
                                <option value="Both">Both SHS & College</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department <span class="required text-danger">*</span></label>
                            <select name="department" required>
                                <option value="">Select a Department</option>
                                <option value="IT">Information Technology</option>
                                <option value="Education">Education</option>
                                <option value="Admin">Administration</option>
                                <option value="Utility">General Services</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Basic Salary (Monthly) <span class="required text-danger">*</span></label>
                            <div class="input-with-icon">
                                <span class="icon">₱</span>
                                <input type="number" name="basicSalary" placeholder="0.00" min="0" required>
                            </div>
                            <div class="error-msg text-danger small mt-1" style="display:none;">Minimum salary 0 required.</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Employment Status</label>
                            <select name="status">
                                <option value="">Select a...</option>
                                <option value="Active">Active</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Contractual">Contractual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Work Status</label>
                            <select name="work_status">
                                <option value="">Select Work Status...</option>
                                <option value="Regular">Regular</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contractual">Contractual</option>
                                <option value="Probationary">Probationary</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Hire Date <span class="required text-danger">*</span></label>
                            <input type="date" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>
                            <div class="error-msg text-danger small mt-1" style="display:none;">Hire date is required.</div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Government Identifiers -->
                <div class="form-step" id="step3">
                    <div class="step-header">
                        <i class="fas fa-id-card"></i>
                        <h4>Government Identifiers</h4>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SSS Number</label>
                            <input type="text" name="sss" placeholder="XX-XXXXXXX-X" pattern="[0-9\\-]*" title="SSS number format: 99-9999999-9">
                        </div>
                        <div class="form-group">
                            <label>PhilHealth ID</label>
                            <input type="text" name="philhealth" placeholder="XX-XXXXXXXXX-X" pattern="[0-9\\-]*" title="PhilHealth format: 99-999999999-9">
                        </div>
                        <div class="form-group">
                            <label>TIN Number</label>
                            <input type="text" name="tin" placeholder="XXX-XXX-XXX-XXX" pattern="[0-9\\-]*" title="TIN format: 999-999-999-999">
                        </div>
                        <div class="form-group">
                            <label>Pag-IBIG MID</label>
                            <input type="text" name="pagibig" placeholder="XXXX-XXXX-XXXX" pattern="[0-9\\-]*" title="Pag-IBIG format: 9999-9999-9999">
                        </div>
                    </div>
                </div>

                <!-- Step 4: Subject Load (Faculty Only) -->
                <div class="form-step" id="step4">
                    <div class="step-header">
                        <i class="fas fa-book"></i>
                        <h4>Subject Load Allocation</h4>
                    </div>
                    <div id="subjectRowsContainer" class="subject-rows-wrapper">
                        <!-- Dynamic rows will be injected here -->
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" onclick="addSubjectRow()">
                        <i class="fas fa-plus"></i> Add Another Subject
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-footer border-top-0">
            <button class="btn btn-secondary" id="prevBtn" onclick="goEmpStep(-1)" style="display:none;">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <button class="btn btn-dark-purple" id="nextBtn" onclick="goEmpStep(1)">
                Next <i class="fas fa-arrow-right"></i>
            </button>
            <button class="btn btn-success" id="saveBtn" onclick="saveEmployee()" style="display:none;">
                <i class="fas fa-check-circle"></i> Save Employee
            </button>
        </div>
    </div>
</div>

<!-- Payslip View Modal -->
<div id="payslipModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Employee Payslip</h3>
            <span class="close" onclick="closeModal('payslipModal')">&times;</span>
        </div>
        <div class="modal-body" id="payslipContent">
            <!-- Dynamic Content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="exportPayslipPDF()">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </button>
        </div>
    </div>
</div>

<!-- Subject Load Modal -->
<div id="addLoadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Subject Load</h3>
            <span class="close" onclick="closeModal('addLoadModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="subjectLoadForm">
                <input type="hidden" id="loadFacultyId">
                <div class="form-group">
                    <label>Select Subject</label>
                    <select id="loadSubjectSelect" class="form-control" onchange="onLoadSubjectChange(this.value)">
                        <option value="">Select a...</option>

                        <!-- Dynamic Content -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject Code</label>
                    <input type="text" id="loadSubjectCode" class="form-control" placeholder="e.g. CS101" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="loadDescription" class="form-control" placeholder="e.g. Introduction to Programming" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Units</label>
                        <input type="number" id="loadUnits" class="form-control" placeholder="3" required>
                    </div>
                    <div class="form-group">
                        <label>Hours/Week</label>
                        <input type="number" id="loadHours" class="form-control" placeholder="5" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('addLoadModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveSubjectLoad()">Save Load</button>
        </div>
    </div>
</div>

<!-- Master Subject Modal -->
<div id="subjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="subjectModalTitle">Create New Subject</h3>
            <span class="close" onclick="closeModal('subjectModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="masterSubjectForm">
                <input type="hidden" id="subjectId">
                <div class="form-group">
                    <label>Subject Code</label>
                    <input type="text" id="subjectCode" class="form-control" placeholder="e.g. MATH101" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="subjectDescription" class="form-control" placeholder="e.g. College Algebra" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Units</label>
                        <input type="number" id="subjectUnits" class="form-control" value="3" required>
                    </div>
                    <div class="form-group">
                        <label>Hours/Week</label>
                        <input type="number" id="subjectHours" class="form-control" value="3" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('subjectModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveMasterSubject()">Save Subject</button>
        </div>
    </div>
</div>

<!-- Run Payroll Modal -->
<div id="runPayrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Process New Payroll</h3>
            <span class="close" onclick="closeModal('runPayrollModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="runPayrollForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="payrollStartDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="payrollEndDate" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Filter by Category/Position</label>
                    <select id="payrollCategorySelect" class="form-control">
                        <option value="">Select a...</option>
                        <option value="all">All Employees</option>

                        <option value="Faculty">Full-Time Faculty</option>
                        <option value="Staff">Regular Staff</option>
                        <option value="Utility">Utility Staff</option>
                        <option value="Payroll Officer">Payroll Officer</option>
                    </select>
                </div>
                <p class="text-muted small"><i class="fas fa-info-circle"></i> This will calculate earnings and deductions for the selected period and employees.</p>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('runPayrollModal')">Cancel</button>
            <button class="btn btn-primary" onclick="runPayroll()">Run Payroll Now</button>
        </div>
    </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Admin Password</h3>
            <span class="close" onclick="closeModal('passwordModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="passwordForm">
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="oldPass" class="password-field" required>
                        <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="newPass" class="password-field" required oninput="checkPasswordStrength()">
                        <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <div id="password-requirements" style="margin-top: 5px; font-size: 0.8rem;">
                        <div id="req-length" class="req-item">At least 8 characters</div>
                        <div id="req-uppercase" class="req-item">One uppercase letter</div>
                        <div id="req-lowercase" class="req-item">One lowercase letter</div>
                        <div id="req-number" class="req-item">One number</div>
                        <div id="req-special" class="req-item">One special character</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmPass" class="password-field" required>
                        <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
            <button class="btn btn-primary" onclick="changePassword()">Update Password</button>
        </div>
    </div>
</div>

<style>
.req-item {
    color: #dc3545;
}
.req-item.valid {
    color: #28a745;
}
</style>

<!-- View Faculty Loads Modal -->
<div id="viewLoadsModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="viewLoadsTitle">Faculty Subject Loads</h3>
            <span class="close" onclick="closeModal('viewLoadsModal')">&times;</span>
        </div>
        <div class="modal-body">
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
                    <tbody id="viewLoadsTableBody">
                        <!-- Dynamic Content -->
                    </tbody>
                </table>
            </div>
            <hr style="margin:20px 0;">
            <h4>Schedule Management</h4>
            <p class="text-muted" style="font-size:0.85rem;margin-bottom:12px;">Select a subject load above and manage its weekly schedules below.</p>
            <div id="scheduleSection" style="display:none;">
                <div class="form-row-custom" style="grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:8px; align-items:end;">
                    <div class="form-group-custom">
                        <label>Day</label>
                        <select id="schedDay" style="padding:8px;border:1px solid #ddd;border-radius:6px;">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="form-group-custom">
                        <label>Time Start</label>
                        <input type="time" id="schedTimeStart" style="padding:8px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div class="form-group-custom">
                        <label>Time End</label>
                        <input type="time" id="schedTimeEnd" style="padding:8px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div class="form-group-custom">
                        <label>Room</label>
                        <input type="text" id="schedRoom" placeholder="Rm. 101" style="padding:8px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div class="form-group-custom">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="saveSchedule()" style="padding:8px 16px;font-size:0.85rem;">Add</button>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>DAY</th>
                                <th>TIME START</th>
                                <th>TIME END</th>
                                <th>ROOM</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <!-- Dynamic schedule rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('viewLoadsModal')">Close</button>
        </div>
    </div>
</div>
