<?php

if (!defined('base_app')) {
    require_once('../config.php');
}
require_once(__DIR__ . '/OrderConstants.php');

Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = 'A database error occurred. Please try again.';
			error_log('DB Error: ' . $this->conn->error);
			return json_encode($resp);
			exit;
		}
	}
	function delete_img(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['error'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		
		$path = isset($_POST['path']) ? $_POST['path'] : '';
		
		if(empty($path)){
			$resp['status'] = 'failed';
			$resp['error'] = 'No file path provided';
			return json_encode($resp);
		}
		
		
		$path = realpath($path);
		if($path === false || strpos($path, realpath(__DIR__ . '/..')) !== 0){
			$resp['status'] = 'failed';
			$resp['error'] = 'Invalid file path';
			return json_encode($resp);
		}
		
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'Failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unknown '.$path.' path';
		}
		return json_encode($resp);
	}
	function save_category(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		$description = isset($_POST['description']) ? addslashes(htmlspecialchars(trim($_POST['description']))) : '';
		$status = isset($_POST['status']) ? intval($_POST['status']) : 1;
		
		
		if(empty($name)){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category name is required.";
			return json_encode($resp);
		}
		
		
		$data = array(
			'name' => $this->conn->real_escape_string($name),
			'description' => $this->conn->real_escape_string($description),
			'status' => $status
		);
		
		
		$data_str = "";
		foreach($data as $k => $v){
			if(!empty($data_str)) $data_str .= ", ";
			$data_str .= "`{$k}`='{$v}'";
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `category_list` WHERE `name` = ? AND delete_flag = 0" . ($id > 0 ? " AND id != ?" : ""));
		if($id > 0){
			$stmt->bind_param('si', $data['name'], $id);
		}else{
			$stmt->bind_param('s', $data['name']);
		}
		$stmt->execute();
		$check = $stmt->get_result()->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exists.";
			return json_encode($resp);
		}
		
		
		if($id == 0){
            $sql = "INSERT INTO `category_list` SET {$data_str}";
            $save = $this->conn->query($sql);
		}else{
            $sql = "UPDATE `category_list` SET {$data_str} WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $save = $stmt->execute();
        }
        if($this->capture_err())
            return $this->capture_err();
        if($save){
            $cid = $id > 0 ? $id : $this->conn->insert_id;
            $resp['cid'] = $cid;
            $resp['status'] = 'success';
            $resp['msg'] = $id > 0 ? "Category successfully updated." : "New Category successfully saved.";
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        if($resp['status'] == 'success')
            $this->settings->set_flashdata('success',$resp['msg']);
        return json_encode($resp);
    }
	function delete_category(){
		$resp = array('status' => 'failed', 'msg' => '', 'error' => '');
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf(false)) {
			$resp['msg'] = $resp['error'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		if($id <= 0){
			$resp['msg'] = $resp['error'] = 'Invalid category ID';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `category_list` WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows == 0){
			$resp['msg'] = $resp['error'] = 'Category not found';
			return json_encode($resp);
		}
		
		$category = $result->fetch_assoc();
		
		
		$this->log_deletion('category_list', $id, $category);
		
		
		$col_check = $this->conn->query("SHOW COLUMNS FROM `category_list` LIKE 'delete_flag'");
		if(!$col_check || $col_check->num_rows == 0){
			@$this->conn->query("ALTER TABLE `category_list` ADD COLUMN `delete_flag` TINYINT(1) NOT NULL DEFAULT 0");
		}
		$stmt = $this->conn->prepare("UPDATE `category_list` SET `delete_flag` = 1 WHERE id = ?");
		$stmt->bind_param('i', $id);
		$del = $stmt->execute();
		
		if($del){
			$resp['status'] = 'success';
			$resp['msg'] = 'Category successfully deleted.';
			$this->settings->set_flashdata('success'," Category successfully deleted.");
		}else{
			$resp['msg'] = $resp['error'] = $this->conn->error ?: 'Failed to delete category.';
		}
		return json_encode($resp);

	}

	function save_product(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        
        $required_fields = ['brand', 'name', 'dose', 'category_id', 'description', 'price', 'unit'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $resp['status'] = 'failed';
                $resp['msg'] = "Field '{$field}' is required";
                return json_encode($resp);
            }
        }
        
        
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $brand = $this->conn->real_escape_string(trim($_POST['brand']));
        $name = $this->conn->real_escape_string(trim($_POST['name']));
        $dose = $this->conn->real_escape_string(trim($_POST['dose']));
        $category_id = (int)$_POST['category_id'];
        $description = $this->conn->real_escape_string(trim($_POST['description']));
        $price = (float)$_POST['price'];
        $unit = $this->conn->real_escape_string(trim($_POST['unit']));
        
        
        $form_id = null;
        if(isset($_POST['form_id']) && $_POST['form_id'] !== '' && $_POST['form_id'] !== '0') {
            $form_id = (int)$_POST['form_id'];
            
            $form_check = $this->conn->query("SELECT id FROM `form_list` WHERE id = {$form_id} AND delete_flag = 0");
            if(!$form_check || $form_check->num_rows == 0) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Selected form does not exist or is not available';
                return json_encode($resp);
            }
        }
        
        
        $category_check = $this->conn->query("SELECT id FROM `category_list` WHERE id = {$category_id} AND delete_flag = 0");
        if(!$category_check || $category_check->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Selected category does not exist';
            return json_encode($resp);
        }
        
        
        $duplicate_check = "SELECT id FROM `product_list` WHERE `brand` = '{$brand}' AND `name` = '{$name}' AND delete_flag = 0";
        if(!empty($id)) {
            $id = (int)$id;
            $duplicate_check .= " AND id != {$id}";
        }
        
        $check_result = $this->conn->query($duplicate_check);
        if($check_result && $check_result->num_rows > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Product '{$brand} - {$name}' already exists";
            return json_encode($resp);
        }
        
        
        $is_prescription = isset($_POST['is_prescription']) ? (int)$_POST['is_prescription'] : 0;
        $fda_approved = isset($_POST['fda_approved']) ? (int)$_POST['fda_approved'] : 1;
        
        
        if(empty($id)) {
            
            $sql = "INSERT INTO `product_list` 
                    (`brand`, `name`, `dose`, `category_id`, `description`, `price`, `unit`, `form_id`, `is_prescription`, `fda_approved`, `date_created`) 
                    VALUES ('{$brand}', '{$name}', '{$dose}', {$category_id}, '{$description}', {$price}, '{$unit}', " . 
                    ($form_id ? $form_id : 'NULL') . ", {$is_prescription}, {$fda_approved}, NOW())";
        } else {
            
            $sql = "UPDATE `product_list` SET 
                    `brand` = '{$brand}',
                    `name` = '{$name}',
                    `dose` = '{$dose}',
                    `category_id` = {$category_id},
                    `description` = '{$description}',
                    `price` = {$price},
                    `unit` = '{$unit}',
                    `form_id` = " . ($form_id ? $form_id : 'NULL') . ",
                    `is_prescription` = {$is_prescription},
                    `fda_approved` = {$fda_approved}
                    WHERE `id` = {$id}";
        }
        
        
        $save = $this->conn->query($sql);
        
        if(!$save) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
            return json_encode($resp);
        }
        
        
        $pid = !empty($id) ? $id : $this->conn->insert_id;
        
        $resp['status'] = 'success';
        $resp['pid'] = $pid;
        $resp['msg'] = empty($id) ? 'Product has been added successfully' : 'Product has been updated successfully';

        
        if(isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
            
            $upload_dir = '../uploads/products';
            
            
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if(is_dir($upload_dir)) {
                $file_info = pathinfo($_FILES['img']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if(!in_array($file_ext, $allowed_types)) {
                    $resp['msg'] .= ' (Image not uploaded: Invalid file type)';
                } elseif($_FILES['img']['size'] > 5 * 1024 * 1024) {
                    $resp['msg'] .= ' (Image not uploaded: File too large)';
                } else {
                    
                    $new_filename = 'product_' . $pid . '_' . time() . '.' . $file_ext;
                    $upload_path = $upload_dir . '/' . $new_filename;
                    
                    
                    if(!empty($id)) {
                        $old_img_query = $this->conn->query("SELECT image_path FROM product_list WHERE id = {$pid}");
                        if($old_img_query && $old_img_query->num_rows > 0) {
                            $old_img = $old_img_query->fetch_assoc()['image_path'];
                            if(!empty($old_img)) {
                                
                                $old_img_clean = preg_replace('/\?v=\d+$/', '', $old_img);
                                
                                $old_img_full = '../' . $old_img_clean;
                                if(file_exists($old_img_full)) {
                                    @unlink($old_img_full);
                                }
                            }
                        }
                    }
                    
                    
                    if(move_uploaded_file($_FILES['img']['tmp_name'], $upload_path)) {
                        
                        $image_path_db = 'uploads/products/' . $new_filename;
                        $image_path_db_escaped = $this->conn->real_escape_string($image_path_db);
                        
                        
                        $stmt = $this->conn->prepare("UPDATE product_list SET image_path = ? WHERE id = ?"); $stmt->bind_param('si', $image_path_db_escaped, $pid); $img_update = $stmt->execute();
                        
                        if($img_update) {
                            $resp['msg'] .= ' with image';
                        } else {
                            $resp['msg'] .= ' (Image uploaded but database update failed)';
                            error_log("Image update failed: " . $this->conn->error);
                        }
                    } else {
                        $resp['msg'] .= ' (Image upload failed)';
                        error_log("Failed to move uploaded file to: " . $upload_path);
                    }
                }
            } else {
                $resp['msg'] .= ' (Upload directory not accessible)';
            }
        }
        
    } catch(Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    if($resp['status'] == 'success' && method_exists($this->settings, 'set_flashdata')) {
        $this->settings->set_flashdata('success', $resp['msg']);
    }
    
    return json_encode($resp);
}

function get_product_forms(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if(empty($product_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Product ID is required';
            return json_encode($resp);
        }
        
        $product_id = (int)$product_id;
        
        
        $query = "SELECT f.*, p.form_id
                  FROM `product_list` p 
                  LEFT JOIN `form_list` f ON p.form_id = f.id 
                  WHERE p.id = {$product_id} 
                  AND p.delete_flag = 0
                  AND (f.id IS NULL OR f.delete_flag = 0)";
        
        $qry = $this->conn->query($query);
        
        if($qry) {
            $forms = array();
            if($qry->num_rows > 0) {
                $row = $qry->fetch_assoc();
                
                if($row['form_id'] && $row['id']) {
                    $forms[] = $row;
                }
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $forms;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

	function delete_product(){
		$resp = array('status' => 'failed', 'msg' => '');
		try {
			require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
			if(!SecurityHelper::validateCsrf(false)) {
				$resp['msg'] = 'Invalid security token. Please refresh and try again.';
				return json_encode($resp);
			}
			$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			if($id <= 0){
				$resp['msg'] = 'Invalid product ID';
				return json_encode($resp);
			}
			$stmt = $this->conn->prepare("SELECT * FROM `product_list` WHERE id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows == 0){
				$resp['msg'] = 'Product not found';
				return json_encode($resp);
			}
			$product = $result->fetch_assoc();
			$this->log_deletion('product_list', $id, $product);
			
			$col_check = $this->conn->query("SHOW COLUMNS FROM `product_list` LIKE 'delete_flag'");
			if(!$col_check || $col_check->num_rows == 0){
				@$this->conn->query("ALTER TABLE `product_list` ADD COLUMN `delete_flag` TINYINT(1) NOT NULL DEFAULT 0");
			}
			$stmt = $this->conn->prepare("UPDATE `product_list` SET `delete_flag` = 1 WHERE id = ?");
			$stmt->bind_param('i', $id);
			$del = $stmt->execute();
			if($del){
				$resp['status'] = 'success';
				$resp['msg'] = 'Product successfully deleted.';
				$this->settings->set_flashdata('success', 'Product successfully deleted.');
			} else {
				$resp['msg'] = $this->conn->error ?: 'Failed to delete product.';
			}
		} catch (Throwable $e) {
			$resp['msg'] = 'Server error: ' . $e->getMessage();
		}
		return json_encode($resp);
	}
	function save_stock(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
        $expiration = isset($_POST['expiration']) && !empty($_POST['expiration']) ? $_POST['expiration'] : null;
        
        
        if(empty($product_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Product ID is required';
            return json_encode($resp);
        }
        
        if(empty($code)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Stock code is required';
            return json_encode($resp);
        }
        
        if($quantity <= 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Valid quantity is required';
            return json_encode($resp);
        }
        
        
        $product_id = (int)$product_id;
        $code = $this->conn->real_escape_string(trim($code));
        $quantity = (float)$quantity;
        
        
        $expiration = null;
        if(isset($_POST['expiration']) && !empty($_POST['expiration'])) {
            $expiration = $this->conn->real_escape_string($_POST['expiration']);
        }
        
        
        $product_check = $this->conn->query("SELECT id, name, brand FROM `product_list` WHERE id = {$product_id} AND delete_flag = 0");
        if(!$product_check || $product_check->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Product not found';
            return json_encode($resp);
        }
        
        $product_data = $product_check->fetch_assoc();
        
        
        $duplicate_check = "SELECT id FROM `stock_list` WHERE `code` = '{$code}'";
        if(!empty($id)) {
            $id = (int)$id;
            $duplicate_check .= " AND id != {$id}";
        }
        
        $check_result = $this->conn->query($duplicate_check);
        if($check_result && $check_result->num_rows > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Stock code '{$code}' already exists";
            return json_encode($resp);
        }
        
        
        if(empty($id)) {
            
            $sql = "INSERT INTO `stock_list` 
                    (`product_id`, `code`, `quantity`, `expiration`, `date_created`) 
                    VALUES ({$product_id}, '{$code}', {$quantity}, " . 
                    ($expiration ? "'{$expiration}'" : 'NULL') . ", NOW())";
            $success_msg = "New stock entry added for {$product_data['name']}";
        } else {
            
            $sql = "UPDATE `stock_list` SET 
                    `code` = '{$code}',
                    `quantity` = {$quantity},
                    `expiration` = " . ($expiration ? "'{$expiration}'" : 'NULL') . ",
                    `date_updated` = NOW()
                    WHERE `id` = {$id}";
            $success_msg = "Stock entry successfully updated";
        }
        
        
        $save = $this->conn->query($sql);
        
        if(!$save) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
            return json_encode($resp);
        }
        
        $sid = !empty($id) ? $id : $this->conn->insert_id;
        
        $resp['status'] = 'success';
        $resp['sid'] = $sid;
        $resp['msg'] = $success_msg;
        
        if(method_exists($this->settings, 'set_flashdata')) {
            $this->settings->set_flashdata('success', $resp['msg']);
        }
        
    } catch(Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_stock_by_branch() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if(empty($branch_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        
        
        $query = "SELECT s.*, p.brand, p.name, p.dose, p.unit, p.image_path,
                  b.branch_name, b.branch_address
                  FROM `stock_list` s
                  INNER JOIN `product_list` p ON s.product_id = p.id
                  INNER JOIN `branch_list` b ON s.branch_id = b.id
                  WHERE s.branch_id = {$branch_id}
                  AND p.delete_flag = 0
                  AND (s.expiration IS NULL OR s.expiration > CURDATE())
                  ORDER BY p.brand ASC, p.name ASC, s.date_created DESC";
        
        $qry = $this->conn->query($query);
        
        if($qry) {
            $stocks = array();
            while($row = $qry->fetch_assoc()) {
                $stocks[] = $row;
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $stocks;
            $resp['count'] = count($stocks);
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_inventory_by_branch() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if(empty($branch_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        
        
        $query = "SELECT p.*, 
                  b.branch_name,
                  COALESCE(SUM(s.quantity), 0) as total_stock,
                  COALESCE((SELECT SUM(oi.quantity) 
                           FROM order_items oi 
                           INNER JOIN order_list ol ON oi.order_id = ol.id
                           WHERE oi.product_id = p.id 
                           AND ol.branch_id = {$branch_id}), 0) as total_orders,
                  (COALESCE(SUM(s.quantity), 0) - 
                   COALESCE((SELECT SUM(oi.quantity) 
                            FROM order_items oi 
                            INNER JOIN order_list ol ON oi.order_id = ol.id
                            WHERE oi.product_id = p.id 
                            AND ol.branch_id = {$branch_id}), 0)) as available
                  FROM `product_list` p
                  INNER JOIN `branch_list` b ON b.id = {$branch_id}
                  LEFT JOIN `stock_list` s ON p.id = s.product_id 
                      AND s.branch_id = {$branch_id}
                      AND (s.expiration IS NULL OR s.expiration > CURDATE())
                  WHERE p.delete_flag = 0
                  AND b.status = 1
                  GROUP BY p.id, b.id
                  HAVING total_stock > 0
                  ORDER BY available ASC, p.brand ASC, p.name ASC";
        
        $qry = $this->conn->query($query);
        
        if($qry) {
            $inventory = array();
            while($row = $qry->fetch_assoc()) {
                $inventory[] = $row;
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $inventory;
            $resp['count'] = count($inventory);
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_branch_stock_stats() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if(empty($branch_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        
        
        $branch_query = $this->conn->query("SELECT * FROM `branch_list` WHERE id = {$branch_id}");
        if(!$branch_query || $branch_query->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Branch not found';
            return json_encode($resp);
        }
        
        $branch = $branch_query->fetch_assoc();
        
        
        $total_entries = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM `stock_list` 
            WHERE branch_id = {$branch_id}
        ");
        
        
        $active_stock = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM `stock_list` 
            WHERE branch_id = {$branch_id}
            AND (expiration IS NULL OR expiration > CURDATE())
        ");
        
        
        $expired_stock = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM `stock_list` 
            WHERE branch_id = {$branch_id}
            AND expiration IS NOT NULL 
            AND expiration <= CURDATE()
        ");
        
        
        $products_with_stock = $this->conn->query("
            SELECT COUNT(DISTINCT product_id) as count 
            FROM `stock_list` 
            WHERE branch_id = {$branch_id}
            AND (expiration IS NULL OR expiration > CURDATE())
        ");
        
        
        $low_stock = $this->conn->query("
            SELECT COUNT(*) as count FROM (
                SELECT p.id,
                       (COALESCE(SUM(s.quantity), 0) - 
                        COALESCE((SELECT SUM(oi.quantity) 
                                 FROM order_items oi 
                                 INNER JOIN order_list ol ON oi.order_id = ol.id
                                 WHERE oi.product_id = p.id 
                                 AND ol.branch_id = {$branch_id}), 0)) as available
                FROM `product_list` p
                LEFT JOIN `stock_list` s ON p.id = s.product_id 
                    AND s.branch_id = {$branch_id}
                    AND (s.expiration IS NULL OR s.expiration > CURDATE())
                WHERE p.delete_flag = 0
                GROUP BY p.id
                HAVING available > 0 AND available <= 10
            ) as low_stock_items
        ");
        
        
        $out_of_stock = $this->conn->query("
            SELECT COUNT(*) as count FROM (
                SELECT p.id,
                       (COALESCE(SUM(s.quantity), 0) - 
                        COALESCE((SELECT SUM(oi.quantity) 
                                 FROM order_items oi 
                                 INNER JOIN order_list ol ON oi.order_id = ol.id
                                 WHERE oi.product_id = p.id 
                                 AND ol.branch_id = {$branch_id}), 0)) as available
                FROM `product_list` p
                LEFT JOIN `stock_list` s ON p.id = s.product_id 
                    AND s.branch_id = {$branch_id}
                    AND (s.expiration IS NULL OR s.expiration > CURDATE())
                WHERE p.delete_flag = 0
                GROUP BY p.id
                HAVING available <= 0
            ) as out_of_stock_items
        ");
        
        $stats = [
            'branch' => $branch,
            'total_stock_entries' => (int)$total_entries->fetch_assoc()['count'],
            'active_stock_entries' => (int)$active_stock->fetch_assoc()['count'],
            'expired_stock_entries' => (int)$expired_stock->fetch_assoc()['count'],
            'products_with_stock' => (int)$products_with_stock->fetch_assoc()['count'],
            'low_stock_items' => (int)$low_stock->fetch_assoc()['count'],
            'out_of_stock_items' => (int)$out_of_stock->fetch_assoc()['count']
        ];
        
        $resp['status'] = 'success';
        $resp['data'] = $stats;
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function transfer_stock() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $stock_id = isset($_POST['stock_id']) ? intval($_POST['stock_id']) : 0;
        $from_branch = isset($_POST['from_branch']) ? intval($_POST['from_branch']) : 0;
        $to_branch = isset($_POST['to_branch']) ? intval($_POST['to_branch']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        
        if(empty($stock_id) || empty($from_branch) || empty($to_branch) || empty($quantity)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'All fields are required for stock transfer';
            return json_encode($resp);
        }
        
        $stock_id = (int)$stock_id;
        $from_branch = (int)$from_branch;
        $to_branch = (int)$to_branch;
        $quantity = (float)$quantity;
        
        if($from_branch == $to_branch) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Source and destination branches cannot be the same';
            return json_encode($resp);
        }
        
        
        $stock_query = $this->conn->query("
            SELECT s.*, p.brand, p.name 
            FROM `stock_list` s
            INNER JOIN `product_list` p ON s.product_id = p.id
            WHERE s.id = {$stock_id} AND s.branch_id = {$from_branch}
        ");
        
        if(!$stock_query || $stock_query->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Stock not found at source branch';
            return json_encode($resp);
        }
        
        $stock = $stock_query->fetch_assoc();
        
        if($quantity > $stock['quantity']) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Transfer quantity exceeds available stock';
            return json_encode($resp);
        }
        
        
        $this->conn->begin_transaction();
        
        try {
            
            if($quantity == $stock['quantity']) {
                
                $update_sql = "UPDATE `stock_list` SET branch_id = ? WHERE id = ?";
            } else {
                
                $new_quantity = $stock['quantity'] - $quantity;
                $update_sql = "UPDATE `stock_list` SET quantity = ? WHERE id = ?";
                
                
                $insert_sql = "INSERT INTO `stock_list` 
                              (`product_id`, `branch_id`, `code`, `quantity`, `expiration`) 
                              VALUES ({$stock['product_id']}, {$to_branch}, '{$stock['code']}-TRANSFER', {$quantity}, " . 
                              ($stock['expiration'] ? "'{$stock['expiration']}'" : 'NULL') . ")";
                
                if(!$this->conn->query($insert_sql)) {
                    throw new Exception('Failed to create stock at destination branch');
                }
            }
            
            if(!$this->conn->query($update_sql)) {
                throw new Exception('Failed to update source stock');
            }
            
            
            $this->conn->commit();
            
            $resp['status'] = 'success';
            $resp['msg'] = "Successfully transferred {$quantity} units of {$stock['brand']} - {$stock['name']}";
            
            if(method_exists($this->settings, 'set_flashdata')) {
                $this->settings->set_flashdata('success', $resp['msg']);
            }
            
        } catch (Exception $e) {
            
            $this->conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Transfer failed: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function update_cart(){
    
    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
    if(!SecurityHelper::validateCsrf()) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid security token. Please refresh and try again.';
        return json_encode($resp);
    }
    
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
    
    
    $qty = intval($qty);
    if($qty <= 0) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Quantity must be greater than 0.';
        return json_encode($resp);
    }
    
    
    $customer_id = $this->settings->userdata('id');
    $stmt = $this->conn->prepare("SELECT product_id FROM `cart_list` WHERE id = ? AND customer_id = ?");
    $stmt->bind_param('ii', $cart_id, $customer_id);
    $stmt->execute();
    $cart_item = $stmt->get_result();
    
    if($cart_item->num_rows <= 0) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Cart item not found.';
        return json_encode($resp);
    }
    
    $cart_data = $cart_item->fetch_assoc();
    $product_id = $cart_data['product_id'];
    
    
    $product_check = $this->conn->query("SELECT (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = '{$product_id}' and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(quantity) FROM `order_items` where product_id = '{$product_id}'), 0)) as `available` FROM `product_list` WHERE id = '{$product_id}' AND delete_flag = 0");
    
    if($product_check->num_rows > 0) {
        $available_stock = $product_check->fetch_assoc()['available'];
        
        if($qty > $available_stock) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Requested quantity ({$qty}) exceeds available stock ({$available_stock}).";
            return json_encode($resp);
        }
    }
    
    $stmt = $this->conn->prepare("UPDATE `cart_list` SET quantity = ? WHERE id = ?");
    $stmt->bind_param('ii', $qty, $cart_id);
    $update = $stmt->execute();
    if($update){
        $resp['status'] = 'success';
    }else{
        $resp['status'] = 'failed';
        $resp['error'] = $this->conn->error;
    }
    if($resp['status'] == 'success'){
        $this->settings->set_flashdata('success', 'Cart Item has been updated.');
    }
    return json_encode($resp);
}	function delete_cart(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid cart ID.';
			return json_encode($resp);
		}
		
		
		$customer_id = $this->settings->userdata('id');
		if(empty($customer_id)){
			$resp['status'] = 'failed';
			$resp['msg'] = 'User not logged in.';
			return json_encode($resp);
		}
		
		$stmt = $this->conn->prepare("DELETE FROM `cart_list` WHERE id = ? AND customer_id = ?");
		$stmt->bind_param('ii', $id, $customer_id);
		$delete = $stmt->execute();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		if($resp['status'] == 'success'){
			$this->settings->set_flashdata('success', 'Cart Item has been deleted.');
		}
		return json_encode($resp);
	}
	
	function clear_cart(){
		
		if (ob_get_length()) ob_clean();
		
		header('Content-Type: application/json');
		$resp = array();
		
		try {
			$customer_id = $this->settings->userdata('id');
			
			if(empty($customer_id)){
				$resp['status'] = 'failed';
				$resp['error'] = 'Customer ID not found';
				error_log("Clear cart failed: No customer ID");
				echo json_encode($resp);
				return;
			}
			
			error_log("Clearing cart for customer: " . $customer_id);
			
			$stmt = $this->conn->prepare("DELETE FROM `cart_list` WHERE customer_id = ?");
			$stmt->bind_param('i', $customer_id);
			$delete = $stmt->execute();
			
			if($delete){
				$affected = $this->conn->affected_rows;
				error_log("Cart cleared successfully. Rows affected: " . $affected);
				$resp['status'] = 'success';
				$resp['msg'] = 'All cart items have been cleared.';
				$resp['deleted_count'] = $affected;
			}else{
				$error = $this->conn->error ? $this->conn->error : 'Unknown database error';
				error_log("Clear cart query failed: " . $error);
				$resp['status'] = 'failed';
				$resp['error'] = $error;
			}
			
			if($resp['status'] == 'success'){
				$this->settings->set_flashdata('success', 'Cart has been cleared.');
			}
		} catch (Exception $e) {
			error_log("Clear cart exception: " . $e->getMessage());
			$resp['status'] = 'failed';
			$resp['error'] = $e->getMessage();
		}
		
		echo json_encode($resp);
		exit;
	}
	
	function place_order(){
    
    header('Content-Type: application/json');
    
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf(false)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        
        $pickup_branch = isset($_POST['pickup_branch']) ? intval($_POST['pickup_branch']) : 0;
        $pickup_date = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : '';
        $pickup_time = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : '';
        $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        $selected_items_param = isset($_POST['selected_items_param']) ? $_POST['selected_items_param'] : '';
        
        
        $payment_method_raw = '';
        if (isset($_POST['payment_method_selected'])) {
            $payment_method_raw = $_POST['payment_method_selected'];
        } elseif (isset($_POST['payment_method'])) {
            $payment_method_raw = $_POST['payment_method'];
        }
        
        
        $payment_map = [
            'ewallet' => 'gcash',
            'gcash' => 'gcash',
            'maya' => 'maya',
            'cod' => 'cash',
            'cash' => 'cash'
        ];
        $payment_method = isset($payment_map[$payment_method_raw]) ? $payment_map[$payment_method_raw] : 'cash';
        $payment_method = $this->conn->real_escape_string($payment_method);
        
        $payment_status = 'unpaid'; 
        $payment_receipt = null;
        
        
        if (in_array($payment_method, ['gcash', 'maya'])) {
            if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/payment_receipts/';
                $full_path = dirname(__DIR__) . '/' . $upload_dir;
                
                
                if (!is_dir($full_path)) {
                    mkdir($full_path, 0755, true);
                }
                
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($_FILES['payment_receipt']['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Invalid receipt file type. Please upload an image (JPG, PNG, GIF).';
                    return json_encode($resp);
                }
                
                
                if ($_FILES['payment_receipt']['size'] > 5 * 1024 * 1024) {
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Receipt file is too large. Maximum size is 5MB.';
                    return json_encode($resp);
                }
                
                
                $ext = pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION);
                $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $ext;
                $destination = $full_path . $filename;
                
                if (move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $destination)) {
                    $payment_receipt = $upload_dir . $filename;
                    $payment_status = 'pending_verification'; 
                } else {
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Failed to upload payment receipt. Please try again.';
                    return json_encode($resp);
                }
            } else {
                
                $payment_status = 'pending_payment';
            }
        }
        
        
        $customer_id = $this->settings->userdata('id');
        if (!$customer_id) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $profile_stmt = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
        $profile_stmt->bind_param('i', $customer_id);
        $profile_stmt->execute();
        $profile_result = $profile_stmt->get_result();
        $profile_data = $profile_result ? $profile_result->fetch_assoc() : [];
        
        $missing = [];
        if (empty(trim($profile_data['firstname'] ?? ''))) $missing[] = 'first name';
        if (empty(trim($profile_data['middlename'] ?? ''))) $missing[] = 'middle name';
        if (empty(trim($profile_data['lastname'] ?? ''))) $missing[] = 'last name';
        if (empty(trim($profile_data['gender'] ?? ''))) $missing[] = 'gender';
        
        $customer_email = trim($profile_data['email'] ?? '');
        if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $missing[] = 'valid email address';
        }
        
        $customer_contact = trim($profile_data['contact'] ?? '');
        if (!preg_match('/^09\d{9}$/', $customer_contact)) {
            $missing[] = '11-digit phone number starting with 09';
        }
        
        $customer_birthdate = trim($profile_data['birthdate'] ?? '');
        $is_adult = false;
        if (!empty($customer_birthdate)) {
            $b_date = DateTime::createFromFormat('Y-m-d', $customer_birthdate);
            if ($b_date) {
                $c_age = (new DateTime())->diff($b_date)->y;
                if ($c_age >= 18) {
                    $is_adult = true;
                }
            }
        }
        if (!$is_adult) {
            $missing[] = 'birthdate (must be at least 18 years old)';
        }
        
        if (!empty($missing)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Cannot proceed to checkout. Please update and complete your profile details: ' . implode(', ', $missing);
            return json_encode($resp);
        }
        
        
        
        
        if (empty($pickup_date)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Please select a pickup date';
            return json_encode($resp);
        }
        
        
        if (empty($pickup_time)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Please select a pickup time';
            return json_encode($resp);
        }
        
        
        $STORE_OPEN_HOUR = 8;  
        $STORE_CLOSE_HOUR = 21; 
        $MIN_ADVANCE_MINUTES = 20; 
        
        
        $pickup_datetime_str = $pickup_date . ' ' . $pickup_time;
        $pickup_datetime = strtotime($pickup_datetime_str);
        $now = time();
        $min_required_time = $now + ($MIN_ADVANCE_MINUTES * 60); 
        
        
        if ($pickup_datetime < $now) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Pickup time cannot be in the past. Current time: ' . date('g:i A', $now);
            return json_encode($resp);
        }
        
        
        if ($pickup_datetime < $min_required_time) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Pickup must be at least 20 minutes from now. Earliest: ' . date('g:i A', $min_required_time);
            return json_encode($resp);
        }
        
        
        $pickup_hour = (int)date('G', $pickup_datetime); 
        if ($pickup_hour < $STORE_OPEN_HOUR || $pickup_hour >= $STORE_CLOSE_HOUR) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Store is only open from 8:00 AM to 9:00 PM. Please select a time within business hours.';
            return json_encode($resp);
        }
        
        
        if ($total_amount <= 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid total amount';
            return json_encode($resp);
        }
        
        
        $selected_items = [];
        $where_clause = "";
        
        if (isset($selected_items_param) && !empty($selected_items_param)) {
            $selected_items = explode(',', $selected_items_param);
            $selected_items = array_map('intval', $selected_items);
            $selected_items = array_filter($selected_items);
        }
        
        
        if (!empty($selected_items)) {
            $where_clause = " AND c.id IN (" . implode(',', $selected_items) . ")";
        }

        
        $calc_sql = "SELECT SUM(c.quantity * p.price) as server_total 
                     FROM `cart_list` c 
                     INNER JOIN product_list p ON c.product_id = p.id 
                     WHERE c.customer_id = '" . $this->conn->real_escape_string($customer_id) . "'";
        if (!empty($selected_items)) {
            $calc_sql .= " AND c.id IN (" . implode(',', $selected_items) . ")";
        }
        $calc_result = $this->conn->query($calc_sql);
        if ($calc_result && $calc_row = $calc_result->fetch_assoc()) {
            $server_total = floatval($calc_row['server_total']);
            if ($server_total > 0) {
                
                $total_amount = $server_total;
            }
        }
        
        
        $rx_check_sql = "SELECT COUNT(*) as rx_count 
                         FROM `cart_list` c 
                         INNER JOIN product_list p ON c.product_id = p.id 
                         WHERE c.customer_id = '{$customer_id}' 
                         AND COALESCE(p.is_prescription, 0) = 1 
                         {$where_clause}";
        
        $rx_check = $this->conn->query($rx_check_sql);
        $rx_data = $rx_check->fetch_assoc();
        $has_rx_products = ($rx_data['rx_count'] > 0);
        
        
        if ($has_rx_products && in_array($payment_method, ['gcash', 'maya'])) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Online payments (GCash/Maya) are not available for orders containing prescription (Rx) medications.';
            return json_encode($resp);
        }
        
        
        error_log('=== PLACE ORDER DEBUG ===');
        error_log('Has RX Products: ' . ($has_rx_products ? 'YES' : 'NO'));
        error_log('Session cart_prescription isset: ' . (isset($_SESSION['cart_prescription']) ? 'YES' : 'NO'));
        if(isset($_SESSION['cart_prescription'])) {
            error_log('Session cart_prescription: ' . json_encode($_SESSION['cart_prescription']));
        }
        
        
        if ($has_rx_products) {
            $rx_stmt = $this->conn->prepare("SELECT id, image_path, expires_at
                FROM customer_prescriptions
                WHERE customer_id = ?
                AND status = 'approved'
                AND (expires_at IS NULL OR expires_at >= CURDATE())
                ORDER BY approved_at DESC
                LIMIT 1");
            $rx_stmt->bind_param('i', $customer_id);
            $rx_stmt->execute();
            $rx_record = $rx_stmt->get_result()->fetch_assoc();
            $rx_stmt->close();

            if (!$rx_record) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Checkout requires a system-validated prescription. You may still message the pharmacy.';
                return json_encode($resp);
            }

            $prescription_path = $rx_record['image_path'];
            $full_path = dirname(__DIR__) . '/' . $prescription_path;
            if (!file_exists($full_path)) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'The approved prescription file is unavailable. Please upload it again or contact the pharmacy.';
                return json_encode($resp);
            }

            $_SESSION['cart_prescription'] = array(
                'id' => intval($rx_record['id']),
                'path' => $prescription_path,
                'expires_at' => $rx_record['expires_at'],
                'validation_status' => 'approved'
            );
        }
        
        
        $pickup_date = $this->conn->real_escape_string($pickup_date);
        $pickup_time = $this->conn->real_escape_string($pickup_time);
        
        
        $cart_check_sql = "SELECT COUNT(*) as count FROM `cart_list` c WHERE customer_id = '{$customer_id}' {$where_clause}";
        $cart_check = $this->conn->query($cart_check_sql);
        
        if (!$cart_check) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Unable to verify cart. Please try again.';
            error_log('place_order cart check error: ' . $this->conn->error);
            return json_encode($resp);
        }
        
        $cart_count = $cart_check->fetch_assoc()['count'];
        if ($cart_count == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = empty($selected_items) ? 'Your cart is empty' : 'Selected items not found in your cart';
            return json_encode($resp);
        }
        
        
        $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'Store') : 'Store';
        $store_address = isset($this->settings) ? ($this->settings->info('address') ?: '') : '';
        $store_phone = isset($this->settings) ? ($this->settings->info('phone') ?: ($this->settings->info('mobile') ?: '')) : '';
        $store_email = isset($this->settings) ? ($this->settings->info('email') ?: '') : '';
        
        
        
        
        
        $pref = date("Ymd");
        $code = sprintf("%'.05d", 1);
        $attempts = 0;
        
        while($attempts < 100) {
            $check = $this->conn->query("SELECT id FROM `order_list` WHERE `code` = '{$pref}{$code}'");
            if($check->num_rows == 0) {
                $code = $pref . $code;
                break;
            }
            $code = sprintf("%'.05d", intval($code) + 1);
            $attempts++;
        }
        
        
        $insert_columns = array();
        $insert_values = array();
        
        
        $insert_columns[] = '`code`';
        $insert_values[] = "'{$code}'";
        
        $insert_columns[] = '`customer_id`';
        $insert_values[] = "'{$customer_id}'";
        
        $insert_columns[] = '`total_amount`';
        $insert_values[] = "'{$total_amount}'";
        
        
        if (!empty($pickup_branch)) {
            $insert_columns[] = '`branch_id`';
            $insert_values[] = "'{$pickup_branch}'";
        }
        
        
        $insert_columns[] = '`pickup_date`';
        $insert_values[] = "'{$pickup_date}'";
        
        $insert_columns[] = '`pickup_time`';
        $insert_values[] = "'{$pickup_time}'";
        
        
        $start_time = date('g:i A', strtotime($pickup_time));
        $end_time = date('g:i A', strtotime($pickup_time . ' +1 hour'));
        $time_range = $start_time . ' - ' . $end_time;
        
        
        $prescription_image = null;
        $prescription_validation = 'pending';
        $prescription_details = null;
        
        if (isset($_POST['prescription_validation']) && isset($_POST['prescription_details'])) {
            
            $prescription_validation = $this->conn->real_escape_string($_POST['prescription_validation']);
            $prescription_details = $this->conn->real_escape_string($_POST['prescription_details']);
            
            
            
            
        } elseif (isset($_SESSION['cart_prescription'])) {
            
            $session_validation = null;
            $session_details = null;
            
            if (isset($_SESSION['cart_prescription']['ai_validation']) && is_array($_SESSION['cart_prescription']['ai_validation'])) {
                $session_details = json_encode($_SESSION['cart_prescription']['ai_validation']);
                $session_validation = $_SESSION['cart_prescription']['ai_validation']['validation'] ?? null;
            }
            
            if ($session_validation === null && isset($_SESSION['cart_prescription']['validation_status'])) {
                $session_validation = $_SESSION['cart_prescription']['validation_status'];
            }
            
            if (!empty($session_validation)) {
                $normalized = $session_validation;
                if ($normalized === 'pending_review') {
                    $normalized = 'mismatch';
                }
                if (!in_array($normalized, ['matched', 'partial', 'mismatch', 'invalid'], true)) {
                    $normalized = 'pending';
                }
                
                $prescription_validation = $this->conn->real_escape_string($normalized);
                if (!empty($session_details)) {
                    $prescription_details = $this->conn->real_escape_string($session_details);
                }
            }
        }
        
        
        
        
        $pickup_datetime_display = date('F d, Y', strtotime($pickup_date)) . ' between ' . $time_range;
        $delivery_info = $this->conn->real_escape_string("PICKUP: " . $store_name . " - " . $store_address . " on " . $pickup_datetime_display);
        $insert_columns[] = '`delivery_address`';
        $insert_values[] = "'{$delivery_info}'";
        
        
        if ($has_rx_products && isset($prescription_path)) {
            $insert_columns[] = '`prescription_image`';
            $insert_values[] = "'" . $this->conn->real_escape_string($prescription_path) . "'";
        }

        if (!empty($prescription_validation) && $prescription_validation !== 'pending') {
            $insert_columns[] = '`prescription_validation`';
            $insert_values[] = "'{$prescription_validation}'";
            
            if (!empty($prescription_details)) {
                $insert_columns[] = '`prescription_details`';
                $insert_values[] = "'{$prescription_details}'";
            }
        }
        
        
        $insert_columns[] = '`status`';
        $insert_values[] = "'0'";
        
        
        
        
        $insert_columns[] = '`payment_method`';
        $insert_values[] = "'{$payment_method}'";
        
        $insert_columns[] = '`payment_status`';
        $insert_values[] = "'{$payment_status}'";
        
        if (!empty($payment_receipt)) {
            $insert_columns[] = '`payment_receipt`';
            $insert_values[] = "'{$payment_receipt}'";
        }
        
        
        $is_urgent = isset($_POST['is_urgent']) ? (int)$_POST['is_urgent'] : 0;
        
        $this->conn->query("ALTER TABLE `order_list` ADD COLUMN IF NOT EXISTS `is_urgent` TINYINT(1) DEFAULT 0 AFTER `status`");
        $insert_columns[] = '`is_urgent`';
        $insert_values[] = "'{$is_urgent}'";
        
        
        $this->conn->begin_transaction();
        
        try {
        $insert_sql = "INSERT INTO `order_list` (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $insert_values) . ")";
        
        $insert = $this->conn->query($insert_sql);
        
        if(!$insert) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to create order. Please try again.';
            error_log('place_order insert error: ' . $this->conn->error);
            return json_encode($resp);
        }
        
        $oid = $this->conn->insert_id;
        
        
        $cart_sql = "SELECT c.*, p.name as product, p.brand as brand, p.price FROM `cart_list` c 
                     INNER JOIN product_list p ON c.product_id = p.id 
                     WHERE customer_id = '{$customer_id}' {$where_clause}";
        
        $cart = $this->conn->query($cart_sql);
        
        if (!$cart || $cart->num_rows == 0) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = 'No items found to process';
            return json_encode($resp);
        }
        
        
        $data = "";
        $processed_cart_ids = [];
        
        while($row = $cart->fetch_assoc()){
            if(!empty($data)) $data .= ", ";
            $data .= "('{$oid}', '{$row['product_id']}', '{$row['quantity']}', '{$row['price']}')";
            $processed_cart_ids[] = $row['id'];
        }
        
        if(!empty($data)){
            $sql = "INSERT INTO order_items (`order_id`, `product_id`, `quantity`, `price`) VALUES {$data}";
            $save = $this->conn->query($sql);
            
            if($save){
                
                if (!empty($processed_cart_ids)) {
                    $cart_ids_str = implode(',', $processed_cart_ids);
                    $stmt = $this->conn->prepare("DELETE FROM `cart_list` WHERE id IN (" . $cart_ids_str . ")");
                    $stmt->execute();
                } else {
                    $stmt_del2 = $this->conn->prepare("DELETE FROM `cart_list` WHERE customer_id = ?");
                    $stmt_del2->bind_param('i', $customer_id);
                    $stmt_del2->execute();
                }
                
                
                
                $this->conn->commit();
                
                
                $pickup_datetime_display = date('F d, Y', strtotime($pickup_date)) . ' at ' . date('g:i A', strtotime($pickup_time));
                
                $resp['status'] = 'success';
                $items_text = !empty($selected_items) ? count($selected_items) . " selected items" : "all items";
                
                
                $payment_msg = '';
                if ($payment_method === 'cash') {
                    $payment_msg = ' Pay ₱' . number_format($total_amount, 2) . ' at pickup.';
                } else {
                    $payment_msg = ' Payment via ' . strtoupper($payment_method) . ' is being verified.';
                }
                
                $resp['msg'] = "Order placed successfully for {$items_text}! Pickup at: " . $store_name . " on " . $pickup_datetime_display . $payment_msg;
                $resp['order_code'] = $code;
                $resp['order_id'] = $oid;
                $resp['pickup_branch'] = $store_name;
                $resp['branch_address'] = $store_address;
                $resp['pickup_date'] = $pickup_date;
                $resp['pickup_time'] = $pickup_time;
                $resp['pickup_datetime_display'] = $pickup_datetime_display;
                $resp['selected_items_count'] = count($processed_cart_ids);
                $resp['payment_method'] = $payment_method;
                $resp['payment_status'] = $payment_status;
                
                
                
                
                
                if (in_array($payment_method, ['gcash', 'maya'])) {
                    try {
                        require_once(__DIR__ . '/PayMongo.php');
                        $paymongo = new PayMongo($this->conn);
                        
                        
                        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                        $base_url .= dirname($_SERVER['REQUEST_URI']);
                        $base_url = rtrim($base_url, '/');
                        
                        
                        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                            $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/pickupdrug';
                        }
                        
                        $success_url = $base_url . '/paymongo_callback.php?type=success&order_id=' . $oid;
                        $failed_url = $base_url . '/paymongo_callback.php?type=failed&order_id=' . $oid;
                        
                        
                        $source_type = ($payment_method === 'maya') ? 'grab_pay' : 'gcash';
                        $source_result = $paymongo->createSource(
                            $source_type,
                            $total_amount,
                            'Order #' . $code . ' - ' . $store_name,
                            $success_url,
                            $failed_url,
                            [
                                'order_id' => $oid,
                                'order_code' => $code,
                                'customer_id' => $customer_id
                            ]
                        );
                        
                        if ($source_result['status'] === 'success') {
                            
                            $source_id = $source_result['source_id'];
                            $upd_stmt = $this->conn->prepare("UPDATE order_list SET paymongo_source_id = ?, payment_status = 'pending_payment' WHERE id = ?");
                            $upd_stmt->bind_param('si', $source_id, $oid);
                            $upd_stmt->execute();
                            
                            
                            $resp['paymongo_checkout'] = true;
                            $resp['checkout_url'] = $source_result['checkout_url'];
                            $resp['source_id'] = $source_id;
                            $resp['msg'] = "Order #{$code} created. Redirecting to " . strtoupper($payment_method) . " for payment...";
                            $resp['payment_status'] = 'pending_payment';
                        } else {
                            
                            error_log("PayMongo source creation failed: " . json_encode($source_result));
                            $resp['paymongo_checkout'] = false;
                            $resp['paymongo_error'] = $source_result['msg'];
                            $resp['msg'] = "Order #{$code} created but payment initialization failed: " . $source_result['msg'] . ". Please try paying again from your orders page.";
                        }
                    } catch (Throwable $e_pay) {
                        error_log("PayMongo error (order already saved): " . $e_pay->getMessage());
                        $resp['paymongo_checkout'] = false;
                        $resp['paymongo_error'] = 'Payment service timeout. Order was saved.';
                        $resp['msg'] = "Order #{$code} created but payment service timed out. Please try paying again from your orders page.";
                    }
                }
                
                
                
                
                
                
                
                $notification_data = [
                    'order_id' => $oid,
                    'order_code' => $code,
                    'customer_id' => $customer_id,
                    'store_name' => $store_name,
                    'store_address' => $store_address,
                    'store_phone' => $store_phone,
                    'pickup_datetime' => $pickup_datetime_display,
                    'total_amount' => $total_amount,
                    'payment_method' => $payment_method,
                    'payment_msg' => $payment_msg,
                    'is_urgent' => $is_urgent
                ];
                
                if (method_exists($this->settings, 'set_flashdata')) {
                    $this->settings->set_flashdata('success', 'Order #' . $code . ' placed successfully! Pickup scheduled for ' . $pickup_datetime_display . ' at ' . $store_name);
                }
                
                
                try {
                    $this->sendOrderNotificationsInstant($notification_data);
                } catch (Throwable $e_notif) {
                    error_log("Order notification error: " . $e_notif->getMessage());
                }
            } else {
                $this->conn->rollback();
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to save order items. Please try again.';
                error_log('place_order items insert error: ' . $this->conn->error);
            }
        } else {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = 'No items found to order';
        }
        
        } catch (Exception $tx_e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = 'An error occurred while processing your order. Please try again.';
            error_log('place_order transaction error: ' . $tx_e->getMessage());
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Please try again.';
        error_log('place_order error: ' . $e->getMessage());
    }
    
    return json_encode($resp);
}

	function update_order_status(){
    $resp = array('status' => 'failed');
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : '';
        
        if(empty($id) || !isset($status)) {
            $resp['msg'] = 'Missing required fields';
            return json_encode($resp);
        }
        
        
        $order_query = $this->conn->query("SELECT o.code, o.customer_id, o.status as old_status 
                                           FROM `order_list` o 
                                           WHERE o.id = '{$id}'");
        
        if(!$order_query || $order_query->num_rows == 0) {
            $resp['msg'] = 'Order not found';
            return json_encode($resp);
        }
        
        $order_data = $order_query->fetch_assoc();
        
        $stmt = $this->conn->prepare("UPDATE `order_list` SET `status` = ? WHERE id = ?");
        $stmt->bind_param('ii', $status, $id);
        $update = $stmt->execute();
        
        if($update){
            $resp['status'] = 'success';
            
            $status_text = OrderStatus::getLabel($status);
            
            
            $old_status = (int)$order_data['old_status'];
            $is_backward = OrderStatus::isBackwardTransition($old_status, $status);
            
            if($is_backward) {
                $status_message = OrderStatus::getBackwardNotificationMessage($status);
                $status_text = OrderStatus::getLabel($status) . ' (Updated)';
            } else {
                $status_message = OrderStatus::getNotificationMessage($status);
            }
            
            
            if($order_data['old_status'] != $status) {
                
                $this->sendStatusUpdateNotificationInstant([
                    'order_id' => $id,
                    'order_code' => $order_data['code'],
                    'customer_id' => $order_data['customer_id'],
                    'status' => $status,
                    'status_text' => $status_text,
                    'status_message' => $status_message
                ]);
                
                
                if($status == OrderStatus::COMPLETED) {
                    try {
                        $this->create_invoice_for_order($id, $order_data['customer_id']);
                    } catch(Exception $e) {
                        
                        error_log("Auto-invoice creation failed for order {$id}: " . $e->getMessage());
                    }
                }
            }
            
            $this->settings->set_flashdata('success', "Order Status has been updated successfully.");
            
        } else {
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch(Exception $e) {
        $resp['msg'] = 'Error: ' . $e->getMessage();
    }

    return json_encode($resp);
}

    
    private function create_invoice_for_order($order_id, $customer_id) {
        
        $check_stmt = $this->conn->prepare("SELECT id FROM `invoice_list` WHERE order_id = ?");
        $check_stmt->bind_param('i', $order_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result();
        
        if ($existing->num_rows > 0) {
            
            return true;
        }
        
        
        $order_stmt = $this->conn->prepare("SELECT * FROM `order_list` WHERE id = ?");
        $order_stmt->bind_param('i', $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows == 0) {
            return false;
        }
        
        $order = $order_result->fetch_assoc();
        
        
        $year = date('Y');
        $month = date('m');
        $code_qry = $this->conn->query("SELECT invoice_code FROM invoice_list 
            WHERE invoice_code LIKE 'INV-$year-$month-%' 
            ORDER BY id DESC LIMIT 1");
        
        if ($code_qry->num_rows > 0) {
            $row = $code_qry->fetch_assoc();
            $last_code = $row['invoice_code'];
            $number = intval(substr($last_code, -4)) + 1;
        } else {
            $number = 1;
        }
        
        $invoice_code = 'INV-' . $year . '-' . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        
        
        $total_amount = floatval($order['total_amount'] ?? 0);
        $subtotal = $total_amount; 
        
        
        $invoice_date = date('Y-m-d');
        $due_date = date('Y-m-d'); 
        $created_by = $this->settings->userdata('id') ?? 1;
        $customer_id_int = intval($customer_id);
        $order_id_int = intval($order_id);
        $created_by_int = intval($created_by);
        
        $insert_stmt = $this->conn->prepare("INSERT INTO `invoice_list` 
            (`invoice_code`, `customer_id`, `order_id`, `invoice_date`, `due_date`, 
             `subtotal`, `tax_rate`, `tax_amount`, `total_amount`, 
             `paid_amount`, `balance_amount`, `status`, `payment_method`, `notes`, 
             `created_by`, `date_created`) 
            VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 0, 1, 'Cash', ?, ?, NOW())");
        
        $notes = "Auto-generated invoice for Order #" . ($order['code'] ?? $order_id);
        
        $insert_stmt->bind_param('siissdddsi', 
            $invoice_code, 
            $customer_id_int, 
            $order_id_int, 
            $invoice_date, 
            $due_date, 
            $subtotal, 
            $total_amount, 
            $total_amount, 
            $notes,
            $created_by_int
        );
        
        $save = $insert_stmt->execute();
        
        if (!$save) {
            error_log("Failed to create invoice: " . $this->conn->error);
            return false;
        }
        
        $invoice_id = $this->conn->insert_id;
        
        
        $items_stmt = $this->conn->prepare("SELECT oi.*, p.name as product_name 
            FROM `order_items` oi 
            LEFT JOIN `product_list` p ON oi.product_id = p.id 
            WHERE oi.order_id = ?");
        $items_stmt->bind_param('i', $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $item_name = $item['product_name'] ?? 'Product';
            $item_qty = floatval($item['quantity'] ?? 1);
            $item_price = floatval($item['price'] ?? 0);
            $item_total = $item_qty * $item_price;
            
            $item_stmt = $this->conn->prepare("INSERT INTO `invoice_items` 
                (`invoice_id`, `item_name`, `description`, `quantity`, `unit_price`, `total_price`) 
                VALUES (?, ?, '', ?, ?, ?)");
            $item_stmt->bind_param('isddd', $invoice_id, $item_name, $item_qty, $item_price, $item_total);
            $item_stmt->execute();
        }
        
        return true;
    }

	
	private function log_deletion($table_name, $item_id, $item_data = null){
		try {
			$create_table = "CREATE TABLE IF NOT EXISTS `deleted_items` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`table_name` VARCHAR(100) NOT NULL,
				`item_id` INT(11) NOT NULL,
				`item_data` TEXT DEFAULT NULL,
				`deleted_by` INT(11) DEFAULT NULL,
				`deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				INDEX `idx_table_item` (`table_name`, `item_id`),
				INDEX `idx_deleted_at` (`deleted_at`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
			$this->conn->query($create_table);
			
			$user_data = $this->settings->userdata('id');
			$user_id = ($user_data !== null && $user_data !== false) ? (int)$user_data : 0;
			$item_data_json = '';
			if ($item_data) {
				$enc = @json_encode($item_data, JSON_UNESCAPED_UNICODE);
				$item_data_json = is_string($enc) ? $enc : '';
			}
			
			$stmt = $this->conn->prepare("INSERT INTO `deleted_items` (`table_name`, `item_id`, `item_data`, `deleted_by`) VALUES (?, ?, ?, ?)");
			if ($stmt) {
				$stmt->bind_param('sisi', $table_name, $item_id, $item_data_json, $user_id);
				@$stmt->execute();
				$stmt->close();
			}
		} catch (Throwable $e) {
			
		}
	}
	
	function delete_order(){
		$resp = array();
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf(false)) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid order ID.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `order_list` WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows == 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Order not found.';
			return json_encode($resp);
		}
		
		$order = $result->fetch_assoc();
		$order_status = $order['status'];
		
		if($order_status != 3 && $order_status != 4){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Only completed (Done) or cancelled orders can be deleted.';
			return json_encode($resp);
		}
		
		
		$this->log_deletion('order_list', $id, $order);
		
		
		$stmt = $this->conn->prepare("UPDATE `order_list` SET `deleted_at` = NOW() WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', 'Order has been deleted successfully.');
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = $this->conn->error ?: 'Failed to delete order.';
		}
		return json_encode($resp);
	}
	function save_inquiry(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		
		$allowed_fields = array('fullname', 'email', 'contact', 'message', 'subject');
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
		$data = "";
		foreach($_POST as $k => $v){
			if($k === 'id' || $k === 'csrf_token') continue;
			if(!in_array($k, $allowed_fields)) continue; 
			if(!empty($data)) $data .=",";
			$v = $this->conn->real_escape_string(htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'));
			$k = preg_replace('/[^a-zA-Z0-9_]/', '', $k); 
			$data .= " `{$k}`='{$v}' ";
		}
		
		if(empty($data)){
			$resp['status'] = 'failed';
			$resp['msg'] = 'No valid data provided.';
			return json_encode($resp);
		}
		
		if(empty($id)){
			$sql = "INSERT INTO `inquiry_list` set {$data} ";
		}else{
			$sql = "UPDATE `inquiry_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$cid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success'," Your Inquiry has been sent successfully. Thank you!");
			else
				$this->settings->set_flashdata('success'," Inquiry successfully updated");
			
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Failed to save inquiry. Please try again.';
			error_log('save_inquiry error: ' . $this->conn->error);
		}
		return json_encode($resp);
	}
	function delete_inquiry(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if($id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid inquiry ID.';
			return json_encode($resp);
		}
		
		$stmt = $this->conn->prepare("DELETE FROM `inquiry_list` WHERE id = ?");
		$stmt->bind_param('i', $id);
		$del = $stmt->execute();
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Inquiry has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_branch() {
    
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $branch_name = isset($_POST['branch_name']) ? trim($_POST['branch_name']) : '';
        $branch_address = isset($_POST['branch_address']) ? trim($_POST['branch_address']) : '';
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        
        
        error_log("Save branch data: " . print_r($_POST, true));
        
        
        if (empty(trim($branch_name)) || empty(trim($branch_address))) {
            $resp['msg'] = 'Branch name and address are required fields';
            return json_encode($resp);
        }
        
        
        $branch_name = $this->conn->real_escape_string(trim($branch_name));
        $branch_address = $this->conn->real_escape_string(trim($branch_address));
        
        
        $branch_phone = !empty(trim($branch_phone)) ? "'" . $this->conn->real_escape_string(trim($branch_phone)) . "'" : "NULL";
        $branch_email = !empty(trim($branch_email)) ? "'" . $this->conn->real_escape_string(trim($branch_email)) . "'" : "NULL";
        $manager_name = !empty(trim($manager_name)) ? "'" . $this->conn->real_escape_string(trim($manager_name)) . "'" : "NULL";
        
        
        $operating_hours_value = "NULL";
        if (!empty($operating_hours)) {
            
            $schedule_data = json_decode($operating_hours, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($schedule_data)) {
                $operating_hours_value = "'" . $this->conn->real_escape_string($operating_hours) . "'";
                error_log("Operating hours JSON processed: " . $operating_hours);
            } else {
                error_log("Invalid JSON in operating hours: " . $operating_hours);
                $resp['msg'] = 'Invalid operating hours format';
                return json_encode($resp);
            }
        }
        
        
        $status = isset($status) ? (int)$status : 1;
        if ($status !== 0 && $status !== 1) {
            $status = 1;
        }
        
        
        if ($branch_email !== "NULL") {
            $email_check = str_replace("'", "", $branch_email);
            if (!filter_var($email_check, FILTER_VALIDATE_EMAIL)) {
                $resp['msg'] = 'Please provide a valid email address';
                return json_encode($resp);
            }
        }
        
        
        $is_update = isset($id) && !empty($id);
        $id = $is_update ? (int)$id : 0;
        
        if ($is_update) {
            
            $check_exists = $this->conn->query("SELECT id, branch_name FROM `branch_list` WHERE id = '$id'");
            if ($check_exists->num_rows == 0) {
                $resp['msg'] = 'Branch not found for update';
                return json_encode($resp);
            }
            
            
            $duplicate_check = $this->conn->query("SELECT id FROM `branch_list` WHERE branch_name = '$branch_name' AND id != '$id'");
            if ($duplicate_check->num_rows > 0) {
                $resp['msg'] = 'A branch with this name already exists';
                return json_encode($resp);
            }
            
            
            $sql = "UPDATE `branch_list` SET 
                        `branch_name` = '$branch_name',
                        `branch_address` = '$branch_address',
                        `branch_phone` = $branch_phone,
                        `branch_email` = $branch_email,
                        `manager_name` = $manager_name,
                        `operating_hours` = $operating_hours_value,
                        `status` = '$status',
                        `updated_at` = CURRENT_TIMESTAMP
                    WHERE `id` = '$id'";
            
            $action = 'updated';
            
        } else {
            
            $duplicate_check = $this->conn->query("SELECT id FROM `branch_list` WHERE branch_name = '$branch_name'");
            if ($duplicate_check->num_rows > 0) {
                $resp['msg'] = 'A branch with this name already exists';
                return json_encode($resp);
            }
            
            
            $sql = "INSERT INTO `branch_list` (
                        `branch_name`, 
                        `branch_address`, 
                        `branch_phone`, 
                        `branch_email`, 
                        `manager_name`, 
                        `operating_hours`, 
                        `status`,
                        `created_at`,
                        `updated_at`
                    ) VALUES (
                        '$branch_name', 
                        '$branch_address', 
                        $branch_phone, 
                        $branch_email, 
                        $manager_name, 
                        $operating_hours_value, 
                        '$status',
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )";
            
            $action = 'created';
        }
        
        
        error_log("SQL Query: " . $sql);
        
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $resp['status'] = 'success';
            $resp['msg'] = "Branch successfully $action!";
            
            
            if (method_exists($this->settings, 'set_flashdata')) {
                $this->settings->set_flashdata('success', $resp['msg']);
            }
            
            
            if (!$is_update) {
                $resp['branch_id'] = $this->conn->insert_id;
            } else {
                $resp['branch_id'] = $id;
            }
            
            
            error_log("Branch successfully saved with ID: " . ($is_update ? $id : $this->conn->insert_id));
            
        } else {
            
            error_log("Branch save database error: " . $this->conn->error . " | SQL: " . $sql);
            $resp['msg'] = 'Database error occurred while saving branch: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        
        error_log("Branch save exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        $resp['msg'] = 'An unexpected error occurred while saving the branch: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_branch() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        $query = $this->conn->query("SELECT * FROM `branch_list` WHERE id = '$id'");
        
        if ($query && $query->num_rows > 0) {
            $branch = $query->fetch_assoc();
            
            
            if (!empty($branch['operating_hours'])) {
                $schedule = json_decode($branch['operating_hours'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $branch['schedule'] = $schedule;
                }
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $branch;
        } else {
            $resp['msg'] = 'Branch not found';
        }
        
    } catch (Exception $e) {
        error_log("Get branch exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch information';
    }
    
    return json_encode($resp);
}

function toggle_branch() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : '';
        
        if (empty($id) || !isset($status)) {
            $resp['msg'] = 'Branch ID and status are required';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        $status = (int)$status;
        
        
        if ($status !== 0 && $status !== 1) {
            $resp['msg'] = 'Invalid status value provided';
            return json_encode($resp);
        }
        
        
        $check = $this->conn->query("SELECT id, branch_name, status FROM `branch_list` WHERE id = '$id'");
        if (!$check || $check->num_rows == 0) {
            $resp['msg'] = 'Branch not found';
            return json_encode($resp);
        }
        
        $branch_data = $check->fetch_assoc();
        
        
        if ($status == 0) {
            
            $possible_columns = ['branch_id', 'pickup_branch_id', 'pickup_branch', 'branch'];
            $pending_count = 0;
            $active_statuses = OrderStatus::getActiveStatusesSQL();
            
            foreach ($possible_columns as $col) {
                $pending_orders = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$id' AND status IN ({$active_statuses})");
                if ($pending_orders) {
                    $pending_count = $pending_orders->fetch_assoc()['count'];
                    break;
                }
            }
            
            if ($pending_count > 0) {
                $resp['msg'] = "Cannot deactivate branch. There are $pending_count pending orders for this branch.";
                return json_encode($resp);
            }
        }
        
        
        $stmt = $this->conn->prepare("UPDATE `branch_list` SET `status` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?");
        $stmt->bind_param('ii', $status, $id);
        $update = $stmt->execute();
        
        if ($update) {
            $action = $status == 1 ? 'activated' : 'deactivated';
            $resp['status'] = 'success';
            $resp['msg'] = "Branch '{$branch_data['branch_name']}' has been successfully $action!";
            
            
            $this->settings->set_flashdata('success', $resp['msg']);
        } else {
            error_log("Branch toggle error: " . $this->conn->error);
            $resp['msg'] = 'Failed to update branch status due to database error';
        }
        
    } catch (Exception $e) {
        error_log("Branch toggle exception: " . $e->getMessage());
        $resp['msg'] = 'An error occurred while updating branch status';
    }
    
    return json_encode($resp);
}

function delete_branch() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        if (empty($_POST['id'])) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $id = (int)$_POST['id'];
        
        
        error_log("Attempting to delete branch with ID: " . $id);
        
        
        $check = $this->conn->query("SELECT id, branch_name FROM `branch_list` WHERE id = '$id'");
        if (!$check || $check->num_rows == 0) {
            $resp['msg'] = 'Branch not found';
            error_log("Branch not found with ID: " . $id);
            return json_encode($resp);
        }
        
        $branch_data = $check->fetch_assoc();
        error_log("Found branch: " . $branch_data['branch_name']);
        
        
        $possible_columns = ['branch_id', 'pickup_branch_id', 'pickup_branch', 'branch'];
        $orders_count = 0;
        
        foreach ($possible_columns as $col) {
            
            $column_check = $this->conn->query("SHOW COLUMNS FROM `order_list` LIKE '$col'");
            if ($column_check && $column_check->num_rows > 0) {
                $orders_check = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$id'");
                if ($orders_check) {
                    $count = $orders_check->fetch_assoc()['count'];
                    if ($count > 0) {
                        $orders_count = $count;
                        break;
                    }
                }
            }
        }
        
        if ($orders_count > 0) {
            $resp['msg'] = "Cannot delete branch '{$branch_data['branch_name']}'. There are $orders_count orders associated with this branch. Consider deactivating it instead.";
            return json_encode($resp);
        }
        
        
        $stmt = $this->conn->prepare("DELETE FROM `branch_list` WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        error_log("Executing delete query for branch ID: " . $id);
        
        $delete = $stmt->execute();
        
        if ($delete) {
            if ($this->conn->affected_rows > 0) {
                $resp['status'] = 'success';
                $resp['msg'] = "Branch '{$branch_data['branch_name']}' has been successfully deleted!";
                
                
                $this->settings->set_flashdata('success', $resp['msg']);
                
                error_log("Successfully deleted branch: " . $branch_data['branch_name']);
            } else {
                $resp['msg'] = 'No branch was deleted - branch may not exist';
                error_log("No rows affected during delete operation for ID: " . $id);
            }
        } else {
            error_log("Branch delete error: " . $this->conn->error);
            $resp['msg'] = 'Failed to delete branch due to database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        error_log("Branch delete exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        $resp['msg'] = 'An error occurred while deleting the branch: ' . $e->getMessage();
    }
    
    error_log("Delete response: " . json_encode($resp));
    return json_encode($resp);
}

function get_all_branches() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        $branches = [];
        $query = $this->conn->query("
            SELECT *, 
                   DATE_FORMAT(created_at, '%M %d, %Y at %h:%i %p') as formatted_created,
                   DATE_FORMAT(updated_at, '%M %d, %Y at %h:%i %p') as formatted_updated
            FROM `branch_list` 
            ORDER BY `status` DESC, `branch_name` ASC
        ");
        
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                
                if (!empty($row['operating_hours'])) {
                    $schedule = json_decode($row['operating_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row['schedule'] = $schedule;
                        $row['formatted_hours'] = $this->format_operating_hours($schedule);
                    }
                }
                $branches[] = $row;
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $branches;
            $resp['count'] = count($branches);
        } else {
            $resp['msg'] = 'Failed to retrieve branches';
        }
        
    } catch (Exception $e) {
        error_log("Get all branches exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branches list';
    }
    
    return json_encode($resp);
}

function get_active_branches() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        $branches = [];
        $query = $this->conn->query("
            SELECT * FROM `branch_list` 
            WHERE status = 1 
            ORDER BY branch_name ASC
        ");
        
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                
                if (!empty($row['operating_hours'])) {
                    $schedule = json_decode($row['operating_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row['schedule'] = $schedule;
                        $row['formatted_hours'] = $this->format_operating_hours($schedule);
                    }
                }
                $branches[] = $row;
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $branches;
            $resp['count'] = count($branches);
        } else {
            $resp['msg'] = 'Failed to retrieve active branches';
        }
        
    } catch (Exception $e) {
        error_log("Get active branches exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving active branches';
    }
    
    return json_encode($resp);
}

function get_branch_stats() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if (empty($branch_id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        
        
        $branch_query = $this->conn->query("SELECT * FROM `branch_list` WHERE id = '$branch_id'");
        if (!$branch_query || $branch_query->num_rows == 0) {
            $resp['msg'] = 'Branch not found';
            return json_encode($resp);
        }
        
        $branch = $branch_query->fetch_assoc();
        
        
        $possible_columns = ['branch_id', 'pickup_branch_id', 'pickup_branch', 'branch'];
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'total_revenue' => 0,
            'monthly_orders' => 0,
            'weekly_orders' => 0
        ];
        
        foreach ($possible_columns as $col) {
            
            $test_query = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' LIMIT 1");
            if ($test_query) {
                
                
                
                $active_statuses = OrderStatus::getActiveStatusesSQL();
                $completed_status = OrderStatus::COMPLETED;
                $cancelled_status = OrderStatus::CANCELLED;
                
                
                $total = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id'");
                $stats['total_orders'] = $total->fetch_assoc()['count'];
                
                
                $pending = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' AND status IN ({$active_statuses})");
                $stats['pending_orders'] = $pending->fetch_assoc()['count'];
                
                
                $completed = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' AND status = {$completed_status}");
                $stats['completed_orders'] = $completed->fetch_assoc()['count'];
                
                
                $cancelled = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' AND status = {$cancelled_status}");
                $stats['cancelled_orders'] = $cancelled->fetch_assoc()['count'];
                
                
                $revenue = $this->conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM `order_list` WHERE `$col` = '$branch_id' AND status = {$completed_status}");
                $stats['total_revenue'] = (float)$revenue->fetch_assoc()['total'];
                
                
                $monthly = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
                $stats['monthly_orders'] = $monthly->fetch_assoc()['count'];
                
                
                $weekly = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$branch_id' AND WEEK(created_at) = WEEK(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
                $stats['weekly_orders'] = $weekly->fetch_assoc()['count'];
                
                break; 
            }
        }
        
        $result = [
            'branch' => $branch,
            'stats' => $stats
        ];
        
        $resp['status'] = 'success';
        $resp['data'] = $result;
        
    } catch (Exception $e) {
        error_log("Branch stats exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch statistics';
    }
    
    return json_encode($resp);
}

function get_branch_open_status() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        $current_day = date('l'); 
        $current_time = date('H:i');
        
        $branches = [];
        $query = $this->conn->query("SELECT id, branch_name, operating_hours FROM `branch_list` WHERE status = 1");
        
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $is_open = false;
                
                if (!empty($row['operating_hours'])) {
                    $schedule = json_decode($row['operating_hours'], true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && 
                        is_array($schedule) && 
                        isset($schedule[$current_day])) {
                        
                        $day_schedule = $schedule[$current_day];
                        
                        if (!(isset($day_schedule['closed']) && $day_schedule['closed'])) {
                            $open_time = isset($day_schedule['open']) ? $day_schedule['open'] : '09:00';
                            $close_time = isset($day_schedule['close']) ? $day_schedule['close'] : '17:00';
                            
                            $is_open = ($current_time >= $open_time && $current_time <= $close_time);
                        }
                    }
                }
                
                $branches[$row['id']] = [
                    'name' => $row['branch_name'],
                    'is_open' => $is_open,
                    'current_time' => $current_time,
                    'current_day' => $current_day
                ];
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $branches;
        } else {
            $resp['msg'] = 'Failed to retrieve branch information';
        }
        
    } catch (Exception $e) {
        error_log("Get branch open status exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch status';
    }
    
    return json_encode($resp);
}

function get_branch_hours_for_day() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $day_of_week = isset($_POST['day_of_week']) ? intval($_POST['day_of_week']) : '';
        
        if (empty($branch_id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        $target_day = isset($day) ? $day : date('l'); 
        
        $query = $this->conn->query("SELECT branch_name, operating_hours FROM `branch_list` WHERE id = '$branch_id' AND status = 1");
        
        if ($query && $query->num_rows > 0) {
            $row = $query->fetch_assoc();
            $branch_hours = null;
            
            if (!empty($row['operating_hours'])) {
                $schedule = json_decode($row['operating_hours'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && 
                    is_array($schedule) && 
                    isset($schedule[$target_day])) {
                    $branch_hours = $schedule[$target_day];
                }
            }
            
            $resp['status'] = 'success';
            $resp['data'] = [
                'branch_name' => $row['branch_name'],
                'day' => $target_day,
                'hours' => $branch_hours,
                'is_closed' => $branch_hours ? ($branch_hours['closed'] ?? false) : true,
                'formatted_hours' => $this->format_day_hours($branch_hours, $target_day)
            ];
        } else {
            $resp['msg'] = 'Branch not found or inactive';
        }
        
    } catch (Exception $e) {
        error_log("Get branch hours exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch hours';
    }
    
    return json_encode($resp);
}

private function format_day_hours($day_hours, $day_name) {
    if (!$day_hours) {
        return "Hours not set for $day_name";
    }
    
    if (isset($day_hours['closed']) && $day_hours['closed']) {
        return "Closed on $day_name";
    }
    
    $open = isset($day_hours['open']) ? $day_hours['open'] : '09:00';
    $close = isset($day_hours['close']) ? $day_hours['close'] : '17:00';
    
    try {
        $open_12h = date('g:i A', strtotime($open));
        $close_12h = date('g:i A', strtotime($close));
        return "$day_name: $open_12h - $close_12h";
    } catch (Exception $e) {
        return "$day_name: $open - $close";
    }
}

function can_branch_accept_orders() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if (empty($branch_id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        
        
        $query = $this->conn->query("SELECT branch_name, operating_hours, status FROM `branch_list` WHERE id = '$branch_id'");
        
        if (!$query || $query->num_rows == 0) {
            $resp['msg'] = 'Branch not found';
            return json_encode($resp);
        }
        
        $branch = $query->fetch_assoc();
        
        
        if ($branch['status'] != 1) {
            $resp['status'] = 'success';
            $resp['data'] = [
                'can_accept_orders' => false,
                'reason' => 'Branch is currently inactive',
                'branch_name' => $branch['branch_name']
            ];
            return json_encode($resp);
        }
        
        
        $current_day = date('l');
        $current_time = date('H:i');
        $can_accept = true;
        $reason = '';
        
        if (!empty($branch['operating_hours'])) {
            $schedule = json_decode($branch['operating_hours'], true);
            
            if (json_last_error() === JSON_ERROR_NONE && 
                is_array($schedule) && 
                isset($schedule[$current_day])) {
                
                $day_schedule = $schedule[$current_day];
                
                if (isset($day_schedule['closed']) && $day_schedule['closed']) {
                    $can_accept = false;
                    $reason = "Branch is closed today ($current_day)";
                } else {
                    $open_time = isset($day_schedule['open']) ? $day_schedule['open'] : '09:00';
                    $close_time = isset($day_schedule['close']) ? $day_schedule['close'] : '17:00';
                    
                    if ($current_time < $open_time) {
                        $can_accept = false;
                        $open_12h = date('g:i A', strtotime($open_time));
                        $reason = "Branch opens at $open_12h today";
                    } elseif ($current_time > $close_time) {
                        $can_accept = false;
                        $close_12h = date('g:i A', strtotime($close_time));
                        $reason = "Branch closed at $close_12h today";
                    }
                }
            } else {
                $reason = 'Operating hours not properly configured';
            }
        } else {
            $reason = 'Operating hours not set - contact branch directly';
        }
        
        $resp['status'] = 'success';
        $resp['data'] = [
            'can_accept_orders' => $can_accept,
            'reason' => $reason,
            'branch_name' => $branch['branch_name'],
            'current_time' => date('g:i A'),
            'current_day' => $current_day
        ];
        
    } catch (Exception $e) {
        error_log("Can branch accept orders exception: " . $e->getMessage());
        $resp['msg'] = 'Error checking branch availability';
    }
    
    return json_encode($resp);
}

function get_all_branch_hours() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        $branches = [];
        $current_day = date('l');
        $current_time = date('H:i');
        
        $query = $this->conn->query("
            SELECT id, branch_name, branch_address, operating_hours, status 
            FROM `branch_list` 
            WHERE status = 1 
            ORDER BY branch_name ASC
        ");
        
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $formatted_hours = [];
                $is_open_now = false;
                $next_opening = null;
                
                if (!empty($row['operating_hours'])) {
                    $schedule = json_decode($row['operating_hours'], true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($schedule)) {
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        
                        foreach ($days as $day) {
                            if (isset($schedule[$day])) {
                                $day_schedule = $schedule[$day];
                                
                                if (isset($day_schedule['closed']) && $day_schedule['closed']) {
                                    $formatted_hours[$day] = 'Closed';
                                } else {
                                    $open = $day_schedule['open'] ?? '09:00';
                                    $close = $day_schedule['close'] ?? '17:00';
                                    
                                    $open_12h = date('g:i A', strtotime($open));
                                    $close_12h = date('g:i A', strtotime($close));
                                    $formatted_hours[$day] = "$open_12h - $close_12h";
                                    
                                    
                                    if ($day === $current_day && 
                                        $current_time >= $open && 
                                        $current_time <= $close) {
                                        $is_open_now = true;
                                    }
                                }
                            } else {
                                $formatted_hours[$day] = 'Hours not set';
                            }
                        }
                    }
                }
                
                $branches[] = [
                    'id' => $row['id'],
                    'name' => $row['branch_name'],
                    'address' => $row['branch_address'],
                    'hours' => $formatted_hours,
                    'is_open_now' => $is_open_now,
                    'status' => $row['status']
                ];
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $branches;
            $resp['current_time'] = date('g:i A');
            $resp['current_day'] = $current_day;
        } else {
            $resp['msg'] = 'Failed to retrieve branch hours';
        }
        
    } catch (Exception $e) {
        error_log("Get all branch hours exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch hours';
    }
    
    return json_encode($resp);
}

function get_branch_orders() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if (empty($branch_id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $branch_id = (int)$branch_id;
        $page = isset($page) ? max(1, (int)$page) : 1;
        $limit = isset($limit) ? max(1, min(100, (int)$limit)) : 10;
        $offset = ($page - 1) * $limit;
        
        
        $possible_columns = ['branch_id', 'pickup_branch_id', 'pickup_branch', 'branch'];
        $working_column = null;
        
        foreach ($possible_columns as $col) {
            $test_query = $this->conn->query("SELECT COUNT(*) as total FROM `order_list` WHERE `$col` = '$branch_id' LIMIT 1");
            if ($test_query) {
                $working_column = $col;
                break;
            }
        }
        
        if (!$working_column) {
            $resp['msg'] = 'Unable to retrieve orders - database schema issue';
            return json_encode($resp);
        }
        
        
        $count_query = $this->conn->query("SELECT COUNT(*) as total FROM `order_list` WHERE `$working_column` = '$branch_id'");
        $total_orders = $count_query->fetch_assoc()['total'];
        
        
        $orders_query = $this->conn->query("
            SELECT o.*, 
                   CONCAT(u.firstname, ' ', u.lastname) as customer_name,
                   u.email as customer_email,
                   COUNT(oi.id) as item_count,
                   DATE_FORMAT(o.created_at, '%M %d, %Y at %h:%i %p') as formatted_date
            FROM `order_list` o 
            LEFT JOIN `users` u ON o.customer_id = u.id 
            LEFT JOIN `order_items` oi ON o.id = oi.order_id
            WHERE o.`$working_column` = '$branch_id'
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        
        $orders = [];
        if ($orders_query) {
            while ($order = $orders_query->fetch_assoc()) {
                
                $status_map = [
                    0 => 'Pending',
                    1 => 'Processing',
                    2 => 'Ready for Pickup',
                    3 => 'Out for Delivery', 
                    4 => 'Completed',
                    5 => 'Cancelled'
                ];
                $order['status_text'] = $status_map[$order['status']] ?? 'Unknown';
                $orders[] = $order;
            }
        }
        
        $result = [
            'orders' => $orders,
            'pagination' => [
                'total' => (int)$total_orders,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total_orders / $limit),
                'has_next' => $page < ceil($total_orders / $limit),
                'has_prev' => $page > 1
            ]
        ];
        
        $resp['status'] = 'success';
        $resp['data'] = $result;
        
    } catch (Exception $e) {
        error_log("Branch orders exception: " . $e->getMessage());
        $resp['msg'] = 'Error retrieving branch orders';
    }
    
    return json_encode($resp);
}

private function format_operating_hours($schedule) {
    if (!is_array($schedule)) {
        return 'Hours not set';
    }
    
    $formatted = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $short_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    
    $grouped_schedule = [];
    $current_group = null;
    
    foreach ($days as $index => $day) {
        if (isset($schedule[$day])) {
            $day_schedule = $schedule[$day];
            
            if (isset($day_schedule['closed']) && $day_schedule['closed']) {
                $hours_text = 'Closed';
            } else {
                $open = isset($day_schedule['open']) ? $day_schedule['open'] : '09:00';
                $close = isset($day_schedule['close']) ? $day_schedule['close'] : '17:00';
                
                
                $open_12h = date('g:i A', strtotime($open));
                $close_12h = date('g:i A', strtotime($close));
                $hours_text = "$open_12h - $close_12h";
            }
            
            
            if ($current_group && $current_group['hours'] === $hours_text && 
                $current_group['end_index'] === $index - 1) {
                $current_group['end_index'] = $index;
                $current_group['end_day'] = $short_days[$index];
            } else {
                if ($current_group) {
                    $grouped_schedule[] = $current_group;
                }
                $current_group = [
                    'start_day' => $short_days[$index],
                    'end_day' => $short_days[$index],
                    'start_index' => $index,
                    'end_index' => $index,
                    'hours' => $hours_text
                ];
            }
        }
    }
    
    
    if ($current_group) {
        $grouped_schedule[] = $current_group;
    }
    
    
    foreach ($grouped_schedule as $group) {
        if ($group['start_day'] === $group['end_day']) {
            $formatted[] = "{$group['start_day']}: {$group['hours']}";
        } else {
            $formatted[] = "{$group['start_day']}-{$group['end_day']}: {$group['hours']}";
        }
    }
    
    return implode('<br>', $formatted);
}

private function validate_branch_data($data) {
    $errors = [];
    
    
    if (empty(trim($data['branch_name'] ?? ''))) {
        $errors[] = 'Branch name is required';
    }
    
    if (empty(trim($data['branch_address'] ?? ''))) {
        $errors[] = 'Branch address is required';
    }
    
    
    if (!empty($data['branch_email']) && !filter_var($data['branch_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address format';
    }
    
    
    if (!empty($data['branch_phone'])) {
        $phone = preg_replace('/\D/', '', $data['branch_phone']);
        if (strlen($phone) < 10) {
            $errors[] = 'Phone number must be at least 10 digits';
        }
    }
    
    
    if (!empty($data['operating_hours'])) {
        $schedule = json_decode($data['operating_hours'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid operating hours format';
        } else {
            
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day) {
                if (isset($schedule[$day]) && !$schedule[$day]['closed']) {
                    $open = $schedule[$day]['open'] ?? '';
                    $close = $schedule[$day]['close'] ?? '';
                    
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $open)) {
                        $errors[] = "Invalid opening time format for $day";
                    }
                    
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $close)) {
                        $errors[] = "Invalid closing time format for $day";
                    }
                    
                    if ($open >= $close) {
                        $errors[] = "Opening time must be before closing time for $day";
                    }
                }
            }
        }
    }
    
    return $errors;
}

function can_delete_branch() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($id)) {
            $resp['msg'] = 'Branch ID is required';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        
        
        $check = $this->conn->query("SELECT branch_name FROM `branch_list` WHERE id = '$id'");
        if (!$check || $check->num_rows == 0) {
            $resp['msg'] = 'Branch not found';
            return json_encode($resp);
        }
        
        $branch_name = $check->fetch_assoc()['branch_name'];
        $dependencies = [];
        
        
        $possible_columns = ['branch_id', 'pickup_branch_id', 'pickup_branch', 'branch'];
        foreach ($possible_columns as $col) {
            $orders = $this->conn->query("SELECT COUNT(*) as count FROM `order_list` WHERE `$col` = '$id'");
            if ($orders) {
                $order_count = $orders->fetch_assoc()['count'];
                if ($order_count > 0) {
                    $dependencies[] = "$order_count orders";
                    break;
                }
            }
        }
        
        
        
        
        $resp['status'] = 'success';
        $resp['data'] = [
            'can_delete' => empty($dependencies),
            'dependencies' => $dependencies,
            'branch_name' => $branch_name
        ];
        
    } catch (Exception $e) {
        error_log("Can delete branch exception: " . $e->getMessage());
        $resp['msg'] = 'Error checking branch dependencies';
    }
    
    return json_encode($resp);
}

function bulk_update_branch_status() {
    $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $status = isset($_POST['status']) ? intval($_POST['status']) : '';
        
        if (empty($ids) || !is_array($ids)) {
            $resp['msg'] = 'Branch IDs are required';
            return json_encode($resp);
        }
        
        if (!isset($status) || ($status !== '0' && $status !== '1')) {
            $resp['msg'] = 'Valid status is required';
            return json_encode($resp);
        }
        
        $status = (int)$status;
        $ids = array_map('intval', $ids);
        $ids_str = implode(',', $ids);
        
        
        $stmt = $this->conn->prepare("UPDATE `branch_list` SET `status` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` IN (" . $ids_str . ")");
        $stmt->bind_param('i', $status);
        $update = $stmt->execute();
        
        if ($update) {
            $affected_rows = $this->conn->affected_rows;
            $action = $status == 1 ? 'activated' : 'deactivated';
            
            $resp['status'] = 'success';
            $resp['msg'] = "$affected_rows branches have been successfully $action";
            
            $this->settings->set_flashdata('success', $resp['msg']);
        } else {
            $resp['msg'] = 'Failed to update branch statuses';
        }
        
    } catch (Exception $e) {
        error_log("Bulk update branch status exception: " . $e->getMessage());
        $resp['msg'] = 'Error updating branch statuses';
    }
    
    return json_encode($resp);
}

function save_form(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $form_name = isset($_POST['form_name']) ? trim($_POST['form_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        
        if(empty($form_name)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Form name is required.';
            return json_encode($resp);
        }
        
        if(empty($description)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Description is required.';
            return json_encode($resp);
        }
        
        if(empty($category_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Category is required.';
            return json_encode($resp);
        }
        
        
        $form_name = $this->conn->real_escape_string($form_name);
        $description = $this->conn->real_escape_string($description);
        $category_id = (int)$category_id;
        $status = isset($status) ? (int)$status : 1;
        
        
        $duplicate_check = "SELECT * FROM `form_list` WHERE `name` = '{$form_name}' AND `category_id` = {$category_id} AND delete_flag = 0";
        if(!empty($id)) {
            $id = (int)$id;
            $duplicate_check .= " AND id != {$id}";
        }
        
        $check = $this->conn->query($duplicate_check);
        if($check && $check->num_rows > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "A form with the name '{$form_name}' already exists in this category.";
            return json_encode($resp);
        }
        
        
        if(empty($id)) {
            $sql = "INSERT INTO `form_list` (`name`, `description`, `category_id`, `status`, `date_created`) 
                    VALUES ('{$form_name}', '{$description}', {$category_id}, {$status}, NOW())";
        } else {
            $sql = "UPDATE `form_list` SET 
                        `name` = '{$form_name}', 
                        `description` = '{$description}', 
                        `category_id` = {$category_id}, 
                        `status` = {$status},
                        `date_updated` = NOW()
                    WHERE `id` = {$id}";
        }
        
        $save = $this->conn->query($sql);
        
        if($save) {
            $fid = !empty($id) ? $id : $this->conn->insert_id;
            $resp['fid'] = $fid;
            $resp['status'] = 'success';
            $resp['msg'] = empty($id) ? 'New form successfully saved.' : 'Form successfully updated.';
            
            if(method_exists($this->settings, 'set_flashdata')) {
                $this->settings->set_flashdata('success', $resp['msg']);
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function delete_form(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Form ID is required.';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        
        
        $check = $this->conn->query("SELECT * FROM `form_list` WHERE `id` = {$id}");
        if(!$check || $check->num_rows == 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Form not found.';
            return json_encode($resp);
        }
        
        
        $stmt = $this->conn->prepare("UPDATE `form_list` SET `delete_flag` = 1 WHERE `id` = ?"); $stmt->bind_param('i', $id); $del = $stmt->execute();
        
        if($del) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Form successfully deleted.';
            
            if(method_exists($this->settings, 'set_flashdata')) {
                $this->settings->set_flashdata('success', $resp['msg']);
            }
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_form(){
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Form ID is required.';
            return json_encode($resp);
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM `form_list` WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result && $result->num_rows > 0) {
            $resp['status'] = 'success';
            $resp['data'] = $result->fetch_assoc();
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Form not found.';
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_forms(){
    $forms = array();
    
    try {
        $query = "SELECT f.*, c.name as category_name 
                  FROM `form_list` f 
                  LEFT JOIN `category_list` c ON f.category_id = c.id 
                  WHERE f.delete_flag = 0 
                  ORDER BY f.name ASC";
        
        $qry = $this->conn->query($query);
        
        if($qry) {
            while($row = $qry->fetch_assoc()) {
                $forms[] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Exception in get_forms(): " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    return json_encode($forms);
}

function get_forms_by_category(){
    
    
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    if(empty($category_id)) {
        return json_encode(['status' => 'failed', 'msg' => 'Category ID is required']);
    }
    
    $category_id = (int)$category_id;
    
    
    $table_check = $this->conn->query("SHOW TABLES LIKE 'form_list'");
    if (!$table_check || $table_check->num_rows == 0) {
        $this->create_form_table();
    }
    
    
    $query = "SELECT * FROM form_list WHERE category_id = {$category_id} AND delete_flag = 0 ORDER BY name ASC";
    $result = $this->conn->query($query);
    
    if (!$result) {
        return json_encode(['status' => 'failed', 'msg' => 'Database error: ' . $this->conn->error]);
    }
    
    $forms = [];
    while($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }
    
    
    if (empty($forms)) {
        $this->create_default_forms($category_id);
        
        
        $result = $this->conn->query($query);
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $forms[] = $row;
            }
        }
    }
    
    return json_encode([
        'status' => 'success', 
        'data' => $forms
    ]);
}

function create_default_forms($category_id) {
    $category_id = (int)$category_id;
    
    
    $cat_query = $this->conn->query("SELECT name FROM `category_list` WHERE id = {$category_id}");
    $category_name = 'General';
    if($cat_query && $cat_query->num_rows > 0) {
        $category_name = $cat_query->fetch_assoc()['name'];
    }
    
    
    $default_forms = [
        'Tablet' => 'Solid oral dosage form',
        'Capsule' => 'Encapsulated medication',
        'Syrup' => 'Sweet liquid medicine',
        'Drops' => 'Liquid drops',
        'Cream' => 'Topical cream',
        'Ointment' => 'Topical ointment'
    ];
    
    $forms_created = 0;
    foreach($default_forms as $form_name => $description) {
        $form_name_esc = $this->conn->real_escape_string($form_name);
        $description_esc = $this->conn->real_escape_string($description);
        
        
        $existing = $this->conn->query("SELECT id FROM `form_list` WHERE name = '{$form_name_esc}' AND category_id = {$category_id} AND delete_flag = 0");
        
        if(!$existing || $existing->num_rows == 0) {
            $insert_sql = "INSERT INTO `form_list` (`name`, `description`, `category_id`, `status`, `delete_flag`) 
                          VALUES ('{$form_name_esc}', '{$description_esc}', {$category_id}, 1, 0)";
            
            if($this->conn->query($insert_sql)) {
                $forms_created++;
            }
        }
    }
    
    return $forms_created;
}

function create_form_table(){
    
    $table_check = $this->conn->query("SHOW TABLES LIKE 'form_list'");
    
    if(!$table_check || $table_check->num_rows == 0) {
        
        $create_sql = "CREATE TABLE `form_list` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `category_id` int(11) NOT NULL DEFAULT 1,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_category_id` (`category_id`),
            KEY `idx_status` (`status`),
            KEY `idx_delete_flag` (`delete_flag`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        return $this->conn->query($create_sql);
    }
    
    return true;
}

function debug_forms_data() {
    try {
        $resp = array();
        
        
        $table_check = $this->conn->query("SHOW TABLES LIKE 'form_list'");
        $resp['table_exists'] = $table_check && $table_check->num_rows > 0;
        
        if($resp['table_exists']) {
            
            $structure = $this->conn->query("DESCRIBE form_list");
            $resp['table_structure'] = array();
            while($row = $structure->fetch_assoc()) {
                $resp['table_structure'][] = $row;
            }
            
            
            $all_forms = $this->conn->query("SELECT * FROM form_list");
            $resp['all_forms'] = array();
            while($row = $all_forms->fetch_assoc()) {
                $resp['all_forms'][] = $row;
            }
            
            
            $categories = $this->conn->query("SELECT * FROM category_list WHERE delete_flag = 0");
            $resp['categories'] = array();
            while($row = $categories->fetch_assoc()) {
                $resp['categories'][] = $row;
            }
        }
        
        return json_encode($resp, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        return json_encode(['error' => $e->getMessage()]);
    }
}

function get_branch_products_report() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        $branch_filter = '';
        if(!empty($branch_id)) {
            $branch_id = (int)$branch_id;
            $branch_filter = "AND (p.branch_id = {$branch_id} OR p.branch_id IS NULL)";
        }
        
        
        $branches_query = "SELECT * FROM `branch_list` WHERE status = 1 ORDER BY branch_name ASC";
        $branches_result = $this->conn->query($branches_query);
        
        $report_data = array();
        
        
        $all_branch_products = $this->conn->query("
            SELECT p.*, c.name as category_name, f.name as form_name
            FROM `product_list` p
            LEFT JOIN `category_list` c ON p.category_id = c.id
            LEFT JOIN `form_list` f ON p.form_id = f.id
            WHERE p.branch_id IS NULL AND p.delete_flag = 0
            ORDER BY p.brand ASC, p.name ASC
        ");
        
        $all_products = array();
        while($product = $all_branch_products->fetch_assoc()) {
            $all_products[] = $product;
        }
        
        if(count($all_products) > 0) {
            $report_data[] = [
                'branch_name' => 'Available at All Branches',
                'branch_address' => '',
                'products' => $all_products
            ];
        }
        
        
        while($branch = $branches_result->fetch_assoc()) {
            $products_query = "
                SELECT p.*, c.name as category_name, f.name as form_name
                FROM `product_list` p
                LEFT JOIN `category_list` c ON p.category_id = c.id
                LEFT JOIN `form_list` f ON p.form_id = f.id
                WHERE p.branch_id = {$branch['id']} AND p.delete_flag = 0
                ORDER BY p.brand ASC, p.name ASC
            ";
            
            $products_result = $this->conn->query($products_query);
            $products = array();
            
            while($product = $products_result->fetch_assoc()) {
                $products[] = $product;
            }
            
            if(count($products) > 0 || empty($branch_id)) {
                $report_data[] = [
                    'branch_id' => $branch['id'],
                    'branch_name' => $branch['branch_name'],
                    'branch_address' => $branch['branch_address'],
                    'products' => $products
                ];
            }
        }
        
        $resp['status'] = 'success';
        $resp['data'] = $report_data;
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_product_statistics() {
    $resp = array();
    
    try {
        
        $total = $this->conn->query("SELECT COUNT(*) as count FROM `product_list` WHERE delete_flag = 0");
        $total_products = $total->fetch_assoc()['count'];
        
        
        $all_branch = $this->conn->query("SELECT COUNT(*) as count FROM `product_list` WHERE branch_id IS NULL AND delete_flag = 0");
        $all_branch_products = $all_branch->fetch_assoc()['count'];
        
        
        $specific = $this->conn->query("SELECT COUNT(*) as count FROM `product_list` WHERE branch_id IS NOT NULL AND delete_flag = 0");
        $specific_products = $specific->fetch_assoc()['count'];
        
        
        $branches = $this->conn->query("SELECT COUNT(*) as count FROM `branch_list` WHERE status = 1");
        $active_branches = $branches->fetch_assoc()['count'];
        
        $resp['status'] = 'success';
        $resp['data'] = [
            'total_products' => $total_products,
            'all_branch_products' => $all_branch_products,
            'specific_products' => $specific_products,
            'active_branches' => $active_branches
        ];
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function get_branches(){
    
    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
    if(!SecurityHelper::validateCsrf()) {
        return json_encode(['status' => 'failed', 'msg' => 'Invalid security token. Please refresh and try again.']);
    }
    $qry = $this->conn->query("SELECT id, name, address FROM `branch_list` WHERE status = 1 ORDER BY name ASC");
    $data = array();
    while($row = $qry->fetch_assoc()){
        $data[] = $row;
    }
    
    if($qry->num_rows > 0){
        return json_encode(array('status' => 'success', 'data' => $data));
    } else {
        return json_encode(array('status' => 'failed', 'msg' => 'No branches available'));
    }
}

function set_selected_branch(){
    
    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
    if(!SecurityHelper::validateCsrf()) {
        return json_encode(['status' => 'failed', 'msg' => 'Invalid security token. Please refresh and try again.']);
    }
    
    $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
    
    if(!isset($branch_id) || empty($branch_id)){
        return json_encode(array('status' => 'failed', 'msg' => 'Branch ID is required'));
    }
    
    
    $check = $this->conn->query("SELECT id, name, address FROM `branch_list` WHERE id = '{$branch_id}' AND status = 1");
    
    if($check->num_rows > 0){
        $branch = $check->fetch_assoc();
        $_SESSION['selected_branch_id'] = $branch_id;
        $_SESSION['selected_branch_name'] = $branch['name'];
        $_SESSION['selected_branch_address'] = $branch['address'];
        
        return json_encode(array(
            'status' => 'success', 
            'msg' => 'Branch selected successfully',
            'branch' => $branch
        ));
    } else {
        return json_encode(array('status' => 'failed', 'msg' => 'Invalid branch'));
    }
}

function get_selected_branch(){
    if(isset($_SESSION['selected_branch_id']) && !empty($_SESSION['selected_branch_id'])){
        $branch_id = $_SESSION['selected_branch_id'];
        
        
        $check = $this->conn->query("SELECT id, name, address FROM `branch_list` WHERE id = '{$branch_id}' AND status = 1");
        
        if($check->num_rows > 0){
            $branch = $check->fetch_assoc();
            return json_encode(array('status' => 'success', 'branch' => $branch));
        } else {
            
            unset($_SESSION['selected_branch_id']);
            unset($_SESSION['selected_branch_name']);
            unset($_SESSION['selected_branch_address']);
            return json_encode(array('status' => 'failed', 'msg' => 'No branch selected'));
        }
    } else {
        return json_encode(array('status' => 'failed', 'msg' => 'No branch selected'));
    }
}

function clear_selected_branch(){
    unset($_SESSION['selected_branch_id']);
    unset($_SESSION['selected_branch_name']);
    unset($_SESSION['selected_branch_address']);
    
    return json_encode(array('status' => 'success', 'msg' => 'Branch selection cleared'));
}

function add_to_card(){
    
    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
    if(!SecurityHelper::validateCsrf()) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid security token. Please refresh and try again.';
        return json_encode($resp);
    }
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    
    $quantity = isset($quantity) ? intval($quantity) : 1;
    
    
    if($quantity <= 0) {
        $quantity = 1;
    }
    
    
    $product_check = $this->conn->query("SELECT p.*, (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = p.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(quantity) FROM `order_items` where product_id = p.id), 0)) as `available` FROM `product_list` p WHERE p.id = '{$product_id}' AND p.delete_flag = 0");
    
    if($product_check->num_rows <= 0) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Product not found.';
        return json_encode($resp);
    }
    
    $product_data = $product_check->fetch_assoc();
    $available_stock = $product_data['available'];
    
    
    if($quantity > $available_stock) {
        $resp['status'] = 'failed';
        $resp['msg'] = "Requested quantity ({$quantity}) exceeds available stock ({$available_stock}).";
        return json_encode($resp);
    }
    
    
    $check = $this->conn->query("SELECT id, quantity FROM `cart_list` where customer_id = '{$this->settings->userdata('id')}' and product_id = '{$product_id}'");
    
    if($check->num_rows > 0 ){
        
        $row = $check->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        
        if($new_quantity > $available_stock) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Adding {$quantity} items would exceed available stock. You already have {$row['quantity']} items in your cart.";
        } else {
            $stmt = $this->conn->prepare("UPDATE `cart_list` SET quantity = ? WHERE id = ?"); $stmt->bind_param('ii', $new_quantity, $row['id']); $update = $stmt->execute();
            if($update){
                $resp['status'] = 'success';
                $resp['msg'] = "Cart updated. Total quantity is now {$new_quantity}.";
            }else{
                $resp['status'] = 'failed';
                $resp['error'] = $this->conn->error;
            }
        }
    }else{
        
        $customer_id = $this->settings->userdata('id');
        $stmt_ins = $this->conn->prepare("INSERT INTO `cart_list` (`customer_id`, `product_id`, `quantity`) VALUES (?, ?, ?)");
        $stmt_ins->bind_param('iii', $customer_id, $product_id, $quantity);
        $insert = $stmt_ins->execute();
        if($insert){
            $resp['status'] = 'success';
            $resp['msg'] = $quantity > 1 ? "{$quantity} items added to cart." : "Product added to cart.";
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
    }

    if($resp['status'] == 'success'){
        $this->settings->set_flashdata('success', isset($resp['msg']) ? $resp['msg'] : 'Product has been added to cart.');
    }
    return json_encode($resp);
}

function set_pickup_schedule(){
    
    header('Content-Type: application/json');
    
    
    require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
    
    $resp = ['status' => 'failed', 'msg' => 'Unknown error'];
    
    
    $token_valid = false;
    try {
        $token_valid = SecurityHelper::validateCsrf(false);
    } catch (Exception $e) {
        $token_valid = false;
    }
    
    
    if (!$token_valid) {
        error_log("CSRF Token validation issue - Token: " . ($_POST['csrf_token'] ?? 'not sent') . ", Session: " . ($_SESSION['csrf_token'] ?? 'not set'));
    }
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $pickup_date = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : '';
    $pickup_time = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : '';
    
    
    if(empty($id) || empty($pickup_date) || empty($pickup_time)) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'All fields are required';
        return json_encode($resp);
    }
    
    
    $date_check = DateTime::createFromFormat('Y-m-d', $pickup_date);
    if(!$date_check) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid date format';
        return json_encode($resp);
    }
    
    
    $time_check = DateTime::createFromFormat('H:i', $pickup_time);
    if(!$time_check) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid time format';
        return json_encode($resp);
    }
    
    
    $pickup_datetime = strtotime($pickup_date . ' ' . $pickup_time);
    if($pickup_datetime < time()) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Pickup date and time must be in the future';
        return json_encode($resp);
    }
    
    
    $order_sql = "SELECT ol.customer_id, ol.id, ol.pickup_date, ol.pickup_time, 
                         cl.firstname, cl.lastname, cl.email, cl.contact
                  FROM order_list ol
                  JOIN customer_list cl ON ol.customer_id = cl.id
                  WHERE ol.id = ?";
    
    $order_stmt = $this->conn->prepare($order_sql);
    $order_stmt->bind_param("i", $id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if($order_result->num_rows == 0) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Order not found';
        return json_encode($resp);
    }
    
    $order_data = $order_result->fetch_assoc();
    $old_pickup_date = $order_data['pickup_date'];
    $old_pickup_time = $order_data['pickup_time'];
    $customer_id = $order_data['customer_id'];
    $customer_email = $order_data['email'];
    $customer_phone = $order_data['contact'];
    $customer_name = trim(($order_data['firstname'] ?? '') . ' ' . ($order_data['lastname'] ?? ''));
    
    
    $sql = "UPDATE `order_list` 
            SET `pickup_date` = ?, 
                `pickup_time` = ? 
            WHERE `id` = ?";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ssi", $pickup_date, $pickup_time, $id);
    
    if($stmt->execute()){
        $resp['status'] = 'success';
        $resp['msg'] = 'Pickup schedule has been saved successfully';
        
        
        $this->sendPickupTimeChangeNotifications(
            $customer_id, 
            $id,
            $customer_name, 
            $customer_email, 
            $customer_phone,
            $old_pickup_date,
            $old_pickup_time,
            $pickup_date,
            $pickup_time
        );
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Failed to save pickup schedule';
    }
    
    return json_encode($resp);
}

private function sendPickupTimeChangeNotifications($customer_id, $order_id, $customer_name, $email, $phone, $old_date, $old_time, $new_date, $new_time) {
    try {
        
        $old_datetime_display = date('M d, Y @ g:i A', strtotime($old_date . ' ' . $old_time));
        $new_datetime_display = date('M d, Y @ g:i A', strtotime($new_date . ' ' . $new_time));
        
        
        $branch_address = '';
        if (!empty($order_id)) {
            $branch_qry = $this->conn->query("SELECT b.branch_name, b.branch_address FROM `order_list` o INNER JOIN `branch_list` b ON o.branch_id = b.id WHERE o.id = '{$order_id}'");
            if ($branch_qry && $branch_qry->num_rows > 0) {
                $branch_row = $branch_qry->fetch_assoc();
                $branch_address = $branch_row['branch_address'];
            }
        }
        
        
        if (!empty($phone)) {
            try {
                $store_name = isset($this->settings) ? $this->settings->info('name') : 'PickupDrug';
                $sms_message = "{$store_name}: Your order pickup time has been UPDATED. ";
                $sms_message .= "Old time: {$old_datetime_display}. ";
                $sms_message .= "New time: {$new_datetime_display}. ";
                if (!empty($branch_address)) {
                    $sms_message .= "Store: {$branch_address}. ";
                }
                $sms_message .= "Please update your schedule accordingly.";
                
                require_once(__DIR__ . '/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($this->conn);
                $result = $smsGateway->sendSMS($phone, $sms_message);
                error_log("Pickup SMS sent to {$phone}. Result: " . json_encode($result));
            } catch (Exception $smsError) {
                error_log("Pickup SMS sending failed: " . $smsError->getMessage());
            }
        }
        
        
        if (!empty($email)) {
            try {
                $this->sendPickupTimeChangeEmail($email, $customer_name, $old_datetime_display, $new_datetime_display);
                error_log("Pickup Email sent to {$email}");
            } catch (Exception $emailError) {
                error_log("Pickup Email sending failed: " . $emailError->getMessage());
            }
        }
        
        
        $title = "Order Pickup Time Updated";
        $message = "Your order pickup time has been changed from {$old_datetime_display} to {$new_datetime_display}.";
        
        $notif_sql = "INSERT INTO notifications (customer_id, title, message, order_id, is_read, created_at) 
                     VALUES (?, ?, ?, ?, 0, NOW())";
        $notif_stmt = $this->conn->prepare($notif_sql);
        if ($notif_stmt) {
            $notif_stmt->bind_param('issi', $customer_id, $title, $message, $order_id);
            $result = $notif_stmt->execute();
            $notif_stmt->close();
            error_log("Pickup: In-app notification created. Result: " . ($result ? 'success' : 'failed'));
        } else {
            error_log("Pickup: Failed to prepare in-app notification statement: " . $this->conn->error);
        }
    } catch (Exception $e) {
        error_log("Error sending pickup time notifications: " . $e->getMessage());
    }
}

private function sendPickupTimeChangeEmail($email, $customer_name, $old_datetime, $new_datetime) {
    try {
        $system_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
        
        $subject = "Order Pickup Time Changed - {$system_name}";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>⏰ Pickup Time Updated</h1>
                </div>
                <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                    <p style='color: #374151; font-size: 16px;'>Hello <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                    
                    <p style='color: #374151; font-size: 16px;'>Your order pickup time has been <strong style='color: #3b82f6;'>changed by our admin</strong>. Please update your schedule accordingly.</p>
                    
                    <div style='background: #f0f4ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 25px 0; border-radius: 4px;'>
                        <p style='color: #1e40af; font-weight: bold; margin-top: 0; margin-bottom: 15px;'>📅 Pickup Schedule Change:</p>
                        
                        <p style='margin: 10px 0; color: #374151;'>
                            <span style='font-weight: 600;'>Previous Time:</span><br/>
                            <span style='color: #6b7280; font-size: 18px;'>" . htmlspecialchars($old_datetime) . "</span>
                        </p>
                        
                        <p style='margin: 20px 0; color: #374151;'>
                            <span style='font-weight: 600;'>New Time:</span><br/>
                            <span style='color: #059669; font-size: 18px; font-weight: bold;'>" . htmlspecialchars($new_datetime) . "</span>
                        </p>
                    </div>
                    
                    <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 25px 0; border-radius: 4px;'>
                        <p style='margin: 0; color: #92400e; font-weight: bold;'>⚠️ Important</p>
                        <p style='margin: 5px 0 0 0; color: #b45309;'>Please make sure you're available at the new pickup time.</p>
                    </div>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>If you have any questions about this change, please contact our support team.</p>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>Thank you for using " . htmlspecialchars($system_name) . "!</p>
                </div>
                <div style='text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;'>
                    <p>" . htmlspecialchars($system_name) . " - Your Trusted Healthcare Partner</p>
                </div>
            </div>
        </body>
        </html>";
        
        
        $smtp_host = isset($this->settings) ? $this->settings->info('smtp_host') : '';
        $smtp_port = isset($this->settings) ? $this->settings->info('smtp_port') : 587;
        $smtp_email = isset($this->settings) ? $this->settings->info('smtp_email') : '';
        $smtp_password = isset($this->settings) ? $this->settings->info('smtp_password') : '';
        $smtp_encryption = isset($this->settings) ? $this->settings->info('smtp_encryption') : 'tls';
        $smtp_from_name = isset($this->settings) ? $this->settings->info('smtp_from_name') : $system_name;
        
        
        if (!empty($smtp_host) && !empty($smtp_email) && !empty($smtp_password)) {
            require_once(__DIR__ . '/SmtpMailer.php');
            $mailer = new SmtpMailer($smtp_host, $smtp_port, $smtp_email, $smtp_password, $smtp_encryption, $smtp_from_name);
            $result = $mailer->send($email, $subject, $body, true);
            
            if ($result) {
                error_log("Pickup Email: Successfully sent to {$email} via {$smtp_host}:{$smtp_port}");
            } else {
                error_log("Pickup Email: Failed to send to {$email} via {$smtp_host}:{$smtp_port}");
            }
        } else {
            error_log("Pickup Email: SMTP not configured. Email not sent to {$email}. Configure SMTP settings in Admin → Settings → Email Configuration.");
        }
        
    } catch (Exception $e) {
        error_log("Error sending pickup time change email: " . $e->getMessage());
    }
}

function queueCancellationNotification($data) {
    try {
        
        
        $customer_name = $data['firstname'] . ' ' . $data['lastname'];
        
        
        if (!empty($data['contact'])) {
            $sms_message = "Dear {$customer_name}, your order #{$data['order_code']} has been cancelled.";
            if (!empty($data['reason'])) {
                $sms_message .= " Reason: {$data['reason']}.";
            }
            if (strtolower($data['payment_method'] ?? '') === 'gcash') {
                $sms_message .= " GCash refund of P" . number_format($data['total_amount'], 2) . " will be processed within 5 mins.";
            }
            $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
            $sms_message .= " Thank you - {$store_name}";
            
            $payload = json_encode(['phone' => $data['contact'], 'message' => $sms_message]);
            $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'sms', 'customer', ?)");
            $stmt->bind_param('is', $data['order_id'], $payload);
            $stmt->execute();
        }
        
        
        if (!empty($data['email'])) {
            $payload = json_encode([
                'customer_id' => $data['customer_id'],
                'order_id' => $data['order_id'],
                'type' => 'cancellation',
                'email' => $data['email'],
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'order_code' => $data['order_code'],
                'total_amount' => $data['total_amount'],
                'payment_method' => $data['payment_method'],
                'reason' => $data['reason'],
                'notes' => $data['notes']
            ]);
            $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'email', 'customer', ?)");
            $stmt->bind_param('is', $data['order_id'], $payload);
            $stmt->execute();
        }
        
        
        $this->triggerBackgroundNotificationProcessing();
        
        return true;
    } catch (Exception $e) {
        error_log("Queue cancellation notification error: " . $e->getMessage());
        return false;
    }
}

function queueStatusUpdateNotification($data) {
    try {
        
        $notification_title = "Order {$data['order_code']} - {$data['status_text']}";
        $this->send_notification($data['customer_id'], $notification_title, $data['status_message'], $data['order_id']);
        
        
        
        
        $stmt = $this->conn->prepare("SELECT contact, email FROM `customer_list` WHERE id = ?");
        $stmt->bind_param('i', $data['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if ($customer) {
            
            if (!empty($customer['contact'])) {
                $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
                $sms_message = "{$store_name}: Order #{$data['order_code']} - {$data['status_text']}. {$data['status_message']}";
                $payload = json_encode(['phone' => $customer['contact'], 'message' => $sms_message]);
                $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'sms', 'customer', ?)");
                $stmt->bind_param('is', $data['order_id'], $payload);
                $stmt->execute();
            }
            
            
            if (!empty($customer['email'])) {
                $payload = json_encode(['customer_id' => $data['customer_id'], 'order_id' => $data['order_id'], 'status' => $data['status'], 'type' => 'status_update']);
                $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'email', 'customer', ?)");
                $stmt->bind_param('is', $data['order_id'], $payload);
                $stmt->execute();
            }
        }
        
        
        $this->triggerBackgroundNotificationProcessing();
        
        return true;
    } catch (Exception $e) {
        error_log("Queue status notification error: " . $e->getMessage());
        return false;
    }
}

function queueStatusUpdateNotifications($data) {
    return $this->queueStatusUpdateNotification($data);
}

function sendStatusUpdateNotificationsInstant($data) {
    try {
        
        $notification_title = "Order {$data['order_code']} - {$data['status_text']}";
        $this->send_notification($data['customer_id'], $notification_title, $data['status_message'], $data['order_id']);
        
        
        $stmt = $this->conn->prepare("SELECT contact, email, firstname FROM `customer_list` WHERE id = ?");
        $stmt->bind_param('i', $data['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if ($customer) {
            $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
            
            
            if (!empty($customer['contact'])) {
                try {
                    require_once(__DIR__ . '/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($this->conn);
                    $sms_message = "{$store_name}: Order #{$data['order_code']} - {$data['status_text']}. {$data['status_message']}";
                    $smsGateway->sendSMS($customer['contact'], $sms_message);
                } catch (Exception $e) {
                    error_log("Instant Status SMS error: " . $e->getMessage());
                }
            }
            
            
            if (!empty($customer['email'])) {
                try {
                    require_once(__DIR__ . '/../customer/classes/CustomerEmailService.php');
                    $emailService = new CustomerEmailService();
                    $emailService->sendOrderStatusUpdate($data['customer_id'], $data['order_id'], $data['status']);
                } catch (Exception $e) {
                    error_log("Instant Status Email error: " . $e->getMessage());
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Instant status update notification error: " . $e->getMessage());
        return false;
    }
}

function sendStatusUpdateNotificationInstant($data) {
    return $this->sendStatusUpdateNotificationsInstant($data);
}

function queueOrderNotification($data) {
    try {
        
        $notification_title = "Order #{$data['order_code']} Confirmed";
        $notification_message = "Your order has been placed! Pickup at " . $data['branch_name'] . " on " . $data['pickup_datetime'] . $data['payment_msg'];
        $this->send_notification($data['customer_id'], $notification_title, $notification_message, $data['order_id']);
        
        
        
        
        $stmt = $this->conn->prepare("SELECT firstname, lastname, contact, email FROM `customer_list` WHERE id = ?");
        $stmt->bind_param('i', $data['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if ($customer) {
            $customer_name = $customer['firstname'] . ' ' . $customer['lastname'];
            
            
            if (!empty($customer['contact'])) {
                $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
                $sms_message = "{$store_name}: Order #{$data['order_code']} confirmed! Total: ₱" . number_format($data['total_amount'], 2) . ". ";
                $sms_message .= "Pickup: {$data['branch_name']} on {$data['pickup_datetime']}. ";
                $sms_message .= ($data['payment_method'] === 'cash') ? "Pay at pickup." : strtoupper($data['payment_method']) . " payment pending.";
                
                $payload = json_encode(['phone' => $customer['contact'], 'message' => $sms_message]);
                $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'sms', 'customer', ?)");
                $stmt->bind_param('is', $data['order_id'], $payload);
                $stmt->execute();
            }
            
            
            if (!empty($customer['email'])) {
                $payload = json_encode(['customer_id' => $data['customer_id'], 'order_id' => $data['order_id']]);
                $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'email', 'customer', ?)");
                $stmt->bind_param('is', $data['order_id'], $payload);
                $stmt->execute();
            }
            
            
            $admin_phone_query = $this->conn->query("SELECT setting_value FROM settings WHERE setting_name = 'httpsms_phone_id'");
            if ($admin_phone_query && $admin_phone_query->num_rows > 0) {
                $admin_phone = $admin_phone_query->fetch_assoc()['setting_value'];
                if (!empty($admin_phone)) {
                    $store_name_admin = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
                    $admin_message = "{$store_name_admin}: New Order #{$data['order_code']} from {$customer_name}. ";
                    $admin_message .= "Total: ₱" . number_format($data['total_amount'], 2) . ". ";
                    $admin_message .= "Pickup: {$data['branch_name']} on {$data['pickup_datetime']}";
                    
                    $payload = json_encode(['phone' => $admin_phone, 'message' => $admin_message]);
                    $stmt = $this->conn->prepare("INSERT INTO `notification_queue` (order_id, notification_type, recipient_type, payload) VALUES (?, 'sms', 'admin', ?)");
                    $stmt->bind_param('is', $data['order_id'], $payload);
                    $stmt->execute();
                }
            }
        }
        
        
        $this->triggerBackgroundNotificationProcessing();
        
        return true;
    } catch (Exception $e) {
        error_log("Queue notification error: " . $e->getMessage());
        return false;
    }
}

function triggerBackgroundNotificationProcessing() {
    try {
        $phpPath = 'C:\\xampp\\php\\php.exe';
        $scriptPath = __DIR__ . '/../cron_process_notifications.php';
        
        
        if (!file_exists($phpPath)) {
            
            $phpPath = 'php';
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            
            $command = "start /B \"\" \"{$phpPath}\" \"{$scriptPath}\" > NUL 2>&1";
            pclose(popen($command, 'r'));
        } else {
            
            $command = "{$phpPath} {$scriptPath} > /dev/null 2>&1 &";
            exec($command);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Background trigger error: " . $e->getMessage());
        return false;
    }
}

function processNotificationQueueAsync() {
    try {
        
        $pending = $this->conn->query("SELECT * FROM `notification_queue` WHERE status = 'pending' AND attempts < 3 LIMIT 5");
        
        if (!$pending || $pending->num_rows == 0) return;
        
        require_once('HttpSmsGateway.php');
        require_once(__DIR__ . '/../customer/classes/EmailIntegration.php');
        $smsGateway = new HttpSmsGateway($this->conn);
        
        while ($item = $pending->fetch_assoc()) {
            $payload = json_decode($item['payload'], true);
            $success = false;
            
            try {
                if ($item['notification_type'] === 'sms') {
                    $result = $smsGateway->sendSMS($payload['phone'], $payload['message']);
                    $success = $result['success'] ?? false;
                } elseif ($item['notification_type'] === 'email') {
                    
                    if (isset($payload['type'])) {
                        switch ($payload['type']) {
                            case 'status_update':
                                $success = EmailIntegration::sendOrderStatusEmail($payload['customer_id'], $payload['order_id'], $payload['status']);
                                break;
                            case 'cancellation':
                                
                                require_once(__DIR__ . '/../customer/classes/CustomerEmailService.php');
                                $emailService = new CustomerEmailService();
                                $subject = "Order #{$payload['order_code']} - Cancellation Confirmation";
                                $body = $this->buildCancellationEmailBody($payload);
                                $success = $emailService->sendEmail($payload['email'], $subject, $body, $payload['firstname']);
                                break;
                            default:
                                $success = EmailIntegration::sendOrderConfirmationEmail($payload['customer_id'], $payload['order_id']);
                        }
                    } else {
                        $success = EmailIntegration::sendOrderConfirmationEmail($payload['customer_id'], $payload['order_id']);
                    }
                }
            } catch (Exception $e) {
                error_log("Notification send error: " . $e->getMessage());
            }
            
            
            $new_status = $success ? 'sent' : 'pending';
            $stmt = $this->conn->prepare("UPDATE `notification_queue` SET status = ?, attempts = attempts + 1, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param('si', $new_status, $item['id']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Process queue error: " . $e->getMessage());
    }
}

private function buildCancellationEmailBody($payload) {
    $customer_name = htmlspecialchars($payload['firstname'] . ' ' . $payload['lastname'], ENT_QUOTES, 'UTF-8');
    $order_code = htmlspecialchars($payload['order_code'], ENT_QUOTES, 'UTF-8');
    $reason = htmlspecialchars($payload['reason'] ?? '', ENT_QUOTES, 'UTF-8');
    $notes = !empty($payload['notes']) ? htmlspecialchars($payload['notes'], ENT_QUOTES, 'UTF-8') : '';
    $notes_block = !empty($notes) ? "<p><strong>Notes:</strong> {$notes}</p>" : '';
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: #dc2626; padding: 24px; text-align: center; color: #ffffff;'>
            <h1 style='margin: 0;'>Order Cancelled</h1>
        </div>
        <div style='padding: 24px; background: #f9fafb; color: #111827;'>
            <p>Hello {$customer_name},</p>
            <p>Your order has been cancelled.</p>
            <div style='background: #ffffff; padding: 16px; border-radius: 8px; margin: 16px 0;'>
                <p><strong>Order Number:</strong> #{$order_code}</p>
                <p><strong>Reason:</strong> {$reason}</p>
                {$notes_block}
            </div>";
    
    
    if (strtolower($payload['payment_method'] ?? '') === 'gcash') {
        $body .= "
            <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h4 style='margin: 0 0 10px 0; color: #92400e;'>💰 GCash Refund</h4>
                <p style='margin: 0; color: #78350f;'>Your refund of <strong>₱" . number_format($payload['total_amount'], 2) . "</strong> will be processed within 5 minutes.</p>
            </div>";
    }
    
    $body .= "
            <p>If you have questions, please contact us.</p>
            <p style='color: #6b7280; margin-top: 20px;'>Thank you,<br><strong>" . (isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug') . " Team</strong></p>
        </div>
    </div>";
    
    return $body;
}

function sendOrderNotificationsInstant($data) {
    try {
        
        $store_name = !empty($data['store_name']) ? $data['store_name'] : (isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug');
        $store_address = !empty($data['store_address']) ? $data['store_address'] : (isset($this->settings) ? ($this->settings->info('address') ?: '') : '');
        $store_phone = !empty($data['store_phone']) ? $data['store_phone'] : (isset($this->settings) ? ($this->settings->info('phone') ?: ($this->settings->info('mobile') ?: '')) : '');
        
        
        $notification_title = "Order #{$data['order_code']} Confirmed";
        $notification_message = "Your order has been placed! Pickup at {$store_name}";
        if (!empty($store_address)) $notification_message .= " ({$store_address})";
        $notification_message .= " on " . $data['pickup_datetime'] . $data['payment_msg'];
        $this->send_notification($data['customer_id'], $notification_title, $notification_message, $data['order_id']);
        
        
        $stmt = $this->conn->prepare("SELECT firstname, lastname, contact, email FROM `customer_list` WHERE id = ?");
        $stmt->bind_param('i', $data['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        
        $has_rx = false;
        if (!empty($data['order_id'])) {
            $rx_qry = $this->conn->query("SELECT COUNT(*) as cnt FROM `order_items` oi INNER JOIN `product_list` p ON oi.product_id = p.id WHERE oi.order_id = '{$data['order_id']}' AND COALESCE(p.is_prescription, 0) = 1");
            if ($rx_qry && $rx_qry->fetch_assoc()['cnt'] > 0) {
                $has_rx = true;
            }
        }
        
        if ($customer) {
            $customer_name = $customer['firstname'] . ' ' . $customer['lastname'];
            
            
            if (!empty($customer['contact'])) {
                try {
                    require_once(__DIR__ . '/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($this->conn);
                    
                    $sms_message = "{$store_name}: Order #{$data['order_code']} confirmed! Total: ₱" . number_format($data['total_amount'], 2) . ". ";
                    $sms_message .= "Pickup: {$store_name}";
                    if (!empty($store_address)) {
                        $sms_message .= " ({$store_address})";
                    }
                    if (!empty($store_phone)) {
                        $sms_message .= " Contact: {$store_phone}.";
                    }
                    $sms_message .= " on {$data['pickup_datetime']}. ";
                    $sms_message .= ($data['payment_method'] === 'cash') ? "Pay at pickup." : strtoupper($data['payment_method']) . " payment pending.";
                    if ($has_rx) {
                        $sms_message .= " IMPORTANT: Bring your valid prescription for Rx items.";
                    }
                    
                    $smsGateway->sendSMS($customer['contact'], $sms_message);
                } catch (Exception $e) {
                    error_log("Order SMS error: " . $e->getMessage());
                }
            }
            
            
            if (!empty($customer['email'])) {
                try {
                    require_once(__DIR__ . '/../customer/classes/EmailIntegration.php');
                    EmailIntegration::sendOrderConfirmationEmail($data['customer_id'], $data['order_id']);
                } catch (Exception $e) {
                    error_log("Order email error: " . $e->getMessage());
                }
            }
            
            
            $is_urgent = !empty($data['is_urgent']);
            $admin_phone_query = $this->conn->query("SELECT setting_value FROM settings WHERE setting_name = 'httpsms_phone_id'");
            if ($admin_phone_query && $admin_phone_query->num_rows > 0) {
                $admin_phone = $admin_phone_query->fetch_assoc()['setting_value'];
                if (!empty($admin_phone)) {
                    try {
                        if (!isset($smsGateway)) {
                            require_once(__DIR__ . '/HttpSmsGateway.php');
                            $smsGateway = new HttpSmsGateway($this->conn);
                        }
                        $urgent_prefix = $is_urgent ? '🚨 URGENT: ' : '';
                        $admin_message = "{$urgent_prefix}{$store_name}: New Order #{$data['order_code']} from {$customer_name}. ";
                        $admin_message .= "Total: ₱" . number_format($data['total_amount'], 2) . ". ";
                        $admin_message .= "Pickup: {$store_name}";
                        if (!empty($store_address)) {
                            $admin_message .= " ({$store_address})";
                        }
                        $admin_message .= " on {$data['pickup_datetime']}";
                        if ($has_rx) {
                            $admin_message .= " [RX ORDER - Prescription verification required]";
                        }
                        if ($is_urgent) {
                            $admin_message .= " ⚡ CUSTOMER NEEDS ASAP PREPARATION";
                        }
                        $smsGateway->sendSMS($admin_phone, $admin_message);
                    } catch (Exception $e) {
                        error_log("Admin SMS error: " . $e->getMessage());
                    }
                }
            }
            
            
            $notif_priority = 'info';
            if ($is_urgent) $notif_priority = 'urgent';
            elseif ($has_rx) $notif_priority = 'warning';
            
            $urgent_label = $is_urgent ? '🚨 URGENT - ' : '';
            $rx_label = $has_rx ? ' ⚠️ Contains Rx items - prescription verification required.' : '';
            $urgent_msg = $is_urgent ? ' ⚡ Customer requests immediate preparation!' : '';
            
            $this->send_admin_notification(
                "{$urgent_label}New Order #{$data['order_code']}",
                "New order from {$customer_name}. Total: ₱" . number_format($data['total_amount'], 2) . ". Pickup: {$store_name} on {$data['pickup_datetime']}.{$rx_label}{$urgent_msg}",
                $data['order_id'],
                $notif_priority
            );
            
            
            if ($is_urgent) {
                
                $this->conn->query("CREATE TABLE IF NOT EXISTS `urgent_order_followups` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `order_code` VARCHAR(50) NOT NULL,
                    `customer_name` VARCHAR(255) NOT NULL,
                    `followup_at` DATETIME NOT NULL,
                    `is_processed` TINYINT(1) DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_followup` (`followup_at`, `is_processed`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                $followup_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $esc_code = $this->conn->real_escape_string($data['order_code']);
                $esc_name = $this->conn->real_escape_string($customer_name);
                $this->conn->query("INSERT INTO `urgent_order_followups` 
                    (`order_id`, `order_code`, `customer_name`, `followup_at`) 
                    VALUES ({$data['order_id']}, '{$esc_code}', '{$esc_name}', '{$followup_time}')");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Order notification error: " . $e->getMessage());
        return false;
    }
}

function sendStatusNotificationsInstant($data) {
    try {
        
        $notification_title = "Order {$data['order_code']} - {$data['status_text']}";
        $this->send_notification($data['customer_id'], $notification_title, $data['status_message'], $data['order_id']);
        
        
        $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'Store') : 'Store';
        $store_address = isset($this->settings) ? ($this->settings->info('address') ?: '') : '';
        $store_phone = isset($this->settings) ? ($this->settings->info('phone') ?: ($this->settings->info('mobile') ?: '')) : '';
        
        
        $has_rx = false;
        if (!empty($data['order_id'])) {
            $rx_qry = $this->conn->query("SELECT COUNT(*) as cnt FROM `order_items` oi INNER JOIN `product_list` p ON oi.product_id = p.id WHERE oi.order_id = '{$data['order_id']}' AND COALESCE(p.is_prescription, 0) = 1");
            if ($rx_qry && $rx_qry->fetch_assoc()['cnt'] > 0) {
                $has_rx = true;
            }
        }
        
        
        $stmt = $this->conn->prepare("SELECT contact, email FROM `customer_list` WHERE id = ?");
        $stmt->bind_param('i', $data['customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if ($customer) {
            
            if (!empty($customer['contact'])) {
                try {
                    require_once(__DIR__ . '/HttpSmsGateway.php');
                    $smsGateway = new HttpSmsGateway($this->conn);
                    
                    $sms_message = "{$store_name}: Order #{$data['order_code']} - {$data['status_text']}. {$data['status_message']}";
                    if (!empty($store_address)) {
                        $sms_message .= " Store: {$store_name}, {$store_address}.";
                    }
                    if (!empty($store_phone)) {
                        $sms_message .= " Contact: {$store_phone}.";
                    }
                    if ($has_rx) {
                        $sms_message .= " Remember to bring your valid prescription for Rx items.";
                    }
                    $smsGateway->sendSMS($customer['contact'], $sms_message);
                } catch (Exception $e) {
                    error_log("Status SMS error: " . $e->getMessage());
                }
            }
            
            
            if (!empty($customer['email'])) {
                try {
                    require_once(__DIR__ . '/../customer/classes/EmailIntegration.php');
                    EmailIntegration::sendOrderStatusEmail($data['customer_id'], $data['order_id'], $data['status']);
                } catch (Exception $e) {
                    error_log("Status email error: " . $e->getMessage());
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Status notification error: " . $e->getMessage());
        return false;
    }
}

function sendCancellationNotificationsInstant($data) {
    try {
        $customer_name = $data['firstname'] . ' ' . $data['lastname'];
        
        
        $store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'Store') : 'Store';
        $store_address = isset($this->settings) ? ($this->settings->info('address') ?: '') : '';
        $store_phone = isset($this->settings) ? ($this->settings->info('phone') ?: ($this->settings->info('mobile') ?: '')) : '';
        
        
        if (!empty($data['contact'])) {
            try {
                require_once(__DIR__ . '/HttpSmsGateway.php');
                $smsGateway = new HttpSmsGateway($this->conn);
                
                $sms_message = "Dear {$customer_name}, your order #{$data['order_code']} has been cancelled.";
                if (!empty($data['reason'])) {
                    $sms_message .= " Reason: {$data['reason']}.";
                }
                if (strtolower($data['payment_method'] ?? '') === 'gcash') {
                    $sms_message .= " GCash refund of P" . number_format($data['total_amount'], 2) . " will be processed within 5 mins.";
                }
                if (!empty($store_address)) {
                    $sms_message .= " Store: {$store_name}, {$store_address}.";
                }
                if (!empty($store_phone)) {
                    $sms_message .= " Contact: {$store_phone}.";
                }
                $sms_message .= " Thank you - {$store_name}";
                
                $smsGateway->sendSMS($data['contact'], $sms_message);
            } catch (Exception $e) {
                error_log("Cancellation SMS error: " . $e->getMessage());
            }
        }
        
        
        if (!empty($data['email'])) {
            try {
                require_once(__DIR__ . '/../customer/classes/CustomerEmailService.php');
                $emailService = new CustomerEmailService();
                
                $subject = "Order #{$data['order_code']} - Cancellation Confirmation";
                $body = $this->buildCancellationEmailBody($data);
                $emailService->sendEmail($data['email'], $subject, $body, $data['firstname']);
            } catch (Exception $e) {
                error_log("Cancellation email error: " . $e->getMessage());
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Cancellation notification error: " . $e->getMessage());
        return false;
    }
}

function send_notification($customer_id, $title, $message, $order_id = null) {
    try {
        $customer_id = (int)$customer_id;
        $order_id_val = $order_id ? (int)$order_id : null;
        
        
        if ($order_id_val) {
            $dup_check = $this->conn->prepare(
                "SELECT id FROM `notifications` WHERE customer_id = ? AND order_id = ? AND title = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1"
            );
            if ($dup_check) {
                $dup_check->bind_param('iis', $customer_id, $order_id_val, $title);
                $dup_check->execute();
                $dup_check->store_result();
                if ($dup_check->num_rows > 0) {
                    $dup_check->close();
                    return true; 
                }
                $dup_check->close();
            }
        }
        
        $safe_title = $this->conn->real_escape_string($title);
        $safe_message = $this->conn->real_escape_string($message);
        $order_id_sql = $order_id_val ? $order_id_val : 'NULL';
        
        $sql = "INSERT INTO `notifications` 
                (`customer_id`, `title`, `message`, `order_id`, `is_read`, `created_at`) 
                VALUES ({$customer_id}, '{$safe_title}', '{$safe_message}', {$order_id_sql}, 0, NOW())";
        
        $result = $this->conn->query($sql);
        
        if(!$result) {
            error_log("Notification insert failed: " . $this->conn->error);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Send notification error: " . $e->getMessage());
        return false;
    }
}

function follow_up_order() {
    $resp = array('status' => 'failed', 'msg' => 'Unknown error');
    
    if (!isset($_SESSION['userdata']['id'])) {
        $resp['msg'] = 'Please login first.';
        return json_encode($resp);
    }
    
    $customer_id = intval($_SESSION['userdata']['id']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if ($order_id <= 0) {
        $resp['msg'] = 'Invalid order.';
        return json_encode($resp);
    }
    
    
    $stmt = $this->conn->prepare("SELECT o.*, c.firstname, c.lastname, c.contact 
                                   FROM order_list o 
                                   INNER JOIN customer_list c ON o.customer_id = c.id 
                                   WHERE o.id = ? AND o.customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        $resp['msg'] = 'Order not found.';
        return json_encode($resp);
    }
    
    if (!in_array($order['status'], [0, 1])) {
        $resp['msg'] = 'Follow up is only available for pending or packed orders.';
        return json_encode($resp);
    }

    
    
    if (!empty($order['prescription_image'])) {
        $rx_stmt = $this->conn->prepare("SELECT id FROM customer_prescriptions
                                         WHERE customer_id = ? AND image_path = ?
                                         AND status = 'approved'
                                         AND (expires_at IS NULL OR expires_at >= CURDATE())
                                         LIMIT 1");
        $rx_stmt->bind_param('is', $customer_id, $order['prescription_image']);
        $rx_stmt->execute();
        $rx_is_valid = $rx_stmt->get_result()->num_rows > 0;
        $rx_stmt->close();
        if (!$rx_is_valid) {
            $resp['msg'] = 'This order cannot send a follow-up notification because its prescription is invalid. You may still message the pharmacy directly.';
            return json_encode($resp);
        }
    }
    
    
    $check = $this->conn->query("SELECT created_at FROM admin_notifications 
                                  WHERE order_id = {$order_id} 
                                  AND title LIKE '%Follow Up%' 
                                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) 
                                  ORDER BY created_at DESC LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $resp['msg'] = 'You already followed up on this order recently. Please wait 30 minutes before following up again.';
        return json_encode($resp);
    }
    
    $customer_name = $order['firstname'] . ' ' . $order['lastname'];
    $order_code = $order['code'];
    $status_text = ($order['status'] == 0) ? 'Pending' : 'Packed';
    $store_name = $this->settings->info('name') ?: 'the store';
    
    
    $this->send_admin_notification(
        "📋 Order Follow Up - #{$order_code}",
        "Customer {$customer_name} is following up on Order #{$order_code} (Status: {$status_text}). Total: ₱" . number_format($order['total_amount'], 2) . ". Please check and update the order status.",
        $order_id,
        'warning'
    );
    
    $resp['status'] = 'success';
    $resp['msg'] = 'Your follow-up has been sent to ' . $store_name . '. The pharmacy will review your order shortly.';
    return json_encode($resp);
}

function customer_cancel_order() {
    
    header('Content-Type: application/json');
    
    $resp = array('status' => 'failed', 'msg' => 'Unknown error');
    
    try {
        if (!isset($_SESSION['userdata']['id'])) {
            $resp['msg'] = 'Please login first.';
            return json_encode($resp);
        }
        
        $customer_id = intval($_SESSION['userdata']['id']);
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        
        if ($order_id <= 0) {
            $resp['msg'] = 'Invalid order.';
            return json_encode($resp);
        }
        
        
        $stmt = $this->conn->prepare("SELECT o.*, c.firstname, c.lastname, c.contact 
                                       FROM order_list o 
                                       INNER JOIN customer_list c ON o.customer_id = c.id 
                                       WHERE o.id = ? AND o.customer_id = ?");
        $stmt->bind_param("ii", $order_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            $resp['msg'] = 'Order not found.';
            return json_encode($resp);
        }
        
        if ($order['status'] != 0) {
            $resp['msg'] = 'Only pending orders can be cancelled.';
            return json_encode($resp);
        }
        
        
        $order_created = strtotime($order['date_created']);
        $now = time();
        $elapsed_seconds = $now - $order_created;
        $cancel_limit_seconds = 15 * 60; 
        
        if ($elapsed_seconds > $cancel_limit_seconds) {
            $resp['msg'] = 'Cancellation window has expired. Orders can only be cancelled within 15 minutes of placement.';
            return json_encode($resp);
        }
        
        $customer_name = $order['firstname'] . ' ' . $order['lastname'];
        $order_code = $order['code'];
        
        
        $cancel_reason = !empty($reason) ? $this->conn->real_escape_string($reason) : 'Cancelled by customer';
        $this->conn->query("UPDATE order_list SET status = 4 WHERE id = {$order_id}");
        
        
        $items_qry = $this->conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = {$order_id}");
        if ($items_qry) {
            while ($item = $items_qry->fetch_assoc()) {
                $this->conn->query("UPDATE `stock_list` SET quantity = quantity + {$item['quantity']} 
                                   WHERE product_id = {$item['product_id']}");
            }
        }
        
        
        $reason_text = !empty($reason) ? " Reason: {$cancel_reason}" : '';
        $this->send_admin_notification(
            "❌ Order Cancelled by Customer - #{$order_code}",
            "Customer {$customer_name} has cancelled Order #{$order_code}. Total: ₱" . number_format($order['total_amount'], 2) . ".{$reason_text} Inventory has been restored.",
            $order_id,
            'urgent'
        );
        
        $resp['status'] = 'success';
        $resp['msg'] = 'Order #' . $order_code . ' has been cancelled successfully.';
    } catch (Exception $e) {
        error_log("Cancel order error: " . $e->getMessage());
        $resp['msg'] = 'An error occurred while cancelling the order.';
    }
    
    return json_encode($resp);
}

function send_admin_notification($title, $message, $order_id = null, $priority = 'info') {
    try {
        
        $this->conn->query("CREATE TABLE IF NOT EXISTS `admin_notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `order_id` INT DEFAULT NULL,
            `priority` ENUM('info','warning','urgent') DEFAULT 'info',
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_is_read` (`is_read`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_priority` (`priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $title = $this->conn->real_escape_string($title);
        $message = $this->conn->real_escape_string($message);
        $order_id = $order_id ? (int)$order_id : 'NULL';
        $priority = $this->conn->real_escape_string($priority);
        
        $sql = "INSERT INTO `admin_notifications` 
                (`title`, `message`, `order_id`, `priority`, `is_read`, `created_at`) 
                VALUES ('{$title}', '{$message}', {$order_id}, '{$priority}', 0, NOW())";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("Admin notification insert failed: " . $this->conn->error);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Send admin notification error: " . $e->getMessage());
        return false;
    }
}

function get_admin_notifications() {
    $resp = array();
    try {
        
        $this->conn->query("CREATE TABLE IF NOT EXISTS `admin_notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `order_id` INT DEFAULT NULL,
            `priority` ENUM('info','warning','urgent') DEFAULT 'info',
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_is_read` (`is_read`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_priority` (`priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $sql = "SELECT * FROM `admin_notifications` ORDER BY `created_at` DESC LIMIT 50";
        $qry = $this->conn->query($sql);
        
        $notifications = array();
        while ($row = $qry->fetch_assoc()) {
            $row['time_ago'] = $this->time_ago($row['created_at']);
            $notifications[] = $row;
        }
        
        
        $unread_qry = $this->conn->query("SELECT COUNT(*) as cnt FROM `admin_notifications` WHERE `is_read` = 0");
        $unread_count = $unread_qry->fetch_assoc()['cnt'];
        
        $resp['status'] = 'success';
        $resp['data'] = $notifications;
        $resp['unread_count'] = (int)$unread_count;
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    return json_encode($resp);
}

function mark_admin_notification_read() {
    $resp = array();
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $this->conn->query("UPDATE `admin_notifications` SET `is_read` = 1 WHERE `id` = {$id}");
        } else {
            
            $this->conn->query("UPDATE `admin_notifications` SET `is_read` = 1 WHERE `is_read` = 0");
        }
        $resp['status'] = 'success';
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
    }
    return json_encode($resp);
}

private function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}

function get_notifications() {
    $resp = array();
    
    try {
        $customer_id = $this->settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        
        $query = "SELECT n.*, 
                         CASE 
                             WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 1 
                             THEN 'Just now'
                             WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 
                             THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' minutes ago')
                             WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 
                             THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hours ago')
                             ELSE CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
                         END as time_ago
                  FROM `notifications` n
                  WHERE n.customer_id = {$customer_id}
                  ORDER BY n.created_at DESC
                  LIMIT 10";
        
        $result = $this->conn->query($query);
        
        if($result) {
            $notifications = array();
            while($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            
            $unread = $this->conn->query("SELECT COUNT(*) as count FROM `notifications` WHERE customer_id = {$customer_id} AND is_read = 0");
            $unread_count = 0;
            if($unread) {
                $unread_count = $unread->fetch_assoc()['count'];
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $notifications;
            $resp['unread_count'] = $unread_count;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }
    
    return json_encode($resp);
}

function mark_notification_read() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Notification ID required';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        $customer_id = $this->settings->userdata('id');
        
        $stmt = $this->conn->prepare("UPDATE `notifications` SET is_read = 1 WHERE id = ? AND customer_id = ?");
        $stmt->bind_param('ii', $id, $customer_id);
        $update = $stmt->execute();
        
        if($update) {
            $resp['status'] = 'success';
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
    $resp = array();
    
    try {
        $customer_id = $this->settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            return json_encode($resp);
        }
        
        $stmt = $this->conn->prepare("UPDATE `notifications` SET is_read = 1 WHERE customer_id = ? AND is_read = 0");
        $stmt->bind_param('i', $customer_id);
        $update = $stmt->execute();
        
        if($update) {
            $resp['status'] = 'success';
            $resp['msg'] = 'All notifications marked as read';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to update notifications';
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred';
    }
    
    return json_encode($resp);
}

function delete_notification() {
    $resp = array();
    
    try {
        
        require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
        if(!SecurityHelper::validateCsrf()) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Invalid security token. Please refresh and try again.';
            return json_encode($resp);
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if(empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Notification ID required';
            return json_encode($resp);
        }
        
        $id = (int)$id;
        $customer_id = $this->settings->userdata('id');
        
        $stmt = $this->conn->prepare("DELETE FROM `notifications` WHERE id = ? AND customer_id = ?");
        $stmt->bind_param('ii', $id, $customer_id);
        $delete = $stmt->execute();
        
        if($delete && $this->conn->affected_rows > 0) {
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

function get_cart_items() {
    $resp = array();
    
    try {
        $customer_id = $this->settings->userdata('id');
        
        if(empty($customer_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = 'User not logged in';
            $resp['data'] = array();
            return json_encode($resp);
        }
        
        $query = "SELECT c.*, p.name as product_name, p.price, 
                         CONCAT('uploads/', p.image_path) as image
                  FROM `cart_list` c 
                  LEFT JOIN `product_list` p ON c.product_id = p.id
                  WHERE c.customer_id = {$customer_id}
                  ORDER BY c.id DESC
                  LIMIT 5";
        
        $result = $this->conn->query($query);
        
        if($result) {
            $items = array();
            while($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            $resp['status'] = 'success';
            $resp['data'] = $items;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Database error: ' . $this->conn->error;
            $resp['data'] = array();
        }
        
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
        $resp['data'] = array();
    }
    
    return json_encode($resp);
}

public function cancel_order(){
    global $conn; 

    
    if(!isset($_SESSION['userdata']['id'])){
        return json_encode(array('status'=>'error','msg'=>'Authentication Error: You must be logged in to cancel an order.'));
    }
    
    
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $customer_id = $_SESSION['userdata']['id'];
    
    
    $qry = $conn->query("SELECT o.*, 
                                o.date_created, 
                                o.status, 
                                o.code,
                                o.payment_method,
                                o.total_amount,
                                c.firstname, 
                                c.lastname, 
                                c.contact,
                                c.email
                        FROM `order_list` o
                        INNER JOIN `customer_list` c ON o.customer_id = c.id
                        WHERE o.id = '{$order_id}' 
                          AND o.customer_id = '{$customer_id}'");
    
    if($qry === false){
        
        error_log('cancel_order SQL error: ' . $conn->error);
        return json_encode(array('status'=>'error','msg'=>'A database error occurred. Please try again.'));
    }

    if($qry->num_rows <= 0){
        return json_encode(array('status'=>'error','msg'=>'Order not found or access denied.'));
    }
    
    $order = $qry->fetch_assoc();
    
    
    if($order['status'] != 0){ 
        return json_encode(array('status'=>'error','msg'=>'Order cannot be cancelled: Status is ' . $order['status'] . '.'));
    }
    
    
    $order_timestamp = strtotime($order['date_created']);
    $current_timestamp = time();
    
    
    $time_diff_minutes = ($current_timestamp - $order_timestamp) / 60;
    
    
    if($time_diff_minutes > 15){
        return json_encode(array('status'=>'error','msg'=>'The cancellation window (15 minutes from order placement) has closed.'));
    }
    
    
    $cancel_status = 4;
    $sql = "UPDATE `order_list` SET `status` = '{$cancel_status}' WHERE `id` = '{$order_id}'";
    
    if($conn->query($sql)){
        
        $notification_title = "Order #{$order['code']} Cancelled";
        $notification_message = "You have successfully cancelled your order.";
        
        
        if(strtolower($order['payment_method']) === 'gcash'){
            $notification_message .= " Your GCash refund of ₱" . number_format($order['total_amount'], 2) . " will be processed within 5 minutes.";
        }
        
        $this->send_notification($customer_id, $notification_title, $notification_message, $order_id);
        
        
        $this->sendCancellationNotificationsInstant([
            'order_id' => $order_id,
            'order_code' => $order['code'],
            'customer_id' => $customer_id,
            'contact' => $order['contact'],
            'email' => $order['email'],
            'firstname' => $order['firstname'],
            'lastname' => $order['lastname'],
            'total_amount' => $order['total_amount'],
            'payment_method' => $order['payment_method'],
            'reason' => 'Customer requested cancellation',
            'notes' => ''
        ]);
        
        return json_encode(array('status'=>'success','msg'=>'Order #'.$order['code'].' has been successfully cancelled. Notifications have been sent.'));
    } else {
        
        error_log('cancel_order update error: ' . $conn->error);
        return json_encode(array('status'=>'error','msg'=>'Failed to cancel order. Please try again.'));
    }
}

	public function admin_cancel_order(){
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
		$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
		$data = "";
		
		
		if(empty($id) || empty($reason)){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Order ID and reason are required.';
			return json_encode($resp);
		}
		
		
		$id = $this->conn->real_escape_string($id);
		$reason = $this->conn->real_escape_string($reason);
		$notes_sql = $this->conn->real_escape_string($notes);
		
		
		$check = $this->conn->query("SELECT * FROM `order_list` WHERE id = '{$id}'")->num_rows;
		if($check <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Order not found.';
			return json_encode($resp);
		}
		
		
		$sql = "UPDATE `order_list` SET 
				`status` = 4,
				`cancellation_reason` = '{$reason}',
				`cancellation_notes` = '{$notes_sql}',
				`date_cancelled` = NOW()
				WHERE id = '{$id}'";
		
		$save = $this->conn->query($sql);
		
		if($save){
			
			$order = $this->conn->query("SELECT o.customer_id, o.code, o.payment_method, o.total_amount, c.firstname, c.lastname, c.contact, c.email 
										FROM `order_list` o 
										LEFT JOIN `customer_list` c ON o.customer_id = c.id 
										WHERE o.id = '{$id}'")->fetch_assoc();
			
			
			$reason_text = '';
			switch($reason){
				case 'no_prescription': $reason_text = 'No prescription uploaded'; break;
				case 'invalid_prescription': $reason_text = 'Invalid or expired prescription'; break;
				case 'out_of_stock': $reason_text = 'Product out of stock'; break;
				case 'customer_request': $reason_text = 'Customer request'; break;
				case 'payment_issue': $reason_text = 'Payment issue'; break;
				default: $reason_text = ucfirst(str_replace('_', ' ', $reason));
			}
			
			$notification_title = "Order #{$order['code']} Cancelled";
			$notification_message = "Your order has been cancelled. Reason: {$reason_text}";
			if(!empty($notes)){
				$notification_message .= " - {$notes}";
			}
			
			
			$this->send_notification($order['customer_id'], $notification_title, $notification_message, $id);
			
			
			$this->sendCancellationNotificationsInstant([
				'order_id' => $id,
				'order_code' => $order['code'],
				'customer_id' => $order['customer_id'],
				'contact' => $order['contact'],
				'email' => $order['email'],
				'firstname' => $order['firstname'],
				'lastname' => $order['lastname'],
				'total_amount' => $order['total_amount'] ?? 0,
				'payment_method' => $order['payment_method'] ?? 'cash',
				'reason' => $reason_text,
				'notes' => $notes
			]);
			
			$resp['status'] = 'success';
			$resp['msg'] = 'Order has been cancelled successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Failed to cancel order. ' . $this->conn->error;
		}
		
		return json_encode($resp);
	}
	
	
	
	
	
	function save_api_settings(){
		$resp = array('status' => 'failed');
		
		$api_fields = [
			'gemini_api_key',
			'smtp_host', 'smtp_port', 'smtp_email', 'smtp_password',
			'sms_gateway_type',
			'semaphore_api_key', 'semaphore_sender_name',
			'twilio_account_sid', 'twilio_auth_token', 'twilio_phone_number',
			'httpsms_api_url', 'httpsms_username', 'httpsms_password'
		];
		
		foreach ($api_fields as $field) {
			if (isset($_POST[$field])) {
				$value = $this->conn->real_escape_string(trim($_POST[$field]));
				
				
				$check = $this->conn->query("SELECT meta_field FROM system_info WHERE meta_field = '{$field}'");
				
				if ($check->num_rows > 0) {
					
					$this->conn->query("UPDATE system_info SET meta_value = '{$value}' WHERE meta_field = '{$field}'");
				} else {
					
					$this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('{$field}', '{$value}')");
				}
			}
		}
		
		$resp['status'] = 'success';
		$resp['msg'] = 'API settings saved successfully!';
		$this->settings->set_flashdata('success', $resp['msg']);
		
		return json_encode($resp);
	}
	
	function test_gemini_api(){
		$resp = array('status' => 'failed');
		
		
		$api_key = $_POST['gemini_api_key'] ?? $_POST['api_key'] ?? '';
		if (empty($api_key)) {
			$resp['msg'] = 'Gemini API key is required';
			return json_encode($resp);
		}
		
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $api_key;
		
		$data = [
			'contents' => [
				[
					'parts' => [
						['text' => 'Say "API connection successful!" in one sentence.']
					]
				]
			]
		];
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($httpCode === 200) {
			$result = json_decode($response, true);
			if (isset($result['candidates'])) {
				$resp['status'] = 'success';
				$resp['msg'] = 'Gemini API connected successfully!';
			} else {
				$resp['msg'] = 'Invalid API response';
			}
		} else {
			$resp['msg'] = 'API connection failed (HTTP ' . $httpCode . ')';
		}
		
		return json_encode($resp);
	}
	
	function test_smtp(){
		$resp = array('status' => 'failed');
		
		$smtp_email = $_POST['smtp_email'] ?? '';
		$smtp_password = $_POST['smtp_password'] ?? '';
		$smtp_host = $_POST['smtp_host'] ?? 'smtp.gmail.com';
		$smtp_port = $_POST['smtp_port'] ?? 587;
		
		if (empty($smtp_email)) {
			$resp['msg'] = 'SMTP email is required';
			return json_encode($resp);
		}
		
		if (empty($smtp_password)) {
			$resp['msg'] = 'SMTP password/app password is required';
			return json_encode($resp);
		}
		
		
		require_once(__DIR__ . '/EmailVerification.php');
		
		
		$temp_fields = ['smtp_host', 'smtp_port', 'smtp_email', 'smtp_password', 'smtp_encryption', 'smtp_from_name'];
		foreach ($temp_fields as $field) {
			if (isset($_POST[$field])) {
				$value = $this->conn->real_escape_string(trim($_POST[$field]));
				$check = $this->conn->query("SELECT meta_field FROM system_info WHERE meta_field = '{$field}'");
				if ($check->num_rows > 0) {
					$this->conn->query("UPDATE system_info SET meta_value = '{$value}' WHERE meta_field = '{$field}'");
				} else {
					$this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('{$field}', '{$value}')");
				}
			}
		}
		
		$emailService = new EmailVerification();
		$store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
		$subject = 'SMTP Test - ' . $store_name;
		$body = 'This is a test email from your ' . $store_name . ' pharmacy system.<br><br>If you receive this, <strong>SMTP is configured correctly!</strong><br><br>Host: ' . $smtp_host . '<br>Port: ' . $smtp_port;
		
		if($emailService->sendEmail($smtp_email, $subject, $body)){
			$resp['status'] = 'success';
			$resp['msg'] = 'Test email sent! Check ' . $smtp_email;
		} else {
			$resp['msg'] = 'Email send failed. Check SMTP credentials (use Gmail App Password, not regular password).';
		}
		
		return json_encode($resp);
	}
	
	
	function test_smsgate_status(){
		$resp = array('status' => 'failed');
		
		
		$local_url = $_POST['smsgate_local_url'] ?? $this->settings->info('smsgate_local_url') ?? '';
		$local_username = $_POST['smsgate_local_username'] ?? $this->settings->info('smsgate_local_username') ?? '';
		$local_password = $this->decryptValue($_POST['smsgate_local_password'] ?? $this->settings->info('smsgate_local_password') ?? '');
		
		$public_url = $_POST['smsgate_public_url'] ?? $this->settings->info('smsgate_public_url') ?? '';
		
		$cloud_url = $_POST['smsgate_cloud_url'] ?? $this->settings->info('smsgate_cloud_url') ?? 'https://api.sms-gate.app';
		$cloud_username = $_POST['smsgate_cloud_username'] ?? $this->settings->info('smsgate_cloud_username') ?? '';
		$cloud_password = $this->decryptValue($_POST['smsgate_cloud_password'] ?? $this->settings->info('smsgate_cloud_password') ?? '');
		$cloud_token = $this->decryptValue($_POST['smsgate_cloud_token'] ?? $this->settings->info('smsgate_cloud_token') ?? '');
		
		$results = [];
		$any_online = false;
		
		
		if (!empty($local_url)) {
			$local_result = $this->testSmsGateServer($local_url . '/device', $local_username, $local_password);
			$results['local'] = [
				'url' => $local_url,
				'online' => $local_result['success'],
				'error' => $local_result['error'] ?? null
			];
			if ($local_result['success']) $any_online = true;
		} else {
			$results['local'] = ['url' => '', 'online' => false, 'error' => 'Not configured'];
		}
		
		
		if (!empty($public_url)) {
			$public_result = $this->testSmsGateServer($public_url . '/device', $local_username, $local_password);
			$results['public'] = [
				'url' => $public_url,
				'online' => $public_result['success'],
				'error' => $public_result['error'] ?? null
			];
			if ($public_result['success']) $any_online = true;
		} else {
			$results['public'] = ['url' => '', 'online' => false, 'error' => 'Not configured'];
		}
		
		
		if (!empty($cloud_url) && (!empty($cloud_username) || !empty($cloud_token))) {
			$auth = !empty($cloud_username) ? $cloud_username . ':' . $cloud_password : '';
			$cloud_endpoint = rtrim($cloud_url, '/') . '/mobile/v1/device';
			$cloud_result = $this->testSmsGateServer($cloud_endpoint, $cloud_username, $cloud_password);
			$results['cloud'] = [
				'url' => $cloud_url,
				'online' => $cloud_result['success'],
				'error' => $cloud_result['error'] ?? null
			];
			if ($cloud_result['success']) $any_online = true;
		} else {
			$results['cloud'] = ['url' => $cloud_url ?: '', 'online' => false, 'error' => 'Credentials not configured'];
		}
		
		
		if ($results['local']['online']) {
			$results['recommendation'] = 'Use LOCAL server - fastest and most reliable!';
		} elseif ($results['public']['online']) {
			$results['recommendation'] = 'Use PUBLIC server - local is offline';
		} elseif ($results['cloud']['online']) {
			$results['recommendation'] = 'Use CLOUD server - local and public are offline';
		} else {
			$results['recommendation'] = 'No servers available! Check your SMS Gate app is running.';
		}
		
		$results['status'] = $any_online ? 'success' : 'failed';
		
		return json_encode($results);
	}
	
	
	private function testSmsGateServer($url, $username = '', $password = '') {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if (!empty($username)) {
			curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		
		$response = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);
		
		if ($code >= 200 && $code < 300) {
			return ['success' => true, 'response' => $response];
		} else {
			return ['success' => false, 'error' => $error ?: "HTTP $code"];
		}
	}

	function test_sms(){
		$resp = array('status' => 'failed');
		
		$test_phone = $_POST['test_phone'] ?? '';
		if (empty($test_phone)) {
			$resp['msg'] = 'Test phone number is required';
			return json_encode($resp);
		}
		
		
		$temp_fields = [
			'sms_gateway_type', 'sms_enabled',
			'semaphore_api_key', 'semaphore_sender_name',
			'twilio_account_sid', 'twilio_auth_token', 'twilio_phone_number',
			'httpsms_api_url', 'httpsms_username', 'httpsms_password',
			'smsgate_local_url', 'smsgate_local_username', 'smsgate_local_password',
			'smsgate_public_url',
			'smsgate_cloud_url', 'smsgate_cloud_token', 'smsgate_device_id',
			'smsgate_server_type', 'smsgate_cloud_username', 'smsgate_cloud_password'
		];
		
		foreach ($temp_fields as $field) {
			if (isset($_POST[$field]) && !empty($_POST[$field])) {
				$value = $this->conn->real_escape_string(trim($_POST[$field]));
				$check = $this->conn->query("SELECT meta_field FROM system_info WHERE meta_field = '{$field}'");
				
				if ($check->num_rows > 0) {
					$this->conn->query("UPDATE system_info SET meta_value = '{$value}' WHERE meta_field = '{$field}'");
				} else {
					$this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('{$field}', '{$value}')");
				}
			}
		}
		
		
		require_once(__DIR__ . '/HttpSmsGateway.php');
		$sms = new HttpSmsGateway($this->conn);
		
		$timestamp = date('H:i:s');
		$store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
		$result = $sms->sendSMS($test_phone, "Test SMS from {$store_name}! Time: {$timestamp}");
		
		if ($result['success']) {
			$resp['status'] = 'success';
			$server_used = strtoupper($result['server'] ?? 'unknown');
			$resp['msg'] = "✅ SMS sent via {$server_used} server to {$test_phone}";
		} else {
			$resp['msg'] = $result['message'] ?? 'SMS send failed';
		}
		
		return json_encode($resp);
	}
	
	
	
	
	
	
	public function process_missed_pickups(){
		$processed = 0;
		$notified = 0;
		$errors = [];
		
		
		$now = date('Y-m-d H:i:s');
		$current_time = time();
		
		
		
		
		
		$query = "SELECT o.*, 
		                 c.firstname, 
		                 c.lastname, 
		                 c.contact, 
		                 c.email,
		                 CONCAT(o.pickup_date, ' ', o.pickup_time) as full_pickup_datetime,
		                 o.pickup_reminder_sent,
		                 o.pickup_reminder_count
		          FROM `order_list` o
		          INNER JOIN `customer_list` c ON o.customer_id = c.id
		          WHERE o.status = 2
		            AND CONCAT(o.pickup_date, ' ', o.pickup_time) < '{$now}'
		            AND (o.pickup_reminder_sent IS NULL OR 
		                 TIMESTAMPDIFF(MINUTE, o.pickup_reminder_sent, '{$now}') >= 20)
		          ORDER BY o.pickup_date ASC, o.pickup_time ASC";
		
		$result = $this->conn->query($query);
		
		if(!$result){
			return json_encode([
				'status' => 'error',
				'msg' => 'Database query failed: ' . $this->conn->error
			]);
		}
		
		while($order = $result->fetch_assoc()){
			$processed++;
			$order_id = $order['id'];
			$reminder_count = intval($order['pickup_reminder_count'] ?? 0);
			
			
			$original_pickup = strtotime($order['full_pickup_datetime']);
			$new_pickup_datetime = date('Y-m-d H:i:s', strtotime('+1 day', $original_pickup));
			$new_pickup_date = date('Y-m-d', strtotime('+1 day', $original_pickup));
			$new_pickup_time = date('H:i:s', strtotime('+1 day', $original_pickup));
			
			
			$original_pickup_formatted = date('M j, Y g:i A', $original_pickup);
			$new_pickup_formatted = date('M j, Y g:i A', strtotime($new_pickup_datetime));
			
			
			$customer_name = $order['firstname'] . ' ' . $order['lastname'];
			$notification_title = "Pickup Reminder - Order #{$order['code']}";
			$notification_message = "You missed your pickup time ({$original_pickup_formatted}). Your order is still available! New deadline: {$new_pickup_formatted} (1-day extension).";
			
			
			$this->send_notification($order['customer_id'], $notification_title, $notification_message, $order_id);
			
			
			if(!empty($order['contact'])){
				try {
					require_once(__DIR__ . '/HttpSmsGateway.php');
					$smsGateway = new HttpSmsGateway($this->conn);
					
					$sms_message = "Hi {$customer_name}! You missed your pickup for Order #{$order['code']} scheduled at {$original_pickup_formatted}. ";
					$sms_message .= "Your order is still ready! NEW DEADLINE: {$new_pickup_formatted} (1-day extension). ";
					$store_name_sms = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
					$sms_message .= "Please pick up ASAP or contact us. - {$store_name_sms}";
					
					$smsGateway->sendSMS($order['contact'], $sms_message);
				} catch(Exception $e){
					$errors[] = "SMS failed for Order #{$order['code']}: " . $e->getMessage();
				}
			}
			
			
			if(!empty($order['email'])){
				try {
					require_once(__DIR__ . '/../customer/classes/CustomerEmailService.php');
					$emailService = new CustomerEmailService($this->conn);
					
					$subject = "⏰ Pickup Reminder - Order #{$order['code']} - 1 Day Extension";
					
					$body = "
					<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
					    <div style='background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;'>
					        <h1 style='color: white; margin: 0;'>⏰ Pickup Reminder</h1>
					    </div>
					    <div style='padding: 30px; background: #f9fafb;'>
					        <p>Dear {$customer_name},</p>
					        
					        <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 20px 0;'>
					            <h3 style='margin: 0 0 10px 0; color: #92400e;'>📦 You Missed Your Pickup Time</h3>
					            <p style='margin: 0; color: #78350f;'>Your order was scheduled for pickup at:</p>
					            <p style='margin: 5px 0; font-size: 18px; font-weight: bold; color: #92400e;'>{$original_pickup_formatted}</p>
					        </div>
					        
					        <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>
					            <h3 style='margin-top: 0; color: #1f2937;'>Order Details</h3>
					            <p><strong>Order Number:</strong> #{$order['code']}</p>
					            <p><strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>Ready for Pickup</span></p>
					            <p><strong>Total Amount:</strong> ₱" . number_format($order['total_amount'], 2) . "</p>
					        </div>
					        
					        <div style='background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; border-radius: 5px; margin: 20px 0;'>
					            <h3 style='margin: 0 0 10px 0; color: #065f46;'>✅ Good News - 1 Day Extension!</h3>
					            <p style='margin: 0; color: #047857;'>Your order is still available and waiting for you!</p>
					            <p style='margin: 10px 0 0 0; font-size: 18px; font-weight: bold; color: #065f46;'>NEW PICKUP DEADLINE: {$new_pickup_formatted}</p>
					        </div>
					        
					        <div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 5px; margin: 20px 0;'>
					            <h4 style='margin: 0 0 10px 0; color: #991b1b;'>⚠️ Important Reminder</h4>
					            <p style='margin: 0; color: #7f1d1d;'>Please pick up your order by the new deadline. After this extension, unclaimed orders may be cancelled.</p>
					        </div>
					        
					        <p>If you have any concerns or cannot pick up by the new deadline, please contact us immediately.</p>
					        
					        <p style='color: #6b7280; margin-top: 30px;'>Best regards,<br><strong>" . (isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug') . " Team</strong></p>
					    </div>
					    <div style='background: #1f2937; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;'>
					        <p>This is an automated reminder. Please do not reply directly to this message.</p>
					    </div>
					</div>";
					
					$emailService->sendEmail($order['email'], $subject, $body, $order['firstname']);
				} catch(Exception $e){
					$errors[] = "Email failed for Order #{$order['code']}: " . $e->getMessage();
				}
			}
			
			
			$reminder_count++;
			$update_sql = "UPDATE `order_list` 
			               SET pickup_date = '{$new_pickup_date}',
			                   pickup_time = '{$new_pickup_time}',
			                   pickup_reminder_sent = '{$now}',
			                   pickup_reminder_count = {$reminder_count}
			               WHERE id = {$order_id}";
			
			if($this->conn->query($update_sql)){
				$notified++;
			} else {
				$errors[] = "DB update failed for Order #{$order['code']}: " . $this->conn->error;
			}
		}
		
		
		$this->add_pickup_reminder_columns();
		
		return json_encode([
			'status' => 'success',
			'msg' => "Processed {$processed} missed pickup(s), notified {$notified} customer(s)",
			'processed' => $processed,
			'notified' => $notified,
			'errors' => $errors
		]);
	}
	
	
	private function add_pickup_reminder_columns(){
		
		$check = $this->conn->query("SHOW COLUMNS FROM `order_list` LIKE 'pickup_reminder_sent'");
		
		if($check->num_rows == 0){
			
			$this->conn->query("ALTER TABLE `order_list` 
			                   ADD COLUMN `pickup_reminder_sent` DATETIME NULL AFTER `pickup_time`");
		}
		
		$check2 = $this->conn->query("SHOW COLUMNS FROM `order_list` LIKE 'pickup_reminder_count'");
		
		if($check2->num_rows == 0){
			
			$this->conn->query("ALTER TABLE `order_list` 
			                   ADD COLUMN `pickup_reminder_count` INT DEFAULT 0 AFTER `pickup_reminder_sent`");
		}
	}
	
	
	
	
	
	
	private function encryptValue($value, $key = null) {
		if(empty($value)) return '';
		
		$key = $key ?: $this->getEncryptionKey();
		$iv = openssl_random_pseudo_bytes(16);
		$encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
		
		return base64_encode($iv . '::' . $encrypted);
	}
	
	
	private function decryptValue($encrypted, $key = null) {
		if(empty($encrypted)) return '';
		
		try {
			$key = $key ?: $this->getEncryptionKey();
			$data = base64_decode($encrypted);
			
			if(strpos($data, '::') === false) {
				
				return $encrypted;
			}
			
			list($iv, $encrypted) = explode('::', $data, 2);
			return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
		} catch(Exception $e) {
			return $encrypted; 
		}
	}
	
	
	private function getEncryptionKey() {
		$result = $this->conn->query("SELECT meta_value FROM system_info WHERE meta_field = 'api_encryption_key'");
		
		if($result && $row = $result->fetch_assoc()) {
			if(!empty($row['meta_value'])) {
				return $row['meta_value'];
			}
		}
		
		
		$newKey = bin2hex(random_bytes(32));
		$this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('api_encryption_key', '$newKey') 
		                    ON DUPLICATE KEY UPDATE meta_value = '$newKey'");
		return $newKey;
	}
	
	
	function save_secure_settings(){
		$resp = array('status' => 'failed');
		
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		try {
			if(!SecurityHelper::validateCsrf(false)) {
				
				error_log('Settings saved without CSRF token - consider adding CSRF protection');
			}
		} catch(Exception $e) {
			
		}
		
		
		
		
		$sensitive_fields = [
			'smtp_password',
			'semaphore_api_key',
			'twilio_auth_token',
			'httpsms_password',
			'smsgate_local_password',
			'smsgate_cloud_token',
			'smsgate_cloud_password',
			'facebook_page_access_token',
			'facebook_app_secret'
		];
		
		
		$allowed_fields = [
			
			'gemini_api_key',
			
			'smtp_host', 'smtp_port', 'smtp_email', 'smtp_password', 'smtp_encryption', 'smtp_from_name',
			
			'sms_gateway_type', 'sms_enabled',
			
			'semaphore_api_key', 'semaphore_sender_name',
			
			'twilio_account_sid', 'twilio_auth_token', 'twilio_phone_number',
			
			'httpsms_api_url', 'httpsms_username', 'httpsms_password',
			
			'smsgate_local_url', 'smsgate_local_username', 'smsgate_local_password',
			
			'smsgate_public_url',
			
			'smsgate_cloud_url', 'smsgate_cloud_token', 'smsgate_device_id',
			'smsgate_server_type', 'smsgate_cloud_username', 'smsgate_cloud_password',
			
			'reminder_enabled', 'reminder_sms_enabled', 'reminder_email_enabled',
			'reminder_advance_hours', 'reminder_daily_limit', 'reminder_cron_interval',
			
			'email_verification_enabled', 'otp_expiry_minutes', 'otp_length', 'max_otp_attempts',
			
			'facebook_page_access_token', 'facebook_verify_token', 'facebook_app_secret',
			
			'api_encryption_key'
		];
		
		$updated = 0;
		
		foreach($allowed_fields as $field) {
			if(isset($_POST[$field])) {
				$value = trim($_POST[$field]);
				
				
				if(in_array($field, $sensitive_fields) && !empty($value)) {
					$value = $this->encryptValue($value);
				}
				
				$value = $this->conn->real_escape_string($value);
				
				
				$check = $this->conn->query("SELECT meta_field FROM system_info WHERE meta_field = '{$field}'");
				
				if($check && $check->num_rows > 0) {
					$this->conn->query("UPDATE system_info SET meta_value = '{$value}' WHERE meta_field = '{$field}'");
				} else {
					$this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('{$field}', '{$value}')");
				}
				
				$updated++;
			}
		}
		
		
		$this->logSecurityEvent('settings_updated', 'Updated ' . $updated . ' settings');
		
		$resp['status'] = 'success';
		$resp['msg'] = 'Settings saved securely! (' . $updated . ' settings updated)';
		$this->settings->set_flashdata('success', $resp['msg']);
		
		return json_encode($resp);
	}
	
	
	private function logSecurityEvent($event, $details = '') {
		
		$this->conn->query("CREATE TABLE IF NOT EXISTS security_logs (
			id INT AUTO_INCREMENT PRIMARY KEY,
			event VARCHAR(100) NOT NULL,
			details TEXT,
			ip_address VARCHAR(45),
			user_id INT,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)");
		
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$user_id = $_SESSION['userdata']['id'] ?? 0;
		$event = $this->conn->real_escape_string($event);
		$details = $this->conn->real_escape_string($details);
		
		$this->conn->query("INSERT INTO security_logs (event, details, ip_address, user_id) 
		                    VALUES ('{$event}', '{$details}', '{$ip}', {$user_id})");
	}
	
	
	function test_email_verification_api(){
		$resp = array('status' => 'failed');
		
		$test_email = $_POST['test_email'] ?? '';
		if(empty($test_email)) {
			$resp['msg'] = 'Test email address is required';
			return json_encode($resp);
		}
		
		if(!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
			$resp['msg'] = 'Invalid email format';
			return json_encode($resp);
		}
		
		
		$otp_length = $_POST['otp_length'] ?? 6;
		$otp = str_pad(random_int(0, pow(10, $otp_length) - 1), $otp_length, '0', STR_PAD_LEFT);
		
		
		require_once(__DIR__ . '/EmailVerification.php');
		$emailService = new EmailVerification();
		
		$store_name = isset($this->settings) ? ($this->settings->info('name') ?: 'PickupDrug') : 'PickupDrug';
		$subject = 'Test OTP - ' . $store_name;
		$body = "This is a test email from {$store_name}.<br><br>Your test OTP is: <strong>{$otp}</strong><br><br>If you received this email, your SMTP is configured correctly!";
		
		if($emailService->sendEmail($test_email, $subject, $body)) {
			$resp['status'] = 'success';
			$resp['msg'] = "Test email sent to {$test_email}! Check your inbox.";
		} else {
			$resp['msg'] = 'Failed to send email. Check SMTP settings.';
		}
		
		return json_encode($resp);
	}
	
	
	function test_reminder_api(){
		$resp = array('status' => 'failed');
		
		
		require_once(__DIR__ . '/MedicationReminderManager.php');
		$reminderManager = new MedicationReminderManager();
		
		$pending = $reminderManager->getPendingReminders();
		
		$resp['status'] = 'success';
		$resp['msg'] = 'Reminder system checked. Found ' . count($pending) . ' pending reminders.';
		
		return json_encode($resp);
	}
	
	function restore_item(){
		$resp = array('status' => 'failed', 'msg' => '');
		
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$deleted_id = isset($_POST['deleted_id']) ? intval($_POST['deleted_id']) : 0;
		
		if($deleted_id <= 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Invalid deleted item ID.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT * FROM `deleted_items` WHERE id = ?");
		$stmt->bind_param('i', $deleted_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows == 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Deleted item not found.';
			return json_encode($resp);
		}
		
		$deleted_item = $result->fetch_assoc();
		$table_name = $deleted_item['table_name'];
		$item_id = $deleted_item['item_id'];
		$item_data = json_decode($deleted_item['item_data'], true);
		
		
		$restore = false;
		if($table_name == 'order_list'){
			
			$stmt = $this->conn->prepare("UPDATE `order_list` SET `deleted_at` = NULL WHERE id = ?");
			$stmt->bind_param('i', $item_id);
			$restore = $stmt->execute();
		} elseif($table_name == 'product_list' || $table_name == 'category_list'){
			
			$stmt = $this->conn->prepare("UPDATE `{$table_name}` SET `delete_flag` = 0 WHERE id = ?");
			$stmt->bind_param('i', $item_id);
			$restore = $stmt->execute();
		} elseif($table_name == 'users'){
			
			$stmt = $this->conn->prepare("UPDATE `users` SET `delete_flag` = 0 WHERE id = ?");
			$stmt->bind_param('i', $item_id);
			$restore = $stmt->execute();
		} elseif($table_name == 'customer_list'){
			
			$stmt = $this->conn->prepare("UPDATE `customer_list` SET `deleted_at` = NULL, `status` = 1 WHERE id = ?");
			$stmt->bind_param('i', $item_id);
			$restore = $stmt->execute();
		} else {
			$resp['msg'] = 'Restore not supported for this item type.';
			return json_encode($resp);
		}
		
		if($restore){
			
			$stmt = $this->conn->prepare("DELETE FROM `deleted_items` WHERE id = ?");
			$stmt->bind_param('i', $deleted_id);
			$stmt->execute();
			
			$resp['status'] = 'success';
			$resp['msg'] = 'Item restored successfully.';
			$this->settings->set_flashdata('success', 'Item restored successfully.');
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Failed to restore item: ' . $this->conn->error;
		}
		
		return json_encode($resp);
	}
	
	function get_deleted_items(){
		
		
		$qry = $this->conn->query("SELECT d.*, u.username as deleted_by_name 
		                          FROM `deleted_items` d 
		                          LEFT JOIN users u ON d.deleted_by = u.id 
		                          ORDER BY d.deleted_at DESC 
		                          LIMIT 1000");
		
		$items = array();
		if($qry){
			while($row = $qry->fetch_assoc()){
				$item_data = json_decode($row['item_data'], true);
				$row['parsed_data'] = $item_data;
				$items[] = $row;
			}
		}
		
		return json_encode($items);
	}
	
	
	function permanent_delete_item(){
		$resp = array('status' => 'failed', 'msg' => '');
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		$deleted_id = isset($_POST['deleted_id']) ? intval($_POST['deleted_id']) : 0;
		
		if($deleted_id <= 0){
			$resp['msg'] = 'Invalid deleted item ID.';
			return json_encode($resp);
		}
		
		
		$stmt = $this->conn->prepare("SELECT id, table_name, item_id FROM `deleted_items` WHERE id = ?");
		$stmt->bind_param('i', $deleted_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows == 0){
			$resp['msg'] = 'Deleted item not found.';
			return json_encode($resp);
		}
		
		$item = $result->fetch_assoc();
		
		
		$table_name = $item['table_name'];
		$item_id = $item['item_id'];
		
		
		if(in_array($table_name, ['order_list', 'product_list', 'category_list', 'users', 'customer_list'])){
			$del_stmt = $this->conn->prepare("DELETE FROM `{$table_name}` WHERE id = ?");
			if($del_stmt){
				$del_stmt->bind_param('i', $item_id);
				@$del_stmt->execute();
				$del_stmt->close();
			}
		}
		
		
		$stmt = $this->conn->prepare("DELETE FROM `deleted_items` WHERE id = ?");
		$stmt->bind_param('i', $deleted_id);
		
		if($stmt->execute()){
			$resp['status'] = 'success';
			$resp['msg'] = 'Item permanently deleted.';
			$this->settings->set_flashdata('success', 'Item permanently deleted.');
		} else {
			$resp['msg'] = 'Failed to delete item: ' . $this->conn->error;
		}
		
		return json_encode($resp);
	}
	
	
	function purge_expired_items(){
		$resp = array('status' => 'failed', 'msg' => '');
		require_once(__DIR__ . '/../app/helpers/SecurityHelper.php');
		if(!SecurityHelper::validateCsrf()) {
			$resp['msg'] = 'Invalid security token. Please refresh and try again.';
			return json_encode($resp);
		}
		
		
		$expired_qry = $this->conn->query("SELECT id, table_name, item_id FROM `deleted_items` WHERE `deleted_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
		$purged_count = 0;
		
		if($expired_qry && $expired_qry->num_rows > 0){
			while($item = $expired_qry->fetch_assoc()){
				
				if(in_array($item['table_name'], ['order_list', 'product_list', 'category_list', 'users', 'customer_list'])){
					$del_stmt = $this->conn->prepare("DELETE FROM `{$item['table_name']}` WHERE id = ?");
					if($del_stmt){
						$del_stmt->bind_param('i', $item['item_id']);
						@$del_stmt->execute();
						$del_stmt->close();
					}
				}
				$purged_count++;
			}
		}
		
		
		$this->conn->query("DELETE FROM `deleted_items` WHERE `deleted_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
		
		$resp['status'] = 'success';
		$resp['msg'] = $purged_count > 0 ? $purged_count . ' expired item(s) permanently deleted.' : 'No expired items found.';
		$this->settings->set_flashdata('success', $resp['msg']);
		
		return json_encode($resp);
	}
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'Master.php') {
    
    if (ob_get_level()) {
        ob_end_clean();
    }

    $Master = new Master();
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
    $sysset = new SystemSettings();

header('Content-Type: application/json');

switch ($action) {
	case 'delete_img':
		echo $Master->delete_img();
	break;
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	case 'save_stock':
		echo $Master->save_stock();
	break;
	case 'delete_stock':
		echo $Master->delete_stock();
	break;
	case 'add_to_card':
		echo $Master->add_to_card();
	break;
	case 'update_cart':
		echo $Master->update_cart();
	break;
	case 'delete_cart':
		echo $Master->delete_cart();
	break;
	case 'clear_cart':
		echo $Master->clear_cart();
	break;
	case 'place_order':
		echo $Master->place_order();
	break;
	case 'delete_order':
		echo $Master->delete_order();
	break;
	case 'restore_item':
		echo $Master->restore_item();
	break;
	case 'get_deleted_items':
		echo $Master->get_deleted_items();
	break;
	case 'permanent_delete_item':
		echo $Master->permanent_delete_item();
	break;
	case 'purge_expired_items':
		echo $Master->purge_expired_items();
	break;
	case 'update_order_status':
		echo $Master->update_order_status();
	break;
	case 'save_inquiry':
		echo $Master->save_inquiry();
	break;
	case 'delete_inquiry':
		echo $Master->delete_inquiry();
	break;
	case 'save_branch':
    echo $Master->save_branch();
    break;
case 'get_branch':
    echo $Master->get_branch();
    break;
case 'toggle_branch':
    echo $Master->toggle_branch();
    break;
case 'delete_branch':
    echo $Master->delete_branch();
    break;
case 'get_all_branches':
    echo $Master->get_all_branches();
    break;
case 'get_active_branches':
    echo $Master->get_active_branches();
    break;
case 'get_branch_stats':
    echo $Master->get_branch_stats();
    break;
case 'get_branch_orders':
    echo $Master->get_branch_orders();
    break;
case 'can_delete_branch':
    echo $Master->can_delete_branch();
    break;
case 'bulk_update_branch_status':
    echo $Master->bulk_update_branch_status();
    break;
case 'get_branch_open_status':
    echo $Master->get_branch_open_status();
    break;
case 'get_branch_hours_for_day':
    echo $Master->get_branch_hours_for_day();
    break;
case 'can_branch_accept_orders':
    echo $Master->can_branch_accept_orders();
    break;
case 'get_all_branch_hours':
    echo $Master->get_all_branch_hours();
    break;
case 'save_form':
    echo $Master->save_form();
    break;
case 'delete_form':
    echo $Master->delete_form();
    break;
case 'get_form':
    echo $Master->get_form();
    break;
case 'get_forms':
    echo $Master->get_forms();
    break;
case 'set_pickup_schedule':
    try {
        echo $Master->set_pickup_schedule();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'failed', 'msg' => 'An error occurred: ' . $e->getMessage()]);
    }
    break;
case 'get_forms_by_category':
    echo $Master->get_forms_by_category();
    break;
case 'get_product_forms':
    echo $Master->get_product_forms();
    break;
case 'debug_forms_data':
    echo $Master->debug_forms_data();
    break;
case 'get_product_statistics':
    echo $Master->get_product_statistics();
    break;
case 'get_notifications':
    echo $Master->get_notifications();
    break;
case 'get_cart_items':
    echo $Master->get_cart_items();
    break;
case 'mark_notification_read':
    echo $Master->mark_notification_read();
    break;
case 'mark_all_notifications_read':
    echo $Master->mark_all_notifications_read();
    break;
case 'delete_notification':
    echo $Master->delete_notification();
    break;
case 'cancel_order':
    echo $Master->cancel_order();
    break;
case 'admin_cancel_order':
    echo $Master->admin_cancel_order();
    break;
case 'create_sample_forms':
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 1;
    $created = $Master->create_sample_forms($category_id);
    echo json_encode(['status' => 'success', 'created' => $created]);
    break;
case 'save_api_settings':
    echo $Master->save_api_settings();
    break;
case 'save_secure_settings':
    echo $Master->save_secure_settings();
    break;
case 'test_gemini_api':
    echo $Master->test_gemini_api();
    break;
case 'test_smtp':
case 'test_smtp_api':
    echo $Master->test_smtp();
    break;
case 'test_sms':
case 'test_sms_api':
    echo $Master->test_sms();
    break;
case 'test_smsgate_status':
    echo $Master->test_smsgate_status();
    break;
case 'test_email_verification_api':
    echo $Master->test_email_verification_api();
    break;
case 'test_reminder_api':
    echo $Master->test_reminder_api();
    break;
case 'get_admin_notifications':
    echo $Master->get_admin_notifications();
    break;
case 'mark_admin_notification_read':
    echo $Master->mark_admin_notification_read();
    break;
}

    exit;
}
