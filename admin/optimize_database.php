<?php

require_once(__DIR__ . '/../config.php');

if(!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['type']) || $_SESSION['userdata']['type'] != 1) {
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be logged in as an Administrator to run this script.</p>";
    echo "<p><a href='index.php'>Go to Admin Login</a></p>";
    exit;
}

$results = [];

function addIndex($conn, $table, $indexName, $columns, $unique = false) {
    global $results;
    
    
    $tableCheck = $conn->query("SHOW TABLES LIKE '{$table}'");
    if(!$tableCheck || $tableCheck->num_rows == 0) {
        $results[] = "⚠️ Table '{$table}' does not exist - SKIPPED";
        return false;
    }
    
    
    $check = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
    if($check && $check->num_rows > 0) {
        $results[] = "✓ Index '{$indexName}' on '{$table}' already exists - SKIPPED";
        return true;
    }
    
    $uniqueStr = $unique ? 'UNIQUE' : '';
    $sql = "ALTER TABLE `{$table}` ADD {$uniqueStr} INDEX `{$indexName}` ({$columns})";
    
    if($conn->query($sql)) {
        $results[] = "✅ Index '{$indexName}' on '{$table}' created SUCCESSFULLY";
        return true;
    } else {
        $results[] = "⚠️ Skipped index '{$indexName}' on '{$table}': " . $conn->error;
        return false;
    }
}

echo "<h2>Database Optimization Script</h2>";
echo "<p>Adding indexes for optimal performance with large datasets...</p>";
echo "<hr>";

addIndex($conn, 'customer_list', 'idx_customer_name', 'firstname(50), lastname(50)');
addIndex($conn, 'customer_list', 'idx_customer_email', 'email(100)');
addIndex($conn, 'customer_list', 'idx_customer_contact', 'contact(20)');

addIndex($conn, 'product_list', 'idx_product_delete', 'delete_flag');
addIndex($conn, 'product_list', 'idx_product_category', 'category_id');
addIndex($conn, 'product_list', 'idx_product_brand', 'brand(50)');
addIndex($conn, 'product_list', 'idx_product_name', 'name(100)');
addIndex($conn, 'product_list', 'idx_product_search', 'name(50), brand(50)');

addIndex($conn, 'stock_list', 'idx_stock_product', 'product_id');
addIndex($conn, 'stock_list', 'idx_stock_branch', 'branch_id');
addIndex($conn, 'stock_list', 'idx_stock_expiration', 'expiration');
addIndex($conn, 'stock_list', 'idx_stock_product_branch', 'product_id, branch_id');
addIndex($conn, 'stock_list', 'idx_stock_product_exp', 'product_id, expiration');

addIndex($conn, 'order_list', 'idx_order_status', 'status');
addIndex($conn, 'order_list', 'idx_order_customer', 'customer_id');
addIndex($conn, 'order_list', 'idx_order_branch', 'branch_id');
addIndex($conn, 'order_list', 'idx_order_date', 'date_created');

addIndex($conn, 'order_items', 'idx_orderitem_product', 'product_id');
addIndex($conn, 'order_items', 'idx_orderitem_order', 'order_id');

addIndex($conn, 'customer_prescriptions', 'idx_prescription_customer', 'customer_id');
addIndex($conn, 'customer_prescriptions', 'idx_prescription_status', 'status');
addIndex($conn, 'customer_prescriptions', 'idx_prescription_date', 'uploaded_at');

addIndex($conn, 'category_list', 'idx_category_delete', 'delete_flag');

addIndex($conn, 'branch_list', 'idx_branch_status', 'status');

$check_table = $conn->query("SHOW TABLES LIKE 'medication_reminders'");
if($check_table && $check_table->num_rows > 0) {
    addIndex($conn, 'medication_reminders', 'idx_reminder_customer', 'customer_id');
    addIndex($conn, 'medication_reminders', 'idx_reminder_active', 'is_active');
}

echo "<h3>Results:</h3>";
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";
foreach($results as $result) {
    echo $result . "\n";
}
echo "</pre>";

echo "<hr>";
echo "<h3>Optimization Complete!</h3>";
echo "<p><strong>Indexes created:</strong> " . count(array_filter($results, fn($r) => strpos($r, 'SUCCESSFULLY') !== false)) . "</p>";
echo "<p><strong>Already existed:</strong> " . count(array_filter($results, fn($r) => strpos($r, 'SKIPPED') !== false)) . "</p>";
echo "<p><strong>Failed:</strong> " . count(array_filter($results, fn($r) => strpos($r, 'Failed') !== false)) . "</p>";

echo "<br><br>";
echo "<a href='index.php' class='btn btn-primary'>← Back to Admin</a>";
?>
