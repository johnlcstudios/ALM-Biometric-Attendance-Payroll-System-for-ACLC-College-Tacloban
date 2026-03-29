<!-- Modals -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Employee</h3>
            <span class="close" onclick="closeModal('employeeModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="step-indicator">
                <span class="step active" id="step1-indicator">1</span>
                <span class="step" id="step2-indicator">2</span>
                <span class="step" id="step3-indicator">3</span>
                <span class="step" id="step4-indicator">4</span>
            </div>
            <form id="employeeForm">
                <!-- Step 1: Personal Info -->
                <div class="form-step active" id="step1">
                    <h4>Personal Information</h4>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullName" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                    </div>
                </div>
                <!-- Step 2: Employment -->
                <div class="form-step" id="step2">
                    <h4>Employment Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Position</label>
                            <select name="position">
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                                <option value="Utility">Utility</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department">
                                <option value="IT">IT</option>
                                <option value="Education">Education</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Basic Salary</label>
                        <input type="number" name="basicSalary">
                    </div>
                </div>
                <!-- Step 3: Subject Load (Faculty) -->
                <div class="form-step" id="step3">
                    <h4>Subject Load (Faculty Only)</h4>
                    <div id="subjectRows">
                        <!-- Dynamic rows -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addSubjectRow()">+ Add Subject</button>
                </div>
                <!-- Step 4: Government IDs -->
                <div class="form-step" id="step4">
                    <h4>Government Identifiers</h4>
                    <div class="form-row">
                        <div class="form-group"><label>SSS</label><input type="text" name="sss"></div>
                        <div class="form-group"><label>PhilHealth</label><input type="text" name="philhealth"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>TIN</label><input type="text" name="tin"></div>
                        <div class="form-group"><label>Pag-IBIG</label><input type="text" name="pagibig"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="prevBtn" onclick="goEmpStep(-1)" style="display:none;">Previous</button>
            <button class="btn btn-primary" id="nextBtn" onclick="goEmpStep(1)">Next</button>
            <button class="btn btn-success" id="saveBtn" onclick="saveEmployee()" style="display:none;">Save Employee</button>
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
                <div class="form-group">
                    <label>Faculty Member</label>
                    <select id="loadFacultySelect" class="form-control" required>
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
                    <input type="password" id="oldPass" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="newPass" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" id="confirmPass" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
            <button class="btn btn-primary" onclick="changePassword()">Update Password</button>
        </div>
    </div>
</div>
