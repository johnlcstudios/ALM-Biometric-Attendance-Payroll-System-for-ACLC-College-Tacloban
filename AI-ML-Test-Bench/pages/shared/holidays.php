<section id="holidays" class="page">
    <div class="page-header">
        <button class="btn btn-primary" onclick="openHolidayModal()">
            <i class="fas fa-plus"></i> Add Holiday
        </button>
    </div>
    <div class="table-container">
        <table id="holidayTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Pay Rate (%)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="holidayTableBody">
            </tbody>
        </table>
    </div>

    <!-- Add Holiday Modal -->
    <div id="holidayModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Add Holiday</h2>
                <span class="close" onclick="closeModal('holidayModal')">&times;</span>
            </div>
            <form id="holidayForm" onsubmit="saveHoliday(event)">
                <div class="form-group-custom">
                    <label>Holiday Name</label>
                    <input type="text" name="name" class="form-control-large-gray" required placeholder="e.g. Independence Day">
                </div>
                <div class="form-group-custom">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control-large-gray" required>
                </div>
                <div class="form-group-custom">
                    <label>Type</label>
                    <select name="type" class="form-control-large-gray" required>
                        <option value="Regular">Regular Holiday</option>
                        <option value="Special">Special Non-Working Day</option>
                        <option value="Non-Working">Non-Working Day</option>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Pay Rate (%)</label>
                    <input type="number" name="pay_rate" class="form-control-large-gray" value="200" min="100" max="500" required>
                    <small class="text-muted">Regular: 200%, Special: 130%</small>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Save Holiday</button>
            </form>
        </div>
    </div>
</section>
