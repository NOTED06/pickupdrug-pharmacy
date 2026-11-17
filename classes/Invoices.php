<?php
require_once('../config.php');
require_once(__DIR__ . '/OrderConstants.php');

class Invoices extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}

	
	function generate_invoice_code() {
		$year = date('Y');
		$month = date('m');
		
		
		$qry = $this->conn->query("SELECT invoice_code FROM invoice_list 
			WHERE invoice_code LIKE 'INV-$year-$month-%' 
			ORDER BY id DESC LIMIT 1");
		
		if($qry->num_rows > 0) {
			$row = $qry->fetch_assoc();
			$last_code = $row['invoice_code'];
			$number = intval(substr($last_code, -4)) + 1;
		} else {
			$number = 1;
		}
		
		$invoice_code = 'INV-' . $year . '-' . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
		
		return $this->conn->query("SELECT 1") ? json_encode(['status' => 'success', 'code' => $invoice_code]) : json_encode(['status' => 'failed']);
	}

	
	
	function get_order_items() {
		
		$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : (isset($_GET['order_id']) ? $_GET['order_id'] : null);
		
		if(!$order_id) {
			return json_encode(array('status' => 'failed', 'msg' => 'Order ID is required'));
		}
		
		$items = array();
		$debug_info = array();
		
		
		$stmt = $this->conn->prepare("SELECT * FROM order_list WHERE id = ?"); $stmt->bind_param('i', $order_id); $stmt->execute(); $order_qry = $stmt->get_result();
		if($order_qry->num_rows == 0) {
			return json_encode(array('status' => 'failed', 'msg' => 'Order not found'));
		}
		
		$order = $order_qry->fetch_assoc();
		$debug_info['order_data'] = $order;
		
		
		$tables_qry = $this->conn->query("SHOW TABLES");
		$tables = array();
		while($row = $tables_qry->fetch_array()) {
			$tables[] = $row[0];
		}
		$debug_info['available_tables'] = $tables;
		
		
		if(in_array('product_list', $tables)) {
			$product_columns = array();
			$columns_qry = $this->conn->query("SHOW COLUMNS FROM product_list");
			while($col = $columns_qry->fetch_assoc()) {
				$product_columns[$col['Field']] = $col['Type'];
			}
			$debug_info['product_list_columns'] = $product_columns;
		}
		
		if(in_array('order_items', $tables)) {
			$order_items_columns = array();
			$columns_qry = $this->conn->query("SHOW COLUMNS FROM order_items");
			while($col = $columns_qry->fetch_assoc()) {
				$order_items_columns[$col['Field']] = $col['Type'];
			}
			$debug_info['order_items_columns'] = $order_items_columns;
		}
		
		if(in_array('cart_list', $tables)) {
			$cart_list_columns = array();
			$columns_qry = $this->conn->query("SHOW COLUMNS FROM cart_list");
			while($col = $columns_qry->fetch_assoc()) {
				$cart_list_columns[$col['Field']] = $col['Type'];
			}
			$debug_info['cart_list_columns'] = $cart_list_columns;
		}
		
		
		if(in_array('order_items', $tables)) {
			$stmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = ?"); $stmt->bind_param('i', $order_id); $stmt->execute(); $simple_qry = $stmt->get_result();
			if($simple_qry && $simple_qry->num_rows > 0) {
				$debug_info['order_items_raw'] = array();
				while($row = $simple_qry->fetch_assoc()) {
					$debug_info['order_items_raw'][] = $row;
					
					
					$item = array(
						'product_name' => $row['product_name'] ?? $row['item_name'] ?? $row['name'] ?? 'Product',
						'description' => $row['description'] ?? '',
						'quantity' => floatval($row['quantity'] ?? $row['qty'] ?? 1),
						'price' => floatval($row['price'] ?? $row['unit_price'] ?? $row['amount'] ?? 0),
						'total' => floatval($row['total'] ?? $row['total_amount'] ?? 0)
					);
					
					
					if($item['total'] == 0) {
						$item['total'] = $item['quantity'] * $item['price'];
					}
					
					$items[] = $item;
				}
			}
		}
		
		
		if(empty($items) && in_array('cart_list', $tables)) {
			$stmt = $this->conn->prepare("SELECT * FROM cart_list WHERE order_id = ?"); $stmt->bind_param('i', $order_id); $stmt->execute(); $simple_qry = $stmt->get_result();
			if($simple_qry && $simple_qry->num_rows > 0) {
				$debug_info['cart_list_raw'] = array();
				while($row = $simple_qry->fetch_assoc()) {
					$debug_info['cart_list_raw'][] = $row;
					
					$item = array(
						'product_name' => $row['product_name'] ?? $row['item_name'] ?? $row['name'] ?? 'Product',
						'description' => $row['description'] ?? '',
						'quantity' => floatval($row['quantity'] ?? $row['qty'] ?? 1),
						'price' => floatval($row['price'] ?? $row['unit_price'] ?? $row['amount'] ?? 0),
						'total' => floatval($row['total'] ?? $row['total_amount'] ?? 0)
					);
					
					if($item['total'] == 0) {
						$item['total'] = $item['quantity'] * $item['price'];
					}
					
					$items[] = $item;
				}
			}
		}
		
		
		if(!empty($items) && isset($debug_info['product_list_columns'])) {
			
			$product_name_col = null;
			if(isset($debug_info['product_list_columns']['name'])) {
				$product_name_col = 'name';
			} elseif(isset($debug_info['product_list_columns']['product_name'])) {
				$product_name_col = 'product_name';
			} elseif(isset($debug_info['product_list_columns']['title'])) {
				$product_name_col = 'title';
			}
			
			$debug_info['selected_product_name_col'] = $product_name_col;
			
			
			foreach($items as $key => $item) {
				$raw_data = $debug_info['order_items_raw'][$key] ?? $debug_info['cart_list_raw'][$key] ?? null;
				if($raw_data && isset($raw_data['product_id']) && $product_name_col) {
					$product_qry = $this->conn->query("SELECT * FROM product_list WHERE id = '{$raw_data['product_id']}'");
					if($product_qry && $product_qry->num_rows > 0) {
						$product = $product_qry->fetch_assoc();
						$items[$key]['product_name'] = $product[$product_name_col] ?? $items[$key]['product_name'];
						$items[$key]['description'] = $product['description'] ?? $items[$key]['description'];
					}
				}
			}
		}
		
		
		if(empty($items) && isset($order['items']) && !empty($order['items'])) {
			$order_items = json_decode($order['items'], true);
			if(is_array($order_items)) {
				$debug_info['json_items'] = $order_items;
				foreach($order_items as $item) {
					$items[] = array(
						'product_name' => $item['name'] ?? $item['product_name'] ?? 'Product',
						'description' => $item['description'] ?? '',
						'quantity' => floatval($item['quantity'] ?? $item['qty'] ?? 1),
						'price' => floatval($item['price'] ?? $item['unit_price'] ?? 0),
						'total' => floatval($item['total'] ?? ($item['quantity'] * $item['price']) ?? 0)
					);
				}
			}
		}
		
		
		if(empty($items)) {
			$items[] = array(
				'product_name' => 'Order Item',
				'description' => 'Items from Order #' . ($order['code'] ?? $order['id']),
				'quantity' => 1,
				'price' => floatval($order['total_amount'] ?? $order['amount'] ?? 0),
				'total' => floatval($order['total_amount'] ?? $order['amount'] ?? 0)
			);
			$debug_info['fallback_used'] = true;
		}
		
		if(!empty($items)) {
			return json_encode(array(
				'status' => 'success', 
				'items' => $items,
				'debug_info' => $debug_info
			));
		} else {
			return json_encode(array(
				'status' => 'failed', 
				'msg' => 'No order items found',
				'debug_info' => $debug_info
			));
		}
	}

	
	function create_from_order() {
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$data = "";
		$invoice_code = isset($_POST['invoice_code']) ? trim($_POST['invoice_code']) : '';
		
		
		if(empty($invoice_code)) {
			$year = date('Y');
			$month = date('m');
			$qry = $this->conn->query("SELECT invoice_code FROM invoice_list 
				WHERE invoice_code LIKE 'INV-$year-$month-%' 
				ORDER BY id DESC LIMIT 1");
			
			if($qry->num_rows > 0) {
				$row = $qry->fetch_assoc();
				$last_code = $row['invoice_code'];
				$number = intval(substr($last_code, -4)) + 1;
			} else {
				$number = 1;
			}
			$invoice_code = 'INV-' . $year . '-' . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
		}
		
		foreach($_POST as $k => $v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		
		$data .= ", `created_by`='{$_settings->userdata('id')}' ";
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `invoice_list` WHERE `invoice_code` = ?");
		$stmt->bind_param('s', $invoice_code);
		$stmt->execute();
		$check = $stmt->get_result()->num_rows;
		if($check > 0){
			return json_encode(['status'=>'failed','msg'=>'Invoice Code already exists.']);
			exit;
		}
		
		$sql = "INSERT INTO `invoice_list` SET {$data}";
		$save = $this->conn->query($sql);
		
		if($save){
			$invoice_id = $this->conn->insert_id;
			$resp['status'] = 'success';
			$resp['msg'] = 'Invoice created successfully!';
			$resp['id'] = $invoice_id;
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'An error occurred while saving the invoice.';
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}

	
	
	function save_invoice() {
		try {
			
			if (!isset($_settings)) {
				global $_settings;
			}
			
			
			$id = isset($_POST['id']) ? $_POST['id'] : '';
			$is_update = !empty($id);
			
			
			if (!isset($_POST['customer_id']) || empty($_POST['customer_id'])) {
				return json_encode(['status' => 'failed', 'msg' => 'Customer is required.']);
			}
			
			if (!isset($_POST['invoice_date']) || empty($_POST['invoice_date'])) {
				return json_encode(['status' => 'failed', 'msg' => 'Invoice date is required.']);
			}
			
			if (!isset($_POST['due_date']) || empty($_POST['due_date'])) {
				return json_encode(['status' => 'failed', 'msg' => 'Due date is required.']);
			}
			
			
			$invoice_code = isset($_POST['invoice_code']) ? trim($_POST['invoice_code']) : '';
			if (!$is_update && empty($invoice_code)) {
				$year = date('Y');
				$month = date('m');
				
				$qry = $this->conn->query("SELECT invoice_code FROM invoice_list 
					WHERE invoice_code LIKE 'INV-$year-$month-%' 
					ORDER BY id DESC LIMIT 1");
				
				if ($qry && $qry->num_rows > 0) {
					$row = $qry->fetch_assoc();
					$last_code = $row['invoice_code'];
					$number = intval(substr($last_code, -4)) + 1;
				} else {
					$number = 1;
				}
				$invoice_code = 'INV-' . $year . '-' . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
			}
			
			
			if (!empty($invoice_code)) {
				$check_sql = "SELECT id FROM invoice_list WHERE invoice_code = '" . $this->conn->real_escape_string($invoice_code) . "'";
				if ($is_update) {
					$check_sql .= " AND id != '" . $this->conn->real_escape_string($id) . "'";
				}
				$check = $this->conn->query($check_sql);
				if ($check && $check->num_rows > 0) {
					return json_encode(['status' => 'failed', 'msg' => 'Invoice code already exists.']);
				}
			}
			
			
			$invoice_data = array();
			
			
			$invoice_data['customer_id'] = $this->conn->real_escape_string($_POST['customer_id']);
			$invoice_data['invoice_date'] = $this->conn->real_escape_string($_POST['invoice_date']);
			$invoice_data['due_date'] = $this->conn->real_escape_string($_POST['due_date']);
			
			
			$invoice_data['order_id'] = isset($_POST['order_id']) && !empty($_POST['order_id']) ? $this->conn->real_escape_string($_POST['order_id']) : 'NULL';
			$invoice_data['subtotal'] = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
			$invoice_data['tax_rate'] = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
			$invoice_data['tax_amount'] = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
			$invoice_data['total_amount'] = $invoice_data['subtotal'] + $invoice_data['tax_amount'];
			$invoice_data['paid_amount'] = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
			$invoice_data['balance_amount'] = isset($_POST['balance_amount']) ? floatval($_POST['balance_amount']) : 0;
			$invoice_data['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;
			$invoice_data['payment_method'] = isset($_POST['payment_method']) ? $this->conn->real_escape_string($_POST['payment_method']) : '';
			$invoice_data['notes'] = isset($_POST['notes']) ? $this->conn->real_escape_string($_POST['notes']) : '';
			
			
			if (!empty($invoice_code)) {
				$invoice_data['invoice_code'] = $this->conn->real_escape_string($invoice_code);
			}

			
			if ($invoice_data['balance_amount'] <= 0) {
				$invoice_data['status'] = 1; 
			} else {
				$invoice_data['status'] = isset($_POST['status']) ? intval($_POST['status']) : 0;
			}
			
			
			if ($is_update) {
				
				$sql_parts = array();
				$params = array();
				$types = '';
				
				foreach ($invoice_data as $key => $value) {
					if ($key === 'order_id' && $value === 'NULL') {
						$sql_parts[] = "`{$key}` = NULL";
					} else {
						$sql_parts[] = "`{$key}` = ?";
						$params[] = $value;
						$types .= is_numeric($value) ? 'i' : 's';
					}
				}
				$sql_parts[] = "`date_updated` = NOW()";
				$params[] = $id;
				$types .= 'i';
				
				$sql = "UPDATE `invoice_list` SET " . implode(', ', $sql_parts) . " WHERE `id` = ?";
				$stmt = $this->conn->prepare($sql);
				$stmt->bind_param($types, ...$params);
				$save = $stmt->execute();
			} else {
				
				$invoice_data['created_by'] = $_settings->userdata('id');
				$invoice_data['date_created'] = date('Y-m-d H:i:s');
				
				$columns = array();
				$placeholders = array();
				$params = array();
				$types = '';
				
				foreach ($invoice_data as $key => $value) {
					$columns[] = "`{$key}`";
					if ($key === 'order_id' && $value === 'NULL') {
						$placeholders[] = "NULL";
					} else {
						$placeholders[] = "?";
						$params[] = $value;
						$types .= is_numeric($value) ? 'i' : 's';
					}
				}
				
				$sql = "INSERT INTO `invoice_list` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
				$stmt = $this->conn->prepare($sql);
				if (!empty($params)) {
					$stmt->bind_param($types, ...$params);
				}
				$save = $stmt->execute();
			}
			
			if (!$save) {
				return json_encode([
					'status' => 'failed', 
					'msg' => 'Database error: ' . $this->conn->error,
					'sql' => $sql
				]);
			}
			
			
			$invoice_id = $is_update ? $id : $this->conn->insert_id;
			
			
			$items_saved = 0;
			$items_error = '';
			
			
			if ($is_update) {
				$stmt = $this->conn->prepare("DELETE FROM `invoice_items` WHERE `invoice_id` = ?");
				$stmt->bind_param('i', $invoice_id);
				$stmt->execute();
			}
			
			
			if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
				foreach ($_POST['item_name'] as $k => $item_name) {
					$item_name = trim($item_name);
					if (empty($item_name)) continue;
					
					$item_description = isset($_POST['item_description'][$k]) ? trim($_POST['item_description'][$k]) : '';
					$item_qty = isset($_POST['item_qty'][$k]) ? floatval($_POST['item_qty'][$k]) : 1;
					$item_price = isset($_POST['item_price'][$k]) ? floatval($_POST['item_price'][$k]) : 0;
					$item_total = isset($_POST['item_total'][$k]) ? floatval($_POST['item_total'][$k]) : ($item_qty * $item_price);
					
					$item_sql = "INSERT INTO `invoice_items` SET 
						`invoice_id` = '" . $this->conn->real_escape_string($invoice_id) . "',
						`item_name` = '" . $this->conn->real_escape_string($item_name) . "',
						`description` = '" . $this->conn->real_escape_string($item_description) . "',
						`quantity` = '$item_qty',
						`unit_price` = '$item_price',
						`total_price` = '$item_total'";
					
					if ($this->conn->query($item_sql)) {
						$items_saved++;
					} else {
						$items_error = $this->conn->error;
					}
				}
			}
			
			
			$response = [
				'status' => 'success',
				'msg' => $is_update ? 'Invoice updated successfully!' : 'Invoice created successfully!',
				'id' => $invoice_id,
				'invoice_code' => $invoice_code,
				'items_saved' => $items_saved
			];
			
			if (!empty($items_error)) {
				$response['items_warning'] = 'Some items may not have been saved: ' . $items_error;
			}
			
			return json_encode($response);
			
		} catch (Exception $e) {
			return json_encode([
				'status' => 'failed',
				'msg' => 'System error: ' . $e->getMessage()
			]);
		}
	}

	
	function delete_invoice(){
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        return json_encode(['status' => 'failed', 'msg' => 'Invoice ID is required.']);
    }
    
    $id = $this->conn->real_escape_string($_POST['id']);
    
    
    $stmt = $this->conn->prepare("SELECT id FROM `invoice_list` WHERE `id` = ?"); $stmt->bind_param('i', $id); $stmt->execute(); $check = $stmt->get_result();
    if ($check->num_rows == 0) {
        return json_encode(['status' => 'failed', 'msg' => 'Invoice not found.']);
    }
    
    
    $this->conn->begin_transaction();
    
    try {
        
        $stmt = $this->conn->prepare("DELETE FROM `invoice_items` WHERE `invoice_id` = ?");
        $stmt->bind_param('i', $id);
        $del_items = $stmt->execute();
        if (!$del_items) {
            throw new Exception('Failed to delete invoice items: ' . $this->conn->error);
        }
        
        
        $payment_table_check = $this->conn->query("SHOW TABLES LIKE 'invoice_payments'");
        if ($payment_table_check->num_rows > 0) {
            $stmt = $this->conn->prepare("DELETE FROM `invoice_payments` WHERE `invoice_id` = ?");
            $stmt->bind_param('i', $id);
            $del_payments = $stmt->execute();
            if (!$del_payments) {
                throw new Exception('Failed to delete invoice payments: ' . $this->conn->error);
            }
        }
        
        
        $stmt = $this->conn->prepare("DELETE FROM `invoice_list` WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        $del = $stmt->execute();
        if (!$del) {
            throw new Exception('Failed to delete invoice: ' . $this->conn->error);
        }
        
        
        if ($this->conn->affected_rows == 0) {
            throw new Exception('No invoice was deleted. Invoice may not exist.');
        }
        
        
        $this->conn->commit();
        
        $resp = [
            'status' => 'success',
            'msg' => 'Invoice deleted successfully.'
        ];
        
        
        if (isset($this->settings)) {
            $this->settings->set_flashdata('success', 'Invoice successfully deleted.');
        }
        
    } catch (Exception $e) {
        
        $this->conn->rollback();
        
        $resp = [
            'status' => 'failed',
            'msg' => $e->getMessage(),
            'error' => $this->conn->error
        ];
    }
    
    return json_encode($resp);
}

	
	function get_invoice() {
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid invoice ID.';
			return json_encode($resp);
		}
		$qry = $this->conn->query("SELECT i.*, 
			CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
			c.email as customer_email, c.contact as customer_contact,
			o.code as order_code
			FROM `invoice_list` i 
			LEFT JOIN `customer_list` c ON i.customer_id = c.id 
			LEFT JOIN `order_list` o ON i.order_id = o.id
			WHERE i.id = '$id'");
		
		if($qry->num_rows > 0){
			$invoice = $qry->fetch_assoc();
			
			
			$items_qry = $this->conn->query("SELECT * FROM `invoice_items` WHERE `invoice_id` = '$id'");
			$items = [];
			while($item = $items_qry->fetch_assoc()){
				$items[] = $item;
			}
			$invoice['items'] = $items;
			
			
			$payments_qry = $this->conn->query("SELECT p.*, 
				CONCAT(u.firstname,' ', u.lastname) as recorded_by_name
			FROM `invoice_list` p 
			LEFT JOIN `users` u ON p.created_by = u.id
			WHERE p.id = '$id' 
			ORDER BY p.date_created DESC");
			$payments = [];
			while($payment = $payments_qry->fetch_assoc()){
				$payments[] = $payment;
			}
			$invoice['payments'] = $payments;
			
			$resp['status'] = 'success';
			$resp['data'] = $invoice;
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invoice not found.';
		}
		return json_encode($resp);
	}

	
	function update_status() {
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$status = isset($_POST['status']) ? intval($_POST['status']) : 0;
		
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid invoice ID.';
			return json_encode($resp);
		}
		
		$stmt = $this->conn->prepare("UPDATE `invoice_list` SET `status` = ? WHERE `id` = ?");
		$stmt->bind_param('ii', $status, $id);
		$update = $stmt->execute();
		
		if($update){
			$resp['status'] = 'success';
			$resp['msg'] = 'Status updated successfully!';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'An error occurred while updating status.';
		}
		return json_encode($resp);
	}

	
	function update_overdue_invoices() {
		$today = date('Y-m-d');
		$update = $this->conn->query("UPDATE `invoice_list` SET `status` = 3 
			WHERE `due_date` < '$today' AND `status` IN (0, 2) AND `balance_amount` > 0");
		
		if($update){
			$affected = $this->conn->affected_rows;
			$resp['status'] = 'success';
			$resp['msg'] = "$affected invoices marked as overdue.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'An error occurred while updating overdue invoices.';
		}
		return json_encode($resp);
	}

	
	function generate_report() {
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
		$date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';
		$status = isset($_POST['status']) ? $_POST['status'] : '';
		$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
		
		$where = "WHERE 1=1";
		if(!empty($date_from)) {
			$where .= " AND i.invoice_date >= '$date_from'";
		}
		if(!empty($date_to)) {
			$where .= " AND i.invoice_date <= '$date_to'";
		}
		if(!empty($status) && $status !== 'all') {
			$where .= " AND i.status = '$status'";
		}
		if(!empty($customer_id)) {
			$where .= " AND i.customer_id = '$customer_id'";
		}
		
		$qry = $this->conn->query("SELECT i.*, 
			CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
			c.email as customer_email,
			o.code as order_code
			FROM `invoice_list` i 
			LEFT JOIN `customer_list` c ON i.customer_id = c.id 
			LEFT JOIN `order_list` o ON i.order_id = o.id
			$where
			ORDER BY i.invoice_date DESC");
		
		$invoices = [];
		$total_amount = 0;
		$total_paid = 0;
		$total_balance = 0;
		
		while($row = $qry->fetch_assoc()){
			$invoices[] = $row;
			$total_amount += $row['total_amount'];
			$total_paid += $row['paid_amount'];
			$total_balance += $row['balance_amount'];
		}
		
		$resp['status'] = 'success';
		$resp['data'] = $invoices;
		$resp['summary'] = [
			'total_invoices' => count($invoices),
			'total_amount' => $total_amount,
			'total_paid' => $total_paid,
			'total_balance' => $total_balance
		];
		
		return json_encode($resp);
	}

	
	function bulk_action() {
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$invoice_ids = isset($_POST['invoice_ids']) ? $_POST['invoice_ids'] : array();
		$action = isset($_POST['action']) ? $_POST['action'] : '';
		
		if(empty($invoice_ids) || !is_array($invoice_ids)){
			$resp['status'] = 'failed';
			$resp['msg'] = 'No invoices selected.';
			return json_encode($resp);
		}
		
		$ids = implode(',', array_map('intval', $invoice_ids));
		$placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
		$types = str_repeat('i', count($invoice_ids));
		
		switch($action) {
			case 'delete':
				
				$stmt = $this->conn->prepare("DELETE FROM `invoice_items` WHERE `invoice_id` IN ($placeholders)");
				$stmt->bind_param($types, ...$invoice_ids);
				$stmt->execute();
				
				
				$stmt = $this->conn->prepare("DELETE FROM `invoice_payments` WHERE `invoice_id` IN ($placeholders)");
				$stmt->bind_param($types, ...$invoice_ids);
				$stmt->execute();
				
				
				$stmt = $this->conn->prepare("DELETE FROM `invoice_list` WHERE `id` IN ($placeholders)");
				$stmt->bind_param($types, ...$invoice_ids);
				$delete = $stmt->execute();
				
				if($delete) {
					$resp['status'] = 'success';
					$resp['msg'] = 'Selected invoices deleted successfully!';
				} else {
					$resp['status'] = 'failed';
					$resp['msg'] = 'An error occurred while deleting invoices.';
				}
				break;
				
			case 'mark_paid':
				$update = $this->conn->query("UPDATE `invoice_list` SET 
					`status` = 1, 
					`paid_amount` = `total_amount`, 
					`balance_amount` = 0 
					WHERE `id` IN ($ids)");
				
				if($update) {
					$resp['status'] = 'success';
					$resp['msg'] = 'Selected invoices marked as paid!';
				} else {
					$resp['status'] = 'failed';
					$resp['msg'] = 'An error occurred while updating invoices.';
				}
				break;
				
			case 'mark_cancelled':
				$stmt = $this->conn->prepare("UPDATE `invoice_list` SET `status` = 4 WHERE `id` IN ($placeholders)");
				$stmt->bind_param($types, ...$invoice_ids);
				$update = $stmt->execute();
				
				if($update) {
					$resp['status'] = 'success';
					$resp['msg'] = 'Selected invoices marked as cancelled!';
				} else {
					$resp['status'] = 'failed';
					$resp['msg'] = 'An error occurred while updating invoices.';
				}
				break;
				
			default:
				$resp['status'] = 'failed';
				$resp['msg'] = 'Invalid action specified.';
		}
		
		return json_encode($resp);
	}

	function get_invoice_details() {
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid invoice ID.';
			return json_encode($resp);
		}
	
	$qry = $this->conn->query("SELECT i.*, 
		CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
		c.email as customer_email, 
		
		o.code as order_code,
		CONCAT(u.firstname,' ', u.lastname) as created_by_name
		FROM `invoice_list` i 
		LEFT JOIN `customer_list` c ON i.customer_id = c.id 
		LEFT JOIN `order_list` o ON i.order_id = o.id
		LEFT JOIN `users` u ON i.created_by = u.id
		WHERE i.id = '$id'");
	
	if($qry->num_rows > 0){
		$invoice = $qry->fetch_assoc();
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `invoice_items` WHERE `invoice_id` = ? ORDER BY id"); $stmt->bind_param('i', $id); $stmt->execute(); $items_qry = $stmt->get_result();
		$items = [];
		while($item = $items_qry->fetch_assoc()){
			$items[] = $item;
		}
		
		
		if(empty($items) && !empty($invoice['order_id'])) {
			
			$order_items_qry = $this->conn->query("SELECT 
				COALESCE(product_name, item_name, name, 'Product') as item_name,
				COALESCE(description, '') as description,
				COALESCE(quantity, qty, 1) as quantity,
				COALESCE(price, unit_price, amount, 0) as unit_price,
				COALESCE(total, total_amount, quantity * price, 0) as total_price
				FROM `order_items` 
				WHERE `order_id` = '{$invoice['order_id']}'");
			
			if($order_items_qry && $order_items_qry->num_rows > 0) {
				while($item = $order_items_qry->fetch_assoc()){
					$items[] = $item;
				}
			} else {
				
				$cart_qry = $this->conn->query("SELECT 
					COALESCE(product_name, item_name, name, 'Product') as item_name,
					COALESCE(description, '') as description,
					COALESCE(quantity, qty, 1) as quantity,
					COALESCE(price, unit_price, amount, 0) as unit_price,
					COALESCE(total, total_amount, quantity * price, 0) as total_price
					FROM `cart_list` 
					WHERE `order_id` = '{$invoice['order_id']}'");
				
				if($cart_qry && $cart_qry->num_rows > 0) {
					while($item = $cart_qry->fetch_assoc()){
						$items[] = $item;
					}
				}
			}
		}
		
		
		if(empty($items)) {
			$items[] = [
				'item_name' => 'Invoice Items',
				'description' => 'Items from Order #' . ($invoice['order_code'] ?? $invoice['id']),
				'quantity' => 1,
				'unit_price' => $invoice['total_amount'],
				'total_price' => $invoice['total_amount']
			];
		}
		
		$invoice['items'] = $items;
		
		
		$payments_qry = $this->conn->query("SELECT p.*, 
			CONCAT(u.firstname,' ', u.lastname) as recorded_by_name
			FROM `invoice_list` p 
			LEFT JOIN `users` u ON p.created_by = u.id
			WHERE p.id = '$id' 
			ORDER BY p.date_created DESC");
		$payments = [];
		while($payment = $payments_qry->fetch_assoc()){
			$payments[] = $payment;
		}
		$invoice['payments'] = $payments;
		
		
		echo $this->generate_invoice_html($invoice);
	} else {
		echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Invoice not found.</div>';
	}
}

function get_invoice_print_content() {
	
	require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
	if(!SecurityHelper::validateCsrf()) {
		$resp['status'] = 'failed';
		$resp['msg'] = 'Invalid security token. Please refresh and try again.';
		return json_encode($resp);
	}
	
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if($id <= 0){
		$resp['status'] = 'failed';
		$resp['msg'] = 'Invalid invoice ID.';
		return json_encode($resp);
	}
	
	$qry = $this->conn->query("SELECT i.*, 
		CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
		c.email as customer_email, 
		
		o.code as order_code
		FROM `invoice_list` i 
		LEFT JOIN `customer_list` c ON i.customer_id = c.id 
		LEFT JOIN `order_list` o ON i.order_id = o.id
		WHERE i.id = '$id'");
	
	if($qry->num_rows > 0){
		$invoice = $qry->fetch_assoc();
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `invoice_items` WHERE `invoice_id` = ? ORDER BY id"); $stmt->bind_param('i', $id); $stmt->execute(); $items_qry = $stmt->get_result();
		$items = [];
		while($item = $items_qry->fetch_assoc()){
			$items[] = $item;
		}
		
		if(empty($items) && !empty($invoice['order_id'])) {
			$order_items_qry = $this->conn->query("SELECT 
				COALESCE(product_name, item_name, name, 'Product') as item_name,
				
				COALESCE(quantity, qty, 1) as quantity,
				COALESCE(price, unit_price, amount, 0) as unit_price,
				COALESCE(total, total_amount, quantity * price, 0) as total_price
				FROM `order_items` 
				WHERE `order_id` = '{$invoice['order_id']}'");
			
			if($order_items_qry && $order_items_qry->num_rows > 0) {
				while($item = $order_items_qry->fetch_assoc()){
					$items[] = $item;
				}
			}
		}
		
		if(empty($items)) {
			$items[] = [
				'item_name' => 'Invoice Items',
				
				'quantity' => 1,
				'unit_price' => $invoice['total_amount'],
				'total_price' => $invoice['total_amount']
			];
		}
		
		$invoice['items'] = $items;
		
		
		echo $this->generate_print_html($invoice);
	} else {
		echo '<div class="alert alert-danger">Invoice not found.</div>';
	}
}

private function generate_invoice_html($invoice) {
	$status_class = '';
	$status_text = '';
	switch($invoice['status']) {
		case 0: $status_class = 'badge-warning'; $status_text = 'Pending'; break;
		case 1: $status_class = 'badge-success'; $status_text = 'Paid'; break;
		case 2: $status_class = 'badge-info'; $status_text = 'Partially Paid'; break;
		case 3: $status_class = 'badge-danger'; $status_text = 'Overdue'; break;
		case 4: $status_class = 'badge-dark'; $status_text = 'Cancelled'; break;
		default: $status_class = 'badge-secondary'; $status_text = 'Unknown'; break;
	}
	
	$html = '
	<div class="invoice-container">
		<!-- Header -->
		<div class="row mb-4">
			<div class="col-md-8">
				<h2 class="text-primary mb-1">INVOICE</h2>
				<h4 class="text-muted">' . htmlspecialchars($invoice['invoice_code']) . '</h4>
			</div>
			<div class="col-md-4 text-right">
				<span class="badge ' . $status_class . ' p-2 font-size-14">' . $status_text . '</span>
			</div>
		</div>
		
		
			<div class="col-md-6">
				<div class="card border-0 bg-light">
					<div class="card-body">
						<h5 class="text-primary mb-2">Bill To:</h5>
						<strong>' . htmlspecialchars($invoice['customer_name']) . '</strong><br>';
	
	if(!empty($invoice['customer_address'])) {
		$html .= htmlspecialchars($invoice['customer_address']) . '<br>';
	}
	
	$html .= '
						Email: ' . htmlspecialchars($invoice['customer_email']) . '<br>';
	
	if(!empty($invoice['customer_contact'])) {
		$html .= 'Phone: ' . htmlspecialchars($invoice['customer_contact']);
	}
	
	$html .= '
					</div>
				</div>
			</div>
		</div>
		
		<!-- Invoice Details -->
		<div class="row mb-4">
			<div class="col-md-6">
				<table class="table table-borderless">
					<tr>
						<td><strong>Invoice Date:</strong></td>
						<td>' . date("M d, Y", strtotime($invoice['invoice_date'])) . '</td>
					</tr>
					<tr>
						<td><strong>Due Date:</strong></td>
						<td>' . date("M d, Y", strtotime($invoice['due_date'])) . '</td>
					</tr>';
	
	if(!empty($invoice['order_code'])) {
		$html .= '
					<tr>
						<td><strong>Order Code:</strong></td>
						<td><span class="badge badge-info">' . htmlspecialchars($invoice['order_code']) . '</span></td>
					</tr>';
	}
	
	$html .= '
				</table>
			</div>
			<div class="col-md-6">
				<div class="card border-primary">
					<div class="card-body text-center">
						<h5 class="text-primary mb-2">Amount Due</h5>
						<h3 class="text-dark">₱' . number_format($invoice['balance_amount'], 2) . '</h3>
						<small class="text-muted">Total: ₱' . number_format($invoice['total_amount'], 2) . '</small>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Items Table -->
		<div class="card mb-4">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0">Items</h5>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead class="bg-light">
							<tr>
								<th>Item</th>
								<th class="text-center">Qty</th>
								<th class="text-right">Unit Price</th>
								<th class="text-right">Total</th>
							</tr>
						</thead>
						<tbody>';
	
	foreach($invoice['items'] as $item) {
		$html .= '
							<tr>
								<td>
									<strong>' . htmlspecialchars($item['item_name']) . '</strong>';
		
		$html .= '
								</td>
								<td class="text-center">' . number_format($item['quantity'], 2) . '</td>
								<td class="text-right">₱' . number_format($item['unit_price'], 2) . '</td>
								<td class="text-right">₱' . number_format($item['total_price'], 2) . '</td>
							</tr>';
	}
	
	$html .= '
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
		<!-- Totals -->
		<div class="totals">
		<table>
			<tr>
				<td>Subtotal:</td>
				<td class="text-right">₱' . number_format($invoice['subtotal'] > 0 ? $invoice['subtotal'] : $invoice['total_amount'], 2) . '</td>
			</tr>';

				if($invoice['tax_amount'] > 0) {
					$html .= '
							<tr>
								<td>Tax (' . number_format($invoice['tax_rate'] ?? 0, 2) . '%):</td>
								<td class="text-right">₱' . number_format($invoice['tax_amount'], 2) . '</td>
							</tr>';
				}

				$html .= '
							<tr class="total-line">
								<td><strong>Total:</strong></td>
								<td class="text-right"><strong>₱' . number_format($invoice['total_amount'], 2) . '</strong></td>
							</tr>
							<tr>
								<td>Paid:</td>
								<td class="text-right">₱' . number_format($invoice['paid_amount'], 2) . '</td>
							</tr>
							<tr class="total-line">
								<td><strong>Balance Due:</strong></td>
								<td class="text-right"><strong>₱' . number_format($invoice['balance_amount'], 2) . '</strong></td>
							</tr>
						</table>
					</div>
						</div>';
	
	
	if(!empty($invoice['payments'])) {
		$html .= '
		<div class="card mt-4">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0">Payment History</h5>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead class="bg-light">
							<tr>
								<th>Date</th>
								<th>Amount</th>
								<th>Method</th>
								<th>Reference</th>
								<th>Notes</th>
							</tr>
						</thead>
						<tbody>';
		
		foreach($invoice['payments'] as $payment) {
			$html .= '
							<tr>
								<td>' . date("M d, Y", strtotime($payment['date_created'])) . '</td>
								<td>₱' . number_format($payment['total_amount'], 2) . '</td>
								<td>' . htmlspecialchars($payment['payment_method']) . '</td>
								<td>' . htmlspecialchars($payment['invoice_code']) . '</td>
								<td>' . htmlspecialchars($payment['notes']) . '</td>
							</tr>';
		}
		
		$html .= '
						</tbody>
					</table>
				</div>
			</div>
		</div>';
	}
	
	if(!empty($invoice['notes'])) {
		$html .= '
		<div class="card mt-4">
			<div class="card-header">
				<h5 class="mb-0">Notes</h5>
			</div>
			<div class="card-body">
				' . nl2br(htmlspecialchars($invoice['notes'])) . '
			</div>
		</div>';
	}
	
	$html .= '
	</div>
	
	<style>
	.invoice-container {
		font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
	}
	
	.font-size-14 {
		font-size: 14px !important;
	}
	
	.invoice-container .table td {
		padding: 0.75rem 0.5rem;
	}
	
	.invoice-container .badge {
		font-size: 0.9em;
	}
	</style>';
	
	return $html;
}

private function generate_print_html($invoice) {
	$status_text = '';
	switch($invoice['status']) {
		case 0: $status_text = 'Pending'; break;
		case 1: $status_text = 'Paid'; break;
		case 2: $status_text = 'Partially Paid'; break;
		case 3: $status_text = 'Overdue'; break;
		case 4: $status_text = 'Cancelled'; break;
		default: $status_text = 'Unknown'; break;
	}
	
	$html = '
	<!DOCTYPE html>
	<html>
	<head>
		<title>Invoice ' . htmlspecialchars($invoice['invoice_code']) . '</title>
		<style>
			@media print {
				@page { margin: 0.5in; }
				body { 
					font-family: Arial, sans-serif; 
					font-size: 12px;
					line-height: 1.4;
					color: #000;
				}
			}
			
			body {
				font-family: Arial, sans-serif;
				font-size: 14px;
				line-height: 1.6;
				margin: 20px;
			}
			
			.header {
				text-align: center;
				margin-bottom: 30px;
				border-bottom: 2px solid #000;
				padding-bottom: 10px;
			}
			
			.company-info, .customer-info {
				float: left;
				width: 48%;
			}
			
			.customer-info {
				float: right;
			}
			
			.clearfix::after {
				content: "";
				display: table;
				clear: both;
			}
			
			.invoice-details {
				margin: 20px 0;
				clear: both;
			}
			
			.items-table {
				width: 100%;
				border-collapse: collapse;
				margin: 20px 0;
			}
			
			.items-table th,
			.items-table td {
				border: 1px solid #ddd;
				padding: 8px;
				text-align: left;
			}
			
			.items-table th {
				background-color: #f5f5f5;
				font-weight: bold;
			}
			
			.text-right { text-align: right; }
			.text-center { text-align: center; }
			
			.totals {
				float: right;
				width: 300px;
				margin-top: 20px;
			}
			
			.totals table {
				width: 100%;
				border-collapse: collapse;
			}
			
			.totals td {
				padding: 5px 10px;
				border-bottom: 1px solid #eee;
			}
			
			.total-line {
				border-top: 2px solid #000 !important;
				font-weight: bold;
			}
		</style>
	</head>
	<body>
		<div class="header">
			<h1>INVOICE</h1>
			<h2>' . htmlspecialchars($invoice['invoice_code']) . '</h2>
			<p>Status: ' . $status_text . '</p>
		</div>
		
		
			
			<div class="customer-info">
				<h3>Bill To:</h3>
				<strong>' . htmlspecialchars($invoice['customer_name']) . '</strong><br>
				Email: ' . htmlspecialchars($invoice['customer_email']) . '<br>';
	
	if(!empty($invoice['customer_contact'])) {
		$html .= 'Phone: ' . htmlspecialchars($invoice['customer_contact']) . '<br>';
	}
	
	$html .= '
			</div>
		</div>
		
		<div class="invoice-details clearfix">
			<p><strong>Invoice Date:</strong> ' . date("M d, Y", strtotime($invoice['invoice_date'])) . '</p>
			<p><strong>Due Date:</strong> ' . date("M d, Y", strtotime($invoice['due_date'])) . '</p>';
	
	if(!empty($invoice['order_code'])) {
		$html .= '<p><strong>Order Code:</strong> ' . htmlspecialchars($invoice['order_code']) . '</p>';
	}
	
	$html .= '
		</div>
		
		<table class="items-table">
			<thead>
				<tr>
					<th>Item</th>
					<th class="text-center">Qty</th>
					<th class="text-right">Unit Price</th>
					<th class="text-right">Total</th>
				</tr>
			</thead>
			<tbody>';
	
	foreach($invoice['items'] as $item) {
		$html .= '
				<tr>
					<td>
						<strong>' . htmlspecialchars($item['item_name']) . '</strong>';
		
		$html .= '
					</td>
					<td class="text-center">' . number_format($item['quantity'], 2) . '</td>
					<td class="text-right">₱' . number_format($item['unit_price'], 2) . '</td>
					<td class="text-right">₱' . number_format($item['total_price'], 2) . '</td>
				</tr>';
	}
	
	$html .= '
			</tbody>
		</table>
		
		<div class="totals">
			<table>
				<tr>
					<td>Subtotal:</td>
					<td class="text-right">₱' . number_format($invoice['subtotal'] ?: $invoice['total_amount'], 2) . '</td>
				</tr>';
	
	if($invoice['tax_amount'] > 0) {
		$html .= '
				<tr>
					<td>Tax:</td>
					<td class="text-right">₱' . number_format($invoice['tax_amount'], 2) . '</td>
				</tr>';
	}
	
	$html .= '
				<tr class="total-line">
					<td><strong>Total:</strong></td>
					<td class="text-right"><strong>₱' . number_format($invoice['total_amount'], 2) . '</strong></td>
				</tr>
				<tr>
					<td>Paid:</td>
					<td class="text-right">₱' . number_format($invoice['paid_amount'], 2) . '</td>
				</tr>
				<tr class="total-line">
					<td><strong>Balance Due:</strong></td>
					<td class="text-right"><strong>₱' . number_format($invoice['balance_amount'], 2) . '</strong></td>
				</tr>
			</table>
		</div>
		
		<div style="clear: both; margin-top: 40px;">';
	
	if(!empty($invoice['notes'])) {
		$html .= '
			<h3>Notes:</h3>
			<p>' . nl2br(htmlspecialchars($invoice['notes'])) . '</p>';
	}
	
	$html .= '
			<p style="margin-top: 30px; font-size: 12px; color: #666;">
				Thank you for your business!<br>
				Generated on ' . date("M d, Y H:i:s") . '
			</p>
		</div>
	</body>
	</html>';
	
	return $html;
}

function send_invoice_email() {
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if($id <= 0){
		$resp['status'] = 'failed';
		$resp['msg'] = 'Invalid invoice ID.';
		return json_encode($resp);
	}
	
	
	$qry = $this->conn->query("SELECT i.*, 
		CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
		c.email as customer_email
		FROM `invoice_list` i 
		LEFT JOIN `customer_list` c ON i.customer_id = c.id 
		WHERE i.id = '$id'");
	
	if($qry->num_rows > 0){
		$invoice = $qry->fetch_assoc();
		
		if(empty($invoice['customer_email'])) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Customer email address not found.';
			return json_encode($resp);
		}
		
		
		$items_qry = $this->conn->query("SELECT * FROM `invoice_items` WHERE `invoice_id` = '$id'");
		$items = [];
		while($item = $items_qry->fetch_assoc()){
			$items[] = $item;
		}
		
		
		$subject = "Invoice #{$invoice['invoice_code']} from " . $this->settings->info('name');
		
		$items_html = '';
		foreach($items as $item) {
			$items_html .= '<tr>
				<td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['item_name'] ?? 'Item') . '</td>
				<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">' . number_format($item['quantity'] ?? 1, 2) . '</td>
				<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">₱' . number_format($item['unit_price'] ?? 0, 2) . '</td>
				<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">₱' . number_format($item['total_price'] ?? 0, 2) . '</td>
			</tr>';
		}
		
		$status_text = 'Pending';
		$status_color = '#ffc107';
		switch($invoice['status']) {
			case 1: $status_text = 'Paid'; $status_color = '#28a745'; break;
			case 2: $status_text = 'Partially Paid'; $status_color = '#17a2b8'; break;
			case 3: $status_text = 'Overdue'; $status_color = '#dc3545'; break;
		}
		
		$body = '
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<div style="background: linear-gradient(135deg, #00b09b, #96c93d); padding: 20px; text-align: center;">
				<h1 style="color: white; margin: 0;">Invoice</h1>
			</div>
			<div style="padding: 20px; background: #f9f9f9;">
				<h2 style="color: #333;">Invoice #' . htmlspecialchars($invoice['invoice_code']) . '</h2>
				<p>Dear ' . htmlspecialchars($invoice['customer_name']) . ',</p>
				<p>Please find your invoice details below:</p>
				
				<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
					<tr>
						<td style="padding: 5px;"><strong>Invoice Date:</strong></td>
						<td style="padding: 5px;">' . date('F d, Y', strtotime($invoice['invoice_date'])) . '</td>
					</tr>
					<tr>
						<td style="padding: 5px;"><strong>Due Date:</strong></td>
						<td style="padding: 5px;">' . date('F d, Y', strtotime($invoice['due_date'])) . '</td>
					</tr>
					<tr>
						<td style="padding: 5px;"><strong>Status:</strong></td>
						<td style="padding: 5px;"><span style="background: ' . $status_color . '; color: white; padding: 3px 10px; border-radius: 3px;">' . $status_text . '</span></td>
					</tr>
				</table>
				
				<h3 style="color: #333; border-bottom: 2px solid #00b09b; padding-bottom: 10px;">Items</h3>
				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background: #00b09b; color: white;">
							<th style="padding: 10px; text-align: left;">Item</th>
							<th style="padding: 10px; text-align: center;">Qty</th>
							<th style="padding: 10px; text-align: right;">Price</th>
							<th style="padding: 10px; text-align: right;">Total</th>
						</tr>
					</thead>
					<tbody>
						' . $items_html . '
					</tbody>
				</table>
				
				<div style="margin-top: 20px; text-align: right;">
					<p><strong>Subtotal:</strong> ₱' . number_format($invoice['subtotal'] ?? $invoice['total_amount'], 2) . '</p>
					<p style="font-size: 18px; color: #00b09b;"><strong>Total Amount:</strong> ₱' . number_format($invoice['total_amount'], 2) . '</p>
					<p><strong>Paid:</strong> ₱' . number_format($invoice['paid_amount'] ?? 0, 2) . '</p>
					<p style="font-size: 18px; color: #dc3545;"><strong>Balance Due:</strong> ₱' . number_format($invoice['balance_amount'] ?? 0, 2) . '</p>
				</div>
				
				<p style="margin-top: 30px; color: #666;">Thank you for your business!</p>
				<p style="color: #666;">' . $this->settings->info('name') . '</p>
			</div>
		</div>';
		
		
		try {
			require_once(__DIR__ . '/../customer/classes/CustomerEmailService.php');
			$emailService = new CustomerEmailService($this->conn);
			$result = $emailService->sendEmail($invoice['customer_email'], $subject, $body, $invoice['customer_name']);
			
			if($result) {
				$resp['status'] = 'success';
				$resp['msg'] = 'Invoice email sent successfully to ' . $invoice['customer_email'];
			} else {
				$resp['status'] = 'failed';
				$resp['msg'] = 'Failed to send email. Please check your SMTP configuration.';
			}
		} catch(Exception $e) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Email error: ' . $e->getMessage();
		}
	} else {
		$resp['status'] = 'failed';
		$resp['msg'] = 'Invoice not found.';
	}
	
	return json_encode($resp);
}

function get_products() {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    
    if(empty($search)) {
        return json_encode(['status' => 'failed', 'msg' => 'Search term is required']);
    }
    
    $search = $this->conn->real_escape_string($search);
    
    $products = array();
    
    
    $table_check = $this->conn->query("SHOW TABLES LIKE 'product_list'");
    if($table_check && $table_check->num_rows > 0) {
        
        $columns_qry = $this->conn->query("SHOW COLUMNS FROM product_list");
        $columns = array();
        while($col = $columns_qry->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        
        $name_col = 'name';
        if(in_array('product_name', $columns)) {
            $name_col = 'product_name';
        } elseif(in_array('title', $columns)) {
            $name_col = 'title';
        }
        
        $price_col = 'price';
        if(in_array('selling_price', $columns)) {
            $price_col = 'selling_price';
        } elseif(in_array('unit_price', $columns)) {
            $price_col = 'unit_price';
        }
        
        
        $select_parts = array(
            "id",
            "$name_col as product_name",
            "$price_col as price"
        );
        
        
        if(in_array('description', $columns)) {
            $select_parts[] = "description";
        }
        if(in_array('sku', $columns)) {
            $select_parts[] = "sku";
        }
        if(in_array('stock', $columns)) {
            $select_parts[] = "stock";
        }
        if(in_array('status', $columns)) {
            $select_parts[] = "status";
        }
        if(in_array('brand', $columns)) {
            $select_parts[] = "brand";
        } else {
            $select_parts[] = "'' as brand";
        }
        
        if(in_array('unit', $columns)) {
            $select_parts[] = "unit";
        } elseif(in_array('unit_of_measure', $columns)) {
            $select_parts[] = "unit_of_measure as unit";
        } elseif(in_array('uom', $columns)) {
            $select_parts[] = "uom as unit";
        } else {
            $select_parts[] = "'pc' as unit";
        }
        
        $select_sql = implode(', ', $select_parts);
        
        
        $where_conditions = array("$name_col LIKE '%$search%'");
        if(in_array('sku', $columns)) {
            $where_conditions[] = "sku LIKE '%$search%'";
        }
        if(in_array('description', $columns)) {
            $where_conditions[] = "description LIKE '%$search%'";
        }
        if(in_array('brand', $columns)) {
            $where_conditions[] = "brand LIKE '%$search%'";
        }
        
        $where_sql = "(" . implode(' OR ', $where_conditions) . ")";
        
        
        if(in_array('status', $columns)) {
            $where_sql .= " AND status = 1";
        }
        
        $qry = $this->conn->query("SELECT $select_sql FROM product_list WHERE $where_sql ORDER BY brand, $name_col ASC LIMIT 15");
        
        if($qry && $qry->num_rows > 0) {
            while($row = $qry->fetch_assoc()) {
                $products[] = array(
                    'id' => $row['id'],
                    'product_name' => $row['product_name'],
                    'brand' => $row['brand'] ?? '',
                    'price' => floatval($row['price'] ?? 0),
                    'description' => $row['description'] ?? '',
                    'sku' => $row['sku'] ?? '',
                    'stock' => isset($row['stock']) ? intval($row['stock']) : null,
                    'status' => isset($row['status']) ? intval($row['status']) : 1,
                    'unit' => $row['unit'] ?? 'pc'
                );
            }
        }
    }
    
    
    if(empty($products)) {
        $item_table_check = $this->conn->query("SHOW TABLES LIKE 'items'");
        if($item_table_check && $item_table_check->num_rows > 0) {
            $item_columns_qry = $this->conn->query("SHOW COLUMNS FROM items");
            $item_columns = array();
            while($col = $item_columns_qry->fetch_assoc()) {
                $item_columns[] = $col['Field'];
            }
            
            $brand_select = "'' as brand";
            if(in_array('brand', $item_columns)) {
                $brand_select = "COALESCE(brand, '') as brand";
            }
            
            $unit_select = "'pc' as unit";
            if(in_array('unit', $item_columns)) {
                $unit_select = "COALESCE(unit, 'pc') as unit";
            }
            
            $qry = $this->conn->query("SELECT id, name as product_name, price, description, $brand_select, $unit_select
                FROM items 
                WHERE (name LIKE '%$search%' OR description LIKE '%$search%'" . 
                (in_array('brand', $item_columns) ? " OR brand LIKE '%$search%'" : "") . ") 
                AND status = 1 
                ORDER BY " . (in_array('brand', $item_columns) ? "brand, " : "") . "name ASC LIMIT 15");
            
            if($qry && $qry->num_rows > 0) {
                while($row = $qry->fetch_assoc()) {
                    $products[] = array(
                        'id' => $row['id'],
                        'product_name' => $row['product_name'],
                        'brand' => $row['brand'] ?? '',
                        'price' => floatval($row['price'] ?? 0),
                        'description' => $row['description'] ?? '',
                        'sku' => '',
                        'stock' => null,
                        'status' => 1,
                        'unit' => $row['unit'] ?? 'pc'
                    );
                }
            }
        }
    }
    
    if(!empty($products)) {
        return json_encode(array(
            'status' => 'success',
            'products' => $products
        ));
    } else {
        return json_encode(array(
            'status' => 'success',
            'products' => array(),
            'msg' => 'No products found matching your search'
        ));
    }
}

function get_product_details() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if($product_id <= 0) {
        return json_encode(['status' => 'failed', 'msg' => 'Product ID is required']);
    }
    
    
    $table_check = $this->conn->query("SHOW TABLES LIKE 'product_list'");
    if($table_check && $table_check->num_rows > 0) {
        $columns_qry = $this->conn->query("SHOW COLUMNS FROM product_list");
        $columns = array();
        while($col = $columns_qry->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        $name_col = 'name';
        if(in_array('product_name', $columns)) {
            $name_col = 'product_name';
        } elseif(in_array('title', $columns)) {
            $name_col = 'title';
        }
        
        $price_col = 'price';
        if(in_array('selling_price', $columns)) {
            $price_col = 'selling_price';
        } elseif(in_array('unit_price', $columns)) {
            $price_col = 'unit_price';
        }
        
        $select_parts = array(
            "id",
            "$name_col as product_name",
            "$price_col as price"
        );
        
        
        if(in_array('description', $columns)) {
            $select_parts[] = "description";
        }
        if(in_array('sku', $columns)) {
            $select_parts[] = "sku";
        }
        if(in_array('stock', $columns)) {
            $select_parts[] = "stock";
        }
        if(in_array('category_id', $columns)) {
            $select_parts[] = "category_id";
        }
        if(in_array('brand', $columns)) {
            $select_parts[] = "brand";
        } else {
            $select_parts[] = "'' as brand";
        }
        if(in_array('unit', $columns)) {
            $select_parts[] = "unit";
        } else {
            $select_parts[] = "'pc' as unit";
        }
        
        $select_sql = implode(', ', $select_parts);
        
        $qry = $this->conn->query("SELECT $select_sql FROM product_list WHERE id = '$product_id'");
        
        if($qry && $qry->num_rows > 0) {
            $product = $qry->fetch_assoc();
            
            
            if(isset($product['category_id']) && !empty($product['category_id'])) {
                $cat_qry = $this->conn->query("SELECT name FROM category_list WHERE id = '{$product['category_id']}'");
                if($cat_qry && $cat_qry->num_rows > 0) {
                    $cat = $cat_qry->fetch_assoc();
                    $product['category_name'] = $cat['name'];
                }
            }
            
            return json_encode(array(
                'status' => 'success',
                'product' => array(
                    'id' => $product['id'],
                    'product_name' => $product['product_name'],
                    'brand' => $product['brand'] ?? '',
                    'price' => floatval($product['price'] ?? 0),
                    'description' => $product['description'] ?? '',
                    'sku' => $product['sku'] ?? '',
                    'stock' => isset($product['stock']) ? intval($product['stock']) : null,
                    'category_name' => $product['category_name'] ?? '',
                    'unit' => $product['unit'] ?? 'pc'
                )
            ));
        }
    }
    
    
    return json_encode(['status' => 'failed', 'msg' => 'Product not found']);
}

function get_customer_orders() {
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    
    if($customer_id <= 0) {
        return json_encode(['status' => 'failed', 'msg' => 'Customer ID is required']);
    }
    
    $orders = array();
    
    
    
    $pending_status = OrderStatus::PENDING;
    $packed_status = OrderStatus::PACKED;
    $ready_status = OrderStatus::READY_FOR_PICKUP;
    $completed_status = OrderStatus::COMPLETED;
    $cancelled_status = OrderStatus::CANCELLED;
    
    $qry = $this->conn->query("SELECT 
        o.id, 
        o.code, 
        o.customer_id, 
        o.total_amount,
        o.status,
        o.date_created,
        CASE 
            WHEN o.status = {$pending_status} THEN 'Pending'
            WHEN o.status = {$packed_status} THEN 'Processing'
            WHEN o.status = {$ready_status} THEN 'Ready for Pickup'
            WHEN o.status = {$completed_status} THEN 'Done'
            WHEN o.status = {$cancelled_status} THEN 'Cancelled'
            ELSE 'Unknown'
        END as status_text
        FROM order_list o 
        WHERE o.customer_id = '$customer_id' 
        AND o.status != {$completed_status}
        ORDER BY o.date_created DESC");
    
    if($qry && $qry->num_rows > 0) {
        while($row = $qry->fetch_assoc()) {
            $orders[] = array(
                'id' => $row['id'],
                'code' => $row['code'],
                'customer_id' => $row['customer_id'],
                'total_amount' => floatval($row['total_amount']),
                'status' => intval($row['status']),
                'status_text' => $row['status_text'],
                'date_created' => $row['date_created']
            );
        }
        
        return json_encode(array(
            'status' => 'success',
            'orders' => $orders,
            'count' => count($orders)
        ));
    } else {
        return json_encode(array(
            'status' => 'success',
            'orders' => array(),
            'count' => 0,
            'msg' => 'No orders found for this customer'
        ));
    }
}
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'Invoices.php') {
    $Invoices = new Invoices();
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
    $sysset = new SystemSettings();
    
    
    $html_actions = array('get_invoice_details', 'get_invoice_print_content');
    if(!in_array($action, $html_actions)) {
        header('Content-Type: application/json');
    }

    switch ($action) {
	case 'generate_invoice_code':
		echo $Invoices->generate_invoice_code();
		break;
	case 'create_from_order':
		echo $Invoices->create_from_order();
		break;
	case 'save_invoice':
		echo $Invoices->save_invoice();
		break;
	case 'get_order_items':
		echo $Invoices->get_order_items();
		break;
	case 'delete_invoice':
		echo $Invoices->delete_invoice();
		break;
	case 'record_payment':
		echo $Invoices->record_payment();
		break;
	case 'get_invoice':
		echo $Invoices->get_invoice();
		break;
	case 'update_status':
		echo $Invoices->update_status();
		break;
	case 'update_overdue_invoices':
		echo $Invoices->update_overdue_invoices();
		break;
	case 'generate_report':
		echo $Invoices->generate_report();
		break;
	case 'bulk_action':
		echo $Invoices->bulk_action();
		break;
	case 'get_invoice_details':
		echo $Invoices->get_invoice_details();
		break;
	case 'get_invoice_print_content':
		echo $Invoices->get_invoice_print_content();
		break;
	case 'send_invoice_email':
		echo $Invoices->send_invoice_email();
		break;
	case 'get_products':
        echo $Invoices->get_products();
        break;
    case 'get_product_details':
        echo $Invoices->get_product_details();
        break;
	case 'get_brands': 
        echo $Invoices->get_brands();
        break;
	case 'get_customer_orders':
    echo $Invoices->get_customer_orders();
    break;
	default:
		break;
    }
    exit;
}

