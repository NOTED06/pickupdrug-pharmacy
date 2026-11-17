<?php

ob_start();

require_once('./config.php');
require_once('classes/Master.php'); 

if (ob_get_length()) ob_clean();

$action = isset($_GET['action']) ? $_GET['action'] : '';

$html_actions = array('filter_products');
if(!in_array($action, $html_actions)) {
    header('Content-Type: application/json');
}

$write_actions = array(
    'add_to_cart', 'update_cart_qty', 'delete_cart', 'clear_cart', 
    'delete_selected_cart', 'clear_and_add_to_cart', 'place_order',
    'upload_prescription', 'upload_cart_prescription', 'delete_prescription',
    'customize_prescription', 'mark_notification_read', 'mark_all_notifications_read',
    'delete_notification', 'follow_up_order', 'customer_cancel_order'
);

if(in_array($action, $write_actions) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    if(empty($csrf_token) || empty($session_token) || !hash_equals($session_token, $csrf_token)) {
        echo json_encode(['status' => 'failed', 'msg' => 'Security token expired. Please refresh the page and try again.']);
        ob_end_flush();
        exit;
    }
}

switch($action) {
    case 'chatbot_chat':
        $_GET['f'] = 'chat';
        require_once('Chatbot/chatbot.php');
        break;
    case 'chatbot_search':
        $_GET['f'] = 'search_products';
        require_once('Chatbot/chatbot.php');
        break;
    case 'chatbot_analyze_image':
        $_GET['f'] = 'analyze_image';
        require_once('Chatbot/chatbot.php');
        break;
    case 'get_notifications':
        echo get_notifications();
        break;
    case 'check_pending_payment':
        echo check_and_cancel_expired_payments($conn);
        break;
    case 'mark_notification_read':
        echo mark_notification_read();
        break;
    case 'mark_all_notifications_read':
        echo mark_all_notifications_read();
        break;
    case 'delete_notification':
        echo delete_notification();
        break;
    case 'get_product_details':
        echo get_product_details();
        break;
    case 'filter_products':
        echo filter_products();
        break;
    case 'add_to_cart':
        echo add_to_cart();
        break;
    case 'get_cart_count':
        echo get_cart_count();
        break;
    case 'get_cart_list':
        echo get_cart_list();
        break;
    case 'update_cart_qty':
        echo update_cart_qty();
        break;
    case 'delete_cart':
        echo delete_cart();
        break;
    case 'clear_cart':
        echo clear_cart();
        break;
    case 'delete_selected_cart':
        echo delete_selected_cart();
        break;
    case 'check_branch_availability':
        echo check_branch_availability();
        break;
    case 'clear_and_add_to_cart':
        echo clear_and_add_to_cart();
        break;
    case 'search_products':
        echo search_products();
        break;
    case 'get_all_products_catalog':
        echo get_all_products_catalog();
        break;
    case 'check_branch_stock':
        echo check_branch_stock();
        break;
    case 'get_available_branches':
        echo get_available_branches();
        break;
    case 'check_prescription_requirement':
        echo check_prescription_requirement();
        break;
    case 'check_maintenance_status':
        echo check_maintenance_status();
        break;
    case 'upload_prescription':
        echo upload_prescription();
        break;
    case 'validate_prescription_cart':
        echo validate_prescription_cart();
        break;
    case 'customize_prescription':
        echo customize_prescription();
        break;
    case 'get_prescription_schedule':
        echo get_prescription_schedule();
        break;
    case 'delete_prescription':
        echo delete_prescription();
        ob_end_flush();
        exit;
        break;
    case 'get_cart_summary':
        echo get_cart_summary();
        break;
    case 'upload_cart_prescription':
        echo upload_cart_prescription();
        break;
    case 'place_order':
        $Master = new Master();
        echo $Master->place_order();
        break;
    case 'follow_up_order':
        $Master = new Master();
        echo $Master->follow_up_order();
        break;
    case 'customer_cancel_order':
        $Master = new Master();
        echo $Master->customer_cancel_order();
        break;
    case 'get_products_by_ids':
        echo get_products_by_ids();
        break;
    default:
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid action']);
}

ob_end_flush();

function get_products_by_ids() {
    global $conn;
    
    $resp = array('status' => 'failed');
    
    try {
        if (!isset($_POST['ids']) || empty($_POST['ids'])) {
            $resp['msg'] = 'No product IDs provided';
            return json_encode($resp);
        }
        
        $ids = explode(',', $_POST['ids']);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            $resp['msg'] = 'No valid product IDs';
            return json_encode($resp);
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id, name, price, image_path FROM product_list WHERE id IN ($placeholders) AND status = 1";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = array();
        while ($row = $result->fetch_assoc()) {
            $products[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image' => $row['image_path']
            );
        }
        
        $resp['status'] = 'success';
        $resp['products'] = $products;
        
    } catch (Exception $e) {
        $resp['msg'] = 'Error: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function upload_cart_prescription() {
    global $conn, $_settings;
    
    $resp = array('status' => 'failed', 'msg' => 'Unknown error');
    
    
    error_log('=== CART PRESCRIPTION UPLOAD START ===');
    
    
    if(!isset($_SESSION['userdata']) || empty($_SESSION['userdata']['id'])) {
        $resp['msg'] = 'Please login to upload prescription';
        return json_encode($resp);
    }
    
    $customer_id = $_SESSION['userdata']['id'];
    error_log('Customer ID: ' . $customer_id);
    
    
    if(!isset($_FILES['prescription_file']) || $_FILES['prescription_file']['error'] != 0) {
        $error_code = isset($_FILES['prescription_file']) ? $_FILES['prescription_file']['error'] : 'no file';
        error_log('File upload error: ' . $error_code);
        $resp['msg'] = 'No file uploaded or upload error occurred';
        return json_encode($resp);
    }
    
    $file = $_FILES['prescription_file'];
    error_log('File received: ' . $file['name'] . ' | Size: ' . $file['size']);
    
    
    $maxSize = 5 * 1024 * 1024; 
    if($file['size'] > $maxSize) {
        $resp['msg'] = 'File size must be less than 5MB';
        return json_encode($resp);
    }
    
    
    $allowedTypes = array('image/jpeg', 'image/jpg', 'image/png', 'application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if(!in_array($mimeType, $allowedTypes)) {
        $resp['msg'] = 'Only JPG, PNG, or PDF files are allowed';
        return json_encode($resp);
    }
    
    
    $ai_validation = null;
    $validation_status = 'pending';
    $automatic_expiry = null;
    
    if(in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'])) {
        
        try {
            require_once(__DIR__ . '/Chatbot/GeminiAI.php');
            
            
            $imageData = base64_encode(file_get_contents($file['tmp_name']));
            
            
            $gemini = new GeminiAI($conn, []);
            
            
            error_log('=== STEP 1: Validating if image is a prescription ===');
            $prescription_check = $gemini->validatePrescription($imageData, $mimeType);
            
            error_log('Prescription Validation Result: ' . json_encode($prescription_check));
            
            if($prescription_check['status'] === 'success') {
                if(!$prescription_check['valid']) {
                    
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'This does not appear to be a valid prescription';
                    $resp['validation_status'] = 'invalid';
                    $resp['reason'] = $prescription_check['reason'] ?? 'Please upload a clear photo of a medical prescription.';
                    error_log('REJECTED: Not a valid prescription - ' . ($prescription_check['reason'] ?? 'unknown'));
                    return json_encode($resp);
                }
                
                
                error_log('APPROVED: Valid prescription detected');
                $validation_status = 'ai_valid';
            } else {
                
                error_log('AI Validation API Error - rejecting upload for safety');
                $resp['status'] = 'failed';
                $resp['msg'] = 'Unable to validate prescription. Please try again.';
                $resp['validation_status'] = 'error';
                $resp['reason'] = $prescription_check['reason'] ?? 'AI validation service temporarily unavailable.';
                return json_encode($resp);
            }
            
            
            $cart_rx_query = $conn->query("
                SELECT p.name 
                FROM cart_list c 
                INNER JOIN product_list p ON c.product_id = p.id 
                WHERE c.customer_id = {$customer_id} 
                AND COALESCE(p.is_prescription, 0) = 1
            ");
            
            $cart_rx_products = [];
            if($cart_rx_query && $cart_rx_query->num_rows > 0) {
                while($row = $cart_rx_query->fetch_assoc()) {
                    $cart_rx_products[] = $row['name'];
                }
            }
            
            
            if(!empty($cart_rx_products)) {
                error_log('=== STEP 2: Matching prescription with cart RX items ===');
                error_log('RX Products in cart: ' . implode(', ', $cart_rx_products));
                
                
                $ai_validation = $gemini->matchPrescriptionWithCart($imageData, $mimeType, $cart_rx_products);
                
                error_log('Cart Match Result: ' . json_encode($ai_validation));
                
                
                
                if($ai_validation['success']) {
                    $validation_status = $ai_validation['validation'] ?? 'invalid';
                    if($validation_status === 'invalid' || $validation_status === 'mismatch') {
                        $resp['status'] = 'failed';
                        $resp['msg'] = 'The uploaded prescription does not match the prescription items in your cart.';
                        $resp['validation'] = $ai_validation;
                        $resp['validation_status'] = $validation_status;
                        return json_encode($resp);
                    }
                }
            } else {
                error_log('No RX products in cart - skipping cart match');
            }
            
        } catch(Exception $e) {
            error_log('AI Validation Error: ' . $e->getMessage());
            
            $resp['status'] = 'failed';
            $resp['msg'] = 'Unable to validate prescription. Please try again.';
            $resp['validation_status'] = 'error';
            $resp['reason'] = 'AI validation service error: ' . $e->getMessage();
            return json_encode($resp);
        }
    } else {
        $resp['msg'] = 'Automatic prescription validation currently requires a JPG or PNG image.';
        return json_encode($resp);
    }

    
    
    $baseDir = dirname(__FILE__);
    $uploadDir = $baseDir . '/uploads/prescriptions/cart/';
    
    error_log('Upload directory: ' . $uploadDir);
    
    if(!file_exists($uploadDir)) {
        if(!mkdir($uploadDir, 0755, true)) {
            error_log('Failed to create directory: ' . $uploadDir);
            $resp['msg'] = 'Failed to create upload directory';
            return json_encode($resp);
        }
    }
    
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'cart_rx_' . $customer_id . '_' . time() . '.' . $fileExt;
    $uploadPath = $uploadDir . $fileName;
    $db_path = 'uploads/prescriptions/cart/' . $fileName;
    
    error_log('Saving to: ' . $uploadPath);
    
    
    if(!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        error_log('move_uploaded_file failed');
        $resp['msg'] = 'Failed to save file';
        return json_encode($resp);
    }
    
    error_log('File saved successfully');
    
    
    $validation_status = 'ai_valid';
    if($ai_validation && $ai_validation['success']) {
        $validation_status = $ai_validation['validation'] ?? 'approved';
    }
    
    
    $_SESSION['cart_prescription'] = array(
        'filename' => $fileName,
        'path' => $db_path,
        'uploaded_at' => date('Y-m-d H:i:s'),
        'validation_status' => $validation_status,
        'ai_validation' => $ai_validation
    );
    
    
    
    $table_check = $conn->query("SHOW COLUMNS FROM customer_prescriptions LIKE 'status'");
    if($table_check && $table_check->num_rows == 0) {
        
        $conn->query("ALTER TABLE customer_prescriptions ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
    
    
    $conn->query("DELETE FROM customer_prescriptions WHERE customer_id = {$customer_id} AND image_path LIKE '%cart_rx_%'");
    
    
    $db_status = 'approved';
    $safe_expiry = !empty($automatic_expiry) ? "'" . $conn->real_escape_string($automatic_expiry) . "'" : "NULL";
    $insert_sql = "INSERT INTO customer_prescriptions (customer_id, image_path, notes, status, approved_at, expires_at, uploaded_at)
                   VALUES ({$customer_id}, '{$db_path}', 'Automatically validated: {$validation_status}', '{$db_status}', NOW(), {$safe_expiry}, NOW())";
    
    if(!$conn->query($insert_sql)) {
        error_log('Database insert failed: ' . $conn->error);
        
    } else {
        error_log('Prescription saved to database');
    }
    
    
    $resp = array(
        'status' => 'success',
        'msg' => 'Prescription validated successfully. You may now proceed to checkout.',
        'filename' => $fileName,
        'path' => $db_path,
        'url' => base_url . $db_path,
        'validation_status' => $validation_status
    );
    
    
    if($ai_validation && $ai_validation['success']) {
        $resp['validation'] = $ai_validation;
        $resp['prescribed_medicines'] = $ai_validation['prescribed_medicines'] ?? [];
        $resp['matched_products'] = $ai_validation['matched_products'] ?? [];
        $resp['confidence'] = $ai_validation['confidence'] ?? 0;
        $resp['notes'] = $ai_validation['notes'] ?? '';
        
        
        if($validation_status === 'matched') {
            $resp['msg'] = 'Prescription validated successfully. It matches the prescription items in your cart.';
        } elseif($validation_status === 'partial') {
            $resp['msg'] = 'The prescription only partially matches your cart and cannot be used for checkout.';
        }
    }
    
    error_log('=== CART PRESCRIPTION UPLOAD SUCCESS ===');
    
    return json_encode($resp);
}

function delete_prescription() {
    global $conn, $_settings;

    try {
        $customer_id = $_settings->userdata('id');
        $login_type = $_settings->userdata('login_type');

        if (empty($customer_id) || (string)$login_type !== '2') {
            return json_encode(['success' => false, 'message' => 'User not logged in']);
        }

        $prescription_id = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;
        if ($prescription_id <= 0) {
            return json_encode(['success' => false, 'message' => 'Invalid prescription ID']);
        }

        $q = $conn->query("SELECT image_path FROM customer_prescriptions WHERE id = {$prescription_id} AND customer_id = {$customer_id}");
        if (!$q || $q->num_rows === 0) {
            return json_encode(['success' => false, 'message' => 'Prescription not found']);
        }

        $row = $q->fetch_assoc();
        $image_path = $row['image_path'] ?? '';

        if (!empty($image_path)) {
            $filepath = __DIR__ . '/' . ltrim(str_replace('\\', '/', $image_path), '/');
            if (is_file($filepath)) {
                @unlink($filepath);
            }
        }

        if (!$conn->query("DELETE FROM customer_prescriptions WHERE id = {$prescription_id} AND customer_id = {$customer_id}")) {
            return json_encode(['success' => false, 'message' => 'Failed to delete prescription']);
        }

        return json_encode(['success' => true, 'message' => 'Prescription deleted successfully']);
    } catch (Exception $e) {
        error_log('delete_prescription error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

function get_notifications() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $query = "SELECT n.*, 
                         o.code as order_code,
                         CASE 
                             WHEN TIMESTAMPDIFF(SECOND, n.created_at, NOW()) < 60 
                             THEN 'Just now'
                             WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 
                             THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' min ago')
                             WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 
                             THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hr ago')
                             WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) = 1
                             THEN 'Yesterday'
                             WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) < 7
                             THEN CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
                             ELSE DATE_FORMAT(n.created_at, '%b %d, %Y')
                         END as time_ago
                  FROM `notifications` n
                  LEFT JOIN `order_list` o ON n.order_id = o.id
                  WHERE n.customer_id = {$customer_id}
                  ORDER BY n.created_at DESC
                  LIMIT 10";
        
        $result = $conn->query($query);
        
        if($result) {
            $notifications = array();
            while($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            
            $unread = $conn->query("SELECT COUNT(*) as count FROM `notifications` WHERE customer_id = {$customer_id} AND is_read = 0");
            $unread_count = 0;
            if($unread) {
                $unread_count = $unread->fetch_assoc()['count'];
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $notifications;
            $resp['unread_count'] = (int)$unread_count;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to load notifications. Please try again.';
            error_log('get_notifications DB error: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function check_branch_availability() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $item_ids = isset($_POST['item_ids']) ? $_POST['item_ids'] : '';
        
        if(empty($item_ids)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'No items provided';
            return json_encode($resp);
        }
        
        $customer_id = $_settings->userdata('id');
        $item_ids_array = explode(',', $item_ids);
        $item_ids_clean = array_map('intval', $item_ids_array);
        $item_ids_sql = implode(',', $item_ids_clean);
        
        
        $columns = $conn->query("SHOW COLUMNS FROM stock_list LIKE 'branch_id'");
        $has_branch_column = ($columns && $columns->num_rows > 0);
        
        if(!$has_branch_column) {
            
            $columns = $conn->query("SHOW COLUMNS FROM product_list LIKE 'branch_id'");
            $has_product_branch = ($columns && $columns->num_rows > 0);
            
            if($has_product_branch) {
                
                $query = "SELECT DISTINCT p.branch_id, b.id as branch_id, b.branch_name as branch_name
                         FROM cart_list c
                         INNER JOIN product_list p ON c.product_id = p.id
                         LEFT JOIN branch_list b ON p.branch_id = b.id
                         WHERE c.id IN ({$item_ids_sql}) 
                         AND c.customer_id = {$customer_id}
                         AND p.branch_id IS NOT NULL";
                
                $result = $conn->query($query);
                
                if($result && $result->num_rows > 0) {
                    $branches = array();
                    while($row = $result->fetch_assoc()) {
                        $branches[] = array(
                            'id' => $row['branch_id'],
                            'name' => $row['branch_name']
                        );
                    }
                    
                    if(count($branches) == 1) {
                        
                        $resp['status'] = 'success';
                        $resp['restricted_branch'] = $branches[0];
                    } else if(count($branches) > 1) {
                        
                        $resp['status'] = 'success';
                        $resp['available_branches'] = array_column($branches, 'id');
                        $resp['msg'] = 'Selected items are available at multiple locations';
                    } else {
                        
                        $resp['status'] = 'success';
                        $resp['restricted_branch'] = null;
                        $resp['available_branches'] = null;
                    }
                } else {
                    
                    $resp['status'] = 'success';
                    $resp['restricted_branch'] = null;
                    $resp['available_branches'] = null;
                }
            } else {
                
                $resp['status'] = 'success';
                $resp['restricted_branch'] = null;
                $resp['available_branches'] = null;
                $resp['msg'] = 'Products available at all branches';
            }
        } else {
            
            $query = "SELECT s.branch_id, b.branch_name, COUNT(DISTINCT c.product_id) as product_count
                     FROM cart_list c
                     INNER JOIN product_list p ON c.product_id = p.id
                     INNER JOIN stock_list s ON p.id = s.product_id
                     INNER JOIN branch_list b ON s.branch_id = b.id
                     WHERE c.id IN ({$item_ids_sql}) 
                     AND c.customer_id = {$customer_id}
                     AND s.quantity > 0
                     AND (s.expiration IS NULL OR s.expiration > CURDATE())
                     GROUP BY s.branch_id, b.branch_name";
            
            $result = $conn->query($query);
            $total_products = count($item_ids_clean);
            
            if($result && $result->num_rows > 0) {
                $available_branches = array();
                $full_stock_branch = null;
                
                while($row = $result->fetch_assoc()) {
                    if($row['product_count'] == $total_products) {
                        
                        $full_stock_branch = array(
                            'id' => $row['branch_id'],
                            'name' => $row['branch_name']
                        );
                        $available_branches[] = $row['branch_id'];
                    }
                }
                
                if($full_stock_branch && count($available_branches) == 1) {
                    
                    $resp['status'] = 'success';
                    $resp['restricted_branch'] = $full_stock_branch;
                } else if(count($available_branches) > 0) {
                    
                    $resp['status'] = 'success';
                    $resp['available_branches'] = $available_branches;
                } else {
                    
                    $resp['status'] = 'success';
                    $resp['restricted_branch'] = null;
                    $resp['available_branches'] = null;
                    $resp['msg'] = 'Some products may not be available at selected locations';
                }
            } else {
                
                $resp['status'] = 'success';
                $resp['restricted_branch'] = null;
                $resp['available_branches'] = null;
            }
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'success'; 
        $resp['restricted_branch'] = null;
        $resp['available_branches'] = null;
        $resp['msg'] = 'Unable to check branch availability';
    }
    
    return json_encode($resp);
}

function filter_products() {
    global $conn;
    
    try {
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50; 
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        
        if($limit > 100) $limit = 100; 
        
        
        $where = array("p.delete_flag = 0");
        
        
        
        
        if($category_id > 0) {
            $where[] = "p.category_id = {$category_id}";
        }
        
        if(!empty($search)) {
            $search = $conn->real_escape_string($search);
            $where[] = "(p.name LIKE '%{$search}%' OR p.description LIKE '%{$search}%' OR p.brand LIKE '%{$search}%')";
        }
        
        $where_sql = implode(" AND ", $where);
        
        
        $count_result = $conn->query("SELECT COUNT(*) as total FROM product_list p WHERE {$where_sql}");
        $total_products = $count_result ? $count_result->fetch_assoc()['total'] : 0;
        
        
        if($total_products > 500) {
            error_log("Large product query: {$total_products} products matching filters - Category: {$category_id}, Search: {$search}");
        }
        
        $products = $conn->query("SELECT p.*, c.name as category_name,
                                 (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = p.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(oi.quantity) FROM `order_items` oi INNER JOIN `order_list` ol ON oi.order_id = ol.id WHERE oi.product_id = p.id AND ol.status != 4), 0)) as `available`,
                                 COALESCE(p.is_prescription, 0) as is_prescription,
                                 COALESCE(p.fda_approved, 1) as fda_approved
                                 FROM product_list p 
                                 LEFT JOIN category_list c ON p.category_id = c.id 
                                 WHERE {$where_sql}
                                 ORDER BY p.date_created DESC
                                 LIMIT {$limit} OFFSET {$offset}");
        
        if(!$products) {
            error_log("Database error in filter_products: " . $conn->error);
            throw new Exception("Database query failed");
        }
        
        if($products && $products->num_rows > 0) {
            ob_start();
            while($product = $products->fetch_assoc()):
                $stock = intval($product['available']);
                $isOutOfStock = $stock <= 0;
                $isLowStock = $stock > 0 && $stock <= 5;
                $isPrescription = isset($product['is_prescription']) && $product['is_prescription'] == 1;
                $isFdaApproved = !isset($product['fda_approved']) || $product['fda_approved'] == 1;
                $isNew = strtotime($product['date_created']) > strtotime('-7 days');
            ?>
            <div class="glass-card" data-product-id="<?php echo $product['id']; ?>">
                <div class="product-image-wrapper">
                    <img src="<?php echo validate_image($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    
                    <div class="badge-container">
                        <?php if($isNew && !$isOutOfStock): ?>
                        <span class="badge badge-new">New</span>
                        <?php endif; ?>
                        <?php if($isPrescription): ?>
                        <span class="badge badge-rx"><i class="fas fa-prescription"></i> Rx</span>
                        <?php endif; ?>
                        <?php if($isFdaApproved): ?>
                        <span class="badge badge-fda"><i class="fas fa-check"></i> FDA</span>
                        <?php endif; ?>
                        <?php if($isOutOfStock): ?>
                        <span class="badge badge-out-of-stock">Out of Stock</span>
                        <?php elseif($isLowStock): ?>
                        <span class="badge badge-low-stock"><?php echo $stock; ?> Left</span>
                        <?php endif; ?>
                    </div>
                    
                    
                    <div class="quick-actions">
                        <button class="quick-action-btn" 
                                data-action="toggle-wishlist" 
                                data-product-id="<?php echo $product['id']; ?>" 
                                title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="quick-action-btn" data-action="view-product" data-product-id="<?php echo $product['id']; ?>" title="Quick View">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-details">
                    <span class="product-category-label"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="stock-status">
                        <span class="status-dot <?php echo $stock > 0 ? 'available' : 'out-of-stock' ?>"></span>
                        <span class="status-text"><?php echo $stock > 0 ? number_format($stock) . ' in stock' : 'Out of stock' ?></span>
                    </div>
                    <div class="product-footer">
                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                        <?php if(!$isOutOfStock): ?>
                        <div class="cart-controls">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" data-action="qty-decrease" data-product-id="<?php echo $product['id']; ?>">−</button>
                                <input type="number" class="qty-input" id="qty-<?php echo $product['id']; ?>" value="1" min="1" max="<?php echo min($stock, 99); ?>" data-product-id="<?php echo $product['id']; ?>">
                                <button type="button" class="qty-btn" data-action="qty-increase" data-product-id="<?php echo $product['id']; ?>" data-max="<?php echo min($stock, 99); ?>">+</button>
                            </div>
                            <button class="btn-add" data-action="add-to-cart" data-product-id="<?php echo $product['id']; ?>" title="Add to Cart">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <button class="btn-add" disabled title="Out of Stock">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            endwhile;
            return ob_get_clean();
        } else {
            return '<div class="no-results"><svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg><h3>No Products Found</h3><p>Try adjusting your filters or search terms</p></div>';
        }
        
    } catch (Exception $e) {
        error_log("Error in filter_products: " . $e->getMessage());
        return '<div class="no-results"><p>An error occurred while filtering products. Please try again.</p></div>';
    }
}

function get_cart_count() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['count'] = 0;
            return json_encode($resp);
        }
        
        $count_query = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM cart_list WHERE customer_id = '{$customer_id}'");
        $cart_count = $count_query->fetch_assoc()['total'];
        
        $resp['status'] = 'success';
        $resp['count'] = intval($cart_count);
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['count'] = 0;
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_cart_list() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['items'] = array();
            $resp['total'] = 0;
            return json_encode($resp);
        }
        
        $cart_query = $conn->query("
            SELECT c.id, c.product_id, c.quantity, 
                   p.name, p.price, p.image_path,
                   (c.quantity * p.price) as subtotal
            FROM cart_list c 
            INNER JOIN product_list p ON c.product_id = p.id 
            WHERE c.customer_id = '{$customer_id}'
            ORDER BY c.id DESC
        ");
        
        $items = array();
        $total = 0;
        
        while($row = $cart_query->fetch_assoc()) {
            $items[] = array(
                'id' => $row['id'],
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'subtotal' => $row['subtotal'],
                'image' => $row['image_path']
            );
            $total += floatval($row['subtotal']);
        }
        
        $resp['status'] = 'success';
        $resp['items'] = $items;
        $resp['total'] = $total;
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['items'] = array();
        $resp['total'] = 0;
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function update_cart_qty() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : (isset($_POST['qty']) ? intval($_POST['qty']) : 1);
        
        if($cart_id <= 0 || $quantity < 1) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid parameters';
            return json_encode($resp);
        }
        
        
        $verify = $conn->query("SELECT id, product_id FROM cart_list WHERE id = {$cart_id} AND customer_id = '{$customer_id}'");
        if($verify->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Cart item not found';
            return json_encode($resp);
        }

        $product_id = (int)$verify->fetch_assoc()['product_id'];

        
        $stock_chk = $conn->query("
            SELECT (COALESCE((SELECT SUM(quantity) FROM `stock_list` WHERE product_id = {$product_id} AND (expiration IS NULL OR DATE(expiration) > CURDATE())), 0) 
                  - COALESCE((SELECT SUM(quantity) FROM `order_items` WHERE product_id = {$product_id}), 0)) as available
        ");
        if($stock_chk && $stock_chk->num_rows > 0) {
            $avail = (int)$stock_chk->fetch_assoc()['available'];
            if($quantity > $avail) {
                $resp['status'] = 'failed';
                $resp['msg'] = "Cannot add more. Only {$avail} units available in stock.";
                return json_encode($resp);
            }
        }
        
        $update = $conn->query("UPDATE cart_list SET quantity = {$quantity} WHERE id = {$cart_id}");
        
        if($update) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Cart updated';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to update cart';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function delete_cart() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
        
        if($cart_id <= 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid cart ID';
            return json_encode($resp);
        }
        
        
        $verify = $conn->query("SELECT id FROM cart_list WHERE id = {$cart_id} AND customer_id = '{$customer_id}'");
        if($verify->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Cart item not found';
            return json_encode($resp);
        }
        
        $delete = $conn->query("DELETE FROM cart_list WHERE id = {$cart_id}");
        
        if($delete) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Item removed from cart';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to remove item';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function clear_cart() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $delete = $conn->query("DELETE FROM cart_list WHERE customer_id = '{$customer_id}'");
        
        if($delete) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Cart cleared successfully';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to clear cart';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function delete_selected_cart() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        $cart_ids_json = isset($_POST['cart_ids']) ? $_POST['cart_ids'] : '[]';
        $cart_ids = json_decode($cart_ids_json, true);
        
        if(empty($cart_ids) || !is_array($cart_ids)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'No items selected';
            return json_encode($resp);
        }
        
        
        $cart_ids = array_map('intval', $cart_ids);
        $cart_ids_str = implode(',', $cart_ids);
        
        
        $delete = $conn->query("DELETE FROM cart_list WHERE id IN ({$cart_ids_str}) AND customer_id = '{$customer_id}'");
        
        if($delete) {
            $resp['status'] = 'success';
            $resp['msg'] = count($cart_ids) . ' item(s) removed from cart';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to remove items';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    
    return json_encode($resp);
}

function add_to_cart() {
    global $conn, $_settings;
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            return 0;
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if($product_id <= 0) {
            return 0;
        }
        
        
        $stock_check = $conn->query("SELECT 
            (COALESCE((SELECT SUM(quantity) FROM stock_list WHERE product_id = {$product_id} AND (expiration IS NULL OR date(expiration) > '".date("Y-m-d")."')), 0) 
            - COALESCE((SELECT SUM(oi.quantity) FROM order_items oi INNER JOIN order_list ol ON oi.order_id = ol.id WHERE oi.product_id = {$product_id} AND ol.status != 4), 0)) as available
        ");
        
        if($stock_check && $stock_check->num_rows > 0) {
            $stock_row = $stock_check->fetch_assoc();
            $available_stock = intval($stock_row['available']);
            
            
            if($available_stock <= 0) {
                return json_encode(['status' => 'out_of_stock', 'msg' => 'Product is out of stock']);
            }
        }
        
        
        $check = $conn->query("SELECT * FROM cart_list WHERE customer_id = {$customer_id} AND product_id = {$product_id}");
        
        if($check === false) {
            return json_encode(['status' => 'failed', 'msg' => 'Database error']);
        }
        
        if($check->num_rows > 0) {
            
            $row = $check->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;
            $update = $conn->query("UPDATE cart_list SET quantity = {$new_quantity} WHERE id = {$row['id']}");
            return $update ? json_encode(['status' => 'success', 'cart_id' => intval($row['id']), 'msg' => "Added {$quantity} item" . ($quantity > 1 ? 's' : '') . " to cart!"]) : json_encode(['status' => 'failed', 'msg' => 'Failed to update quantity']);
        } else {
            
            $insert = $conn->query("INSERT INTO cart_list (customer_id, product_id, quantity) VALUES ({$customer_id}, {$product_id}, {$quantity})");
            if ($insert) {
                return json_encode(['status' => 'success', 'cart_id' => intval($conn->insert_id), 'msg' => "Added {$quantity} item" . ($quantity > 1 ? 's' : '') . " to cart!"]);
            } else {
                return json_encode(['status' => 'failed', 'msg' => 'Failed to insert item to cart']);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in add_to_cart: " . $e->getMessage());
        return json_encode(['status' => 'failed', 'msg' => $e->getMessage()]);
    }
}

function clear_and_add_to_cart() {
    global $conn, $_settings;
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            return json_encode(['status' => 'failed', 'msg' => 'Please login first']);
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if($product_id <= 0 || $branch_id <= 0) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid data']);
        }
        
        
        $delete = $conn->query("DELETE FROM cart_list WHERE customer_id = {$customer_id}");
        
        if(!$delete) {
            return json_encode(['status' => 'failed', 'msg' => 'Failed to clear cart']);
        }
        
        
        $insert = $conn->query("INSERT INTO cart_list (customer_id, product_id, quantity, branch_id) 
                               VALUES ({$customer_id}, {$product_id}, {$quantity}, {$branch_id})");
        
        if($insert) {
            return json_encode(['status' => 'success', 'msg' => 'Cart cleared and item added']);
        } else {
            return json_encode(['status' => 'failed', 'msg' => 'Failed to add item']);
        }
        
    } catch (Exception $e) {
        return json_encode(['status' => 'failed', 'msg' => 'An error occurred']);
    }
}

function mark_notification_read() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Notification ID required';
            return json_encode($resp);
        }
        
        $customer_id = $_settings->userdata('id');
        
        $update = $conn->query("UPDATE `notifications` SET is_read = 1 WHERE id = {$id} AND customer_id = {$customer_id}");
        
        if($update) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Marked as read';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to mark as read';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred';
    }
    
    return json_encode($resp);
}

function mark_all_notifications_read() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $stmt = $conn->prepare("UPDATE `notifications` SET is_read = 1 WHERE customer_id = ? AND is_read = 0");
        $stmt->bind_param('i', $customer_id);
        $update = $stmt->execute();
        
        if($update) {
            $affected = $stmt->affected_rows;
            $resp['status'] = 'success';
            $resp['msg'] = $affected > 0 ? "Marked {$affected} notification(s) as read" : 'All notifications already read';
            $resp['affected_rows'] = $affected;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to update notifications: ' . $conn->error;
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
        error_log('mark_all_notifications_read error: ' . $e->getMessage());
    }
    
    return json_encode($resp);
}

function delete_notification() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Notification ID required';
            return json_encode($resp);
        }
        
        $customer_id = $_settings->userdata('id');
        
        $delete = $conn->query("DELETE FROM `notifications` WHERE id = {$id} AND customer_id = {$customer_id}");
        
        if($delete) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Notification deleted';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to delete notification';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred';
    }
    
    return json_encode($resp);
}

function get_product_details() {
    global $conn;
    $resp = array();
    
    try {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if($product_id <= 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid product ID';
            return json_encode($resp);
        }
        
        $qry = $conn->query("SELECT p.*, c.name as category_name,
                            (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = p.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(oi.quantity) FROM `order_items` oi INNER JOIN `order_list` ol ON oi.order_id = ol.id WHERE oi.product_id = p.id AND ol.status != 4), 0)) as `available`
                            FROM product_list p 
                            LEFT JOIN category_list c ON p.category_id = c.id 
                            WHERE p.id = {$product_id} AND p.delete_flag = 0");
        
        if($qry && $qry->num_rows > 0) {
            $product = $qry->fetch_assoc();
            
            
            $image_path = validate_image($product['image_path']);
            
            $resp['status'] = 'success';
            $resp['data'] = array(
                'id' => $product['id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'category_name' => $product['category_name'],
                'brand' => isset($product['brand']) ? $product['brand'] : '',
                'dose' => isset($product['dose']) ? $product['dose'] : '',
                'unit' => isset($product['unit']) ? $product['unit'] : '',
                'available' => $product['available'],
                'image_path' => $image_path,
                'is_prescription' => isset($product['is_prescription']) ? $product['is_prescription'] : 0,
                'fda_approved' => isset($product['fda_approved']) ? $product['fda_approved'] : 1
            );
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Product not found';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function check_branch_stock() {
    global $conn, $_settings;
    $resp = array();
    
    try {
        $customer_id = $_settings->userdata('id');
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if(empty($customer_id) || empty($branch_id)) {
            return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        }
        
        
        $cart_items = $conn->query("SELECT c.*, p.name as product_name 
                                    FROM cart_list c 
                                    INNER JOIN product_list p ON c.product_id = p.id 
                                    WHERE c.customer_id = {$customer_id}");
        
        $unavailable_products = array();
        $all_available = true;
        
        while($item = $cart_items->fetch_assoc()) {
            $stock_check = $conn->query("SELECT p.id, p.name,
                                        COALESCE((SELECT SUM(quantity) FROM stock_list WHERE product_id = p.id AND (expiration IS NULL OR date(expiration) > CURDATE())), 0) as available_stock
                                        FROM product_list p 
                                        WHERE p.id = {$item['product_id']}");
            
            if($stock_check && $stock_check->num_rows > 0) {
                $stock = $stock_check->fetch_assoc();
                if($stock['available_stock'] < $item['quantity']) {
                    $all_available = false;
                    $unavailable_products[] = array(
                        'name' => $item['product_name'],
                        'requested' => $item['quantity'],
                        'available' => $stock['available_stock']
                    );
                }
            } else {
                $all_available = false;
                $unavailable_products[] = array(
                    'name' => $item['product_name'],
                    'requested' => $item['quantity'],
                    'available' => 0
                );
            }
        }
        
        if($all_available) {
            return json_encode([
                'status' => 'success',
                'available' => true,
                'msg' => 'All products available at selected branch'
            ]);
        } else {
            return json_encode([
                'status' => 'success',
                'available' => false,
                'msg' => 'Some products are not available',
                'unavailable_products' => $unavailable_products
            ]);
        }
        
    } catch (Exception $e) {
        return json_encode(['status' => 'failed', 'msg' => 'An error occurred']);
    }
}

function get_available_branches() {
    global $conn, $_settings;
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            return json_encode(['status' => 'failed', 'msg' => 'Please login first']);
        }
        
        $cart_items = $conn->query("SELECT product_id, quantity FROM cart_list WHERE customer_id = {$customer_id}");
        
        if(!$cart_items || $cart_items->num_rows == 0) {
            return json_encode(['status' => 'failed', 'msg' => 'Cart is empty']);
        }
        
        $branches = $conn->query("SELECT * FROM branch_list WHERE status = 1 ORDER BY branch_name ASC");
        
        $available_branches = array();
        
        while($branch = $branches->fetch_assoc()) {
            $can_fulfill = true;
            
            $cart_items->data_seek(0);
            
            while($item = $cart_items->fetch_assoc()) {
                $stock = $conn->query("SELECT COALESCE(SUM(quantity), 0) as available 
                                      FROM stock_list 
                                      WHERE product_id = {$item['product_id']} 
                                      AND (expiration IS NULL OR date(expiration) > CURDATE())")->fetch_assoc();
                
                if($stock['available'] < $item['quantity']) {
                    $can_fulfill = false;
                    break;
                }
            }
            
            if($can_fulfill) {
                $available_branches[] = array(
                    'id' => $branch['id'],
                    'name' => $branch['branch_name'],
                    'address' => $branch['branch_address'],
                    'phone' => $branch['branch_phone']
                );
            }
        }
        
        return json_encode([
            'status' => 'success',
            'branches' => $available_branches
        ]);
        
    } catch (Exception $e) {
        return json_encode(['status' => 'failed', 'msg' => 'An error occurred']);
    }
}

function check_maintenance_status() {
    global $conn, $_settings;
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            return json_encode(['success' => false, 'msg' => 'User not logged in']);
        }
        
        
        $maintenance_query = $conn->query("
            SELECT COUNT(*) as maintenance_count
            FROM medication_reminders
            WHERE customer_id = {$customer_id}
            AND status = 'active'
            AND (end_date IS NULL OR end_date >= CURDATE())
        ");
        
        $maintenance_data = $maintenance_query->fetch_assoc();
        $has_maintenance = $maintenance_data['maintenance_count'] > 0;
        $maintenance_count = $maintenance_data['maintenance_count'];
        
        return json_encode([
            'success' => true,
            'has_maintenance' => $has_maintenance,
            'count' => $maintenance_count
        ]);
        
    } catch (Exception $e) {
        return json_encode(['success' => false, 'msg' => 'An error occurred']);
    }
}

function upload_prescription() {
    global $conn, $_settings;
    
    
    error_log('=== PRESCRIPTION UPLOAD START ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));
    
    try {
        $customer_id = $_settings->userdata('id');
        
        if(empty($customer_id)) {
            error_log('Upload failed: User not logged in');
            return json_encode(['success' => false, 'message' => 'User not logged in']);
        }
        
        error_log('Customer ID: ' . $customer_id);
        
        
        $file_key = isset($_FILES['prescription_image']) ? 'prescription_image' : 'prescription_file';
        
        if(!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != 0) {
            return json_encode(['success' => false, 'message' => 'Please select a file to upload']);
        }
        
        $file = $_FILES[$file_key];
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $create_reminders = isset($_POST['create_reminders']) && $_POST['create_reminders'] == 'on';
        $automatic_expiry = null;
        
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actual_mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if(!in_array($actual_mime, $allowed_types)) {
            return json_encode(['success' => false, 'message' => 'Only JPG, PNG, and PDF files are allowed']);
        }
        
        
        if($file['size'] > 5 * 1024 * 1024) {
            return json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        }
        
        
        if(in_array($actual_mime, ['image/jpeg', 'image/jpg', 'image/png'])) {
            
            
            $bypass_validation = isset($_POST['bypass_validation']) && 
                                  $_POST['bypass_validation'] == '1' && 
                                  $_settings->userdata('type') == 1; 
            
            if(!$bypass_validation) {
                require_once(__DIR__ . '/Chatbot/GeminiAI.php');
                
                
                $imageData = base64_encode(file_get_contents($file['tmp_name']));
                $mimeType = $file['type'];
                
                
                $gemini = new GeminiAI($conn, []);
                
                
                $validation = $gemini->validatePrescription($imageData, $mimeType);
                
                
                error_log('Prescription Validation Result: ' . json_encode($validation));
                error_log('File uploaded: ' . $file['name'] . ' | Type: ' . $file['type'] . ' | Size: ' . $file['size'] . ' bytes');
                
                
                if(!isset($validation['valid']) || $validation['valid'] !== true) {
                    
                    $errorReason = $validation['reason'] ?? 'This image does not appear to be a valid medical prescription.';
                    
                    if(isset($validation['status']) && $validation['status'] === 'error') {
                        $errorReason = 'Unable to verify prescription image. ' . $errorReason . ' Please ensure the image is clear and shows a complete prescription from a licensed doctor.';
                    }
                    
                    return json_encode([
                        'success' => false, 
                        'message' => 'Invalid Prescription Image',
                        'reason' => $errorReason,
                        'validation_failed' => true,
                        'validation_details' => $validation
                    ]);
                }
                
                
                if(isset($validation['confidence']) && $validation['confidence'] < 70) {
                    return json_encode([
                        'success' => false, 
                        'message' => 'Low Confidence Prescription',
                        'reason' => 'The uploaded image has low confidence as a valid prescription ('. $validation['confidence'] .'%). Please upload a clearer image showing: doctor name, signature, patient details, medication names, and prescription date.',
                        'validation_failed' => true
                    ]);
                }
                
            } else {
                
                error_log('Prescription validation bypassed by admin user: ' . $_settings->userdata('id'));
            }
        } else {
            return json_encode(['success' => false, 'message' => 'Automatic validation currently requires a JPG or PNG image.']);
        }

        
        
        $upload_dir = __DIR__ . '/classes/uploads/prescriptions/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'prescription_' . $customer_id . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        $db_path = 'classes/uploads/prescriptions/' . $filename;
        
        
        if(!move_uploaded_file($file['tmp_name'], $filepath)) {
            return json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        
        
        $safe_expiry = !empty($automatic_expiry) ? "'" . $conn->real_escape_string($automatic_expiry) . "'" : "NULL";
        $query = "INSERT INTO customer_prescriptions (customer_id, image_path, notes, status, approved_at, expires_at, uploaded_at)
                  VALUES ({$customer_id}, '{$db_path}', '{$notes}', 'approved', NOW(), {$safe_expiry}, NOW())";
        
        if(!$conn->query($query)) {
            unlink($filepath); 
            return json_encode(['success' => false, 'message' => 'Failed to save prescription record']);
        }
        
        $prescription_id = $conn->insert_id;
        $reminders_created = 0;
        
        
        if($create_reminders) {
            
            
            require_once(__DIR__ . '/classes/MedicationReminderManager.php');
            $reminderManager = new MedicationReminderManager();
            
            
            $reminder_data = [
                'customer_id' => $customer_id,
                'medicine_name' => 'Medication from Prescription',
                'dosage' => 'As prescribed',
                'frequency' => 'twice_daily',
                'reminder_times' => ['09:00:00', '21:00:00'],
                'start_date' => date('Y-m-d'),
                'end_date' => null,
                'instructions' => 'Please update medication details from uploaded prescription',
                'prescription_image' => $db_path,
                'created_from_order' => null,
                'notify_customer' => 1
            ];
            
            $result = $reminderManager->createReminder($reminder_data);
            if($result['success']) {
                $reminders_created = 1;
            }
        }
        
        return json_encode([
            'success' => true,
            'message' => 'Prescription uploaded and verified successfully!',
            'prescription_id' => $prescription_id,
            'reminders_created' => $reminders_created,
            'instructions' => [
                'title' => 'Important: Medication Instructions',
                'content' => 'Your prescription has been uploaded and verified by our AI system. Please follow the medication schedule prescribed by your doctor. Take medications as directed on the prescription label.',
                'tips' => [
                    'Take medication at the same time every day',
                    'Do not skip doses or stop medication early',
                    'Store medications in a cool, dry place',
                    'Check expiration dates before taking',
                    'Contact your doctor if you experience side effects'
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Prescription upload error: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'An error occurred during upload']);
    }
}

function check_prescription_requirement() {
    global $conn, $_settings;
    $resp = array('status' => 'success', 'has_rx_products' => false);
    
    try {
        $customer_id = $_settings->userdata('id');
        $cart_ids = isset($_POST['cart_ids']) ? $_POST['cart_ids'] : '';
        
        if(empty($cart_ids) || empty($customer_id)) {
            return json_encode($resp);
        }
        
        
        $cart_ids_array = explode(',', $cart_ids);
        $cart_ids_array = array_map('intval', $cart_ids_array);
        $cart_ids_array = array_filter($cart_ids_array);
        
        if(empty($cart_ids_array)) {
            return json_encode($resp);
        }
        
        $cart_ids_clean = implode(',', $cart_ids_array);
        $customer_id = intval($customer_id);
        
        
        $query = "SELECT COUNT(*) as rx_count 
                  FROM cart_list c 
                  INNER JOIN product_list p ON c.product_id = p.id 
                  WHERE c.id IN ({$cart_ids_clean}) 
                  AND c.customer_id = {$customer_id} 
                  AND COALESCE(p.is_prescription, 0) = 1";
        
        $result = $conn->query($query);
        if($result) {
            $data = $result->fetch_assoc();
            $resp['has_rx_products'] = $data['rx_count'] > 0;
        }
        
    } catch (Exception $e) {
        error_log('Prescription check error: ' . $e->getMessage());
    }
    
    return json_encode($resp);
}

function validate_prescription_cart() {
    global $conn, $_settings;
    header('Content-Type: application/json');
    
    $resp = array('success' => false);
    
    try {
        
        $customer_id = $_settings->userdata('id');
        if (empty($customer_id)) {
            $resp['message'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        if (!isset($_POST['image_data']) || !isset($_POST['cart_products'])) {
            $resp['message'] = 'Missing required parameters';
            return json_encode($resp);
        }
        
        $imageData = $_POST['image_data'];
        $cartProducts = json_decode($_POST['cart_products'], true);
        
        if (empty($imageData) || empty($cartProducts)) {
            $resp['message'] = 'Invalid data provided';
            return json_encode($resp);
        }
        
        
        require_once(__DIR__ . '/Chatbot/GeminiAI.php');
        $ai = new GeminiAI($conn);
        
        
        $validation = $ai->matchPrescriptionWithCart($imageData, 'image/jpeg', $cartProducts);
        
        if ($validation['success']) {
            $resp['success'] = true;
            $resp['validation'] = $validation;
        } else {
            $resp['message'] = $validation['message'] ?? 'Validation failed';
        }
        
    } catch (Exception $e) {
        error_log('Prescription cart validation error: ' . $e->getMessage());
        $resp['message'] = 'Error processing validation';
    }
    
    return json_encode($resp);
}

function customize_prescription() {
    global $conn, $_settings;
    header('Content-Type: application/json');
    
    $resp = array('success' => false);
    
    try {
        
        $customer_id = $_settings->userdata('id');
        if (empty($customer_id)) {
            $resp['message'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $prescription_id = isset($_POST['prescription_id']) ? intval($_POST['prescription_id']) : 0;
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        
        $medication_reminders = null;
        if (isset($_POST['medication_reminders'])) {
            $medication_reminders = $_POST['medication_reminders'];
        }
        
        if ($prescription_id <= 0) {
            $resp['message'] = 'Invalid prescription ID';
            return json_encode($resp);
        }
        
        
        $check = $conn->query("SELECT id, image_path FROM customer_prescriptions 
                              WHERE id = {$prescription_id} AND customer_id = {$customer_id}");
        
        if (!$check || $check->num_rows == 0) {
            $resp['message'] = 'Prescription not found or access denied';
            return json_encode($resp);
        }
        
        $prescription_data = $check->fetch_assoc();
        $prescription_image = $prescription_data['image_path'];
        
        
        $notes_escaped = $conn->real_escape_string($notes);
        
        
        $ai_schedule_update = '';
        if ($medication_reminders !== null) {
            $medication_reminders_escaped = $conn->real_escape_string($medication_reminders);
            $ai_schedule_update = ", ai_medication_schedule = '{$medication_reminders_escaped}'";
        }
        
        
        $update_sql = "UPDATE customer_prescriptions SET 
                      notes = '{$notes_escaped}',
                      updated_at = NOW()
                      {$ai_schedule_update}
                      WHERE id = {$prescription_id} AND customer_id = {$customer_id}";
        
        if ($conn->query($update_sql)) {
            $resp['success'] = true;
            $resp['message'] = 'Medication schedule saved successfully';
            
            
            $reminders_created = 0;
            $reminders_updated = 0;
            
            if ($medication_reminders !== null) {
                $meds = json_decode($medication_reminders, true);
                
                if (is_array($meds) && count($meds) > 0) {
                    require_once(__DIR__ . '/classes/MedicationReminderManager.php');
                    $reminderManager = new MedicationReminderManager();
                    
                    foreach ($meds as $med) {
                        
                        if (isset($med['create_reminder']) && $med['create_reminder'] === '1') {
                            $medicine_name = $med['medicine_name'] ?? 'Unknown Medication';
                            $dosage = $med['dosage'] ?? 'As prescribed';
                            $frequency = $med['frequency'] ?? 'daily';
                            $times = $med['times'] ?? '08:00';
                            $duration_days = isset($med['duration_days']) && $med['duration_days'] !== '' ? intval($med['duration_days']) : 30;
                            $instructions = $med['instructions'] ?? '';
                            
                            
                            $times_array = array_map(function($t) {
                                $t = trim($t);
                                
                                if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
                                    return $t . ':00';
                                }
                                
                                if (preg_match('/^(\d{1,2}):?(\d{2})?\s*(am|pm)$/i', $t, $matches)) {
                                    $h = intval($matches[1]);
                                    $m = isset($matches[2]) ? intval($matches[2]) : 0;
                                    $period = strtolower($matches[3]);
                                    
                                    if ($period === 'pm' && $h < 12) $h += 12;
                                    if ($period === 'am' && $h === 12) $h = 0;
                                    
                                    return sprintf('%02d:%02d:00', $h, $m);
                                }
                                return $t . ':00';
                            }, explode(',', $times));
                            
                            
                            $existing = $conn->query("SELECT id FROM medication_reminders 
                                                     WHERE customer_id = {$customer_id} 
                                                     AND medicine_name = '" . $conn->real_escape_string($medicine_name) . "'
                                                     AND prescription_image = '" . $conn->real_escape_string($prescription_image) . "'");
                            
                            if ($existing && $existing->num_rows > 0) {
                                
                                $reminder_row = $existing->fetch_assoc();
                                $reminder_id = $reminder_row['id'];
                                
                                $update_reminder = $reminderManager->updateReminder($reminder_id, [
                                    'dosage' => $dosage,
                                    'reminder_times' => $times_array,
                                    'instructions' => $instructions,
                                    'end_date' => date('Y-m-d', strtotime("+{$duration_days} days")),
                                    'status' => 'active'
                                ]);
                                
                                if ($update_reminder['success']) {
                                    $reminders_updated++;
                                }
                            } else {
                                
                                $reminder_data = [
                                    'customer_id' => $customer_id,
                                    'medicine_name' => $medicine_name,
                                    'dosage' => $dosage,
                                    'frequency' => $frequency,
                                    'reminder_times' => $times_array,
                                    'start_date' => date('Y-m-d'),
                                    'end_date' => date('Y-m-d', strtotime("+{$duration_days} days")),
                                    'instructions' => $instructions,
                                    'prescription_image' => $prescription_image,
                                    'created_from_order' => null,
                                    'notify_customer' => 1
                                ];
                                
                                $result = $reminderManager->createReminder($reminder_data);
                                if ($result['success']) {
                                    $reminders_created++;
                                }
                            }
                        }
                    }
                }
            }
            
            if ($reminders_created > 0 || $reminders_updated > 0) {
                $resp['message'] = "Schedule saved! {$reminders_created} reminders created, {$reminders_updated} updated.";
                $resp['reminders_created'] = $reminders_created;
                $resp['reminders_updated'] = $reminders_updated;
            }
            
            
            error_log("Prescription {$prescription_id} customized by customer {$customer_id} - Created: {$reminders_created}, Updated: {$reminders_updated}");
        } else {
            $resp['message'] = 'Failed to save customization: ' . $conn->error;
            error_log("Failed to customize prescription {$prescription_id}: " . $conn->error);
        }
        
    } catch (Exception $e) {
        error_log('Prescription customization error: ' . $e->getMessage());
        $resp['message'] = 'Error processing customization';
    }
    
    return json_encode($resp);
}

function get_prescription_schedule() {
    global $conn, $_settings;
    header('Content-Type: application/json');
    
    $resp = array('success' => false);
    
    try {
        
        $customer_id = $_settings->userdata('id');
        if (empty($customer_id)) {
            $resp['message'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $prescription_id = isset($_GET['prescription_id']) ? intval($_GET['prescription_id']) : 0;
        
        if ($prescription_id <= 0) {
            $resp['message'] = 'Invalid prescription ID';
            return json_encode($resp);
        }
        
        
        $stmt = $conn->prepare("SELECT ai_medication_schedule FROM customer_prescriptions 
                               WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $prescription_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $prescription = $result->fetch_assoc();
        $stmt->close();
        
        if (!$prescription) {
            $resp['message'] = 'Prescription not found or access denied';
            return json_encode($resp);
        }
        
        
        $schedule = [];
        if (!empty($prescription['ai_medication_schedule'])) {
            $schedule = json_decode($prescription['ai_medication_schedule'], true);
            if (!is_array($schedule)) {
                $schedule = [];
            }
        }
        
        $resp['success'] = true;
        $resp['schedule'] = $schedule;
        $resp['count'] = count($schedule);
        
    } catch (Exception $e) {
        error_log('Get prescription schedule error: ' . $e->getMessage());
        $resp['message'] = 'Error retrieving schedule';
    }
    
    return json_encode($resp);
}

function get_cart_summary() {
    global $conn, $_settings;
    
    $resp = array('status' => 'failed', 'msg' => 'Unknown error');
    
    
    if(!isset($_SESSION['userdata']) || empty($_SESSION['userdata']['id'])) {
        $resp['msg'] = 'Please login to view cart';
        return json_encode($resp);
    }
    
    $customer_id = $_SESSION['userdata']['id'];
    
    try {
        
        $sql = "SELECT c.*, c.quantity, 
                       p.name, p.description, p.price, 
                       COALESCE(p.image_path, 'uploads/no-image-available.png') as image_path,
                       cat.name as category,
                       COALESCE(p.is_prescription, 0) as is_rx
                FROM cart_list c
                INNER JOIN product_list p ON c.product_id = p.id
                LEFT JOIN category_list cat ON p.category_id = cat.id
                WHERE c.customer_id = ?
                ORDER BY c.id DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = array();
        $subtotal = 0;
        $has_rx_products = false;
        
        while($row = $result->fetch_assoc()) {
            $item_total = floatval($row['price']) * intval($row['quantity']);
            $subtotal += $item_total;
            
            $is_rx = intval($row['is_rx']) === 1;
            if ($is_rx) {
                $has_rx_products = true;
            }
            
            $items[] = array(
                'id' => $row['id'],
                'cart_id' => $row['id'],
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'total' => $item_total,
                'image_path' => $row['image_path'],
                'category' => $row['category'],
                'is_rx' => $is_rx
            );
        }
        $stmt->close();
        
        
        $tax_rate = 0;
        $tax = $subtotal * $tax_rate;
        $total = $subtotal + $tax;
        
        $resp = array(
            'status' => 'success',
            'items' => $items,
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax' => number_format($tax, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'item_count' => count($items),
            'has_rx_products' => $has_rx_products
        );
        
    } catch (Exception $e) {
        error_log('Get cart summary error: ' . $e->getMessage());
        $resp['msg'] = 'Error loading cart';
    }
    
    return json_encode($resp);
}

function search_products() {
    global $conn;
    header('Content-Type: application/json');
    
    try {
        $query = isset($_POST['query']) ? trim($_POST['query']) : '';
        
        if(empty($query) || strlen($query) < 1) {
            return json_encode(['status' => 'error', 'message' => 'Query too short', 'data' => []]);
        }
        
        $query = $conn->real_escape_string($query);
        
        
        $sql = "SELECT p.*, c.name as category_name,
                (COALESCE((SELECT SUM(quantity) FROM stock_list WHERE product_id = p.id AND (expiration IS NULL OR date(expiration) > '".date("Y-m-d")."')), 0) 
                - COALESCE((SELECT SUM(oi.quantity) FROM order_items oi INNER JOIN order_list ol ON oi.order_id = ol.id WHERE oi.product_id = p.id AND ol.status != 4), 0)) as available
                FROM product_list p 
                LEFT JOIN category_list c ON p.category_id = c.id 
                WHERE p.delete_flag = 0 
                AND (
                    LOWER(p.name) LIKE LOWER('%{$query}%') 
                    OR LOWER(p.description) LIKE LOWER('%{$query}%') 
                    OR LOWER(p.brand) LIKE LOWER('%{$query}%') 
                    OR LOWER(c.name) LIKE LOWER('%{$query}%')
                )
                ORDER BY 
                    CASE 
                        WHEN LOWER(p.name) LIKE LOWER('{$query}%') THEN 1
                        WHEN LOWER(p.name) LIKE LOWER('%{$query}%') THEN 2
                        ELSE 3
                    END,
                    p.name ASC
                LIMIT 10";
        
        $result = $conn->query($sql);
        
        if(!$result) {
            error_log("Search products SQL error: " . $conn->error . " | Query: " . $sql);
            return json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error, 'data' => []]);
        }
        
        $products = [];
        while($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['id'],
                'name' => htmlspecialchars($row['name']),
                'category_name' => htmlspecialchars($row['category_name']),
                'price' => $row['price'],
                'image_path' => validate_image($row['image_path']),
                'available' => intval($row['available'])
            ];
        }
        
        
        error_log("Search query: '{$query}' | Results found: " . count($products));
        
        return json_encode(['status' => 'success', 'data' => $products, 'query' => $query]);
        
    } catch (Exception $e) {
        error_log("Search products exception: " . $e->getMessage());
        return json_encode(['status' => 'error', 'message' => 'Error searching products', 'data' => []]);
    }
}

function get_all_products_catalog() {
    global $conn;
    header('Content-Type: application/json');
    
    try {
        $sql = "SELECT p.*, c.name as category_name,
                (COALESCE((SELECT SUM(quantity) FROM stock_list WHERE product_id = p.id AND (expiration IS NULL OR date(expiration) > '".date("Y-m-d")."')), 0) 
                - COALESCE((SELECT SUM(oi.quantity) FROM order_items oi INNER JOIN order_list ol ON oi.order_id = ol.id WHERE oi.product_id = p.id AND ol.status != 4), 0)) as available,
                COALESCE(p.is_prescription, 0) as is_prescription,
                COALESCE(p.fda_approved, 1) as fda_approved
                FROM product_list p 
                LEFT JOIN category_list c ON p.category_id = c.id 
                WHERE p.delete_flag = 0 
                ORDER BY p.date_created DESC";
        
        $result = $conn->query($sql);
        
        if(!$result) {
            error_log("Get all products SQL error: " . $conn->error);
            return json_encode(['status' => 'error', 'message' => 'Database error', 'data' => []]);
        }
        
        $products = [];
        while($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'brand' => $row['brand'],
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'price' => $row['price'],
                'image_path' => validate_image($row['image_path']),
                'available' => intval($row['available']),
                'date_created' => $row['date_created'],
                'is_prescription' => intval($row['is_prescription']),
                'fda_approved' => intval($row['fda_approved'])
            ];
        }
        
        return json_encode(['status' => 'success', 'data' => $products, 'count' => count($products)]);
        
    } catch (Exception $e) {
        error_log("Get all products exception: " . $e->getMessage());
        return json_encode(['status' => 'error', 'message' => 'Error loading products', 'data' => []]);
    }
}

function check_and_cancel_expired_payments($conn) {
    try {
        if (!isset($_SESSION['userdata']['id'])) {
            return json_encode(['status' => 'ok', 'cancelled' => 0]);
        }
        
        $customer_id = intval($_SESSION['userdata']['id']);
        
        
        $stmt = $conn->prepare(
            "SELECT id, code, paymongo_source_id, payment_method, total_amount
             FROM order_list
             WHERE customer_id = ?
               AND status = 0
               AND payment_status IN ('pending_payment', 'pending_verification')
               AND paymongo_source_id IS NOT NULL
               AND paymongo_source_id != ''
             LIMIT 5"
        );
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            return json_encode(['status' => 'ok', 'cancelled' => 0, 'restored' => 0]);
        }
        
        require_once(__DIR__ . '/classes/PayMongo.php');
        $paymongo = new PayMongo($conn);
        
        $restored_count = 0;
        $restored_codes = [];
        
        while ($order = $result->fetch_assoc()) {
            $source_id = $order['paymongo_source_id'];
            
            
            $source = $paymongo->getSource($source_id);
            
            if ($source['status'] !== 'success') {
                continue; 
            }
            
            $source_status = $source['source_status'] ?? '';
            
            
            if (in_array($source_status, ['expired', 'cancelled', 'failed'], true)) {
                $order_id = (int) $order['id'];
                $order_code = $order['code'];
                $rollback = $paymongo->rollbackFailedPendingOrder($order_id, 'PayMongo payment status: ' . $source_status);

                if ($rollback['success']) {
                    $restored_count++;
                    $restored_codes[] = $order_code;
                    error_log("Restored cart and removed failed PayMongo order #{$order_code} (ID:{$order_id}) — status: {$source_status}");
                } else {
                    error_log("Unable to roll back failed PayMongo order #{$order_id}: {$rollback['msg']}");
                }
            }
        }
        
        return json_encode([
            'status'    => 'ok',
            'cancelled' => 0,
            'restored'  => $restored_count,
            'codes'     => $restored_codes
        ]);
        
    } catch (Exception $e) {
        error_log("check_and_cancel_expired_payments error: " . $e->getMessage());
        return json_encode(['status' => 'ok', 'cancelled' => 0, 'restored' => 0]);
    }
}
?>
