<!-- Employee Modal -->
<div id="employeeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="employeeModalTitle">Add Employee</h3>
            <span class="close" onclick="closeModal('employeeModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="employeeForm">
                <input type="hidden" id="employeeId" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Position *</label>
                        <select id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Utility">Utility</option>
                            <option value="Staff">Staff</option>
                            <option value="Payroll Officer">Payroll Officer</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <input type="text" id="department" name="department" required>
                    </div>
                    <div class="form-group">
                        <label>Basic Salary *</label>
                        <input type="number" id="basicSalary" name="basicSalary" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>SSS Number</label>
                        <input type="text" id="sss" name="sss">
                    </div>
                    <div class="form-group">
                        <label>TIN</label>
                        <input type="text" id="tin" name="tin">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>PhilHealth</label>
                        <input type="text" id="philhealth" name="philhealth">
                    </div>
                    <div class="form-group">
                        <label>Pag-IBIG</label>
                        <input type="text" id="pagibig" name="pagibig">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="empStatus" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="On Leave">On Leave</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('employeeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deduction Modal -->
<div id="deductionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="deductionModalTitle">Add Deduction</h3>
            <span class="close" onclick="closeModal('deductionModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="deductionForm">
                <input type="hidden" id="deductionId" name="id">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" id="deductionName" name="name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Type *</label>
                        <select id="deductionType" name="type" required>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Value *</label>
                        <input type="number" id="deductionValue" name="value" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Active</label>
                        <input type="checkbox" id="deductionActive" name="is_active" checked>
                    </div>
                    <div class="form-group">
                        <label>Government Deduction</label>
                        <input type="checkbox" id="deductionGovernment" name="is_government">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deductionModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payroll Run Modal -->
<div id="payrollModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Run Payroll</h3>
            <span class="close" onclick="closeModal('payrollModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="payrollForm">
                <input type="hidden" id="payrollType" name="type">
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" id="payrollStartDate" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" id="payrollEndDate" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('payrollModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Run Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Face Registration Modal -->
<div id="faceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Register Face</h3>
            <span class="close" onclick="closeModal('faceModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="faceCamera" style="width: 100%; max-width: 400px; margin: 0 auto;">
                <video id="faceVideo" autoplay playsinline style="width: 100%; border-radius: 8px;"></video>
            </div>
            <p class="text-center" style="margin-top: 15px;">Position your face clearly in the camera</p>
            <input type="hidden" id="faceEmployeeId">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('faceModal')">Cancel</button>
            <button type="button" class="btn-primary" onclick="captureFace()">Capture Face</button>
        </div>
    </div>
</div>

<!-- Leave Request Modal (for ESS) -->
<div id="leaveModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Apply for Leave</h3>
            <span class="close" onclick="closeModal('leaveModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="leaveForm">
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select id="leaveType" name="type" required>
                        <option value="">Select Type</option>
                        <option value="Vacation">Vacation Leave</option>
                        <option value="Sick">Sick Leave</option>
                        <option value="Emergency">Emergency Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Duration *</label>
                    <select id="leaveDuration" name="duration" required>
                        <option value="Full Day">Full Day</option>
                        <option value="Half Day">Half Day</option>
                        <option value="Multiple Days">Multiple Days</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <textarea id="leaveReason" name="reason" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('leaveModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loan Request Modal (for ESS) -->
<div id="loanModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Apply for Loan</h3>
            <span class="close" onclick="closeModal('loanModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="loanForm">
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" id="loanAmount" name="amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <textarea id="loanReason" name="reason" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('loanModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 id="confirmTitle">Confirm Action</h3>
            <span class="close" onclick="closeModal('confirmModal')">&times;</span>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
            <button type="button" class="btn-danger" id="confirmBtn">Confirm</button>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    margin: 3% auto;
    border-radius: var(--border-radius-lg);
    width: 90%;
    max-width: 650px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-light);
    overflow: hidden;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-sm {
    max-width: 450px;
}

.modal-large {
    max-width: 900px;
}

.modal-header {
    padding: 1.75rem 2rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-color), var(--accent-light));
}

.modal-header h3 {
    margin: 0;
    color: white;
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.3px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.close {
    color: rgba(255, 255, 255, 0.8);
    font-size: 32px;
    font-weight: 300;
    cursor: pointer;
    transition: var(--transition);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.close:hover {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.modal-body {
    padding: 2rem;
    max-height: calc(100vh - 300px);
    overflow-y: auto;
}

.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: var(--bg-light);
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 10px;
    transition: var(--transition-fast);
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: var(--text-light);
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 2px solid var(--border-light);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    background: var(--bg-light);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.6rem;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.9rem;
}

.form-group label::after {
    content: ' *';
    color: var(--accent-color);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.9rem 1.1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    font-size: 0.9rem;
    transition: var(--transition);
    background: var(--bg-white);
    color: var(--text-dark);
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(30, 1, 120, 0.08);
    transform: translateY(-1px);
}

.form-group input:hover,
.form-group select:hover,
.form-group textarea:hover {
    border-color: var(--text-light);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.btn-primary,
.btn-secondary,
.btn-danger {
    padding: 0.85rem 1.75rem;
    border: none;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent-color), var(--accent-light));
    color: white;
    box-shadow: 0 4px 12px rgba(219, 38, 31, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(219, 38, 31, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-secondary {
    background: var(--bg-white);
    color: var(--text-dark);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-light);
    border-color: var(--primary-color);
    color: var(--primary-color);
    transform: translateY(-2px);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger-color), var(--accent-light));
    color: white;
    box-shadow: 0 4px 12px rgba(219, 38, 31, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(219, 38, 31, 0.4);
}

.text-center {
    text-align: center;
}

.text-danger {
    color: var(--danger-color);
}

/* Checkbox Styling */
.form-group input[type="checkbox"] {
    width: auto;
    margin-right: 0.5rem;
    cursor: pointer;
}

/* Responsive Design for Modals */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 5% auto;
        width: 95%;
    }
    
    .modal-header {
        padding: 1.25rem 1.5rem;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1.25rem 1.5rem;
        flex-direction: column;
    }
    
    .modal-footer button {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .modal-header h3 {
        font-size: 1.1rem;
    }
    
    .modal-content {
        margin: 2% auto;
    }
}
</style>
