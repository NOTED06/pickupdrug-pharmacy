<?php

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once('../../config.php');

while (ob_get_level() > 1) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$stmt = $conn->prepare("SELECT customer_id FROM order_list WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
if (!$order_result || $order_result->num_rows == 0) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}
$order_info = $order_result->fetch_assoc();
$customer_id = $order_info['customer_id'];
$stmt->close();

$reminders_table_exists = $conn->query("SHOW TABLES LIKE 'medication_reminders'");
$join_sql = '';
$select_schedule_fields = "NULL as reminder_id, NULL as dosage, NULL as frequency, NULL as times, NULL as duration_days, NULL as instructions, NULL as is_active, NULL as reminder_times, NULL as start_date, NULL as end_date, NULL as status";

if ($reminders_table_exists && $reminders_table_exists->num_rows > 0) {
    $columns = [];
    $columns_qry = $conn->query("SHOW COLUMNS FROM medication_reminders");
    if ($columns_qry) {
        while ($col = $columns_qry->fetch_assoc()) {
            $columns[$col['Field']] = true;
        }
    }

    $has_order_id = isset($columns['order_id']);
    $has_product_id = isset($columns['product_id']);
    $has_created_from_order = isset($columns['created_from_order']);
    $has_times = isset($columns['times']);
    $has_reminder_times = isset($columns['reminder_times']);
    $has_duration_days = isset($columns['duration_days']);
    $has_is_active = isset($columns['is_active']);
    $has_status = isset($columns['status']);
    $has_start_date = isset($columns['start_date']);
    $has_end_date = isset($columns['end_date']);

    
    $customer_id = intval($customer_id);
    $order_id = intval($order_id);
    
    $join_conditions = ["mr.customer_id = {$customer_id}"];
    
    if ($has_order_id && $has_created_from_order) {
        $join_conditions[] = "(mr.order_id = {$order_id} OR mr.created_from_order = {$order_id})";
    } elseif ($has_created_from_order) {
        $join_conditions[] = "mr.created_from_order = {$order_id}";
    } elseif ($has_order_id) {
        $join_conditions[] = "mr.order_id = {$order_id}";
    }
    if ($has_product_id) $join_conditions[] = "mr.product_id = p.id";

    $join_sql = "LEFT JOIN medication_reminders mr ON " . implode(' AND ', $join_conditions);

    $select_schedule_fields = "
        mr.id as reminder_id,
        mr.dosage,
        mr.frequency,
        " . ($has_times ? "mr.times" : ($has_reminder_times ? "mr.reminder_times" : "NULL")) . " as times,
        " . ($has_duration_days ? "mr.duration_days" : "NULL") . " as duration_days,
        mr.instructions,
        " . ($has_is_active ? "mr.is_active" : ($has_status ? "CASE WHEN mr.status = 'active' THEN 1 ELSE 0 END" : "NULL")) . " as is_active,
        " . ($has_reminder_times ? "mr.reminder_times" : ($has_times ? "mr.times" : "NULL")) . " as reminder_times,
        " . ($has_start_date ? "mr.start_date" : "NULL") . " as start_date,
        " . ($has_end_date ? "mr.end_date" : "NULL") . " as end_date,
        " . ($has_status ? "mr.status" : "NULL") . " as status
    ";
}

$query = "
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.brand,
        p.dose,
        oi.quantity,
        oi.price,
        {$select_schedule_fields}
    FROM order_items oi
    INNER JOIN product_list p ON oi.product_id = p.id
    {$join_sql}
    WHERE oi.order_id = ?
    AND p.is_prescription = 1
    ORDER BY p.name
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Database prepare error in get_order_rx_products.php: " . $conn->error);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please contact administrator.']);
    exit;
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    error_log("Database error in get_order_rx_products.php: " . $conn->error . " | Query: " . $query);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please contact administrator.']);
    $stmt->close();
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) {
    
    $times_raw = $row['times'] ?? $row['reminder_times'] ?? null;
    $times_array = null;
    
    if ($times_raw !== null && $times_raw !== '') {
        
        $times_array = json_decode($times_raw, true);
        
        if ($times_array === null && json_last_error() !== JSON_ERROR_NONE) {
            $times_array = explode(',', $times_raw);
        }
    }
    
    
    if ($times_array === null) {
        $times_array = [];
    }
    
    $schedule_times = '';
    if ($times_array) {
        $schedule_times = implode(', ', array_map(function($t) {
            return date('g:i A', strtotime(trim($t)));
        }, $times_array));
    }

    $duration_days = $row['duration_days'];
    if (!$duration_days && $row['start_date'] && $row['end_date']) {
        $start = strtotime($row['start_date']);
        $end = strtotime($row['end_date']);
        if ($start && $end && $end >= $start) {
            $duration_days = ceil(($end - $start) / 86400);
        }
    }

    $is_active = $row['is_active'];
    if ($is_active === null && isset($row['status'])) {
        $is_active = strtolower($row['status']) === 'active' ? 1 : 0;
    }
    
    $products[] = [
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'brand' => $row['brand'],
        'dose' => $row['dose'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'existing_schedule' => !empty($row['reminder_id']),
        'reminder_id' => $row['reminder_id'],
        'dosage' => $row['dosage'],
        'frequency' => $row['frequency'],
        'times' => $times_raw,
        'schedule_times' => $schedule_times,
        'duration_days' => $duration_days,
        'instructions' => $row['instructions'],
        'is_active' => $is_active
    ];
}

$stmt->close();

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode([
    'success' => true,
    'products' => $products
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
