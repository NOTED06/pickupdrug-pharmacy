<?php
require_once('../../config.php');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    die('Unauthorized');
}

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("
    SELECT cp.*, 
           CONCAT(c.firstname, ' ', c.lastname) as customer_name,
           c.contact as customer_contact,
           c.email as customer_email,
           c.address as customer_address
    FROM customer_prescriptions cp
    LEFT JOIN customer_list c ON cp.customer_id = c.id
    WHERE cp.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$prescription = $result->fetch_assoc();

if(!$prescription) {
    die('Prescription not found');
}

$prescription_image = $prescription['image_path'];
$customer_id = $prescription['customer_id'];

$reminders_query = "SELECT * FROM medication_reminders 
                    WHERE customer_id = {$customer_id} 
                    AND prescription_image = '{$prescription_image}' 
                    AND status = 'active'
                    ORDER BY created_at DESC";
$reminders_result = $conn->query($reminders_query);
$has_reminders = $reminders_result && $reminders_result->num_rows > 0;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h5>Customer Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="40%">Name:</th>
                    <td><?= $prescription['customer_name'] ?></td>
                </tr>
                <tr>
                    <th>Contact:</th>
                    <td><?= $prescription['customer_contact'] ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?= $prescription['customer_email'] ?></td>
                </tr>
                <tr>
                    <th>Address:</th>
                    <td><?= $prescription['customer_address'] ?></td>
                </tr>
            </table>
            
            <h5 class="mt-3">Prescription Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="40%">Uploaded:</th>
                    <td><?= date('F d, Y h:i A', strtotime($prescription['uploaded_at'])) ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <?php if($prescription['status'] == 'pending'): ?>
                            <span class="badge badge-warning badge-lg">Pending Review</span>
                        <?php elseif($prescription['status'] == 'approved'): ?>
                            <span class="badge badge-success badge-lg">Validated</span>
                        <?php else: ?>
                            <span class="badge badge-danger badge-lg">Rejected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if($prescription['approved_at']): ?>
                <tr>
                    <th>Processed:</th>
                    <td><?= date('F d, Y h:i A', strtotime($prescription['approved_at'])) ?></td>
                </tr>
                <?php endif; ?>
                <?php if(!empty($prescription['expires_at'])): ?>
                <tr>
                    <th>Valid Until:</th>
                    <td>
                        <?= date('F d, Y', strtotime($prescription['expires_at'])) ?>
                        <?php if($prescription['expires_at'] < date('Y-m-d')): ?>
                            <span class="badge badge-danger ml-1">Expired</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if($prescription['rejected_reason']): ?>
                <tr>
                    <th>Rejection Reason:</th>
                    <td><span class="text-danger"><?= $prescription['rejected_reason'] ?></span></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Notes:</th>
                    <td><?= $prescription['notes'] ? nl2br($prescription['notes']) : '<em class="text-muted">No notes provided</em>' ?></td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h5>Prescription Image</h5>
            <div class="card">
                <div class="card-body text-center p-2">
                    <img src="<?= base_url . $prescription['image_path'] ?>" 
                         style="max-width: 100%; max-height: 500px; border-radius: 5px;">
                </div>
                <div class="card-footer text-center">
                    <a href="<?= base_url . $prescription['image_path'] ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Open Full Size
                    </a>
                </div>
            </div>
            
            
            <?php if($has_reminders): ?>
            <div class="mt-4">
                <h5 class="border-bottom pb-2">
                    <i class="fas fa-pills text-success"></i> Medication Schedule 
                    <span class="badge badge-success">Active Reminders</span>
                </h5>
                <div class="medication-schedule">
                    <?php while($reminder = $reminders_result->fetch_assoc()): 
                        $times = json_decode($reminder['reminder_times'], true);
                        $times_display = array_map(function($t) { return date('g:i A', strtotime($t)); }, $times);
                    ?>
                    <div class="medication-card mb-3 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-2">
                                    <i class="fas fa-prescription-bottle text-primary"></i>
                                    <strong><?= htmlspecialchars($reminder['medicine_name']) ?></strong>
                                    <?php if(!empty($reminder['dosage'])): ?>
                                        <span class="badge badge-info"><?= htmlspecialchars($reminder['dosage']) ?></span>
                                    <?php endif; ?>
                                </h6>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-clock"></i> 
                                    <strong>Times:</strong> <?= implode(', ', $times_display) ?>
                                </p>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <strong>Duration:</strong> 
                                    <?= date('M d, Y', strtotime($reminder['start_date'])) ?> 
                                    to 
                                    <?= $reminder['end_date'] ? date('M d, Y', strtotime($reminder['end_date'])) : 'Ongoing' ?>
                                </p>
                                <?php if(!empty($reminder['frequency'])): ?>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-redo"></i> 
                                    <strong>Frequency:</strong> <?= htmlspecialchars($reminder['frequency']) ?>
                                </p>
                                <?php endif; ?>
                                <?php if(!empty($reminder['instructions'])): ?>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Instructions:</strong> <?= htmlspecialchars($reminder['instructions']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-success badge-lg">
                                    <i class="fas fa-bell"></i> Active
                                </span>
                                <p class="small text-muted mt-2 mb-0">
                                    Created: <?= date('M d, Y', strtotime($reminder['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($prescription['status'] == 'pending'): ?>
            <div class="mt-3">
                <form method="POST" action="index.php?page=prescriptions">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="prescription_id" value="<?= $prescription['id'] ?>">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check"></i> Approve Prescription
                    </button>
                </form>
                <button class="btn btn-danger btn-block mt-2" onclick="$('#rejectModal').modal('show')">
                    <i class="fas fa-times"></i> Reject Prescription
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=prescriptions">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Reject Prescription</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="prescription_id" value="<?= $prescription['id'] ?>">
                    
                    <div class="form-group">
                        <label>Rejection Reason *</label>
                        <select class="form-control" name="reject_reason" required>
                            <option value="">Select reason...</option>
                            <option value="Blurry or unreadable">Blurry or unreadable</option>
                            <option value="Expired prescription">Expired prescription</option>
                            <option value="Missing doctor signature">Missing doctor signature</option>
                            <option value="Missing patient details">Missing patient details</option>
                            <option value="Not a valid prescription">Not a valid prescription</option>
                            <option value="Incomplete information">Incomplete information</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.badge-lg {
    font-size: 14px;
    padding: 8px 12px;
}

.medication-schedule {
    max-height: 500px;
    overflow-y: auto;
}

.medication-card {
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid var(--primary-color, #0d9488) !important;
}

.medication-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.medication-card h6 {
    color: #2c3e50;
}

.medication-card .badge-info {
    background-color: var(--primary-color, #0d9488);
    font-size: 0.85rem;
}

.medication-card .badge-success {
    background-color: #28a745;
}
</style>
