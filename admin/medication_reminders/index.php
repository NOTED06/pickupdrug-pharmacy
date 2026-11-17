<?php

require_once('../config.php');
require_once('../../classes/MedicationReminderManager.php');

$reminderManager = new MedicationReminderManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_reminder') {
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $reminder = $reminderManager->getReminder($reminder_id);
        die(json_encode(['success' => true, 'data' => $reminder]));
    }
    
    if ($_POST['action'] === 'update_reminder') {
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $data = [
            'medicine_name' => $_POST['medicine_name'] ?? '',
            'dosage' => $_POST['dosage'] ?? '',
            'frequency' => $_POST['frequency'] ?? '',
            'reminder_times' => json_decode($_POST['reminder_times'] ?? '[]', true),
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? null,
            'instructions' => $_POST['instructions'] ?? '',
            'timing_food' => $_POST['timing_food'] ?? '',
            'timing_time_of_day' => $_POST['timing_time_of_day'] ?? '',
            'timing_interval' => $_POST['timing_interval'] ?? '',
            'interactions_medicines' => $_POST['interactions_medicines'] ?? '',
            'interactions_alcohol' => isset($_POST['interactions_alcohol']) ? 1 : 0,
            'interactions_supplements' => $_POST['interactions_supplements'] ?? '',
            'side_effects' => $_POST['side_effects'] ?? '',
            'duration_days' => intval($_POST['duration_days'] ?? 0),
            'special_conditions' => $_POST['special_conditions'] ?? '',
            'missed_dose_instructions' => $_POST['missed_dose_instructions'] ?? ''
        ];
        
        
        $result = $reminderManager->updateReminder($reminder_id, $data, true);
        die(json_encode($result));
    }
    
    if ($_POST['action'] === 'pause_reminder') {
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $result = $reminderManager->pauseReminder($reminder_id);
        die(json_encode($result));
    }
    
    if ($_POST['action'] === 'resume_reminder') {
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $result = $reminderManager->resumeReminder($reminder_id);
        die(json_encode($result));
    }
    
    die(json_encode(['success' => false, 'message' => 'Unknown action']));
}

$all_reminders = [];
$query = "SELECT mr.*, c.firstname, c.lastname, c.contact, c.email 
          FROM medication_reminders mr
          LEFT JOIN customer_list c ON mr.customer_id = c.id
          WHERE mr.status != 'cancelled'
          ORDER BY mr.created_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['reminder_times'] = json_decode($row['reminder_times'] ?? '[]', true);
        $all_reminders[] = $row;
    }
}

$page_title = "Medication Reminders Management";
?>
<?php include('../inc/header.php'); ?>
<style>
.reminder-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background: #fff;
}
.reminder-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.time-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
    font-size: 12px;
    margin: 2px;
}
.info-section {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin-top: 10px;
    font-size: 13px;
}
.info-section h6 {
    font-size: 14px;
    margin-bottom: 8px;
    color: #495057;
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-pills"></i> Medication Reminders Management</h1>
                </div>
            </div>
        </div>
    </div>
    
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Medication Reminders</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($all_reminders)): ?>
                                <div class="alert alert-info">No medication reminders found.</div>
                            <?php else: ?>
                                <?php foreach ($all_reminders as $reminder): ?>
                                    <div class="reminder-card">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5>
                                                    <i class="fas fa-pills text-primary"></i>
                                                    <?= htmlspecialchars($reminder['medicine_name']) ?>
                                                    <span class="badge badge-<?= $reminder['status'] === 'active' ? 'success' : ($reminder['status'] === 'paused' ? 'warning' : 'secondary') ?>">
                                                        <?= ucfirst($reminder['status']) ?>
                                                    </span>
                                                </h5>
                                                <p class="mb-2">
                                                    <strong>Customer:</strong> <?= htmlspecialchars(($reminder['firstname'] ?? '') . ' ' . ($reminder['lastname'] ?? '')) ?><br>
                                                    <strong>Dosage:</strong> <?= htmlspecialchars($reminder['dosage']) ?><br>
                                                    <strong>Frequency:</strong> <?= htmlspecialchars($reminder['frequency']) ?><br>
                                                    <strong>Times:</strong>
                                                    <?php foreach ($reminder['reminder_times'] as $time): ?>
                                                        <span class="time-badge"><?= date('g:i A', strtotime($time)) ?></span>
                                                    <?php endforeach; ?>
                                                    <br>
                                                    <strong>Duration:</strong> <?= date('M d, Y', strtotime($reminder['start_date'])) ?>
                                                    <?php if ($reminder['end_date']): ?>
                                                        to <?= date('M d, Y', strtotime($reminder['end_date'])) ?>
                                                    <?php else: ?>
                                                        (Ongoing)
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <?php if ($reminder['instructions'] || $reminder['timing_food'] || $reminder['side_effects']): ?>
                                                    <div class="info-section">
                                                        <?php if ($reminder['instructions']): ?>
                                                            <p><strong>Instructions:</strong> <?= htmlspecialchars($reminder['instructions']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($reminder['timing_food']): ?>
                                                            <p><strong>Food Timing:</strong> <?= ucwords(str_replace('_', ' ', $reminder['timing_food'])) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($reminder['side_effects']): ?>
                                                            <p><strong>Side Effects:</strong> <?= htmlspecialchars($reminder['side_effects']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($reminder['interactions_alcohol']): ?>
                                                            <p><strong class="text-danger">⚠️ Avoid Alcohol</strong></p>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <button class="btn btn-sm btn-primary" onclick="editReminder(<?= $reminder['id'] ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if ($reminder['status'] === 'active'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="pauseReminder(<?= $reminder['id'] ?>)">
                                                        <i class="fas fa-pause"></i> Pause
                                                    </button>
                                                <?php elseif ($reminder['status'] === 'paused'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="resumeReminder(<?= $reminder['id'] ?>)">
                                                        <i class="fas fa-play"></i> Resume
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="editReminderModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Medication Reminder</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="editReminderForm">
                <input type="hidden" id="edit_reminder_id" name="reminder_id">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                            <div class="form-group">
                                <label>Medicine Name *</label>
                                <input type="text" class="form-control" id="edit_medicine_name" name="medicine_name" required>
                            </div>
                            <div class="form-group">
                                <label>Dosage *</label>
                                <input type="text" class="form-control" id="edit_dosage" name="dosage" placeholder="e.g., 1 tablet, 500mg" required>
                            </div>
                            <div class="form-group">
                                <label>Frequency *</label>
                                <select class="form-control" id="edit_frequency" name="frequency" required>
                                    <option value="Once daily">Once daily</option>
                                    <option value="Twice daily">Twice daily</option>
                                    <option value="Three times daily">Three times daily</option>
                                    <option value="Four times daily">Four times daily</option>
                                    <option value="Five times daily">Five times daily</option>
                                    <option value="Every 4 hours">Every 4 hours</option>
                                    <option value="Every 6 hours">Every 6 hours</option>
                                    <option value="Every 8 hours">Every 8 hours</option>
                                    <option value="Every 12 hours">Every 12 hours</option>
                                    <option value="As needed">As needed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Reminder Times *</label>
                                <div id="edit_times_container"></div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addEditTimeInput()">
                                    <i class="fas fa-plus"></i> Add Time
                                </button>
                                <button type="button" class="btn btn-sm btn-info mt-2" onclick="setupMultipleDoses()">
                                    <i class="fas fa-magic"></i> Setup 5 Times/Day
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Start Date *</label>
                                        <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" id="edit_end_date" name="end_date">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Duration (days)</label>
                                <input type="number" class="form-control" id="edit_duration_days" name="duration_days" min="1" max="365">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">⏰ Timing</h6>
                            <div class="form-group">
                                <label>Food Timing</label>
                                <select class="form-control" id="edit_timing_food" name="timing_food">
                                    <option value="">Select...</option>
                                    <option value="before_food">Before Food</option>
                                    <option value="after_food">After Food</option>
                                    <option value="with_food">With Food</option>
                                    <option value="empty_stomach">Empty Stomach</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Time of Day</label>
                                <select class="form-control" id="edit_timing_time_of_day" name="timing_time_of_day">
                                    <option value="">Select...</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                    <option value="night">Night</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fixed Interval</label>
                                <select class="form-control" id="edit_timing_interval" name="timing_interval">
                                    <option value="">Select...</option>
                                    <option value="every_4_hours">Every 4 Hours</option>
                                    <option value="every_6_hours">Every 6 Hours</option>
                                    <option value="every_8_hours">Every 8 Hours</option>
                                    <option value="every_12_hours">Every 12 Hours</option>
                                </select>
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 mt-4">⚠️ Interactions</h6>
                            <div class="form-group">
                                <label>Other Medicines to Avoid</label>
                                <textarea class="form-control" id="edit_interactions_medicines" name="interactions_medicines" rows="2" placeholder="List other medicines that may interact"></textarea>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_interactions_alcohol" name="interactions_alcohol">
                                <label class="form-check-label" for="edit_interactions_alcohol">
                                    <strong class="text-danger">❌ Avoid Alcohol</strong>
                                </label>
                            </div>
                            <div class="form-group mt-2">
                                <label>Supplements/Herbal Meds to Avoid</label>
                                <textarea class="form-control" id="edit_interactions_supplements" name="interactions_supplements" rows="2" placeholder="List supplements or herbal medicines"></textarea>
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 mt-4">🧠 Side Effects</h6>
                            <div class="form-group">
                                <label>Common Side Effects to Watch For</label>
                                <textarea class="form-control" id="edit_side_effects" name="side_effects" rows="3" placeholder="e.g., Drowsiness, Stomach pain, Allergic reaction (rash, swelling)"></textarea>
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 mt-4">🧒🤰 Special Conditions</h6>
                            <div class="form-group">
                                <label>Special Conditions</label>
                                <textarea class="form-control" id="edit_special_conditions" name="special_conditions" rows="2" placeholder="Pregnancy, Kidney problems, Liver problems, Children vs Adults"></textarea>
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 mt-4">📋 Additional Instructions</h6>
                            <div class="form-group">
                                <label>Instructions</label>
                                <textarea class="form-control" id="edit_instructions" name="instructions" rows="2" placeholder="Additional instructions"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Missed Dose Instructions</label>
                                <textarea class="form-control" id="edit_missed_dose_instructions" name="missed_dose_instructions" rows="2" placeholder="What to do if a dose is missed"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editReminder(reminderId) {
    fetch('?page=medication_reminders', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_reminder&reminder_id=' + reminderId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data) {
            const r = data.data;
            $('#edit_reminder_id').val(r.id);
            $('#edit_medicine_name').val(r.medicine_name);
            $('#edit_dosage').val(r.dosage);
            $('#edit_frequency').val(r.frequency);
            $('#edit_start_date').val(r.start_date);
            $('#edit_end_date').val(r.end_date || '');
            $('#edit_duration_days').val(r.duration_days || '');
            $('#edit_instructions').val(r.instructions || '');
            $('#edit_timing_food').val(r.timing_food || '');
            $('#edit_timing_time_of_day').val(r.timing_time_of_day || '');
            $('#edit_timing_interval').val(r.timing_interval || '');
            $('#edit_interactions_medicines').val(r.interactions_medicines || '');
            $('#edit_interactions_alcohol').prop('checked', r.interactions_alcohol == 1);
            $('#edit_interactions_supplements').val(r.interactions_supplements || '');
            $('#edit_side_effects').val(r.side_effects || '');
            $('#edit_special_conditions').val(r.special_conditions || '');
            $('#edit_missed_dose_instructions').val(r.missed_dose_instructions || '');
            
            
            const timesContainer = $('#edit_times_container');
            timesContainer.empty();
            if (r.reminder_times && r.reminder_times.length > 0) {
                r.reminder_times.forEach(time => {
                    const timeStr = time.substring(0, 5); 
                    timesContainer.append(`<div class="input-group mb-2">
                        <input type="time" class="form-control" name="reminder_times[]" value="${timeStr}" required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-danger" onclick="$(this).closest('.input-group').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>`);
                });
            } else {
                addEditTimeInput();
            }
            
            $('#editReminderModal').modal('show');
        }
    });
}

function addEditTimeInput() {
    $('#edit_times_container').append(`<div class="input-group mb-2">
        <input type="time" class="form-control" name="reminder_times[]" value="08:00" required>
        <div class="input-group-append">
            <button type="button" class="btn btn-danger" onclick="$(this).closest('.input-group').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>`);
}

function setupMultipleDoses() {
    
    const times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
    const container = $('#edit_times_container');
    container.empty();
    times.forEach(time => {
        container.append(`<div class="input-group mb-2">
            <input type="time" class="form-control" name="reminder_times[]" value="${time}" required>
            <div class="input-group-append">
                <button type="button" class="btn btn-danger" onclick="$(this).closest('.input-group').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`);
    });
}

$('#editReminderForm').on('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_reminder');
    
    
    const times = [];
    $(this).find('input[name="reminder_times[]"]').each(function() {
        const timeVal = $(this).val();
        if (timeVal) {
            times.push(timeVal + ':00');
        }
    });
    formData.set('reminder_times', JSON.stringify(times));
    
    
    formData.delete('reminder_times[]');
    
    fetch('?page=medication_reminders', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert_toast('Reminder updated successfully! Customer will be notified via SMS and email.', 'success');
            $('#editReminderModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert_toast(data.error || 'Failed to update reminder', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert_toast('An error occurred while updating the reminder', 'error');
    });
});

function pauseReminder(id) {
    if (confirm('Pause this reminder?')) {
        fetch('?page=medication_reminders', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=pause_reminder&reminder_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function resumeReminder(id) {
    fetch('?page=medication_reminders', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=resume_reminder&reminder_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php include('../inc/footer.php'); ?>
