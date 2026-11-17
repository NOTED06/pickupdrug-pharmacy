<?php
require_once('../initialize.php');
require_once('../classes/MedicationReminderManager.php');

if(!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 2) {
    redirect('index.php');
}

$customer_id = $_SESSION['userdata']['id'];
$reminderManager = new MedicationReminderManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = [];
        
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'customer_id' => $customer_id,
                    'medicine_name' => $_POST['medicine_name'],
                    'dosage' => $_POST['dosage'],
                    'frequency' => $_POST['frequency'],
                    'reminder_times' => $_POST['reminder_times'], 
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'] ?? null,
                    'instructions' => $_POST['instructions'] ?? null,
                    'notify_customer' => isset($_POST['notify_customer']) ? 1 : 0
                ];
                $response = $reminderManager->createReminder($data);
                break;
                
            case 'get_reminder':
                $reminder_id = $_POST['reminder_id'];
                $reminder = $reminderManager->getReminder($reminder_id);
                if ($reminder && $reminder['customer_id'] == $customer_id) {
                    $response = ['success' => true, 'data' => $reminder];
                } else {
                    $response = ['success' => false, 'error' => 'Reminder not found'];
                }
                break;
                
            case 'update':
                $reminder_id = $_POST['reminder_id'];
                
                $reminder = $reminderManager->getReminder($reminder_id);
                if (!$reminder || $reminder['customer_id'] != $customer_id) {
                    $response = ['success' => false, 'error' => 'Unauthorized'];
                    break;
                }
                
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
                
                $response = $reminderManager->updateReminder($reminder_id, $data, true);
                break;
                
            case 'pause':
                $response = $reminderManager->pauseReminder($_POST['reminder_id']);
                break;
                
            case 'resume':
                $response = $reminderManager->resumeReminder($_POST['reminder_id']);
                break;
                
            case 'complete':
                $response = $reminderManager->completeReminder($_POST['reminder_id']);
                break;
                
            case 'delete':
                $response = $reminderManager->deleteReminder($_POST['reminder_id']);
                break;
        }
        
        echo json_encode($response);
        exit;
    }
}

$active_reminders = $reminderManager->getCustomerReminders($customer_id, 'active');
$paused_reminders = $reminderManager->getCustomerReminders($customer_id, 'paused');
$completed_reminders = $reminderManager->getCustomerReminders($customer_id, 'completed');
$stats = $reminderManager->getStatistics($customer_id);
$logs = $reminderManager->getReminderLogs($customer_id, 20);

$page_title = "Medication Reminders";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - PickupDrug</title>
    <?php include('../inc/packages.php'); ?>
    <link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/reminders.css">
    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">
</head>
<body>
    
    <div class="container-fluid py-4">
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3 class="mb-0"><?= $stats['active_reminders'] ?></h3>
                        <small>Active Reminders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h3 class="mb-0"><?= $stats['sent_today'] ?></h3>
                        <small>Reminders Sent Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h3 class="mb-0"><?= $stats['sent_this_week'] ?></h3>
                        <small>Sent This Week</small>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="row mb-3">
            <div class="col-12">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addReminderModal">
                    <i class="fas fa-plus"></i> Add New Reminder
                </button>
            </div>
        </div>
        
        
        <div class="row">
            <div class="col-12">
                <h4>Active Reminders</h4>
                <?php if (empty($active_reminders)): ?>
                    <div class="alert alert-info">
                        No active medication reminders. Click "Add New Reminder" to create one.
                    </div>
                <?php else: ?>
                    <?php foreach ($active_reminders as $reminder): ?>
                        <div class="card reminder-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="mb-1"><?= htmlspecialchars($reminder['medicine_name']) ?></h5>
                                        <p class="mb-2">
                                            <strong>Dosage:</strong> <?= htmlspecialchars($reminder['dosage']) ?><br>
                                            <strong>Frequency:</strong> <?= ucwords(str_replace('_', ' ', $reminder['frequency'])) ?><br>
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
                                        <?php if ($reminder['instructions']): ?>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($reminder['instructions']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button class="btn btn-sm btn-primary mb-1" data-action="edit-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <br>
                                        <button class="btn btn-sm btn-warning" data-action="pause-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                            <i class="fas fa-pause"></i> Pause
                                        </button>
                                        <button class="btn btn-sm btn-success" data-action="complete-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-action="delete-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        
        <?php if (!empty($paused_reminders)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h4>Paused Reminders</h4>
                <?php foreach ($paused_reminders as $reminder): ?>
                    <div class="card reminder-card paused">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-1"><?= htmlspecialchars($reminder['medicine_name']) ?></h5>
                                    <p class="mb-0"><strong>Dosage:</strong> <?= htmlspecialchars($reminder['dosage']) ?></p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-sm btn-primary" data-action="resume-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                        <i class="fas fa-play"></i> Resume
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-action="delete-reminder" data-reminder-id="<?= $reminder['id'] ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        
        <div class="row mt-4">
            <div class="col-12">
                <h4>Recent Reminder History</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Scheduled Time</th>
                                    <th>Sent At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="5" class="text-center">No reminder history yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['medicine_name']) ?></td>
                                            <td><?= htmlspecialchars($log['dosage']) ?></td>
                                            <td><?= date('M d, Y g:i A', strtotime($log['scheduled_time'])) ?></td>
                                            <td><?= date('M d, Y g:i A', strtotime($log['sent_at'])) ?></td>
                                            <td>
                                                <?php if ($log['status'] === 'sent'): ?>
                                                    <span class="badge badge-success">Sent</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="modal fade" id="addReminderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Medication Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="addReminderForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="medicine_name" required>
                        </div>
                        <div class="form-group">
                            <label>Dosage <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dosage" placeholder="e.g., 500mg, 2 tablets" required>
                        </div>
                        <div class="form-group">
                            <label>Frequency <span class="text-danger">*</span></label>
                            <select class="form-control" name="frequency" id="frequency" required>
                                <option value="once_daily">Once Daily</option>
                                <option value="twice_daily">Twice Daily</option>
                                <option value="three_times_daily">Three Times Daily</option>
                                <option value="four_times_daily">Four Times Daily</option>
                                <option value="every_6_hours">Every 6 Hours</option>
                                <option value="every_8_hours">Every 8 Hours</option>
                                <option value="every_12_hours">Every 12 Hours</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reminder Times <span class="text-danger">*</span></label>
                            <div id="reminderTimes">
                                <input type="time" class="form-control mb-2" name="reminder_times[]" value="09:00" required>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addTimeField()">
                                <i class="fas fa-plus"></i> Add Another Time
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>End Date (Optional)</label>
                                    <input type="date" class="form-control" name="end_date">
                                    <small class="text-muted">Leave blank for ongoing reminders</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Special Instructions</label>
                            <textarea class="form-control" name="instructions" rows="2" placeholder="e.g., Take with food, Avoid alcohol"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_customer" id="notify_customer" checked>
                            <label class="form-check-label" for="notify_customer">
                                Send SMS reminders
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Reminder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    <div class="modal fade" id="editReminderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Medication Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                                    <input type="text" class="form-control" id="edit_dosage" name="dosage" required>
                                </div>
                                <div class="form-group">
                                    <label>Frequency *</label>
                                    <select class="form-control" id="edit_frequency" name="frequency" required>
                                        <option value="once_daily">Once Daily</option>
                                        <option value="twice_daily">Twice Daily</option>
                                        <option value="three_times_daily">Three Times Daily</option>
                                        <option value="four_times_daily">Four Times Daily</option>
                                        <option value="five_times_daily">Five Times Daily</option>
                                        <option value="every_6_hours">Every 6 Hours</option>
                                        <option value="every_8_hours">Every 8 Hours</option>
                                        <option value="every_12_hours">Every 12 Hours</option>
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
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4">⚠️ Interactions</h6>
                                <div class="form-group">
                                    <label>Other Medicines to Avoid</label>
                                    <textarea class="form-control" id="edit_interactions_medicines" name="interactions_medicines" rows="2"></textarea>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_interactions_alcohol" name="interactions_alcohol">
                                    <label class="form-check-label" for="edit_interactions_alcohol">
                                        <strong class="text-danger">❌ Avoid Alcohol</strong>
                                    </label>
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4">🧠 Side Effects</h6>
                                <div class="form-group">
                                    <label>Common Side Effects</label>
                                    <textarea class="form-control" id="edit_side_effects" name="side_effects" rows="2"></textarea>
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4">📋 Instructions</h6>
                                <div class="form-group">
                                    <label>Instructions</label>
                                    <textarea class="form-control" id="edit_instructions" name="instructions" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Missed Dose Instructions</label>
                                    <textarea class="form-control" id="edit_missed_dose_instructions" name="missed_dose_instructions" rows="2"></textarea>
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
    
    <?php include('../inc/customer-footer.php'); ?>
    <script src="<?php echo base_url ?>customer/assets/js/reminders.js"></script>
</body>
</html>
