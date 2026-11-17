<?php

require_once('../config.php');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    redirect('admin/login.php');
}

if(isset($_POST['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if($_POST['action'] == 'set_medication_schedule') {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $customer_id = intval($_POST['customer_id']);
        $medicine_name = $_POST['medicine_name'];
        $dosage = $_POST['dosage'];
        $frequency = $_POST['frequency'];
        $times = $_POST['times']; 
        $duration_days = intval($_POST['duration_days']);
        $instructions = $_POST['instructions'];
        
        require_once(__DIR__ . '/../../classes/MedicationReminderManager.php');
        $reminderManager = new MedicationReminderManager();
        
        
        $times_array = json_decode($times, true);
        if(!$times_array) {
            $times_array = ['08:00'];
        }
        
        $reminderData = [
            'customer_id' => $customer_id,
            'order_id' => $order_id,
            'product_id' => $product_id,
            'medicine_name' => $medicine_name,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'reminder_times' => $times_array,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime("+{$duration_days} days")),
            'instructions' => $instructions,
            'notify_customer' => 1
        ];
        
        $result = $reminderManager->createReminder($reminderData);
        
        if($result['success']) {
            
            $customer_info = $conn->query("SELECT firstname, lastname, email, contact FROM customer_list WHERE id = {$customer_id}")->fetch_assoc();
            
            
            if(!empty($customer_info['contact'])) {
                require_once(__DIR__ . '/../../classes/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($conn);
                
                $times_str = implode(', ', array_map(function($t) {
                    return date('g:i A', strtotime($t));
                }, $times_array));
                
                $sms_message = "PickupDrug: Hi {$customer_info['firstname']}! Medication schedule set for {$medicine_name}.\n";
                $sms_message .= "Dosage: {$dosage}\n";
                $sms_message .= "Times: {$times_str}\n";
                $sms_message .= "Duration: {$duration_days} days\n";
                $sms_message .= "You'll receive reminders before each dose. Stay healthy!";
                
                $smsGateway->sendSMS($customer_info['contact'], $sms_message);
            }
            
            
            if(!empty($customer_info['email'])) {
                require_once(__DIR__ . '/../../classes/EmailVerification.php');
                $emailService = new EmailVerification();
                $customer_name = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
                
                $emailBody = "
                    <h2>Medication Schedule Set</h2>
                    <p>Hello {$customer_name},</p>
                    <p>Your medication schedule has been configured:</p>
                    <ul>
                        <li><strong>Medication:</strong> {$medicine_name}</li>
                        <li><strong>Dosage:</strong> {$dosage}</li>
                        <li><strong>Times:</strong> " . implode(', ', array_map(function($t) { return date('g:i A', strtotime($t)); }, $times_array)) . "</li>
                        <li><strong>Duration:</strong> {$duration_days} days</li>
                    </ul>
                    " . (!empty($instructions) ? "<p><strong>Instructions:</strong> {$instructions}</p>" : "") . "
                    <p>You will receive SMS reminders before each scheduled dose.</p>
                    <p>Stay healthy!</p>
                ";
                
                $emailService->sendNotificationEmail($customer_info['email'], $customer_name, 'Medication Schedule Set', $emailBody);
            }
            
            echo json_encode(['success' => true, 'message' => 'Medication schedule set successfully. Notifications sent to customer.']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to create medication schedule.']);
        }
        exit;
    }
    
    if($_POST['action'] == 'delete_schedule') {
        $reminder_id = intval($_POST['reminder_id']);
        $stmt = $conn->prepare("DELETE FROM medication_reminders WHERE id = ?");
        $stmt->bind_param("i", $reminder_id);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete schedule.']);
        }
        exit;
    }
}

$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';

$where_conditions = ["p.is_prescription = 1"];
$where_conditions[] = "o.status IN (2, 3)"; 

if(!empty($search)) {
    $where_conditions[] = "(o.code LIKE '%{$search}%' OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%{$search}%' OR c.contact LIKE '%{$search}%')";
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

$orders_result = $conn->query($query);
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state i {
    font-size: 60px;
    margin-bottom: 15px;
}

#scheduleModal .modal-header {
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

.schedule-form .form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    display: block;
    margin-bottom: 5px;
}

.schedule-form .form-group input,
.schedule-form .form-group select,
.schedule-form .form-group textarea {
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
        </div>
        
        
        <div class="filters-section">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" id="searchInput" placeholder="Order code, customer name, or contact..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        
        <div class="orders-table">
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <table>
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

<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-pills"></i> Manage Medication Schedules
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
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

<script>

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
        var table = document.querySelector('.orders-table table');
        
        if (!searchInput || !table) return;
        
        var rows = table.querySelectorAll('tbody tr');
        
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

function renderScheduleForm(products, orderId, customerName, customerId) {
    let html = `
        <div class="mb-3">
            <h6><i class="fas fa-user"></i> Customer: <strong>${customerName}</strong></h6>
            <p class="text-muted mb-0">Set medication schedules for RX products in this order</p>
        </div>
        <hr>
    `;
    
    products.forEach(function(product) {
        const safeName = product.product_name.replace(/'/g, "\\'").replace(/"/g, '\\"');
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
                    <form id="scheduleForm${product.product_id}" onsubmit="saveSchedule(event, ${orderId}, ${product.product_id}, ${customerId}, '${safeName}')">
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
                            <div class="form-group"></div>
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

function saveSchedule(event, orderId, productId, customerId, medicineName) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    
    const times = Array.from(form.querySelectorAll('input[name="times[]"]')).map(input => input.value);
    
    const data = {
        action: 'set_medication_schedule',
        order_id: orderId,
        product_id: productId,
        customer_id: customerId,
        medicine_name: medicineName,
        dosage: formData.get('dosage'),
        frequency: formData.get('frequency'),
        times: JSON.stringify(times),
        duration_days: formData.get('duration_days'),
        instructions: formData.get('instructions')
    };
    
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            alert_toast(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert_toast(response.message || 'Failed to save schedule', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule & Notify Customer';
        }
    })
    .catch(() => {
        alert_toast('Server error. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule & Notify Customer';
    });
}

function deleteSchedule(reminderId) {
    if (!confirm('Are you sure you want to delete this medication schedule?')) {
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete_schedule',
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

function openScheduleModal(orderId, customerName, customerId) {
    
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

document.addEventListener('click', function(e) {
    
    const btn = e.target.closest('.btn-open-schedule');
    if (btn) {
        e.preventDefault();
        const orderId = btn.getAttribute('data-order-id');
        const customerName = btn.getAttribute('data-customer-name');
        const customerId = btn.getAttribute('data-customer-id');
        openScheduleModal(orderId, customerName, customerId);
    }
    
    
    if (e.target.matches('[data-dismiss="modal"]') || e.target.closest('[data-dismiss="modal"]') || e.target.classList.contains('modal-backdrop')) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
