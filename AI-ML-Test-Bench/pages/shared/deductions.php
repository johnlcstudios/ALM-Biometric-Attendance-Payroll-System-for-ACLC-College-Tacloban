<section id="deductions" class="page">
    <div class="payroll-header">
    </div>

    <div class="allowances-grid">
        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Deduction Categories</h3>
                <div class="table-container small-table">
                    <table id="deductionCategoriesTable" class="payroll-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>STD. RATE</th>
                                <th>TYPE</th>
                                <th>RECURRING</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="deductionCategoriesBody">
                        </tbody>
                    </table>
                </div>
                
                <div class="add-new-section">
                    <h4>Add New Type</h4>
                    <div class="allowance-form-row">
                        <input type="text" id="deductionName" placeholder="Name" class="form-control-gray">
                        <select id="deductionType" class="form-control-gray">
                            <option value="Fixed">Fixed</option>
                            <option value="Percentage">Percentage</option>
                        </select>
                        <input type="number" id="deductionRate" placeholder="Rate" class="form-control-gray">
                    </div>
                    <div class="allowance-form-row">
                        <input type="text" id="deductionDesc" placeholder="Description" class="form-control-gray">
                        <button class="btn-dark-purple" onclick="addDeductionCategory()">Add Category</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Assign to Employee</h3>
                <div class="assign-form">
                    <div class="form-group-custom">
                        <label>Select Employee</label>
                        <select id="assignDeductionEmployeeSelect" class="form-control-large-gray">
                            <option value="">Select Employee...</option>
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Deduction Types</label>
                        <div class="selection-box-gray" id="deductionTypesList">
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Override Amount</label>
                            <input type="number" id="deductionOverrideAmount" class="form-control-gray">
                        </div>
                        <div class="form-group-custom">
                            <label>Effective Date</label>
                            <input type="date" id="deductionEffectiveDate" class="form-control-gray">
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <button class="btn-dark-purple" style="flex: 1;" onclick="assignDeduction()">Assign Deduction</button>
                        <button class="btn-secondary" style="flex: 1;" onclick="applyDeductionToAll()"><i class="fas fa-users"></i> Apply to All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card breakdown-card">
        <h3>Employee Deduction Breakdown</h3>
        <div class="table-container">
            <table id="deductionBreakdownTable" class="payroll-table">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>DEDUCTION</th>
                        <th>AMOUNT</th>
                        <th>EFFECTIVE DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="deductionBreakdownBody">
                </tbody>
            </table>
        </div>
    </div>
</section>
