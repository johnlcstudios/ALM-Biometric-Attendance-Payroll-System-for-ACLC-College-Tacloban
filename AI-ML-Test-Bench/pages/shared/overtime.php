<section id="overtime" class="page">
    <div class="page-header" id="overtime-header">
        <!-- Submit button added by JS for employees -->
    </div>
    <div class="table-container">
        <table id="overtimeTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Hours</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="overtimeTableBody">
            </tbody>
        </table>
    </div>

    <!-- Submit Overtime Modal -->
    <div id="overtimeModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Submit Overtime</h2>
                <span class="close" onclick="closeModal('overtimeModal')">&times;</span>
            </div>
            <form id="overtimeForm" onsubmit="submitOvertime(event)">
                <div class="form-group-custom">
                    <label>Date</label>
                    <input type="date" name="ot_date" class="form-control-large-gray" required>
                </div>
                <div class="form-group-custom">
                    <label>Hours</label>
                    <input type="number" name="hours" class="form-control-large-gray" step="0.5" min="0.5" max="12" required placeholder="e.g. 2.5">
                </div>
                <div class="form-group-custom">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control-large-gray" rows="3" required placeholder="Reason for overtime..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Submit Request</button>
            </form>
        </div>
    </div>
</section>
