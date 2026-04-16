<section id="allowances" class="page">
    <div class="payroll-header">
    </div>

    <div class="allowances-grid">
        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Allowance Categories</h3>
                <div class="table-container small-table">
                    <table id="allowanceCategoriesTable" class="payroll-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>STD. RATE</th>
                                <th>TYPE</th>
                                <th>RECURRING</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="allowanceCategoriesBody">
                        </tbody>
                    </table>
                </div>
                
                <div class="add-new-section">
                    <h4>Add New Type</h4>
                    <div class="allowance-form-row">
                        <input type="text" id="allowanceName" placeholder="Name" class="form-control-gray">
                        <select id="allowanceType" class="form-control-gray">
                            <option value="Fixed">Fixed</option>
                            <option value="Percentage">Percentage</option>
                        </select>
                        <input type="number" id="allowanceRate" placeholder="Rate" class="form-control-gray">
                    </div>
                    <div class="allowance-form-row">
                        <input type="text" id="allowanceDesc" placeholder="Description" class="form-control-gray">
                        <button class="btn-dark-purple" onclick="addAllowanceCategory()">Add Category</button>
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
                        <select id="assignEmployeeSelect" class="form-control-large-gray">
                            <option value="">Select Employee...</option>
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Select Allowance Types</label>
                        <div class="multi-select-container" id="allowanceTypesList">
                            <div class="multi-select-header" onclick="toggleMultiSelect('allowance')">
                                <span class="multi-select-placeholder">Select allowance types...</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="multi-select-dropdown" id="allowanceDropdown">
                                <div class="multi-select-search">
                                    <i class="fas fa-search"></i>
                                    <input type="text" placeholder="Search..." id="allowanceSearch" oninput="filterMultiSelect('allowance')">
                                </div>
                                <div class="multi-select-options" id="allowanceOptions">
                                    <!-- Options will be populated by JavaScript -->
                                </div>
                            </div>
                            <div class="multi-select-selected" id="allowanceSelected">
                                <!-- Selected items will appear here -->
                            </div>
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Override Amount</label>
                            <input type="number" id="overrideAmount" class="form-control-gray">
                        </div>
                        <div class="form-group-custom">
                            <label>Effective Date</label>
                            <input type="date" id="effectiveDate" class="form-control-gray">
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <button class="btn-dark-purple" style="flex: 1;" onclick="assignAllowance()">Assign Benefit</button>
                        <button class="btn-secondary" style="flex: 1;" onclick="applyAllowanceToAll()"><i class="fas fa-users"></i> Apply to All</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card breakdown-card">
        <h3>Employee Allowance Breakdown</h3>
        <div class="table-container">
            <table id="allowanceBreakdownTable" class="payroll-table">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>BENEFIT/ALLOWANCE</th>
                        <th>AMOUNT</th>
                        <th>EFFECTIVE DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="allowanceBreakdownBody">
                </tbody>
            </table>
        </div>
    </div>
</section>
