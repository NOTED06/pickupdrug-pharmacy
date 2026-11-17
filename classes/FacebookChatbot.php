<?php

require_once(__DIR__ . '/DBConnection.php');

class FacebookChatbot extends DBConnection {
    
    
    
    
    
    const STATUS_PENDING = 0;
    const STATUS_PACKED = 1;
    const STATUS_READY_FOR_PICKUP = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS_REJECTED = 5;
    
    
    const EXCLUDED_STATUSES = [self::STATUS_CANCELLED, self::STATUS_REJECTED];
    
    
    
    
    const RX_STATUS_PENDING = 'pending';
    const RX_STATUS_APPROVED = 'approved';
    const RX_STATUS_REJECTED = 'rejected';
    
    
    
    
    private $page_access_token;
    private $verify_token;
    private $app_secret;
    private $ssl_verify = true; 
    private $logs_table_checked = false;
    
    
    
    
    private $keywords = [
        'sales' => ['sales', 'sale', 'revenue', 'income', 'earnings', 'how much', 'total sales', 'benta', 'kita'],
        'today' => ['today', "today's", 'todays', 'ngayon', 'today na'],
        'yesterday' => ['yesterday', "yesterday's", 'kahapon'],
        'week' => ['week', 'this week', 'weekly', 'linggo'],
        'month' => ['month', 'this month', 'monthly', 'buwan'],
        'year' => ['year', 'this year', 'yearly', 'annual', 'taon'],
        'product' => ['product', 'products', 'top product', 'best selling', 'bestselling', 'top selling'],
        'order' => ['order', 'orders', 'pending', 'how many orders', 'mga order'],
        'prescription' => ['prescription', 'prescriptions', 'rx', 'reseta'],
        'customer' => ['customer', 'customers', 'how many customers', 'mga customer'],
        'help' => ['help', 'commands', 'what can you do', 'menu', 'options', 'tulong', 'ano pwede'],
        'greeting' => ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'kumusta', 'musta']
    ];
    
    
    
    
    public function __construct() {
        parent::__construct();
        $this->loadSettings();
        $this->ensureLogsTableExists();
        
        
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            $this->ssl_verify = false;
        }
    }
    
    
    private function loadSettings() {
        $stmt = $this->conn->prepare(
            "SELECT meta_field, meta_value FROM system_info 
             WHERE meta_field IN ('fb_page_access_token', 'fb_verify_token', 'fb_app_secret')"
        );
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                switch ($row['meta_field']) {
                    case 'fb_page_access_token':
                        $this->page_access_token = $row['meta_value'];
                        break;
                    case 'fb_verify_token':
                        $this->verify_token = $row['meta_value'];
                        break;
                    case 'fb_app_secret':
                        $this->app_secret = $row['meta_value'];
                        break;
                }
            }
            $stmt->close();
        }
        
        
        if (empty($this->verify_token)) {
            $this->verify_token = 'pickupdrug_verify_2025';
        }
    }
    
    
    private function ensureLogsTableExists() {
        if ($this->logs_table_checked) {
            return;
        }
        
        $this->conn->query("CREATE TABLE IF NOT EXISTS `chatbot_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `recipient_id` VARCHAR(100) NOT NULL,
            `message` TEXT NOT NULL,
            `response` TEXT,
            `error` TEXT,
            `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_recipient` (`recipient_id`),
            INDEX `idx_date` (`date_created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $this->logs_table_checked = true;
    }
    
    
    
    
    
    
    public function verifyWebhook($hub_mode, $hub_verify_token, $hub_challenge) {
        if ($hub_mode === 'subscribe' && $hub_verify_token === $this->verify_token) {
            return $hub_challenge;
        }
        return false;
    }
    
    
    public function validateSignature($payload, $signature) {
        if (empty($this->app_secret) || empty($signature)) {
            
            return true;
        }
        
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $this->app_secret);
        return hash_equals($expected, $signature);
    }
    
    
    
    
    
    
    public function processMessage($input) {
        $data = json_decode($input, true);
        
        if (!isset($data['entry'])) {
            $this->logError('Invalid webhook data - no entry');
            return false;
        }
        
        foreach ($data['entry'] as $entry) {
            if (!isset($entry['messaging'])) continue;
            
            foreach ($entry['messaging'] as $event) {
                $sender_id = $event['sender']['id'] ?? null;
                
                if (!$sender_id) continue;
                
                if (isset($event['message']['text'])) {
                    $message_text = strtolower(trim($event['message']['text']));
                    $response = $this->generateResponse($message_text);
                    $this->sendMessage($sender_id, $response);
                }
            }
        }
        
        return true;
    }
    
    
    private function generateResponse($message) {
        
        $has = function($type) use ($message) {
            return $this->containsKeyword($message, $this->keywords[$type]);
        };
        
        
        if ($has('help')) {
            return $this->getHelpMessage();
        }
        
        
        if ($has('sales')) {
            if ($has('today')) {
                return $this->getSalesReport('today');
            } elseif ($has('yesterday')) {
                return $this->getSalesReport('yesterday');
            } elseif ($has('week')) {
                return $this->getSalesReport('week');
            } elseif ($has('month')) {
                return $this->getSalesReport('month');
            } elseif ($has('year')) {
                return $this->getSalesReport('year');
            } else {
                return $this->getSalesSummary();
            }
        }
        
        
        if ($has('product')) {
            return $this->getTopProducts();
        }
        
        
        if ($has('order')) {
            return $this->getOrdersSummary();
        }
        
        
        if ($has('prescription')) {
            return $this->getPrescriptionsSummary();
        }
        
        
        if ($has('customer')) {
            return $this->getCustomersSummary();
        }
        
        
        if ($has('greeting')) {
            return $this->getGreeting();
        }
        
        return $this->getDefaultResponse();
    }
    
    
    private function containsKeyword($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    
    
    
    
    
    private function getGreeting() {
        $hour = (int) date('H');
        if ($hour < 12) {
            $greeting = "Good morning";
        } elseif ($hour < 18) {
            $greeting = "Good afternoon";
        } else {
            $greeting = "Good evening";
        }
        
        return "$greeting! 👋\n\nI'm your MedPick Sales Assistant. I can help you with:\n\n" .
               "📊 Sales Reports\n" .
               "📦 Orders Status\n" .
               "💊 Prescriptions\n" .
               "👥 Customer Info\n" .
               "🏆 Top Products\n\n" .
               "Type 'help' to see all available commands.";
    }
    
    
    private function getHelpMessage() {
        return "📋 Available Commands:\n\n" .
               "💰 SALES:\n" .
               "• 'sales today' - Today's sales\n" .
               "• 'sales yesterday' - Yesterday's sales\n" .
               "• 'sales this week' - Weekly sales\n" .
               "• 'sales this month' - Monthly sales\n" .
               "• 'sales this year' - Yearly sales\n" .
               "• 'sales' - Overall summary\n\n" .
               "📦 ORDERS:\n" .
               "• 'orders' - Orders summary\n" .
               "• 'pending orders' - Pending orders\n\n" .
               "💊 PRESCRIPTIONS:\n" .
               "• 'prescriptions' - Prescription status\n\n" .
               "👥 CUSTOMERS:\n" .
               "• 'customers' - Customer count\n\n" .
               "🏆 PRODUCTS:\n" .
               "• 'top products' - Best sellers";
    }
    
    
    private function getDefaultResponse() {
        return "🤔 I didn't understand that. Here are some things you can ask:\n\n" .
               "• 'How much are today's sales?'\n" .
               "• 'Sales this month'\n" .
               "• 'How many orders today?'\n" .
               "• 'Top products'\n" .
               "• 'Pending prescriptions'\n\n" .
               "Type 'help' for all commands.";
    }
    
    
    
    
    
    
    private function getSalesReport($period) {
        $data = $this->fetchSalesData($period);
        
        $sales = number_format($data['total_sales'], 2);
        $orders = $data['order_count'];
        
        switch ($period) {
            case 'today':
                $title = "📊 Today's Sales Report";
                $date_label = "📅 " . date('F j, Y');
                $footer = "\n\nKeep up the great work! 💪";
                break;
                
            case 'yesterday':
                $title = "📊 Yesterday's Sales Report";
                $date_label = "📅 " . date('F j, Y', strtotime('-1 day'));
                $footer = "";
                break;
                
            case 'week':
                $title = "📊 This Week's Sales";
                $date_label = "📅 Week of " . date('F j, Y', strtotime('monday this week'));
                $avg = $orders > 0 ? number_format($data['total_sales'] / $orders, 2) : '0.00';
                $footer = "\n📈 Avg per Order: ₱$avg";
                break;
                
            case 'month':
                $title = "📊 This Month's Sales";
                $date_label = "📅 " . date('F Y');
                $comparison = $this->getMonthComparison($data['total_sales']);
                $footer = "\n$comparison";
                break;
                
            case 'year':
                $title = "📊 Year-to-Date Sales";
                $date_label = "📅 " . date('Y');
                $monthly_avg = number_format($data['total_sales'] / max(1, (int)date('n')), 2);
                $footer = "\n📈 Monthly Average: ₱$monthly_avg";
                break;
                
            default:
                $title = "📊 Sales Report";
                $date_label = "";
                $footer = "";
        }
        
        return "$title\n$date_label\n\n" .
               "💰 Total Sales: ₱$sales\n" .
               "📦 Orders: $orders" .
               $footer;
    }
    
    
    private function fetchSalesData($period) {
        $excluded = implode(',', self::EXCLUDED_STATUSES);
        
        
        switch ($period) {
            case 'today':
                $date_condition = "DATE(o.date_created) = CURDATE()";
                break;
            case 'yesterday':
                $date_condition = "DATE(o.date_created) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $date_condition = "YEARWEEK(o.date_created, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $date_condition = "MONTH(o.date_created) = MONTH(CURDATE()) AND YEAR(o.date_created) = YEAR(CURDATE())";
                break;
            case 'year':
                $date_condition = "YEAR(o.date_created) = YEAR(CURDATE())";
                break;
            default:
                $date_condition = "1=1";
        }
        
        $sql = "SELECT 
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
                FROM order_list o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE $date_condition
                AND o.status NOT IN ($excluded)";
        
        $result = $this->conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            return [
                'total_sales' => (float) $row['total_sales'],
                'order_count' => (int) $row['order_count']
            ];
        }
        
        return ['total_sales' => 0, 'order_count' => 0];
    }
    
    
    private function getMonthComparison($current_sales) {
        $excluded = implode(',', self::EXCLUDED_STATUSES);
        
        $sql = "SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
                FROM order_list o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE MONTH(o.date_created) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND YEAR(o.date_created) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                AND o.status NOT IN ($excluded)";
        
        $result = $this->conn->query($sql);
        $last_month = $result ? (float) $result->fetch_assoc()['total_sales'] : 0;
        
        if ($last_month > 0) {
            $change = (($current_sales - $last_month) / $last_month) * 100;
            $icon = $change >= 0 ? "📈" : "📉";
            $sign = $change >= 0 ? "+" : "";
            return "$icon {$sign}" . number_format($change, 1) . "% vs last month";
        }
        
        return "📊 No data from last month";
    }
    
    
    private function getSalesSummary() {
        $today = $this->fetchSalesData('today');
        $week = $this->fetchSalesData('week');
        $month = $this->fetchSalesData('month');
        
        return "📊 Sales Summary\n\n" .
               "📅 Today: ₱" . number_format($today['total_sales'], 2) . "\n" .
               "📅 This Week: ₱" . number_format($week['total_sales'], 2) . "\n" .
               "📅 This Month: ₱" . number_format($month['total_sales'], 2) . "\n\n" .
               "💡 Tip: Ask 'sales today' for detailed reports!";
    }
    
    
    
    
    
    
    private function getTopProducts() {
        $excluded = implode(',', self::EXCLUDED_STATUSES);
        
        $sql = "SELECT p.name, SUM(oi.quantity) as total_sold
                FROM order_items oi
                JOIN product_list p ON oi.product_id = p.id
                JOIN order_list o ON oi.order_id = o.id
                WHERE o.status NOT IN ($excluded)
                AND o.date_created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY oi.product_id
                ORDER BY total_sold DESC
                LIMIT 5";
        
        $result = $this->conn->query($sql);
        
        $response = "🏆 Top 5 Products (Last 30 Days)\n\n";
        $rank = 1;
        $emojis = ['🥇', '🥈', '🥉', '📦', '📦'];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $emoji = $emojis[$rank - 1] ?? '📦';
                $response .= "$emoji {$row['name']}\n   Sold: {$row['total_sold']} units\n\n";
                $rank++;
            }
        } else {
            $response = "📦 No sales data available yet.";
        }
        
        return $response;
    }
    
    
    private function getOrdersSummary() {
        $sql = "SELECT 
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as packed,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ready,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                    COUNT(*) as total
                FROM order_list
                WHERE DATE(date_created) = CURDATE()";
        
        $stmt = $this->conn->prepare($sql);
        $pending = self::STATUS_PENDING;
        $packed = self::STATUS_PACKED;
        $ready = self::STATUS_READY_FOR_PICKUP;
        $completed = self::STATUS_COMPLETED;
        
        $stmt->bind_param('iiii', $pending, $packed, $ready, $completed);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return "📦 Today's Orders Summary\n\n" .
               "⏳ Pending: " . ($data['pending'] ?? 0) . "\n" .
               "📦 Packed: " . ($data['packed'] ?? 0) . "\n" .
               "🚚 Ready for Pick-up: " . ($data['ready'] ?? 0) . "\n" .
               "✅ Completed: " . ($data['completed'] ?? 0) . "\n\n" .
               "📊 Total: " . ($data['total'] ?? 0);
    }
    
    
    private function getPrescriptionsSummary() {
        $sql = "SELECT 
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
                    COUNT(*) as total
                FROM customer_prescriptions
                WHERE DATE(uploaded_at) = CURDATE()";
        
        $stmt = $this->conn->prepare($sql);
        $pending = self::RX_STATUS_PENDING;
        $approved = self::RX_STATUS_APPROVED;
        $rejected = self::RX_STATUS_REJECTED;
        
        $stmt->bind_param('sss', $pending, $approved, $rejected);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        
        $pending_result = $this->conn->query(
            "SELECT COUNT(*) as cnt FROM customer_prescriptions WHERE status = '" . self::RX_STATUS_PENDING . "'"
        );
        $pending_all = $pending_result ? (int) $pending_result->fetch_assoc()['cnt'] : 0;
        
        return "💊 Prescriptions Summary\n\n" .
               "📅 Today:\n" .
               "   ⏳ Pending: " . ($data['pending'] ?? 0) . "\n" .
               "   ✅ Approved: " . ($data['approved'] ?? 0) . "\n" .
               "   ❌ Rejected: " . ($data['rejected'] ?? 0) . "\n\n" .
               "⚠️ Total Pending Review: $pending_all";
    }
    
    
    private function getCustomersSummary() {
        $queries = [
            'total' => "SELECT COUNT(*) as cnt FROM customer_list",
            'today' => "SELECT COUNT(*) as cnt FROM customer_list WHERE DATE(date_created) = CURDATE()",
            'month' => "SELECT COUNT(*) as cnt FROM customer_list WHERE MONTH(date_created) = MONTH(CURDATE()) AND YEAR(date_created) = YEAR(CURDATE())",
            'verified' => "SELECT COUNT(*) as cnt FROM customer_list WHERE email_verified = 1"
        ];
        
        $data = [];
        foreach ($queries as $key => $sql) {
            $result = $this->conn->query($sql);
            $data[$key] = $result ? (int) $result->fetch_assoc()['cnt'] : 0;
        }
        
        return "👥 Customers Summary\n\n" .
               "📊 Total Customers: {$data['total']}\n" .
               "🆕 New Today: {$data['today']}\n" .
               "📅 This Month: {$data['month']}\n" .
               "✅ Email Verified: {$data['verified']}";
    }
    
    
    
    
    
    
    private function sendMessage($recipient_id, $message_text) {
        if (empty($this->page_access_token)) {
            $this->logError("Page Access Token not configured");
            return false;
        }
        
        $url = "https://graph.facebook.com/v18.0/me/messages?access_token=" . $this->page_access_token;
        
        $data = [
            'recipient' => ['id' => $recipient_id],
            'message' => ['text' => $message_text],
            'messaging_type' => 'RESPONSE'
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verify,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        
        $this->logMessage($recipient_id, $message_text, $response, $error ?: null);
        
        if ($error) {
            $this->logError("cURL Error: $error");
            return false;
        }
        
        if ($http_code !== 200) {
            $this->logError("Facebook API Error (HTTP $http_code): $response");
            return false;
        }
        
        return $response;
    }
    
    
    
    
    
    
    private function logMessage($recipient_id, $message, $response, $error = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO chatbot_logs (recipient_id, message, response, error, date_created) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        
        if ($stmt) {
            $stmt->bind_param("ssss", $recipient_id, $message, $response, $error);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    
    private function logError($message) {
        error_log("FacebookChatbot Error: $message");
    }
    
    
    
    
    
    
    public function getVerifyToken() {
        return $this->verify_token;
    }
    
    
    public function isConfigured() {
        return !empty($this->page_access_token);
    }
}
