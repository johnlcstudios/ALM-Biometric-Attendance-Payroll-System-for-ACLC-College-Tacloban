<section id="generate_dtr" class="page">
    <div class="settings-container">
        <div class="settings-card">
            <h3>Generate Daily Time Record (DTR)</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Employee</label>
                    <select id="dtr-employee" class="form-control-large-gray">
                        <option value="">Select Employee...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" id="dtr-from" class="form-control-large-gray" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" id="dtr-to" class="form-control-large-gray" value="<?php echo date('Y-m-t'); ?>">
                </div>
            </div>
            <button class="btn btn-primary" onclick="generateDTR()">
                <i class="fas fa-file-alt"></i> Generate DTR
            </button>
        </div>

        <div id="dtr-result" style="display:none;" class="settings-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 id="dtr-title">Daily Time Record</h3>
                <button class="btn btn-primary btn-sm" onclick="printDTR()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <div id="dtr-content"></div>
        </div>
    </div>
</section>
