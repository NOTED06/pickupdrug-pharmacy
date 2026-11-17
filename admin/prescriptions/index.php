<?php
require_once('../config.php');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    redirect('admin/login.php');
}

if(isset($_POST['action'])) {
    if($_POST['action'] == 'approve') {
        $id = intval($_POST['prescription_id']);
        $expires_at = $_POST['expires_at'] ?? '';
        $expiry = DateTime::createFromFormat('Y-m-d', $expires_at);
        if (!$expiry || $expiry->format('Y-m-d') !== $expires_at || $expires_at < date('Y-m-d')) {
            $_settings->set_flashdata('error', 'Enter a valid prescription expiry date that is today or later.');
            redirect('admin/?page=prescriptions');
            exit;
        }
        
        
        $medication_schedule = null;
        if(isset($_POST['medications']) && is_array($_POST['medications'])) {
            $medication_schedule = json_encode($_POST['medications']);
        }
        
        
        $stmt = $conn->prepare("UPDATE customer_prescriptions SET status = 'approved', approved_at = NOW(), expires_at = ?, ai_medication_schedule = ? WHERE id = ?");
        $stmt->bind_param("ssi", $expires_at, $medication_schedule, $id);
        
        if($stmt->execute()) {
            
            $rx_info = $conn->query("SELECT cp.customer_id, cp.image_path, c.firstname, c.lastname, c.email, c.contact 
                                     FROM customer_prescriptions cp 
                                     LEFT JOIN customer_list c ON cp.customer_id = c.id 
                                     WHERE cp.id = {$id}")->fetch_assoc();
            $customer_id = $rx_info['customer_id'];
            $customer_name = $rx_info['firstname'] . ' ' . $rx_info['lastname'];
            $customer_email = $rx_info['email'];
            $customer_contact = $rx_info['contact'];
            $rx_image = $rx_info['image_path'];
            
            
            if(isset($_POST['medications']) && is_array($_POST['medications'])) {
                require_once(__DIR__ . '/../../classes/MedicationReminderManager.php');
                $reminderManager = new MedicationReminderManager();
                
                $reminders_created = 0;
                foreach($_POST['medications'] as $med) {
                    if(!empty($med['medicine_name']) && isset($med['create_reminder']) && $med['create_reminder'] == '1') {
                        
                        $times = [];
                        if(!empty($med['times'])) {
                            $times = array_filter(explode(',', $med['times']));
                        }
                        if(empty($times)) {
                            $times = ['08:00']; 
                        }
                        
                        
                        $start_date = date('Y-m-d');
                        $duration_days = intval($med['duration_days'] ?? 7);
                        $end_date = date('Y-m-d', strtotime("+{$duration_days} days"));
                        
                        $reminderData = [
                            'customer_id' => $customer_id,
                            'medicine_name' => $med['medicine_name'],
                            'dosage' => $med['dosage'] ?? '',
                            'frequency' => $med['frequency'] ?? 'As directed',
                            'reminder_times' => $times,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'instructions' => $med['instructions'] ?? '',
                            'prescription_image' => $rx_image,
                            'notify_customer' => 1
                        ];
                        
                        $result = $reminderManager->createReminder($reminderData);
                        if($result['success']) {
                            $reminders_created++;
                        }
                    }
                }
                
                
                if(!empty($customer_contact)) {
                    require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($conn);
                    
                    
                    $schedule_lines = [];
                    foreach($_POST['medications'] as $med) {
                        if(!empty($med['medicine_name']) && isset($med['create_reminder']) && $med['create_reminder'] == '1') {
                            $times_arr = array_filter(explode(',', $med['times'] ?? '08:00'));
                            $times_str = implode(', ', array_map(function($t) {
                                return date('g:i A', strtotime($t));
                            }, $times_arr));
                            $schedule_lines[] = "{$med['medicine_name']} ({$med['dosage']}) - {$times_str} for {$med['duration_days']} days";
                        }
                    }
                    
                    $sms_message = "PickupDrug: Hi {$rx_info['firstname']}! Your prescription has been APPROVED.\n\n";
                    $sms_message .= "MEDICATION SCHEDULE:\n";
                    $sms_message .= implode("\n", $schedule_lines);
                    $sms_message .= "\n\nYou'll receive SMS reminders 10 & 5 min before each dose. Stay healthy!";
                    
                    $smsGateway->sendSMS($customer_contact, $sms_message);
                }
                
                
                if(!empty($customer_email)) {
                    require_once(__DIR__ . '/../../classes/EmailVerification.php');
                    $emailService = new EmailVerification();
                    $emailBody = $emailService->getPrescriptionApprovalTemplate($customer_name, $_POST['medications']);
                    $emailService->sendNotificationEmail($customer_email, $customer_name, 'Your Prescription Has Been Approved!', $emailBody);
                }
                
                if($reminders_created > 0) {
                    $_settings->set_flashdata('success', "Prescription approved! {$reminders_created} medication reminder(s) created. SMS & Email sent to customer.");
                } else {
                    $_settings->set_flashdata('success', 'Prescription approved successfully! Notifications sent.');
                }
            } else {
                
                if(!empty($customer_email)) {
                    require_once(__DIR__ . '/../../classes/EmailVerification.php');
                    $emailService = new EmailVerification();
                    $emailBody = $emailService->getPrescriptionApprovalTemplate($customer_name);
                    $emailService->sendNotificationEmail($customer_email, $customer_name, 'Your Prescription Has Been Approved!', $emailBody);
                }
                
                if(!empty($customer_contact)) {
                    require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($conn);
                    $sms_message = "PickupDrug: Hi {$rx_info['firstname']}! Great news - your prescription has been APPROVED. You can now order your medications online or visit our branch. Thank you!";
                    $smsGateway->sendSMS($customer_contact, $sms_message);
                }
                
                
                try {
                    require_once(__DIR__ . '/../../classes/Master.php');
                    $master = new Master();
                    $master->send_notification(
                        $rx_info['customer_id'],
                        'Prescription Approved ✅',
                        'Great news! Your prescription has been approved. You can now order your medications.',
                        null
                    );
                } catch(Exception $e) {
                    error_log("Failed to send prescription approval notification: " . $e->getMessage());
                }
                
                $_settings->set_flashdata('success', 'Prescription approved successfully! Notifications sent.');
            }
        } else {
            $_settings->set_flashdata('error', 'Failed to approve prescription.');
        }
        $stmt->close();
        redirect('admin/?page=prescriptions');
    } else if($_POST['action'] == 'reject') {
        $id = intval($_POST['prescription_id']);
        $reason = $_POST['reject_reason'];
        $stmt = $conn->prepare("UPDATE customer_prescriptions SET status = 'rejected', rejected_reason = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $reason, $id);
        if($stmt->execute()) {
            
            $rx_info = $conn->query("SELECT cp.customer_id, c.firstname, c.lastname, c.email, c.contact 
                                     FROM customer_prescriptions cp 
                                     LEFT JOIN customer_list c ON cp.customer_id = c.id 
                                     WHERE cp.id = {$id}")->fetch_assoc();
            $customer_name = $rx_info['firstname'] . ' ' . $rx_info['lastname'];
            $customer_email = $rx_info['email'];
            $customer_contact = $rx_info['contact'];
            
            
            if(!empty($customer_email)) {
                require_once(__DIR__ . '/../../classes/EmailVerification.php');
                $emailService = new EmailVerification();
                $emailBody = $emailService->getPrescriptionRejectionTemplate($customer_name, $reason);
                $emailService->sendNotificationEmail($customer_email, $customer_name, 'Prescription Status Update', $emailBody);
            }
            
            
            if(!empty($customer_contact)) {
                require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($conn);
                $sms_message = "PickupDrug: Hi {$rx_info['firstname']}, unfortunately your prescription could not be approved.";
                if(!empty($reason)) {
                    $sms_message .= " Reason: " . substr($reason, 0, 100);
                }
                $sms_message .= " Please upload a new prescription or contact us for help.";
                $smsGateway->sendSMS($customer_contact, $sms_message);
            }
            
            
            try {
                require_once(__DIR__ . '/../../classes/Master.php');
                $master = new Master();
                $notification_msg = 'Your prescription was not approved.';
                if(!empty($reason)) {
                    $notification_msg .= ' Reason: ' . substr($reason, 0, 100);
                }
                $master->send_notification(
                    $rx_info['customer_id'],
                    'Prescription Rejected ❌',
                    $notification_msg,
                    null
                );
            } catch(Exception $e) {
                error_log("Failed to send prescription rejection notification: " . $e->getMessage());
            }
            
            $_settings->set_flashdata('success', 'Prescription rejected. Notifications sent to customer.');
        } else {
            $_settings->set_flashdata('error', 'Failed to reject prescription.');
        }
        $stmt->close();
        redirect('admin/?page=prescriptions');
    }
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;
$offset = ($page_num - 1) * $limit;

$where = "";
$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';

if($status_filter != 'all') {
    $allowed_statuses = ['pending', 'approved', 'rejected', 'processed', 'archived'];
    if(in_array($status_filter, $allowed_statuses)) {
        $where = "WHERE cp.status = '" . $conn->real_escape_string($status_filter) . "'";
    }
}

if(!empty($search)) {
    $search_cond = "(CONCAT(c.firstname, ' ', c.lastname) LIKE '%{$search}%' OR c.contact LIKE '%{$search}%' OR c.email LIKE '%{$search}%' OR cp.id = '{$search}')";
    $where = $where ? "{$where} AND {$search_cond}" : "WHERE {$search_cond}";
}

$count_sql = "SELECT COUNT(*) as total FROM customer_prescriptions cp LEFT JOIN customer_list c ON cp.customer_id = c.id {$where}";
$total_result = $conn->query($count_sql);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$prescriptions = $conn->query("
    SELECT cp.*, 
           CONCAT(c.firstname, ' ', c.lastname) as customer_name,
           c.contact as customer_contact,
           c.email as customer_email
    FROM customer_prescriptions cp
    LEFT JOIN customer_list c ON cp.customer_id = c.id
    {$where}
    ORDER BY cp.uploaded_at DESC
    LIMIT {$limit} OFFSET {$offset}
");
?>

<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<?php if($_settings->chk_flashdata('error')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('error') ?>",'error')</script>
<?php endif;?>

<style>

.prescription-header {
    margin-bottom: 1rem;
}
.prescription-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
}
.prescription-header h1 i {
    color: #0d9488;
}

.small-box {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}
.small-box:hover {
    transform: translateY(-2px);
}
.small-box .inner h3 {
    font-size: 2rem;
    font-weight: 700;
}
.small-box .inner p {
    font-size: 0.9rem;
}
.small-box .icon {
    font-size: 4rem;
    opacity: 0.3;
}
.small-box-footer {
    font-size: 0.85rem;
}

.prescription-table-card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: none;
}
.prescription-table-card .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}
.prescription-table-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

#prescriptionTable {
    font-size: 0.875rem;
}
#prescriptionTable thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    padding: 0.6rem 0.5rem;
    white-space: nowrap;
}
#prescriptionTable tbody td {
    vertical-align: middle;
    padding: 0.5rem;
}
#prescriptionTable tbody tr:hover {
    background-color: #f1f8f7;
}

.rx-thumbnail {
    width: 70px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.rx-thumbnail:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.badge-status {
    font-size: 0.7rem;
    padding: 0.35em 0.6em;
    font-weight: 600;
    border-radius: 4px;
}

.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
.btn-action i {
    margin-right: 2px;
}

.customer-cell {
    line-height: 1.3;
}
.customer-cell .customer-name {
    font-weight: 500;
    color: #333;
}
.customer-cell .customer-contact {
    font-size: 0.8rem;
    color: #666;
}

.notes-cell {
    max-width: 100px;
    font-size: 0.8rem;
    color: #666;
}

.date-cell {
    font-size: 0.8rem;
    white-space: nowrap;
}

#liveSearch {
    transition: box-shadow 0.2s ease;
}
#liveSearch:focus {
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    border-color: #0d9488;
}
#clearSearch {
    border-left: none;
}
.search-highlight {
    background-color: #fff3cd;
    padding: 1px 2px;
    border-radius: 2px;
    font-weight: 600;
}
#searchHint {
    position: absolute;
    right: 0;
    top: 100%;
}
</style>

<div class="prescription-header d-flex justify-content-between align-items-center flex-wrap">
    <h1><i class="fas fa-prescription"></i> Prescription Management</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 bg-transparent p-0">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active">Prescriptions</li>
        </ol>
    </nav>
</div>

<div class="row mb-3">
    <?php
    
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM customer_prescriptions
    ")->fetch_assoc();
    $total = $stats['total'];
    $pending = $stats['pending'];
    $approved = $stats['approved'];
    $rejected = $stats['rejected'];
    ?>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $total ?></h3>
                <p>Total Prescriptions</p>
            </div>
            <div class="icon"><i class="fas fa-file-medical"></i></div>
            <a href="?page=prescriptions&status=all" class="small-box-footer">View ME <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $pending ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="?page=prescriptions&status=pending" class="small-box-footer">Review <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $approved ?></h3>
                <p>Validated</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="?page=prescriptions&status=approved" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $rejected ?></h3>
                <p>Rejected</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <a href="?page=prescriptions&status=rejected" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="card prescription-table-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-1"></i>
            <?= ucfirst($status_filter) ?> Prescriptions
            <span class="badge badge-secondary ml-2" id="total_records_badge"><?= number_format($total_records) ?> records</span>
            <span id="selected_count" class="badge badge-info ml-2" style="display: none;">0 selected</span>
        </h3>
        <div class="card-tools">
            
            <div class="d-inline-block mr-2">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>
                    <input type="text" id="liveSearch" class="form-control border-left-0" placeholder="Type to search..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                    <div class="input-group-append">
                        <button type="button" id="clearSearch" class="btn btn-outline-secondary" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <small id="searchHint" class="text-muted d-block mt-1" style="font-size: 0.7rem;"></small>
            </div>
            <?php if($status_filter == 'pending'): ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table id="prescriptionTable" class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php if($status_filter == 'pending'): ?>
                    <th style="width: 30px;">
                        <input type="checkbox" id="select_all" title="Select All">
                    </th>
                    <?php endif; ?>
                    <th style="width:50px;">ID</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th style="width:80px;">Image</th>
                    <th>Notes</th>
                    <th>Uploaded</th>
                    <th style="width:80px;">Status</th>
                    <th style="width:150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $prescriptions->fetch_assoc()): ?>
                <tr>
                    <?php if($status_filter == 'pending'): ?>
                    <td>
                        <input type="checkbox" class="prescription_checkbox" value="<?= $row['id'] ?>">
                    </td>
                    <?php endif; ?>
                    <td><strong><?= $row['id'] ?></strong></td>
                    <td class="customer-cell">
                        <span class="customer-name"><?= $row['customer_name'] ?></span>
                    </td>
                    <td class="customer-cell">
                        <span class="customer-contact"><?= $row['customer_contact'] ?: $row['customer_email'] ?></span>
                    </td>
                    <td>
                        <img src="<?= base_url . $row['image_path'] ?>" 
                             class="rx-thumbnail"
                             onclick="viewImage('<?= base_url . $row['image_path'] ?>')"
                             alt="Prescription">
                    </td>
                    <td class="notes-cell"><?= $row['notes'] ? htmlspecialchars(substr($row['notes'], 0, 30)) . (strlen($row['notes']) > 30 ? '...' : '') : '<em class="text-muted">No notes</em>' ?></td>
                    <td class="date-cell"><?= date('M d, Y', strtotime($row['uploaded_at'])) ?><br><small class="text-muted"><?= date('h:i A', strtotime($row['uploaded_at'])) ?></small></td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                            <span class="badge badge-warning badge-status">Pending</span>
                        <?php elseif($row['status'] == 'approved' && !empty($row['expires_at']) && $row['expires_at'] < date('Y-m-d')): ?>
                            <span class="badge badge-danger badge-status">Expired</span>
                        <?php elseif($row['status'] == 'approved'): ?>
                            <span class="badge badge-success badge-status">Validated</span>
                        <?php else: ?>
                            <span class="badge badge-danger badge-status">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-info btn-action" onclick="viewPrescription(<?= $row['id'] ?>)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        
                        <?php if($row['status'] == 'pending'): ?>
                        <button class="btn btn-success btn-action" onclick="approvePrescription(<?= $row['id'] ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-danger btn-action" onclick="rejectPrescription(<?= $row['id'] ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        
        
        <?php if($total_pages > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_records)) ?> of <?= number_format($total_records) ?> prescriptions
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if($page_num > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=prescriptions&status=<?= $status_filter ?>&p=1<?= $search ? '&search='.urlencode($search) : '' ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=prescriptions&status=<?= $status_filter ?>&p=<?= $page_num - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">&laquo;</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page_num - 2);
                    $end_page = min($total_pages, $page_num + 2);
                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                        <a class="page-link" href="?page=prescriptions&status=<?= $status_filter ?>&p=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if($page_num < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=prescriptions&status=<?= $status_filter ?>&p=<?= $page_num + 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">&raquo;</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=prescriptions&status=<?= $status_filter ?>&p=<?= $total_pages ?><?= $search ? '&search='.urlencode($search) : '' ?>">Last</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prescription Image</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Reject Prescription</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="prescription_id" id="reject_prescription_id">
                    
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

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" id="approveForm">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white"><i class="fas fa-check-circle"></i> Approve Prescription & Set Medication Reminders</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="prescription_id" id="approve_prescription_id">
                    
                    <div class="row">
                        <div class="col-md-5">
                            <h6><i class="fas fa-file-image"></i> Prescription Image</h6>
                            <img id="approveModalImage" src="" class="img-fluid border rounded mb-2" style="max-height: 400px;">
                            <div class="text-center">
                                <button type="button" class="btn btn-sm btn-info" onclick="extractMedications()">
                                    <i class="fas fa-robot"></i> AI Extract Medications
                                </button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="expires_at"><strong>Prescription Valid Until *</strong></label>
                                <input type="date" class="form-control" id="expires_at" name="expires_at" min="<?= date('Y-m-d') ?>" required>
                                <small class="text-muted">Checkout is blocked after this date.</small>
                            </div>
                            <h6><i class="fas fa-pills"></i> Medications & Reminders</h6>
                            <p class="text-muted small">AI will extract medications automatically. You can edit before approving.</p>
                            
                            <div id="medicationsContainer">
                                <div class="text-center py-4" id="loadingMeds" style="display: none;">
                                    <div class="spinner-border text-primary"></div>
                                    <p class="mt-2">AI is reading the prescription...</p>
                                </div>
                                <div id="medicationsList">
                                    
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
                                        <p>Click "AI Extract Medications" to analyze the prescription</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addManualMedication()">
                                    <i class="fas fa-plus"></i> Add Medication Manually
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve & Create Reminders
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

$(function() {
    var searchTimer;
    var $table = $('#prescriptionTable');
    var $tbody = $table.find('tbody');
    var $rows = $tbody.find('tr');
    var $searchInput = $('#liveSearch');
    var $clearBtn = $('#clearSearch');
    var $searchHint = $('#searchHint');
    var $totalBadge = $('#total_records_badge');
    var originalTotal = <?= $total_records ?>;
    
    
    $searchInput.on('keyup', function() {
        clearTimeout(searchTimer);
        var query = $(this).val().toLowerCase().trim();
        
        
        $clearBtn.toggle(query.length > 0);
        
        if(query.length === 0) {
            
            $rows.show();
            $searchHint.text('');
            $totalBadge.text(originalTotal + ' records');
            return;
        }
        
        
        searchTimer = setTimeout(function() {
            var matchCount = 0;
            
            $rows.each(function() {
                var $row = $(this);
                var rowText = $row.text().toLowerCase();
                
                
                if(rowText.indexOf(query) > -1) {
                    $row.show();
                    matchCount++;
                    
                    
                    highlightMatches($row, query);
                } else {
                    $row.hide();
                }
            });
            
            
            if(matchCount === 0) {
                $searchHint.html('<span class="text-danger">No matches found</span>');
            } else {
                $searchHint.html('<span class="text-success">' + matchCount + ' match' + (matchCount !== 1 ? 'es' : '') + ' found</span>');
            }
            $totalBadge.text(matchCount + ' records');
            
        }, 150); 
    });
    
    
    $clearBtn.on('click', function() {
        $searchInput.val('').trigger('keyup').focus();
    });
    
    
    function highlightMatches($row, query) {
        
        $row.find('.search-highlight').contents().unwrap();
        
        if(query.length < 2) return; 
        
        
        $row.find('td').each(function() {
            var $cell = $(this);
            
            if($cell.find('button, input, img').length > 0) return;
            
            var html = $cell.html();
            var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
            var newHtml = html.replace(regex, '<span class="search-highlight">$1</span>');
            if(newHtml !== html) {
                $cell.html(newHtml);
            }
        });
    }
    
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\
// ========================================
// LIVE SEARCH FUNCTIONALITY
// ========================================
$(function() {
    var searchTimer;
    var $table = $('#prescriptionTable');
    var $tbody = $table.find('tbody');
    var $rows = $tbody.find('tr');
    var $searchInput = $('#liveSearch');
    var $clearBtn = $('#clearSearch');
    var $searchHint = $('#searchHint');
    var $totalBadge = $('#total_records_badge');
    var originalTotal = <?= $total_records ?>;
    
    // Live search on keyup
    $searchInput.on('keyup', function() {
        clearTimeout(searchTimer);
        var query = $(this).val().toLowerCase().trim();
        
        // Show/hide clear button
        $clearBtn.toggle(query.length > 0);
        
        if(query.length === 0) {
            // Show all rows
            $rows.show();
            $searchHint.text('');
            $totalBadge.text(originalTotal + ' records');
            return;
        }
        
        // Debounce for better performance
        searchTimer = setTimeout(function() {
            var matchCount = 0;
            
            $rows.each(function() {
                var $row = $(this);
                var rowText = $row.text().toLowerCase();
                
                // Check if any cell matches
                if(rowText.indexOf(query) > -1) {
                    $row.show();
                    matchCount++;
                    
                    // Highlight matching text (optional)
                    highlightMatches($row, query);
                } else {
                    $row.hide();
                }
            });
            
            // Update search hint
            if(matchCount === 0) {
                $searchHint.html('<span class="text-danger">No matches found</span>');
            } else {
                $searchHint.html('<span class="text-success">' + matchCount + ' match' + (matchCount !== 1 ? 'es' : '') + ' found</span>');
            }
            $totalBadge.text(matchCount + ' records');
            
        }, 150); // 150ms debounce
    });
    
    // Clear search
    $clearBtn.on('click', function() {
        $searchInput.val('').trigger('keyup').focus();
    });
    
    // Highlight matching text in cells
    function highlightMatches($row, query) {
        // Remove previous highlights
        $row.find('.search-highlight').contents().unwrap();
        
        if(query.length < 2) return; // Don't highlight single characters
        
        // Highlight in specific cells (Customer, Contact, Notes)
        $row.find('td').each(function() {
            var $cell = $(this);
            // Skip cells with buttons/checkboxes/images
            if($cell.find('button, input, img').length > 0) return;
            
            var html = $cell.html();
            var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
            var newHtml = html.replace(regex, '<span class="search-highlight">$1</span>');
            if(newHtml !== html) {
                $cell.html(newHtml);
            }
        });
    }
    
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Check for initial search value
    if($searchInput.val().length > 0) {
        $searchInput.trigger('keyup');
    }
});

var currentPrescriptionId = null;
var currentImagePath = null;

function viewImage(src) {
    $('#modalImage').attr('src', src);
    $('#imageModal').modal('show');
}

function viewPrescription(id) {
    uni_modal("View Prescription", "prescriptions/view_prescription.php?id=" + id, 'large');
}

function approvePrescription(id) {
    currentPrescriptionId = id;
    $('#approve_prescription_id').val(id);
    
    // Find the image path from the table
    var row = $('button[onclick="approvePrescription(' + id + ')"]').closest('tr');
    var imgSrc = row.find('img').attr('src');
    currentImagePath = imgSrc;
    
    $('#approveModalImage').attr('src', imgSrc);
    $('#medicationsList').html(`
        <div class="text-center text-muted py-4">
            <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
            <p>Click "AI Extract Medications" to analyze the prescription</p>
        </div>
    `);
    
    $('#approveModal').modal('show');
    
    // Auto-extract when modal opens
    setTimeout(function() {
        extractMedications();
    }, 500);
}

function extractMedications() {
    if(!currentPrescriptionId) return;
    
    $('#loadingMeds').show();
    $('#medicationsList').hide();
    
    $.ajax({
        url: 'prescriptions/extract_medications.php',
        type: 'POST',
        data: { prescription_id: currentPrescriptionId },
        dataType: 'json',
        success: function(response) {
            $('#loadingMeds').hide();
            $('#medicationsList').show();
            
            if(response.success && response.medications && response.medications.length > 0) {
                renderMedications(response.medications);
            } else {
                $('#medicationsList').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ${response.message || 'Could not extract medications. Please add manually.'}
                    </div>
                `);
            }
        },
        error: function() {
            $('#loadingMeds').hide();
            $('#medicationsList').show().html(`
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Error connecting to AI service. Please add medications manually.
                </div>
            `);
        }
    });
}

function renderMedications(medications) {
    var html = '';
    medications.forEach(function(med, index) {
        html += createMedicationCard(med, index);
    });
    $('#medicationsList').html(html);
}

function createMedicationCard(med, index) {
    var timesStr = (med.times || ['08:00']).join(',');
    return `
        <div class="card mb-2 medication-card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" 
                                   id="createReminder${index}" name="medications[${index}][create_reminder]" value="1" checked>
                            <label class="custom-control-label" for="createReminder${index}">
                                <strong>Create Reminder</strong>
                            </label>
                        </div>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][medicine_name]" 
                               value="${escapeHtml(med.medicine_name)}" placeholder="Medicine name" required>
                        <div class="row">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-sm" 
                                       name="medications[${index}][dosage]" 
                                       value="${escapeHtml(med.dosage || '')}" placeholder="Dosage">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="medications[${index}][duration_days]" 
                                       value="${med.duration_days || 7}" placeholder="Days" min="1" max="365">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted">Frequency</label>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][frequency]" 
                               value="${escapeHtml(med.frequency || 'As directed')}" placeholder="Frequency">
                        <label class="small text-muted">Reminder Times (comma-separated)</label>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][times]" 
                               value="${timesStr}" placeholder="08:00,14:00,20:00">
                        <input type="text" class="form-control form-control-sm" 
                               name="medications[${index}][instructions]" 
                               value="${escapeHtml(med.instructions || '')}" placeholder="Instructions">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="$(this).closest('.medication-card').remove()">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
    `;
}

function addManualMedication() {
    var index = $('.medication-card').length;
    var med = {
        medicine_name: '',
        dosage: '',
        frequency: 'Once daily',
        times: ['08:00'],
        duration_days: 7,
        instructions: ''
    };
    $('#medicationsList').append(createMedicationCard(med, index));
}

function escapeHtml(text) {
    if(!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function rejectPrescription(id) {
    $('#reject_prescription_id').val(id);
    $('#rejectModal').modal('show');
}

// ========================================
// BULK OPERATIONS FOR 5+ PRESCRIPTIONS
// ========================================

$(document).ready(function() {
    // Select all checkbox
    $('#select_all').on('change', function() {
        $('.prescription_checkbox').prop('checked', $(this).is(':checked'));
        updateBulkButtons();
    });
    
    // Individual checkbox
    $(document).on('change', '.prescription_checkbox', function() {
        updateBulkButtons();
    });
    
    // Update bulk action buttons visibility
    function updateBulkButtons() {
        const selectedCount = $('.prescription_checkbox:checked').length;
        $('#selected_count').text(selectedCount + ' selected').toggle(selectedCount > 0);
        $('#bulk_ai_analyze').toggle(selectedCount > 0);
    }
    
    // Bulk AI Analyze
    $('#bulk_ai_analyze').on('click', function() {
        const selectedIds = [];
        $('.prescription_checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if(selectedIds.length === 0) {
            alert('Please select prescriptions to analyze');
            return;
        }
        
        if(!confirm(`Analyze ${selectedIds.length} prescriptions with AI? This may take a moment.`)) {
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Analyzing...');
        
        // Process prescriptions one by one
        bulkAIAnalyze(selectedIds, 0);
    });
    
});

// Recursive function to analyze prescriptions one by one
function bulkAIAnalyze(ids, index) {
    if(index >= ids.length) {
        alert('AI analysis complete for ' + ids.length + ' prescriptions!');
        location.reload();
        return;
    }
    
    const prescriptionId = ids[index];
    const progressText = `Analyzing ${index + 1} of ${ids.length}...`;
    $('#bulk_ai_analyze').html('<i class="fas fa-spinner fa-spin"></i> ' + progressText);
    
    // Get prescription image path
    $.ajax({
        url: 'get_prescription_image.php',
        method: 'GET',
        data: { id: prescriptionId },
        dataType: 'json',
        success: function(resp) {
            if(resp.success && resp.image_path) {
                // Call AI analysis
                $.ajax({
                    url: '../Chatbot/GeminiAI.php',
                    method: 'POST',
                    data: {
                        action: 'analyze_prescription',
                        image_path: resp.image_path
                    },
                    dataType: 'json',
                    success: function(aiResp) {
                        if(aiResp.success && aiResp.medications) {
                            // Auto-approve with AI data
                            const formData = new FormData();
                            formData.append('action', 'approve');
                            formData.append('prescription_id', prescriptionId);
                            
                            aiResp.medications.forEach(function(med, idx) {
                                formData.append(`medications[${idx}][medicine_name]`, med.medicine_name);
                                formData.append(`medications[${idx}][dosage]`, med.dosage || '');
                                formData.append(`medications[${idx}][frequency]`, med.frequency || '');
                                formData.append(`medications[${idx}][times]`, med.times || '08:00');
                                formData.append(`medications[${idx}][duration_days]`, med.duration_days || 7);
                                formData.append(`medications[${idx}][instructions]`, med.instructions || '');
                                formData.append(`medications[${idx}][create_reminder]`, '1');
                            });
                            
                            $.ajax({
                                url: '',
                                method: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function() {
                                    // Move to next prescription
                                    bulkAIAnalyze(ids, index + 1);
                                },
                                error: function() {
                                    console.error('Failed to approve prescription ' + prescriptionId);
                                    // Continue with next anyway
                                    bulkAIAnalyze(ids, index + 1);
                                }
                            });
                        } else {
                            // AI failed, skip to next
                            bulkAIAnalyze(ids, index + 1);
                        }
                    },
                    error: function() {
                        // AI failed, skip to next
                        bulkAIAnalyze(ids, index + 1);
                    }
                });
            } else {
                // No image, skip to next
                bulkAIAnalyze(ids, index + 1);
            }
        },
        error: function() {
            // Error getting image, skip to next
            bulkAIAnalyze(ids, index + 1);
        }
    });
}
');
    }
    
    
    if($searchInput.val().length > 0) {
        $searchInput.trigger('keyup');
    }
});

var currentPrescriptionId = null;
var currentImagePath = null;

function viewImage(src) {
    $('#modalImage').attr('src', src);
    $('#imageModal').modal('show');
}

function viewPrescription(id) {
    uni_modal("View Prescription", "prescriptions/view_prescription.php?id=" + id, 'large');
}

function approvePrescription(id) {
    currentPrescriptionId = id;
    $('#approve_prescription_id').val(id);
    
    
    var row = $('button[onclick="approvePrescription(' + id + ')"]').closest('tr');
    var imgSrc = row.find('img').attr('src');
    currentImagePath = imgSrc;
    
    $('#approveModalImage').attr('src', imgSrc);
    $('#medicationsList').html(`
        <div class="text-center text-muted py-4">
            <i class="fas fa-prescription-bottle-alt fa-3x mb-3"></i>
            <p>Click "AI Extract Medications" to analyze the prescription</p>
        </div>
    `);
    
    $('#approveModal').modal('show');
    
    
    setTimeout(function() {
        extractMedications();
    }, 500);
}

function extractMedications() {
    if(!currentPrescriptionId) return;
    
    $('#loadingMeds').show();
    $('#medicationsList').hide();
    
    $.ajax({
        url: 'prescriptions/extract_medications.php',
        type: 'POST',
        data: { prescription_id: currentPrescriptionId },
        dataType: 'json',
        success: function(response) {
            $('#loadingMeds').hide();
            $('#medicationsList').show();
            
            if(response.success && response.medications && response.medications.length > 0) {
                renderMedications(response.medications);
            } else {
                $('#medicationsList').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ${response.message || 'Could not extract medications. Please add manually.'}
                    </div>
                `);
            }
        },
        error: function() {
            $('#loadingMeds').hide();
            $('#medicationsList').show().html(`
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Error connecting to AI service. Please add medications manually.
                </div>
            `);
        }
    });
}

function renderMedications(medications) {
    var html = '';
    medications.forEach(function(med, index) {
        html += createMedicationCard(med, index);
    });
    $('#medicationsList').html(html);
}

function createMedicationCard(med, index) {
    var timesStr = (med.times || ['08:00']).join(',');
    return `
        <div class="card mb-2 medication-card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" 
                                   id="createReminder${index}" name="medications[${index}][create_reminder]" value="1" checked>
                            <label class="custom-control-label" for="createReminder${index}">
                                <strong>Create Reminder</strong>
                            </label>
                        </div>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][medicine_name]" 
                               value="${escapeHtml(med.medicine_name)}" placeholder="Medicine name" required>
                        <div class="row">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-sm" 
                                       name="medications[${index}][dosage]" 
                                       value="${escapeHtml(med.dosage || '')}" placeholder="Dosage">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="medications[${index}][duration_days]" 
                                       value="${med.duration_days || 7}" placeholder="Days" min="1" max="365">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted">Frequency</label>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][frequency]" 
                               value="${escapeHtml(med.frequency || 'As directed')}" placeholder="Frequency">
                        <label class="small text-muted">Reminder Times (comma-separated)</label>
                        <input type="text" class="form-control form-control-sm mb-2" 
                               name="medications[${index}][times]" 
                               value="${timesStr}" placeholder="08:00,14:00,20:00">
                        <input type="text" class="form-control form-control-sm" 
                               name="medications[${index}][instructions]" 
                               value="${escapeHtml(med.instructions || '')}" placeholder="Instructions">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="$(this).closest('.medication-card').remove()">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
    `;
}

function addManualMedication() {
    var index = $('.medication-card').length;
    var med = {
        medicine_name: '',
        dosage: '',
        frequency: 'Once daily',
        times: ['08:00'],
        duration_days: 7,
        instructions: ''
    };
    $('#medicationsList').append(createMedicationCard(med, index));
}

function escapeHtml(text) {
    if(!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function rejectPrescription(id) {
    $('#reject_prescription_id').val(id);
    $('#rejectModal').modal('show');
}

$(document).ready(function() {
    
    $('#select_all').on('change', function() {
        $('.prescription_checkbox').prop('checked', $(this).is(':checked'));
        updateBulkButtons();
    });
    
    
    $(document).on('change', '.prescription_checkbox', function() {
        updateBulkButtons();
    });
    
    
    function updateBulkButtons() {
        const selectedCount = $('.prescription_checkbox:checked').length;
        $('#selected_count').text(selectedCount + ' selected').toggle(selectedCount > 0);
        $('#bulk_ai_analyze').toggle(selectedCount > 0);
    }
    
    
    $('#bulk_ai_analyze').on('click', function() {
        const selectedIds = [];
        $('.prescription_checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if(selectedIds.length === 0) {
            alert('Please select prescriptions to analyze');
            return;
        }
        
        if(!confirm(`Analyze ${selectedIds.length} prescriptions with AI? This may take a moment.`)) {
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Analyzing...');
        
        
        bulkAIAnalyze(selectedIds, 0);
    });
    
});

function bulkAIAnalyze(ids, index) {
    if(index >= ids.length) {
        alert('AI analysis complete for ' + ids.length + ' prescriptions!');
        location.reload();
        return;
    }
    
    const prescriptionId = ids[index];
    const progressText = `Analyzing ${index + 1} of ${ids.length}...`;
    $('#bulk_ai_analyze').html('<i class="fas fa-spinner fa-spin"></i> ' + progressText);
    
    
    $.ajax({
        url: 'get_prescription_image.php',
        method: 'GET',
        data: { id: prescriptionId },
        dataType: 'json',
        success: function(resp) {
            if(resp.success && resp.image_path) {
                
                $.ajax({
                    url: '../Chatbot/GeminiAI.php',
                    method: 'POST',
                    data: {
                        action: 'analyze_prescription',
                        image_path: resp.image_path
                    },
                    dataType: 'json',
                    success: function(aiResp) {
                        if(aiResp.success && aiResp.medications) {
                            
                            const formData = new FormData();
                            formData.append('action', 'approve');
                            formData.append('prescription_id', prescriptionId);
                            
                            aiResp.medications.forEach(function(med, idx) {
                                formData.append(`medications[${idx}][medicine_name]`, med.medicine_name);
                                formData.append(`medications[${idx}][dosage]`, med.dosage || '');
                                formData.append(`medications[${idx}][frequency]`, med.frequency || '');
                                formData.append(`medications[${idx}][times]`, med.times || '08:00');
                                formData.append(`medications[${idx}][duration_days]`, med.duration_days || 7);
                                formData.append(`medications[${idx}][instructions]`, med.instructions || '');
                                formData.append(`medications[${idx}][create_reminder]`, '1');
                            });
                            
                            $.ajax({
                                url: '',
                                method: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function() {
                                    
                                    bulkAIAnalyze(ids, index + 1);
                                },
                                error: function() {
                                    console.error('Failed to approve prescription ' + prescriptionId);
                                    
                                    bulkAIAnalyze(ids, index + 1);
                                }
                            });
                        } else {
                            
                            bulkAIAnalyze(ids, index + 1);
                        }
                    },
                    error: function() {
                        
                        bulkAIAnalyze(ids, index + 1);
                    }
                });
            } else {
                
                bulkAIAnalyze(ids, index + 1);
            }
        },
        error: function() {
            
            bulkAIAnalyze(ids, index + 1);
        }
    });
}
</script>
