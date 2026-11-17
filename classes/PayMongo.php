<?php

class PayMongo {
    
    private $secret_key;
    private $public_key;
    private $api_base = 'https://api.paymongo.com/v1';
    private $conn;
    
    
    public function __construct($conn = null) {
        
        $this->loadApiKeys();
        $this->conn = $conn;
    }
    
    
    private function loadApiKeys() {
        global $_settings;
        
        
        if (isset($_settings) && method_exists($_settings, 'info')) {
            $this->secret_key = $_settings->info('paymongo_secret_key') ?: '';
            $this->public_key = $_settings->info('paymongo_public_key') ?: '';
        }
        
        
        if (empty($this->secret_key)) {
            
            $this->secret_key = 'YOUR_PAYMONGO_SECRET_KEY';
        }
        
        if (empty($this->public_key)) {
            
            $this->public_key = '';
        }
    }
    
    
    public function setApiKeys($secret_key, $public_key = '') {
        $this->secret_key = $secret_key;
        $this->public_key = $public_key;
    }
    
    
    public function createGcashSource($amount, $description, $success_url, $failed_url, $metadata = []) {
        return $this->createSource('gcash', $amount, $description, $success_url, $failed_url, $metadata);
    }
    
    
    public function createMayaSource($amount, $description, $success_url, $failed_url, $metadata = []) {
        return $this->createSource('grab_pay', $amount, $description, $success_url, $failed_url, $metadata);
        
        
    }
    
    
    public function createSource($type, $amount, $description, $success_url, $failed_url, $metadata = []) {
        
        $amount_centavos = (int) round($amount * 100);
        
        
        if ($amount_centavos < 10000) {
            return [
                'status' => 'failed',
                'msg' => 'Minimum amount for GCash payment is ₱100.00'
            ];
        }
        
        
        $billing = $metadata['billing'] ?? null;
        unset($metadata['billing']);
        
        
        $flat_metadata = [];
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $flat_metadata[$key] = json_encode($value);
            } else {
                $flat_metadata[$key] = (string) $value;
            }
        }
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_centavos,
                    'currency' => 'PHP',
                    'type' => $type,
                    'redirect' => [
                        'success' => $success_url,
                        'failed' => $failed_url
                    ]
                ]
            ]
        ];
        
        
        if ($billing) {
            $data['data']['attributes']['billing'] = $billing;
        }
        
        
        if (!empty($flat_metadata)) {
            $data['data']['attributes']['metadata'] = $flat_metadata;
        }
        
        $response = $this->makeRequest('POST', '/sources', $data);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'source_id' => $response['data']['id'],
                'checkout_url' => $response['data']['attributes']['redirect']['checkout_url'],
                'status_source' => $response['data']['attributes']['status'],
                'raw' => $response
            ];
        }
        
        
        $error_msg = 'Payment initialization failed';
        if (isset($response['errors'][0]['detail'])) {
            $error_msg = $response['errors'][0]['detail'];
        } elseif (isset($response['error'])) {
            $error_msg = $response['error'];
        }
        
        return [
            'status' => 'failed',
            'msg' => $error_msg,
            'raw' => $response
        ];
    }
    
    
    public function rollbackFailedPendingOrder($order_id, $reason = '') {
        $result = ['success' => false, 'msg' => 'Unable to restore checkout.'];
        $order_id = (int) $order_id;

        if (!$this->conn || $order_id <= 0) {
            $result['msg'] = 'Invalid temporary order.';
            return $result;
        }

        $transaction_started = false;

        try {
            $this->conn->begin_transaction();
            $transaction_started = true;

            $order_stmt = $this->conn->prepare(
                "SELECT id, code, customer_id, branch_id, status, payment_method
                 FROM order_list
                 WHERE id = ?
                 FOR UPDATE"
            );
            if (!$order_stmt) {
                throw new RuntimeException('Unable to lock temporary order.');
            }

            $order_stmt->bind_param('i', $order_id);
            $order_stmt->execute();
            $order = $order_stmt->get_result()->fetch_assoc();
            $order_stmt->close();

            if (!$order) {
                $this->conn->rollback();
                return ['success' => true, 'msg' => 'Temporary order was already removed.'];
            }

            if ((int) $order['status'] !== 0 || !in_array(strtolower($order['payment_method']), ['gcash', 'maya'], true)) {
                $this->conn->rollback();
                $result['msg'] = 'Only pending online-payment orders can be restored.';
                return $result;
            }

            $items_stmt = $this->conn->prepare(
                "SELECT product_id, quantity
                 FROM order_items
                 WHERE order_id = ?"
            );
            if (!$items_stmt) {
                throw new RuntimeException('Unable to load temporary order items.');
            }

            $items_stmt->bind_param('i', $order_id);
            $items_stmt->execute();
            $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $items_stmt->close();

            if (empty($items)) {
                throw new RuntimeException('Temporary order has no items to restore.');
            }

            $customer_id = (int) $order['customer_id'];
            $branch_id = $order['branch_id'] === null ? null : (int) $order['branch_id'];

            foreach ($items as $item) {
                $product_id = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                $cart_stmt = $this->conn->prepare(
                    "SELECT id
                     FROM cart_list
                     WHERE customer_id = ? AND product_id = ? AND branch_id <=> ?
                     LIMIT 1
                     FOR UPDATE"
                );
                if (!$cart_stmt) {
                    throw new RuntimeException('Unable to check restored cart item.');
                }

                $cart_stmt->bind_param('iii', $customer_id, $product_id, $branch_id);
                $cart_stmt->execute();
                $cart_item = $cart_stmt->get_result()->fetch_assoc();
                $cart_stmt->close();

                if ($cart_item) {
                    $cart_id = (int) $cart_item['id'];
                    $update_cart = $this->conn->prepare(
                        "UPDATE cart_list SET quantity = quantity + ? WHERE id = ?"
                    );
                    if (!$update_cart) {
                        throw new RuntimeException('Unable to restore cart quantity.');
                    }
                    $update_cart->bind_param('ii', $quantity, $cart_id);
                    $update_cart->execute();
                    $update_cart->close();
                } else {
                    $insert_cart = $this->conn->prepare(
                        "INSERT INTO cart_list (customer_id, product_id, quantity, branch_id)
                         VALUES (?, ?, ?, ?)"
                    );
                    if (!$insert_cart) {
                        throw new RuntimeException('Unable to restore cart item.');
                    }
                    $insert_cart->bind_param('iiii', $customer_id, $product_id, $quantity, $branch_id);
                    $insert_cart->execute();
                    $insert_cart->close();
                }
            }

            
            foreach (['notifications', 'notification_queue', 'admin_notifications', 'urgent_order_followups'] as $table_name) {
                $table_check = $this->conn->query("SHOW TABLES LIKE '{$table_name}'");
                if ($table_check && $table_check->num_rows > 0) {
                    $cleanup_stmt = $this->conn->prepare("DELETE FROM `{$table_name}` WHERE order_id = ?");
                    if ($cleanup_stmt) {
                        $cleanup_stmt->bind_param('i', $order_id);
                        $cleanup_stmt->execute();
                        $cleanup_stmt->close();
                    }
                }
            }

            
            $delete_items = $this->conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            if (!$delete_items) {
                throw new RuntimeException('Unable to remove temporary order items.');
            }
            $delete_items->bind_param('i', $order_id);
            $delete_items->execute();
            $delete_items->close();

            $delete_order = $this->conn->prepare("DELETE FROM order_list WHERE id = ? AND status = 0");
            if (!$delete_order) {
                throw new RuntimeException('Unable to remove temporary order.');
            }
            $delete_order->bind_param('i', $order_id);
            $delete_order->execute();
            $deleted_rows = $delete_order->affected_rows;
            $delete_order->close();

            if ($deleted_rows !== 1) {
                throw new RuntimeException('Temporary order was updated before it could be removed.');
            }

            $this->conn->commit();
            $transaction_started = false;
            error_log("Removed failed PayMongo order #{$order['code']} (ID: {$order_id}). Reason: {$reason}");

            return ['success' => true, 'msg' => 'Payment failed. Your items were returned to checkout.'];
        } catch (Throwable $e) {
            if ($transaction_started) {
                $this->conn->rollback();
            }
            error_log('Failed PayMongo order rollback error: ' . $e->getMessage());
            $result['msg'] = 'Unable to restore your cart. Please try again.';
            return $result;
        }
    }

    
    public function getSource($source_id) {
        $response = $this->makeRequest('GET', '/sources/' . $source_id);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'source_id' => $response['data']['id'],
                'source_status' => $response['data']['attributes']['status'],
                'amount' => $response['data']['attributes']['amount'] / 100, 
                'type' => $response['data']['attributes']['type'],
                'metadata' => $response['data']['attributes']['metadata'] ?? [],
                'raw' => $response
            ];
        }
        
        return [
            'status' => 'failed',
            'msg' => 'Source not found',
            'raw' => $response
        ];
    }
    
    
    public function createPayment($source_id, $amount, $description = '') {
        $amount_centavos = (int) round($amount * 100);
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_centavos,
                    'currency' => 'PHP',
                    'description' => $description,
                    'source' => [
                        'id' => $source_id,
                        'type' => 'source'
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest('POST', '/payments', $data);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'payment_id' => $response['data']['id'],
                'payment_status' => $response['data']['attributes']['status'],
                'amount' => $response['data']['attributes']['amount'] / 100,
                'raw' => $response
            ];
        }
        
        $error_msg = 'Payment creation failed';
        if (isset($response['errors'][0]['detail'])) {
            $error_msg = $response['errors'][0]['detail'];
        }
        
        return [
            'status' => 'failed',
            'msg' => $error_msg,
            'raw' => $response
        ];
    }
    
    
    public function getPayment($payment_id) {
        $response = $this->makeRequest('GET', '/payments/' . $payment_id);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'payment_id' => $response['data']['id'],
                'payment_status' => $response['data']['attributes']['status'],
                'amount' => $response['data']['attributes']['amount'] / 100,
                'raw' => $response
            ];
        }
        
        return [
            'status' => 'failed',
            'msg' => 'Payment not found',
            'raw' => $response
        ];
    }
    
    
    public function verifyWebhookSignature($payload, $signature, $webhook_secret) {
        
        $parts = explode(',', $signature);
        $timestamp = null;
        $test_sig = null;
        $live_sig = null;
        
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part, 2);
            if ($key === 't') $timestamp = $value;
            if ($key === 'te') $test_sig = $value;
            if ($key === 'li') $live_sig = $value;
        }
        
        if (!$timestamp) {
            return false;
        }
        
        
        $signed_payload = $timestamp . '.' . $payload;
        $expected_signature = hash_hmac('sha256', $signed_payload, $webhook_secret);
        
        
        $provided_sig = $this->isTestMode() ? $test_sig : $live_sig;
        
        return hash_equals($expected_signature, $provided_sig);
    }
    
    
    public function isTestMode() {
        return strpos($this->secret_key, 'sk_test_') === 0;
    }
    
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->api_base . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->secret_key . ':')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("PayMongo cURL error: " . $error);
            return ['error' => 'Connection error: ' . $error];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("PayMongo JSON decode error: " . json_last_error_msg());
            return ['error' => 'Invalid response from payment gateway'];
        }
        
        
        if ($http_code >= 400 && $this->isTestMode()) {
            error_log("PayMongo API Error [{$http_code}]: " . $response);
        }
        
        return $decoded;
    }
    
    
    public static function getStatusMessage($status) {
        $messages = [
            'pending' => 'Waiting for payment authorization',
            'chargeable' => 'Payment authorized, processing',
            'paid' => 'Payment successful',
            'expired' => 'Payment link expired',
            'cancelled' => 'Payment was cancelled',
            'failed' => 'Payment failed'
        ];
        
        return $messages[$status] ?? $status;
    }
}
