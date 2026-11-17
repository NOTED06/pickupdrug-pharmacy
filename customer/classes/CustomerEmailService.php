<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../classes/EmailVerification.php');
require_once(__DIR__ . '/../../classes/OrderConstants.php');

class CustomerEmailService extends EmailVerification {
    
    public function __construct() {
        parent::__construct();
    }
    
    
    public function sendOrderConfirmation($customer_id, $order_id) {
        $resp = array('status' => 'failed');
        
        try {
            
            $customer_query = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
            $customer_query->bind_param('i', $customer_id);
            $customer_query->execute();
            $customer_result = $customer_query->get_result();
            
            if ($customer_result->num_rows === 0) {
                $resp['msg'] = 'Customer not found.';
                return json_encode($resp);
            }
            
            $customer = $customer_result->fetch_assoc();
            
            
            $order_query = $this->conn->prepare("
                SELECT o.*
                FROM order_list o
                WHERE o.id = ?
            ");
            $order_query->bind_param('i', $order_id);
            $order_query->execute();
            $order_result = $order_query->get_result();
            
            if ($order_result->num_rows === 0) {
                $resp['msg'] = 'Order not found.';
                return json_encode($resp);
            }
            
            $order = $order_result->fetch_assoc();
            
            
            $items_query = $this->conn->prepare("
                SELECT oi.*, p.name as product_name, p.price
                FROM order_items oi
                LEFT JOIN product_list p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $items_query->bind_param('i', $order_id);
            $items_query->execute();
            $items_result = $items_query->get_result();
            
            $order_items = array();
            while ($item = $items_result->fetch_assoc()) {
                $order_items[] = $item;
            }
            
            
            $subject = "Order Confirmation - #{$order['code']}";
            $body = $this->getOrderConfirmationTemplate($customer, $order, $order_items);
            
            $emailSent = $this->sendCustomerEmail($customer['email'], $customer['firstname'], $subject, $body);
            
            if ($emailSent) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Order confirmation sent successfully.';
            } else {
                $resp['msg'] = 'Failed to send order confirmation email.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function sendOrderStatusUpdate($customer_id, $order_id, $new_status) {
        $resp = array('status' => 'failed');
        
        try {
            
            $customer_query = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
            $customer_query->bind_param('i', $customer_id);
            $customer_query->execute();
            $customer_result = $customer_query->get_result();
            
            if ($customer_result->num_rows === 0) {
                $resp['msg'] = 'Customer not found.';
                return json_encode($resp);
            }
            
            $customer = $customer_result->fetch_assoc();
            
            
            $order_query = $this->conn->prepare("
                SELECT o.*
                FROM order_list o
                WHERE o.id = ?
            ");
            $order_query->bind_param('i', $order_id);
            $order_query->execute();
            $order_result = $order_query->get_result();
            
            if ($order_result->num_rows === 0) {
                $resp['msg'] = 'Order not found.';
                return json_encode($resp);
            }
            
            $order = $order_result->fetch_assoc();
            
            
            $subject = "Order Status Update - #{$order['code']}";
            $body = $this->getOrderStatusUpdateTemplate($customer, $order, $new_status);
            
            $emailSent = $this->sendCustomerEmail($customer['email'], $customer['firstname'], $subject, $body);
            
            if ($emailSent) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Status update email sent successfully.';
            } else {
                $resp['msg'] = 'Failed to send status update email.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function sendPrescriptionNotification($customer_id, $prescription_id, $status) {
        $resp = array('status' => 'failed');
        
        try {
            
            $customer_query = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
            $customer_query->bind_param('i', $customer_id);
            $customer_query->execute();
            $customer_result = $customer_query->get_result();
            
            if ($customer_result->num_rows === 0) {
                $resp['msg'] = 'Customer not found.';
                return json_encode($resp);
            }
            
            $customer = $customer_result->fetch_assoc();
            
            
            $prescription_query = $this->conn->prepare("SELECT * FROM customer_prescriptions WHERE id = ?");
            $prescription_query->bind_param('i', $prescription_id);
            $prescription_query->execute();
            $prescription_result = $prescription_query->get_result();
            
            if ($prescription_result->num_rows === 0) {
                $resp['msg'] = 'Prescription not found.';
                return json_encode($resp);
            }
            
            $prescription = $prescription_result->fetch_assoc();
            
            
            $subject = "Prescription Update - " . ucfirst($status);
            $body = $this->getPrescriptionNotificationTemplate($customer, $prescription, $status);
            
            $emailSent = $this->sendCustomerEmail($customer['email'], $customer['firstname'], $subject, $body);
            
            if ($emailSent) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Prescription notification sent successfully.';
            } else {
                $resp['msg'] = 'Failed to send prescription notification email.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function sendGeneralNotification($customer_id, $subject, $message, $notification_type = 'info') {
        $resp = array('status' => 'failed');
        
        try {
            
            $customer_query = $this->conn->prepare("SELECT * FROM customer_list WHERE id = ?");
            $customer_query->bind_param('i', $customer_id);
            $customer_query->execute();
            $customer_result = $customer_query->get_result();
            
            if ($customer_result->num_rows === 0) {
                $resp['msg'] = 'Customer not found.';
                return json_encode($resp);
            }
            
            $customer = $customer_result->fetch_assoc();
            
            
            $body = $this->getGeneralNotificationTemplate($customer, $subject, $message, $notification_type);
            
            $emailSent = $this->sendCustomerEmail($customer['email'], $customer['firstname'], $subject, $body);
            
            if ($emailSent) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Notification sent successfully.';
            } else {
                $resp['msg'] = 'Failed to send notification email.';
            }
            
        } catch (Exception $e) {
            $resp['msg'] = 'Error: ' . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    
    public function sendEmail($to_email, $subject, $body, $name = 'Customer') {
        return $this->sendCustomerEmail($to_email, $name, $subject, $body);
    }
    
    
    private function sendCustomerEmail($to_email, $name, $subject, $body) {
        
        if (empty($this->smtp_username) || empty($this->smtp_password)) {
            
            return $this->sendWithPhpMail($to_email, $name, $subject, $body);
        }
        
        
        return $this->sendWithSmtp($to_email, $name, $subject, $body);
    }
    
    
    private function sendWithSmtp($to_email, $name, $subject, $body) {
        try {
            
            $socket = @fsockopen('ssl://' . $this->smtp_host, 465, $errno, $errstr, 10);
            
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return $this->sendWithPhpMail($to_email, $name, $subject, $body);
            }
            
            
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "EHLO localhost\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "AUTH LOGIN\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_username) . "\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, base64_encode($this->smtp_password) . "\r\n");
            $response = $this->smtpRead($socket);
            
            if (strpos($response, '235') === false) {
                fclose($socket);
                error_log("SMTP Auth failed: $response");
                return $this->sendWithPhpMail($to_email, $name, $subject, $body);
            }
            
            
            $this->smtpSend($socket, "MAIL FROM:<{$this->smtp_username}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "RCPT TO:<{$to_email}>\r\n");
            $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "DATA\r\n");
            $this->smtpRead($socket);
            
            
            $headers = "From: {$this->from_name} <{$this->smtp_username}>\r\n";
            $headers .= "To: {$name} <{$to_email}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $this->smtpSend($socket, $headers . $body . "\r\n.\r\n");
            $response = $this->smtpRead($socket);
            
            
            $this->smtpSend($socket, "QUIT\r\n");
            fclose($socket);
            
            return strpos($response, '250') !== false;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return $this->sendWithPhpMail($to_email, $name, $subject, $body);
        }
    }
    
    
    private function sendWithPhpMail($to_email, $name, $subject, $body) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->from_name} <noreply@pickupdrug.com>\r\n";
        
        return @mail($to_email, $subject, $body, $headers);
    }
    
    
    private function getOrderConfirmationTemplate($customer, $order, $items) {
        $items_html = '';
        $total = 0;
        
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            
            $items_html .= "
                <tr>
                    <td style='padding: 15px; border-bottom: 1px solid #e5e7eb;'>
                        <strong>{$item['product_name']}</strong>
                    </td>
                    <td style='padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: center;'>
                        {$item['quantity']}
                    </td>
                    <td style='padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: right;'>
                        ₱" . number_format($item['price'], 2) . "
                    </td>
                    <td style='padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: right;'>
                        <strong>₱" . number_format($subtotal, 2) . "</strong>
                    </td>
                </tr>
            ";
        }
        
        $status_color = $this->getStatusColor($order['status']);
        $status_text = $this->getStatusText($order['status']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Order Confirmation</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #16a085 0%, #0d9488 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
                .header p { margin: 8px 0 0; opacity: 0.9; font-size: 16px; }
                .content { padding: 30px; }
                .order-info { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
                .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                .info-row:last-child { margin-bottom: 0; }
                .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; color: white; background: {$status_color}; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
                .total-row { background: #f8f9fa; font-weight: 600; }
                .pickup-info { background: #e8f4fd; border-left: 4px solid #0ea5e9; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
                .footer { text-align: center; padding: 30px; background: #f8f9fa; color: #666; border-top: 1px solid #e5e7eb; }
                .footer p { margin: 5px 0; font-size: 14px; }
                .button { display: inline-block; background: #16a085; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 0; }
                .button:hover { background: #0d9488; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Order Confirmed!</h1>
                    <p>Thank you for your order, {$customer['firstname']}!</p>
                </div>
                
                <div class='content'>
                    <div class='order-info'>
                        <div class='info-row'>
                            <span><strong>Order Number:</strong></span>
                            <span>#{$order['code']}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Order Date:</strong></span>
                            <span>" . date('F j, Y g:i A', strtotime($order['date_created'])) . "</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Status:</strong></span>
                            <span class='status-badge'>{$status_text}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Total Amount:</strong></span>
                            <span><strong>₱" . number_format($order['total_amount'], 2) . "</strong></span>
                        </div>
                    </div>
                    
                    <h3>Order Items</h3>
                    <table class='items-table'>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style='text-align: center;'>Qty</th>
                                <th style='text-align: right;'>Price</th>
                                <th style='text-align: right;'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$items_html}
                            <tr class='total-row'>
                                <td colspan='3' style='padding: 15px; text-align: right;'>
                                    <strong>Order Total:</strong>
                                </td>
                                <td style='padding: 15px; text-align: right;'>
                                    <strong>₱" . number_format($order['total_amount'], 2) . "</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class='pickup-info'>
                        <h3 style='margin-top: 0; color: #0ea5e9;'>📍 Pickup Information</h3>
                        <p><strong>Store:</strong> \" . \$_settings->info('name') . \"</p>
                        <p><strong>Address:</strong> \" . \$_settings->info('address') . \"</p>
                        <p><strong>Contact:</strong> \" . \$_settings->info('phone') . \"</p>
                        <p><strong>Pickup Hours:</strong> Monday - Sunday, 8:00 AM - 8:00 PM</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . base_url . "customer/orders.php?order_id={$order['id']}' class='button'>
                            View Order Details
                        </a>
                    </div>
                    
                    <div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                        <p style='margin: 0; color: #856404;'>
                            <strong>📝 Important:</strong> Please bring a valid ID when picking up your order. 
                            If you have prescription medications, ensure you have the required documents.
                        </p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Need help? Contact us at <strong>" . ($this->settings ? $this->settings->info('contact') : 'support@pickupdrug.com') . "</strong></p>
                    <p>Thank you for choosing {$this->from_name}!</p>
                    <p style='font-size: 12px; opacity: 0.7;'>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    
    private function getOrderStatusUpdateTemplate($customer, $order, $new_status) {
        $status_color = $this->getStatusColor($new_status);
        $status_text = $this->getStatusText($new_status);
        $status_message = $this->getStatusMessage($new_status);
        $status_icon = $this->getStatusIcon($new_status);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Order Status Update</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, {$status_color} 0%, {$status_color}dd 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
                .status-icon { font-size: 48px; margin-bottom: 15px; }
                .content { padding: 30px; }
                .order-info { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
                .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; text-transform: uppercase; color: white; background: {$status_color}; }
                .message-box { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid {$status_color}; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
                .footer { text-align: center; padding: 30px; background: #f8f9fa; color: #666; border-top: 1px solid #e5e7eb; }
                .button { display: inline-block; background: {$status_color}; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='status-icon'>{$status_icon}</div>
                    <h1>Order Status Update</h1>
                    <p>Hello {$customer['firstname']}, your order has been updated!</p>
                </div>
                
                <div class='content'>
                    <div class='order-info'>
                        <div class='info-row'>
                            <span><strong>Order Number:</strong></span>
                            <span>#{$order['code']}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Current Status:</strong></span>
                            <span class='status-badge'>{$status_text}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Updated:</strong></span>
                            <span>" . date('F j, Y g:i A') . "</span>
                        </div>
                    </div>
                    
                    <div class='message-box'>
                        <h3 style='margin-top: 0; color: {$status_color};'>Status Update</h3>
                        <p style='margin-bottom: 0; font-size: 16px;'>{$status_message}</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . base_url . "customer/orders.php?order_id={$order['id']}' class='button'>
                            View Order Details
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Need help? Contact us at <strong>" . ($this->settings ? $this->settings->info('contact') : 'support@pickupdrug.com') . "</strong></p>
                    <p>Thank you for choosing {$this->from_name}!</p>
                    <p style='font-size: 12px; opacity: 0.7;'>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    
    private function getPrescriptionNotificationTemplate($customer, $prescription, $status) {
        $status_color = $status === 'approved' ? '#10b981' : ($status === 'rejected' ? '#ef4444' : '#f59e0b');
        $status_icon = $status === 'approved' ? '✅' : ($status === 'rejected' ? '❌' : '⏳');
        $status_message = $this->getPrescriptionStatusMessage($status);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Prescription Update</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, {$status_color} 0%, {$status_color}dd 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
                .status-icon { font-size: 48px; margin-bottom: 15px; }
                .content { padding: 30px; }
                .prescription-info { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
                .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                .message-box { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid {$status_color}; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
                .footer { text-align: center; padding: 30px; background: #f8f9fa; color: #666; border-top: 1px solid #e5e7eb; }
                .button { display: inline-block; background: {$status_color}; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='status-icon'>{$status_icon}</div>
                    <h1>Prescription Update</h1>
                    <p>Hello {$customer['firstname']}, your prescription has been updated!</p>
                </div>
                
                <div class='content'>
                    <div class='prescription-info'>
                        <div class='info-row'>
                            <span><strong>Prescription ID:</strong></span>
                            <span>#{$prescription['id']}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Status:</strong></span>
                            <span style='color: {$status_color}; font-weight: 600; text-transform: uppercase;'>{$status}</span>
                        </div>
                        <div class='info-row'>
                            <span><strong>Updated:</strong></span>
                            <span>" . date('F j, Y g:i A') . "</span>
                        </div>
                    </div>
                    
                    <div class='message-box'>
                        <h3 style='margin-top: 0; color: {$status_color};'>Status Update</h3>
                        <p style='margin-bottom: 0; font-size: 16px;'>{$status_message}</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . base_url . "customer/prescriptions.php' class='button'>
                            View My Prescriptions
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Need help? Contact us at <strong>" . ($this->settings ? $this->settings->info('contact') : 'support@pickupdrug.com') . "</strong></p>
                    <p>Thank you for choosing {$this->from_name}!</p>
                    <p style='font-size: 12px; opacity: 0.7;'>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    
    private function getGeneralNotificationTemplate($customer, $subject, $message, $type) {
        $type_color = $this->getNotificationTypeColor($type);
        $type_icon = $this->getNotificationTypeIcon($type);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Notification</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, {$type_color} 0%, {$type_color}dd 100%); color: white; padding: 40px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
                .type-icon { font-size: 48px; margin-bottom: 15px; }
                .content { padding: 30px; }
                .message-box { background: #f8f9fa; border-left: 4px solid {$type_color}; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
                .footer { text-align: center; padding: 30px; background: #f8f9fa; color: #666; border-top: 1px solid #e5e7eb; }
                .button { display: inline-block; background: {$type_color}; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='type-icon'>{$type_icon}</div>
                    <h1>Notification</h1>
                    <p>Hello {$customer['firstname']}!</p>
                </div>
                
                <div class='content'>
                    <div class='message-box'>
                        <h3 style='margin-top: 0; color: {$type_color};'>{$subject}</h3>
                        <div style='font-size: 16px;'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . base_url . "customer/' class='button'>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Need help? Contact us at <strong>" . ($this->settings ? $this->settings->info('contact') : 'support@pickupdrug.com') . "</strong></p>
                    <p>Thank you for choosing {$this->from_name}!</p>
                    <p style='font-size: 12px; opacity: 0.7;'>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    
    
    private function getStatusColor($status) {
        switch((int)$status) {
            case OrderStatus::PENDING:           return '#f59e0b';
            case OrderStatus::PACKED:            return '#3b82f6';
            case OrderStatus::READY_FOR_PICKUP:  return '#8b5cf6';
            case OrderStatus::COMPLETED:         return '#10b981';
            case OrderStatus::CANCELLED:         return '#ef4444';
            case OrderStatus::REJECTED:          return '#6b7280';
            default: return '#6b7280';
        }
    }
    
    private function getStatusText($status) {
        return OrderStatus::getLabel((int)$status);
    }
    
    private function getStatusMessage($status) {
        $msg = OrderStatus::getNotificationMessage((int)$status);
        return $msg !== '' ? $msg : 'Your order status has been updated.';
    }
    
    private function getStatusIcon($status) {
        switch((int)$status) {
            case OrderStatus::PENDING:           return '⏳';
            case OrderStatus::PACKED:            return '📦';
            case OrderStatus::READY_FOR_PICKUP:  return '✅';
            case OrderStatus::COMPLETED:         return '🎉';
            case OrderStatus::CANCELLED:         return '❌';
            case OrderStatus::REJECTED:          return '❌';
            default: return '📄';
        }
    }
    
    private function getPrescriptionStatusMessage($status) {
        switch($status) {
            case 'approved': return 'Your prescription has been approved! You can now place orders for prescription medications.';
            case 'rejected': return 'Unfortunately, your prescription could not be approved. Please contact us or submit a new prescription.';
            case 'pending': return 'Your prescription is currently being reviewed by our pharmacy team.';
            default: return 'Your prescription status has been updated.';
        }
    }
    
    private function getNotificationTypeColor($type) {
        switch($type) {
            case 'success': return '#10b981';
            case 'warning': return '#f59e0b';
            case 'error': return '#ef4444';
            case 'info':
            default: return '#3b82f6';
        }
    }
    
    private function getNotificationTypeIcon($type) {
        switch($type) {
            case 'success': return '✅';
            case 'warning': return '⚠️';
            case 'error': return '❌';
            case 'info':
            default: return 'ℹ️';
        }
    }
}

if (isset($_GET['f']) && basename($_SERVER['SCRIPT_FILENAME']) === 'CustomerEmailService.php') {
    header('Content-Type: application/json');
    $emailService = new CustomerEmailService();
    $f = $_GET['f'];
    
    switch ($f) {
        case 'send_order_confirmation':
            $customer_id = $_POST['customer_id'] ?? 0;
            $order_id = $_POST['order_id'] ?? 0;
            echo $emailService->sendOrderConfirmation($customer_id, $order_id);
            break;
            
        case 'send_status_update':
            $customer_id = $_POST['customer_id'] ?? 0;
            $order_id = $_POST['order_id'] ?? 0;
            $status = $_POST['status'] ?? 0;
            echo $emailService->sendOrderStatusUpdate($customer_id, $order_id, $status);
            break;
            
        case 'send_prescription_notification':
            $customer_id = $_POST['customer_id'] ?? 0;
            $prescription_id = $_POST['prescription_id'] ?? 0;
            $status = $_POST['status'] ?? 'pending';
            echo $emailService->sendPrescriptionNotification($customer_id, $prescription_id, $status);
            break;
            
        case 'send_general_notification':
            $customer_id = $_POST['customer_id'] ?? 0;
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';
            $type = $_POST['type'] ?? 'info';
            echo $emailService->sendGeneralNotification($customer_id, $subject, $message, $type);
            break;
            
        default:
            echo json_encode(['status' => 'failed', 'msg' => 'Invalid action']);
    }
    exit;
}