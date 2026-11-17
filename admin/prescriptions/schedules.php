<?php

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    
    require_once('../config.php');
    
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    
    if (!function_exists('validate_csrf') || !validate_csrf()) {
        die(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']));
    }
    
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    try {
        if($_POST['action'] == 'set_medication_schedule') {
            $order_id = intval($_POST['order_id'] ?? 0);
            $product_id = intval($_POST['product_id'] ?? 0);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $medicine_name = trim($_POST['medicine_name'] ?? '');
            $dosage = trim($_POST['dosage'] ?? '');
            $frequency = trim($_POST['frequency'] ?? '');
            $times = $_POST['times'] ?? '[]';
            $duration_days = intval($_POST['duration_days'] ?? 7);
            $instructions = trim($_POST['instructions'] ?? '');
            $reminder_id = intval($_POST['reminder_id'] ?? 0);
            
            if (!$customer_id || !$medicine_name || !$dosage) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                die(json_encode($response));
            }
            if (!$duration_days || $duration_days < 1) $duration_days = 7;
            
            $times_array = json_decode($times, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($times_array) || empty($times_array)) $times_array = ['08:00'];
            $times_str = implode(', ', array_map(function($t) { return date('g:i A', strtotime($t)); }, $times_array));
            
            $customer_info = null;
            $stmt = $conn->prepare("SELECT firstname, lastname, contact, email FROM customer_list WHERE id = ?");
            if ($stmt) { $stmt->bind_param("i", $customer_id); $stmt->execute(); $r = $stmt->get_result(); $customer_info = $r ? $r->fetch_assoc() : null; $stmt->close(); }
            
            $reminderManager = null;
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+{$duration_days} days"));
            try {
                $reminder_file = __DIR__ . '/../../classes/MedicationReminderManager.php';
                if (file_exists($reminder_file)) {
                    require_once($reminder_file);
                    if (class_exists('MedicationReminderManager')) {
                        $reminderManager = new MedicationReminderManager();
                        if ($reminder_id > 0) {
                            $reminderManager->updateReminder($reminder_id, ['medicine_name'=>$medicine_name,'dosage'=>$dosage,'frequency'=>$frequency,'reminder_times'=>$times_array,'start_date'=>$start_date,'end_date'=>$end_date,'instructions'=>$instructions,'status'=>'active']);
                        } else {
                            $exist = $conn->query("SELECT id FROM medication_reminders WHERE customer_id=".(int)$customer_id." AND created_from_order=".(int)$order_id." AND medicine_name='".$conn->real_escape_string($medicine_name)."' AND status IN ('active','paused') LIMIT 1");
                            if ($exist && $exist->num_rows > 0) {
                                $reminderManager->updateReminder((int)$exist->fetch_assoc()['id'], ['medicine_name'=>$medicine_name,'dosage'=>$dosage,'frequency'=>$frequency,'reminder_times'=>$times_array,'start_date'=>$start_date,'end_date'=>$end_date,'instructions'=>$instructions,'status'=>'active']);
                            } else {
                                $reminderManager->createReminder(['customer_id'=>$customer_id,'created_from_order'=>$order_id>0?$order_id:null,'order_id'=>$order_id>0?$order_id:null,'product_id'=>$product_id>0?$product_id:null,'medicine_name'=>$medicine_name,'dosage'=>$dosage,'frequency'=>$frequency,'reminder_times'=>$times_array,'start_date'=>$start_date,'end_date'=>$end_date,'instructions'=>$instructions,'notify_customer'=>1,'skip_immediate_sms'=>1]);
                            }
                        }
                    }
                }
            } catch (Exception $e) { error_log("MedicationReminderManager error: " . $e->getMessage()); }
            
            
            try {
                if ($customer_info && !empty($customer_info['contact'])) {
                    require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($conn);
                    $sms_message = "PickupDrug: Hi {$customer_info['firstname']}! Medication schedule: {$medicine_name} {$dosage} {$times_str} for {$duration_days} days";
                    $smsGateway->sendSMS($customer_info['contact'], $sms_message);
                }
            } catch (Exception $e) { error_log("SMS error: " . $e->getMessage()); }
            
            
            try {
                $ntitle = "Medication Schedule Saved";
                $nmsg = "Your medication schedule for {$medicine_name} ({$dosage}) has been set. Reminder times: {$times_str}. Duration: {$duration_days} days.";
                $oid = $order_id > 0 ? $order_id : 0;
                $nstmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message, order_id, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                if ($nstmt) {
                    $nstmt->bind_param('issi', $customer_id, $ntitle, $nmsg, $oid);
                    $nstmt->execute();
                    $nstmt->close();
                }
            } catch (Exception $e) { error_log("Notification insert error: " . $e->getMessage()); }
            
            
            try {
                if ($customer_info && !empty($customer_info['email']) && $reminderManager) {
                    $cname = trim(($customer_info['firstname'] ?? '') . ' ' . ($customer_info['lastname'] ?? ''));
                    $reminderManager->sendScheduleSavedEmail($customer_info['email'], $cname, $medicine_name, $dosage, $times_str, $duration_days, $instructions);
                }
            } catch (Exception $e) { error_log("Email error: " . $e->getMessage()); }
            
            $response = ['success' => true, 'message' => 'Schedule saved. Customer notified (in-app, SMS, and email).'];
            die(json_encode($response));
        }
        
        if($_POST['action'] == 'delete_schedule') {
            $reminder_id = intval($_POST['reminder_id'] ?? 0);
            
            if ($reminder_id > 0) {
                try {
                    $stmt = $conn->prepare("DELETE FROM medication_reminders WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $reminder_id);
                        $stmt->execute();
                        $response = ['success' => true, 'message' => 'Schedule deleted'];
                        die(json_encode($response));
                    }
                } catch (Exception $e) {
                    error_log("Delete schedule error: " . $e->getMessage());
                }
            }
            
            $response = ['success' => false, 'message' => 'Failed to delete schedule'];
            die(json_encode($response));
        }
        
        
        die(json_encode($response));
        
    } catch (Exception $e) {
        error_log("Prescriptions AJAX error: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        die(json_encode($response));
    }
}

require_once('../config.php');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    redirect('admin/login.php');
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : 0;

$where_conditions = ["p.is_prescription = 1"];
$params = [];
$types = '';

if($status_filter > 0) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 'i';
} else {
    $where_conditions[] = "o.status IN (2, 3)"; 
}

if(!empty($search)) {
    $search_param = "%{$search}%";
    $where_conditions[] = "(o.code LIKE ? OR CONCAT(c.firstname, ' ', c.lastname) LIKE ? OR c.contact LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$query = "
    SELECT 
        o.id as order_id,
        o.code as order_code,
        o.date_created,
        o.status,
        o.customer_id,
        CONCAT(c.firstname, ' ', c.lastname) as customer_name,
        c.contact as customer_contact,
        c.email as customer_email,
        COUNT(oi.product_id) as total_rx_items
    FROM order_list o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN product_list p ON oi.product_id = p.id
    INNER JOIN customer_list c ON o.customer_id = c.id
    {$where_clause}
    GROUP BY o.id
    ORDER BY o.date_created DESC
    LIMIT 100
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $orders_result = $stmt->get_result();
    } else {
        
        error_log("Failed to prepare query in prescriptions/index.php: " . $conn->error);
        $orders_result = $conn->query($query);
    }
} else {
    $orders_result = $conn->query($query);
}
?>

<style>

.rx-schedule-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 20px;
}

.page-header {
    background: white;
    padding: 20px 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f766e;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filters-section {
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 5px;
    display: block;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
}

.orders-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.orders-table table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table thead {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
}

.orders-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
}

.orders-table td {
    padding: 15px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.orders-table tbody tr:hover {
    background: #f0fdfa;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-packed {
    background: #dbeafe;
    color: #1e40af;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

.btn-manage {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-manage:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13,148,136,0.3);
}

.btn-add-reminder {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(16,185,129,0.3);
}

.btn-add-reminder:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.btn-add-reminder i {
    font-size: 16px;
}

.btn-add-reminder-small {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(16,185,129,0.3);
}

.btn-add-reminder-small:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}

.btn-add-reminder-small i {
    font-size: 14px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 15px;
}

.modal-header {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
}

.rx-product-card {
    background: #f0fdfa;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #0d9488;
    margin-bottom: 15px;
}

.rx-product-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.rx-product-name {
    font-size: 16px;
    font-weight: 600;
    color: #0f766e;
}

.schedule-form {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    position: relative;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    display: block;
    margin-bottom: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
}

.time-input-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.time-input {
    flex: 1;
    min-width: 120px;
}

.btn-add-time {
    background: #0d9488;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
}

.existing-schedule {
    background: #fff7ed;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid #f59e0b;
    margin-top: 10px;
}

.existing-schedule-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.btn-delete-schedule {
    background: #ef4444;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
}

.customer-search-results {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin-top: 5px;
    max-height: 300px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    width: 100%;
}

.customer-result-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.customer-result-item:hover {
    background: #f0fdfa;
}

.customer-result-item:last-child {
    border-bottom: none;
}

.customer-result-name {
    font-weight: 600;
    color: #0f766e;
    font-size: 14px;
}

.customer-result-details {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}
</style>

<div class="rx-schedule-page">
    <div class="container-fluid">
        
        
        <div class="page-header">
            <div>
                <h1>
                    <i class="fas fa-pills"></i>
                    RX Medication Schedule Management
                </h1>
                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">
                    Set medication schedules for customer orders with prescription products
                </p>
            </div>
            <div>
                <button type="button" class="btn-add-reminder" onclick="openNewReminderModal()">
                    <i class="fas fa-plus-circle"></i> Add New Reminder
                </button>
            </div>
        </div>
        
        
        <div class="filters-section">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" id="searchInput" placeholder="Order code, customer name, or contact..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        
        <div class="orders-table">
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <div class="table-responsive">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>RX Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong style="color: #0f766e;">
                                        <i class="fas fa-receipt"></i>
                                        <?= htmlspecialchars($order['order_code']) ?>
                                    </strong>
                                </td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>
                                    <?php if (!empty($order['customer_contact'])): ?>
                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($order['customer_contact']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['date_created'])) ?></td>
                                <td>
                                    <span style="background: #fee2e2; color: #991b1b; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-prescription"></i> <?= $order['total_rx_items'] ?> RX
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn-manage btn-open-schedule" 
                                        data-order-id="<?= $order['order_id'] ?>" 
                                        data-customer-name="<?= htmlspecialchars($order['customer_name'], ENT_QUOTES) ?>" 
                                        data-customer-id="<?= $order['customer_id'] ?>">
                                        <i class="fas fa-clock"></i> Manage Schedule
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php 
                
                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
            ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Orders Found</h3>
                    <p>No orders with prescription products matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <h5 class="modal-title" style="margin: 0;">
                        <i class="fas fa-pills"></i> Manage Medication Schedules
                    </h5>
                    <button type="button" class="btn-add-reminder-small" onclick="openNewReminderFromSchedule(currentScheduleCustomerId, currentScheduleCustomerName)" style="margin-right: 40px;">
                        <i class="fas fa-plus-circle"></i> Add New Reminder
                    </button>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" style="position: absolute; right: 15px; top: 15px;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="scheduleModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newReminderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Add New Medication Reminder
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="newReminderForm" onsubmit="saveNewReminder(event)">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Customer *</label>
                        <input type="text" id="customerSearch" class="form-control" placeholder="Search customer by name, email, or contact..." autocomplete="off">
                        <input type="hidden" id="selectedCustomerId" name="customer_id" required>
                        <div id="customerResults" class="customer-search-results" style="display: none;"></div>
                        <small class="text-muted" id="selectedCustomerInfo"></small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-pills"></i> Medicine Name *</label>
                            <input type="text" name="medicine_name" class="form-control" placeholder="e.g., Amlodipine" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-capsules"></i> Dosage *</label>
                            <input type="text" name="dosage" class="form-control" placeholder="e.g., 1 tablet" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-sync"></i> Frequency *</label>
                            <select name="frequency" class="form-control" required>
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily" selected>Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Duration (days) *</label>
                            <input type="number" name="duration_days" class="form-control" min="1" max="365" value="7" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Reminder Times *</label>
                        <div class="time-input-group" id="newReminderTimesContainer">
                            <input type="time" class="form-control time-input" name="times[]" value="08:00" required>
                            <input type="time" class="form-control time-input" name="times[]" value="20:00" required>
                        </div>
                        <button type="button" class="btn-add-time mt-2" onclick="addTimeInputNewReminder()">
                            <i class="fas fa-plus"></i> Add Time
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Instructions</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="Take with food, avoid alcohol, etc."></textarea>
                    </div>
                    
                    <div class="form-group text-right mt-4">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-add-reminder">
                            <i class="fas fa-save"></i> Save Reminder & Notify Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

const CSRF_TOKEN = '<?php echo csrf_token(); ?>';

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    let url = '<?= $_SERVER['PHP_SELF'] ?>?page=prescriptions';
    if (search) url += '&search=' + encodeURIComponent(search);
    window.location.href = url;
}

document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

(function() {
    
    function initLiveSearch() {
        var searchInput = document.getElementById('searchInput');
        var table = document.getElementById('ordersTable');
        
        if (!searchInput || !table) {
            console.log('Live search: elements not found', searchInput, table);
            return;
        }
        
        var rows = table.querySelectorAll('tbody tr');
        console.log('Live search initialized with', rows.length, 'rows');
        
        searchInput.addEventListener('keyup', function(e) {
            
            if (e.key === 'Enter') return;
            
            var searchTerm = this.value.toLowerCase().trim();
            
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = (searchTerm === '' || text.indexOf(searchTerm) > -1) ? '' : 'none';
            });
        });
    }
    
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLiveSearch);
    } else {
        initLiveSearch();
    }
})();

let currentScheduleCustomerId = null;
let currentScheduleCustomerName = null;

function renderScheduleForm(products, orderId, customerName, customerId) {
    
    currentScheduleCustomerId = customerId;
    currentScheduleCustomerName = customerName;
    
    let html = `
        <div class="mb-3">
            <h6><i class="fas fa-user"></i> Customer: <strong>${customerName}</strong></h6>
            <p class="text-muted mb-0">Set medication schedules for RX products in this order</p>
        </div>
        <hr>
    `;
    
    products.forEach(function(product) {
        html += `
            <div class="rx-product-card">
                <div class="rx-product-header">
                    <div>
                        <div class="rx-product-name">
                            <i class="fas fa-prescription-bottle-alt"></i>
                            ${product.product_name}
                            ${product.brand ? `<span style="font-weight: 400; color: #64748b;">(${product.brand})</span>` : ''}
                        </div>
                        <small style="color: #64748b;">
                            ${product.dose ? `<i class="fas fa-capsules"></i> ${product.dose} • ` : ''}
                            <i class="fas fa-shopping-cart"></i> Qty: ${product.quantity}
                        </small>
                    </div>
                </div>
                
                ${product.existing_schedule ? `
                    <div class="existing-schedule">
                        <div class="existing-schedule-header">
                            <strong style="color: #92400e;">
                                <i class="fas fa-clock"></i> Current Schedule
                            </strong>
                            <button type="button" class="btn-delete-schedule" onclick="deleteSchedule(${product.reminder_id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div style="color: #78350f; font-size: 13px;">
                            <strong>Times:</strong> ${product.schedule_times}<br>
                            <strong>Dosage:</strong> ${product.dosage}<br>
                            <strong>Duration:</strong> ${product.duration_days} days
                            ${product.instructions ? `<br><strong>Instructions:</strong> ${product.instructions}` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <div class="schedule-form">
                    <form id="scheduleForm${product.product_id}" onsubmit="saveSchedule(event, ${orderId}, ${product.product_id}, ${customerId}, '${product.product_name.replace(/'/g, "\\'")}', ${product.reminder_id || 0})">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Dosage *</label>
                                <input type="text" name="dosage" placeholder="e.g., 1 tablet" required value="${product.dosage || ''}">
                            </div>
                            <div class="form-group">
                                <label>Frequency *</label>
                                <select name="frequency" required>
                                    <option value="Once daily" ${product.frequency === 'Once daily' ? 'selected' : ''}>Once daily</option>
                                    <option value="Twice daily" ${product.frequency === 'Twice daily' ? 'selected' : ''}>Twice daily</option>
                                    <option value="Three times daily" ${product.frequency === 'Three times daily' ? 'selected' : ''}>Three times daily</option>
                                    <option value="As needed" ${product.frequency === 'As needed' ? 'selected' : ''}>As needed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Reminder Times *</label>
                            <div class="time-input-group" id="timesContainer${product.product_id}">
                                <input type="time" class="form-control time-input" name="times[]" value="08:00" required>
                            </div>
                            <button type="button" class="btn-add-time mt-2" onclick="addTimeInput(${product.product_id})">
                                <i class="fas fa-plus"></i> Add Time
                            </button>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Duration (days) *</label>
                                <input type="number" name="duration_days" min="1" max="365" value="${product.duration_days || '7'}" required>
                            </div>
                            <div class="form-group">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Instructions</label>
                            <textarea name="instructions" rows="2" placeholder="Take with food, avoid alcohol, etc.">${product.instructions || ''}</textarea>
                        </div>
                        
                        <button type="submit" class="btn-manage" style="width: 100%;">
                            <i class="fas fa-save"></i> Save Schedule & Notify Customer
                        </button>
                    </form>
                </div>
            </div>
        `;
    });
    
    
    html += `
        <hr style="margin: 30px 0;">
        <div style="text-align: center; padding: 20px; background: #f0fdfa; border-radius: 10px; border: 2px dashed #0d9488;">
            <p style="margin: 0 0 15px 0; color: #64748b; font-size: 14px;">
                <i class="fas fa-info-circle"></i> Need to add a reminder for a different medication?
            </p>
            <button type="button" class="btn-add-reminder-small" onclick="openNewReminderFromSchedule(${customerId}, '${customerName.replace(/'/g, "\\'")}')">
                <i class="fas fa-plus-circle"></i> Add New Reminder for This Customer
            </button>
        </div>
    `;
    
    document.getElementById('scheduleModalBody').innerHTML = html;
}

function addTimeInput(productId) {
    const container = document.getElementById('timesContainer' + productId);
    const input = document.createElement('input');
    input.type = 'time';
    input.className = 'form-control time-input';
    input.name = 'times[]';
    input.required = true;
    container.appendChild(input);
}

function saveSchedule(event, orderId, productId, customerId, medicineName, reminderId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    
    const times = Array.from(form.querySelectorAll('input[name="times[]"]')).map(input => input.value);
    
    const data = {
        action: 'set_medication_schedule',
        csrf_token: CSRF_TOKEN,
        order_id: orderId,
        product_id: productId,
        customer_id: customerId,
        medicine_name: medicineName,
        reminder_id: reminderId || 0,
        dosage: formData.get('dosage'),
        frequency: formData.get('frequency'),
        times: JSON.stringify(times),
        duration_days: formData.get('duration_days'),
        instructions: formData.get('instructions')
    };
    
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('./prescriptions/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const response = JSON.parse(text);
            if (response.success) {
                alert_toast(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                alert_toast(response.message || 'Failed to save schedule', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule & Notify Customer';
            }
        } catch (e) {
            console.error("JSON parse error:", e);
            console.error("Response text:", text);
            alert_toast('Server response error: ' + text.substring(0, 100), 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule & Notify Customer';
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        alert_toast('Server error: ' + err.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule & Notify Customer';
    });
}

function deleteSchedule(reminderId) {
    if (!confirm('Are you sure you want to delete this medication schedule?')) {
        return;
    }
    
    fetch('./prescriptions/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete_schedule',
            csrf_token: CSRF_TOKEN,
            reminder_id: reminderId
        })
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            alert_toast(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert_toast(response.message || 'Failed to delete schedule', 'error');
        }
    })
    .catch(() => {
        alert_toast('Server error. Please try again.', 'error');
    });
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-open-schedule');
    if (btn) {
        e.preventDefault();
        const orderId = btn.getAttribute('data-order-id');
        const customerName = btn.getAttribute('data-customer-name');
        const customerId = btn.getAttribute('data-customer-id');
        
        
        const modal = document.getElementById('scheduleModal');
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.setAttribute('aria-modal', 'true');
        modal.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        
        
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
        
        
        document.getElementById('scheduleModalBody').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Loading...</p>
            </div>
        `;
        
        
        const apiUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '<?= base_url ?>') + 'admin/prescriptions/get_order_rx_products.php?order_id=' + orderId;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid response from server. Please check console for details.');
                    }
                });
            })
            .then(response => {
                if (response.success) {
                    renderScheduleForm(response.products, orderId, customerName, customerId);
                } else {
                    document.getElementById('scheduleModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${response.message || 'Failed to load products'}
                        </div>
                    `;
                }
            })
            .catch((error) => {
                console.error('Error loading products:', error);
                console.error('API URL:', apiUrl);
                document.getElementById('scheduleModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Failed to load products: ${error.message || 'Network error'}
                        <br><small>Check browser console for more details.</small>
                    </div>
                `;
            });
    }
});

document.addEventListener('click', function(e) {
    if (e.target.matches('[data-dismiss="modal"]') || e.target.closest('[data-dismiss="modal"]') || e.target.classList.contains('modal-backdrop')) {
        closeModal();
    }
});

function closeModal() {
    const modal = document.getElementById('scheduleModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

let customerSearchTimeout = null;

function openNewReminderModal() {
    const modal = document.getElementById('newReminderModal');
    modal.classList.add('show');
    modal.style.display = 'block';
    modal.setAttribute('aria-modal', 'true');
    modal.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    
    
    let backdrop = document.querySelector('.modal-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    } else {
        
        const newBackdrop = document.createElement('div');
        newBackdrop.className = 'modal-backdrop fade show';
        newBackdrop.style.zIndex = '1040';
        document.body.appendChild(newBackdrop);
    }
    
    
    modal.style.zIndex = '1055';
    
    
    document.getElementById('newReminderForm').reset();
    document.getElementById('selectedCustomerId').value = '';
    document.getElementById('selectedCustomerInfo').textContent = '';
    document.getElementById('customerResults').style.display = 'none';
    
    
    const timesContainer = document.getElementById('newReminderTimesContainer');
    timesContainer.innerHTML = `
        <input type="time" class="form-control time-input" name="times[]" value="08:00" required>
        <input type="time" class="form-control time-input" name="times[]" value="20:00" required>
    `;
}

function openNewReminderFromSchedule(customerId = null, customerName = null) {
    
    if (!customerId && currentScheduleCustomerId) {
        customerId = currentScheduleCustomerId;
        customerName = currentScheduleCustomerName;
    }
    
    
    const scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal && scheduleModal.classList.contains('show')) {
        closeModal();
        
        setTimeout(() => {
            openNewReminderModalWithCustomer(customerId, customerName);
        }, 300);
    } else {
        
        openNewReminderModalWithCustomer(customerId, customerName);
    }
}

function openNewReminderModalWithCustomer(customerId, customerName) {
    
    openNewReminderModal();
    
    
    if (customerId && customerName) {
        document.getElementById('selectedCustomerId').value = customerId;
        document.getElementById('customerSearch').value = customerName;
        document.getElementById('selectedCustomerInfo').innerHTML = `
            <i class="fas fa-check-circle text-success"></i> Selected: ${customerName}
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const customerSearch = document.getElementById('customerSearch');
    if (customerSearch) {
        customerSearch.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                document.getElementById('customerResults').style.display = 'none';
                return;
            }
            
            clearTimeout(customerSearchTimeout);
            customerSearchTimeout = setTimeout(() => {
                searchCustomers(query);
            }, 300);
        });
        
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults')) {
                document.getElementById('customerResults').style.display = 'none';
            }
        });
    }
});

function searchCustomers(query) {
    const apiUrl = (typeof _base_url_ !== 'undefined' ? _base_url_ : '<?= base_url ?>') + 'admin/prescriptions/get_customers.php?q=' + encodeURIComponent(query);
    
    fetch(apiUrl)
        .then(response => response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON:', text);
                return { success: false, customers: [] };
            }
        }))
        .then(data => {
            const resultsDiv = document.getElementById('customerResults');
            if (data.success && data.customers && data.customers.length > 0) {
                let html = '';
                data.customers.forEach(customer => {
                    html += `
                        <div class="customer-result-item" onclick="selectCustomer(${customer.id}, '${customer.name.replace(/'/g, "\\'")}', '${(customer.contact || '').replace(/'/g, "\\'")}', '${(customer.email || '').replace(/'/g, "\\'")}')">
                            <div class="customer-result-name">${customer.name}</div>
                            <div class="customer-result-details">
                                ${customer.contact ? '<i class="fas fa-phone"></i> ' + customer.contact : ''}
                                ${customer.email ? ' <i class="fas fa-envelope"></i> ' + customer.email : ''}
                            </div>
                        </div>
                    `;
                });
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.innerHTML = '<div class="customer-result-item" style="color: #64748b;">No customers found</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Error searching customers:', err);
        });
}

function selectCustomer(id, name, contact, email) {
    document.getElementById('selectedCustomerId').value = id;
    document.getElementById('customerSearch').value = name;
    document.getElementById('selectedCustomerInfo').innerHTML = `
        <i class="fas fa-check-circle text-success"></i> Selected: ${name}
        ${contact ? ' | <i class="fas fa-phone"></i> ' + contact : ''}
        ${email ? ' | <i class="fas fa-envelope"></i> ' + email : ''}
    `;
    document.getElementById('customerResults').style.display = 'none';
}

function addTimeInputNewReminder() {
    const container = document.getElementById('newReminderTimesContainer');
    const input = document.createElement('input');
    input.type = 'time';
    input.className = 'form-control time-input';
    input.name = 'times[]';
    input.required = true;
    container.appendChild(input);
}

function saveNewReminder(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    
    const times = Array.from(form.querySelectorAll('input[name="times[]"]')).map(input => input.value);
    
    if (times.length === 0) {
        alert_toast('Please add at least one reminder time', 'error');
        return;
    }
    
    const data = {
        action: 'create_new_reminder',
        csrf_token: CSRF_TOKEN,
        customer_id: formData.get('customer_id'),
        medicine_name: formData.get('medicine_name'),
        dosage: formData.get('dosage'),
        frequency: formData.get('frequency'),
        times: JSON.stringify(times),
        duration_days: formData.get('duration_days'),
        instructions: formData.get('instructions')
    };
    
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('./prescriptions/ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response from server');
            }
        });
    })
    .then(response => {
        if (response.success) {
            alert_toast(response.message || 'Reminder created successfully!', 'success');
            setTimeout(() => {
                closeNewReminderModal();
                
                location.reload();
            }, 1500);
        } else {
            alert_toast(response.message || 'Failed to create reminder', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Reminder & Notify Customer';
        }
    })
    .catch(err => {
        console.error('Error saving reminder:', err);
        alert_toast('Server error: ' + err.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Reminder & Notify Customer';
    });
}

function closeNewReminderModal() {
    const modal = document.getElementById('newReminderModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    
    
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 0) {
        
        backdrops[backdrops.length - 1].remove();
    }
    
    
    const openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop') || (e.target.matches('[data-dismiss="modal"]') || e.target.closest('[data-dismiss="modal"]'))) {
        const newReminderModal = document.getElementById('newReminderModal');
        if (newReminderModal && newReminderModal.classList.contains('show')) {
            closeNewReminderModal();
        } else {
            closeModal();
        }
    }
});
</script>