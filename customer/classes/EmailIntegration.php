<?php

require_once(__DIR__ . '/CustomerEmailService.php');

class EmailIntegration {
    private $emailService;
    
    public function __construct() {
        $this->emailService = new CustomerEmailService();
    }
    
    
    public static function sendOrderConfirmationEmail($customer_id, $order_id) {
        try {
            $emailService = new CustomerEmailService();
            $result = json_decode($emailService->sendOrderConfirmation($customer_id, $order_id), true);
            
            
            error_log("Order Confirmation Email - Customer: {$customer_id}, Order: {$order_id}, Status: " . $result['status']);
            
            return $result['status'] === 'success';
        } catch (Exception $e) {
            error_log("Error sending order confirmation email: " . $e->getMessage());
            return false;
        }
    }
    
    
    public static function sendOrderStatusEmail($customer_id, $order_id, $new_status) {
        try {
            $emailService = new CustomerEmailService();
            $result = json_decode($emailService->sendOrderStatusUpdate($customer_id, $order_id, $new_status), true);
            
            
            error_log("Order Status Email - Customer: {$customer_id}, Order: {$order_id}, Status: {$new_status}, Result: " . $result['status']);
            
            return $result['status'] === 'success';
        } catch (Exception $e) {
            error_log("Error sending order status email: " . $e->getMessage());
            return false;
        }
    }
    
    
    public static function sendPrescriptionEmail($customer_id, $prescription_id, $status) {
        try {
            $emailService = new CustomerEmailService();
            $result = json_decode($emailService->sendPrescriptionNotification($customer_id, $prescription_id, $status), true);
            
            
            error_log("Prescription Email - Customer: {$customer_id}, Prescription: {$prescription_id}, Status: {$status}, Result: " . $result['status']);
            
            return $result['status'] === 'success';
        } catch (Exception $e) {
            error_log("Error sending prescription email: " . $e->getMessage());
            return false;
        }
    }
    
    
    public static function sendNotificationEmail($customer_id, $subject, $message, $type = 'info') {
        try {
            $emailService = new CustomerEmailService();
            $result = json_decode($emailService->sendGeneralNotification($customer_id, $subject, $message, $type), true);
            
            
            error_log("General Notification Email - Customer: {$customer_id}, Subject: {$subject}, Result: " . $result['status']);
            
            return $result['status'] === 'success';
        } catch (Exception $e) {
            error_log("Error sending notification email: " . $e->getMessage());
            return false;
        }
    }
}
?>