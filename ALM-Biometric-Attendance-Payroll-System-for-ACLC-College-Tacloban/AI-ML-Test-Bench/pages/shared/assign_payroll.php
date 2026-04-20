<section id="assign_payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Assign Payroll Officer</h2>
            <p>Grant payroll management access to selected employees.</p>
        </div>
    </div>
    
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3>Set Payroll Officer Role</h3>
        <div class="form-group-custom">
            <label>Select Employee</label>
            <select id="payrollOfficerSelect" class="form-control-large-gray">
                <option value="">Choose Employee...</option>
            </select>
        </div>
        <button class="btn-dark-purple btn-full" onclick="assignPayrollOfficerRole()">Assign Access</button>
        
        <div style="margin-top: 2rem;">
            <h4>Current Officers</h4>
            <ul id="payrollOfficersList" class="selection-box-gray">
            </ul>
        </div>
    </div>
</section>
