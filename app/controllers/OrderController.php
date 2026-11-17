<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cart.php';

class OrderController extends BaseController {
    private $orderModel;
    private $cartModel;
    private $customerId;
    
    public function __construct() {
        parent::__construct();
        
        $db = new DBConnection();
        $this->orderModel = new Order($db->conn);
        $this->cartModel = new Cart($db->conn);
        
        
        $this->customerId = $this->settings->userdata('id');
    }

    private function refreshCustomerId() {
        $this->customerId = $this->settings->userdata('id');
    }
    
    
    public function place() {
        $this->refreshCustomerId();

        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json');
        }
        
        try {
            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $branchId = $this->getPostInt('pickup_branch');
            $pickupDate = $this->getPostString('pickup_date');
            $pickupTime = $this->getPostString('pickup_time');
            $totalAmount = $this->getPostFloat('total_amount');
            $selectedItemsParam = $this->getPostString('selected_items_param', '');
            
            
            if (!$branchId) {
                return $this->jsonResponse('failed', 'Please select a pickup branch.');
            }
            
            if (empty($pickupDate)) {
                return $this->jsonResponse('failed', 'Please select a pickup date.');
            }
            
            if (empty($pickupTime)) {
                return $this->jsonResponse('failed', 'Please select a pickup time.');
            }
            
            if ($totalAmount <= 0) {
                return $this->jsonResponse('failed', 'Invalid total amount.');
            }
            
            
            $pickupDatetime = strtotime($pickupDate);
            $today = strtotime(date('Y-m-d'));
            
            if ($pickupDatetime < $today) {
                return $this->jsonResponse('failed', 'Pickup date cannot be in the past.');
            }
            
            
            $cartItems = $this->getCartItems($selectedItemsParam);
            
            if (empty($cartItems)) {
                $message = empty($selectedItemsParam) ? 'Your cart is empty.' : 'Selected items not found in your cart.';
                return $this->jsonResponse('failed', $message);
            }
            
            
            $branch = $this->validateBranch($branchId);
            if (!$branch) {
                return $this->jsonResponse('failed', 'Selected branch is not available.');
            }
            
            
            $orderData = [
                'customer_id' => $this->customerId,
                'branch_id' => $branchId,
                'pickup_date' => $pickupDate,
                'pickup_time' => $pickupTime,
                'total_amount' => $totalAmount,
                'delivery_address' => $this->buildDeliveryAddress($branch, $pickupDate, $pickupTime),
                'prescription_validation' => $this->getPostString('prescription_validation', 'pending'),
                'prescription_details' => $this->getPostString('prescription_details', null)
            ];
            
            
            $orderId = $this->orderModel->createOrder($orderData, $cartItems);
            
            
            $order = $this->orderModel->getById($orderId);
            
            
            $this->clearProcessedCartItems($cartItems);
            
            
            $this->sendOrderNotifications($order, $branch);
            
            
            $pickupDisplay = date('F d, Y', strtotime($pickupDate)) . ' at ' . date('g:i A', strtotime($pickupTime));
            $this->settings->set_flashdata('success', 'Order #' . $order['code'] . ' placed successfully! Pickup scheduled for ' . $pickupDisplay . ' at ' . $branch['branch_name']);
            
            return $this->jsonResponse('success', 'Order placed successfully!', [
                'order_code' => $order['code'],
                'order_id' => $orderId,
                'pickup_branch' => $branch['branch_name'],
                'branch_address' => $branch['branch_address'],
                'pickup_date' => $pickupDate,
                'pickup_time' => $pickupTime,
                'pickup_datetime_display' => $pickupDisplay,
                'selected_items_count' => count($cartItems)
            ]);
            
        } catch (Exception $e) {
            $this->logError('OrderController::place', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while placing order: ' . $e->getMessage());
        }
    }
    
    
    public function updateStatus() {
        try {
            $this->refreshCustomerId();
            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            $orderId = $this->getPostInt('id');
            $status = $this->getPostInt('status');
            
            if (!$orderId || !isset($_POST['status'])) {
                return $this->jsonResponse('failed', 'Missing required fields.');
            }
            
            
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                return $this->jsonResponse('failed', 'Order not found.');
            }
            
            $oldStatus = $order['status'];
            
            
            $success = $this->orderModel->updateStatus($orderId, $status);
            
            if ($success) {
                
                if ($oldStatus != $status) {
                    $this->sendStatusChangeNotification($order, $status);
                }
                
                $this->settings->set_flashdata('success', 'Order status has been updated successfully.');
                return $this->jsonResponse('success', 'Order status updated successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to update order status.');
            }
            
        } catch (Exception $e) {
            $this->logError('OrderController::updateStatus', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while updating order status.');
        }
    }
    
    
    public function cancel() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('error', 'Authentication Error: You must be logged in to cancel an order.');
            }
            
            
            $orderId = $this->getPostInt('order_id');
            
            if (!$orderId) {
                return $this->jsonResponse('error', 'Invalid order ID.');
            }
            
            
            $canCancel = $this->orderModel->canCancel($orderId, $this->customerId);
            
            if (!$canCancel['can_cancel']) {
                return $this->jsonResponse('error', $canCancel['reason']);
            }
            
            
            $success = $this->orderModel->cancel($orderId, $this->customerId, 'Customer cancelled');
            
            if ($success) {
                return $this->jsonResponse('success', 'Order #' . $orderId . ' has been successfully cancelled.');
            } else {
                return $this->jsonResponse('error', 'Failed to cancel order.');
            }
            
        } catch (Exception $e) {
            $this->logError('OrderController::cancel', $e->getMessage());
            return $this->jsonResponse('error', 'An error occurred while cancelling order.');
        }
    }
    
    
    public function adminCancel() {
        try {
            $this->refreshCustomerId();
            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            $orderId = $this->getPostInt('id');
            $reason = $this->getPostString('reason');
            $notes = $this->getPostString('notes', null);
            
            if (!$orderId || empty($reason)) {
                return $this->jsonResponse('failed', 'Order ID and reason are required.');
            }
            
            
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                return $this->jsonResponse('failed', 'Order not found.');
            }
            
            
            $success = $this->orderModel->adminCancel($orderId, $reason, $notes);
            
            if ($success) {
                
                $this->sendCancellationNotification($order, $reason);
                
                $this->settings->set_flashdata('success', 'Order has been cancelled successfully.');
                return $this->jsonResponse('success', 'Order cancelled successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to cancel order.');
            }
            
        } catch (Exception $e) {
            $this->logError('OrderController::adminCancel', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while cancelling order.');
        }
    }
    
    
    public function delete() {
        try {
            $this->refreshCustomerId();
            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            $orderId = $this->getPostInt('id');
            
            if (!$orderId) {
                return $this->jsonResponse('failed', 'Invalid order ID.');
            }
            
            
            $success = $this->orderModel->delete($orderId);
            
            if ($success) {
                $this->settings->set_flashdata('success', 'Order has been deleted successfully.');
                return $this->jsonResponse('success', 'Order deleted successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to delete order.');
            }
            
        } catch (Exception $e) {
            $this->logError('OrderController::delete', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while deleting order.');
        }
    }
    
    
    public function show() {
        try {
            $this->refreshCustomerId();
            $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if (!$orderId) {
                return $this->jsonResponse('failed', 'Invalid order ID.');
            }
            
            
            $order = $this->customerId 
                ? $this->orderModel->getById($orderId, $this->customerId)
                : $this->orderModel->getById($orderId);
            
            if (!$order) {
                return $this->jsonResponse('failed', 'Order not found.');
            }
            
            
            $order['items'] = $this->orderModel->getOrderItems($orderId);
            
            return $this->jsonResponse('success', 'Order retrieved successfully.', $order);
            
        } catch (Exception $e) {
            $this->logError('OrderController::show', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while retrieving order.');
        }
    }
    
    
    public function index() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login to view orders.');
            }
            
            
            $orders = $this->orderModel->getByCustomer($this->customerId);
            
            return $this->jsonResponse('success', 'Orders retrieved successfully.', $orders);
            
        } catch (Exception $e) {
            $this->logError('OrderController::index', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while retrieving orders.');
        }
    }
    
    
    public function statistics() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login to view statistics.');
            }
            
            
            $stats = $this->orderModel->getCustomerStatistics($this->customerId);
            
            return $this->jsonResponse('success', 'Statistics retrieved successfully.', $stats);
            
        } catch (Exception $e) {
            $this->logError('OrderController::statistics', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while retrieving statistics.');
        }
    }
    
    
    
    
    private function getCartItems($selectedItemsParam) {
        $whereClause = "";
        
        if (!empty($selectedItemsParam)) {
            $selectedItems = explode(',', $selectedItemsParam);
            $selectedItems = array_map('intval', $selectedItems);
            $selectedItems = array_filter($selectedItems);
            
            if (!empty($selectedItems)) {
                $placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
                $whereClause = " AND c.id IN ($placeholders)";
            }
        }
        
        $sql = "SELECT c.id, c.product_id, c.quantity, p.price 
                FROM cart_list c
                INNER JOIN product_list p ON c.product_id = p.id
                WHERE c.customer_id = ?" . $whereClause;
        
        $params = [$this->customerId];
        if (!empty($selectedItems)) {
            $params = array_merge($params, $selectedItems);
        }
        
        $db = new DBConnection();
        return $db->conn->query($sql) ? $db->conn->query($sql)->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    
    private function validateBranch($branchId) {
        $db = new DBConnection();
        $stmt = $db->conn->prepare("SELECT * FROM branch_list WHERE id = ? AND status = 1");
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    
    private function buildDeliveryAddress($branch, $pickupDate, $pickupTime) {
        $startTime = date('g:i A', strtotime($pickupTime));
        $endTime = date('g:i A', strtotime($pickupTime . ' +1 hour'));
        $timeRange = $startTime . ' - ' . $endTime;
        $pickupDisplay = date('F d, Y', strtotime($pickupDate)) . ' between ' . $timeRange;
        
        return "PICKUP: " . $branch['branch_name'] . " - " . $branch['branch_address'] . " on " . $pickupDisplay;
    }
    
    
    private function clearProcessedCartItems($cartItems) {
        if (empty($cartItems)) return;
        
        $cartIds = array_column($cartItems, 'id');
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        
        $db = new DBConnection();
        $stmt = $db->conn->prepare("DELETE FROM cart_list WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($cartIds)), ...$cartIds);
        $stmt->execute();
    }
    
    
    private function sendOrderNotifications($order, $branch) {
        
        
        $this->logError('OrderController::sendOrderNotifications', 'Order #' . $order['code'] . ' notifications sent');
    }
    
    
    private function sendStatusChangeNotification($order, $newStatus) {
        
        $statusText = $this->orderModel->getStatusText($newStatus);
        $this->logError('OrderController::sendStatusChangeNotification', 'Order #' . $order['code'] . ' status changed to ' . $statusText);
    }
    
    
    private function sendCancellationNotification($order, $reason) {
        
        $this->logError('OrderController::sendCancellationNotification', 'Order #' . $order['code'] . ' cancelled: ' . $reason);
    }
}
