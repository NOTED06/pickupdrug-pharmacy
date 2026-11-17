<?php

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/GeminiAI.php');
require_once(__DIR__ . '/ChatbotCache.php');

class PharmacyChatbot {
    private $conn;
    private $settings;
    private $gemini;
    private $cache;
    private $use_ai = true; 
    private $use_cache = true; 
    
    
    private $store_info = [];
    
    
    private $conversation_history = [];
    private const MAX_HISTORY = 5;

    
    private $topic_ttl_minutes = 480; 
    
    
    const LANG_ENGLISH = 'en';
    const LANG_BISAYA = 'bi';
    const LANG_UNKNOWN = 'unknown';
    
    
    const INTENT_STORE_INFO = 'store_info';
    const INTENT_MEDICINE_AVAILABILITY = 'medicine_availability';
    const INTENT_PRESCRIPTION = 'prescription';
    const INTENT_FDA_INQUIRY = 'fda_inquiry';
    const INTENT_GENERIC_BRANDED = 'generic_branded';
    const INTENT_ORDER_STATUS = 'order_status';
    
    
    const INTENT_EMERGENCY = 'emergency';
    const INTENT_GREETING = 'greeting';
    const INTENT_UNKNOWN = 'unknown';
    const INTENT_DOSAGE_ADVICE = 'dosage_advice';
    
    public function __construct() {
        global $_settings;
        $this->settings = $_settings;
        $this->conn = $this->settings->conn;
        $this->loadStoreInfo();
        
        
        $this->gemini = new GeminiAI($this->conn, $this->store_info);
        
        
        $this->cache = new ChatbotCache($this->conn);
    }
    
    
    private function loadStoreInfo() {
        $this->store_info = [
            'name' => $this->settings->info('name') ?: 'PharmaCare',
            'address' => $this->settings->info('address') ?: 'Toril, Davao City',
            'contact' => $this->settings->info('contact') ?: $this->settings->info('phone') ?: 'N/A',
            'mobile' => $this->settings->info('mobile') ?: 'N/A',
            'email' => $this->settings->info('email') ?: 'N/A',
            'hours' => $this->settings->info('store_hours') ?: '8:00 AM - 9:00 PM (Mon-Sat), 9:00 AM - 6:00 PM (Sun)'
        ];
    }
    
    
    public function processMessage($message, $topicHint = '') {
        
        $rate_check = $this->cache->checkRateLimit();
        if (!$rate_check['allowed']) {
            return $this->response($rate_check['message'], 'warning', [
                'rate_limited' => true,
                'wait_seconds' => $rate_check['wait_seconds']
            ]);
        }
        
        
        $validation = $this->validateInput($message);
        if (!$validation['valid']) {
            return $this->response($validation['message'], 'warning');
        }
        
        $message = $validation['cleaned_message'];
        
        
        if ($this->isEmergency($message)) {
            return $this->handleEmergency($message);
        }

        
        
        $activeTopic = $this->getCurrentTopic();
        
        
        if (!$activeTopic && !empty($topicHint)) {
            $hintProduct = $this->findBestProductMatch($topicHint);
            if ($hintProduct) {
                $this->setTopic($hintProduct);
                $activeTopic = $this->getCurrentTopic();
            }
        }
        
        $maybeNewMed = trim($this->extractMedicineName($message));
        if (!empty($maybeNewMed)) {
            
            $best = $this->findBestProductMatch($maybeNewMed);
            if ($best) {
                if (!$activeTopic || (int)$activeTopic['product_id'] !== (int)$best['id']) {
                    $this->setTopic($best);
                    $activeTopic = $this->getCurrentTopic();
                } else {
                    
                    $this->setTopic($best);
                }
            }
        } elseif ($activeTopic) {
            
            $this->setTopic($activeTopic);
        }
        
        $topicLockActive = $activeTopic !== null;
        
        
        if ($this->use_cache && !$topicLockActive) {
            $cached = $this->cache->getCachedResponse($message);
            if ($cached) {
                return $this->response($cached['message'], $cached['type'], [
                    'cached' => true,
                    'ai_powered' => false
                ]);
            }
        }
        
        
        if (!$this->cache->checkDailyQuota()) {
            
            $this->use_ai = false;
        }
        
        
        if ($this->use_ai && $this->gemini) {
            $ai_response = $this->processWithAI($message);
            if ($ai_response['status'] === 'success' && !empty($ai_response['ai_powered'])) {
                
                if ($this->use_cache && !$topicLockActive) {
                    $this->cache->cacheResponse($message, $ai_response['message'], $ai_response['type']);
                }
                
                $this->cache->logAPICall();
                return $ai_response;
            }
            
        }
        
        
        $language = $this->detectLanguage($message);
        if ($language === self::LANG_UNKNOWN) {
            return $this->response(
                "Please use English or Bisaya. / Palihug gamit og English o Bisaya.",
                'info'
            );
        }
        
        
        $intent = $this->detectIntent($message, $language);
        
        $intentsRequiringMed = [self::INTENT_MEDICINE_AVAILABILITY, self::INTENT_FDA_INQUIRY, self::INTENT_GENERIC_BRANDED, self::INTENT_PRESCRIPTION];
        if ($topicLockActive && in_array($intent['type'], $intentsRequiringMed, true)) {
            $hasMed = !empty($intent['data']['medicine'] ?? '');
            if (!$hasMed && $activeTopic) {
                $intent['data']['medicine'] = $activeTopic['name'];
            }
        }
        
        
        $response = $this->generateResponse($intent, $message, $language);
        
        
        if ($this->use_cache && $response['status'] === 'success') {
            $this->cache->cacheResponse($message, $response['message'], $response['type']);
        }
        
        return $response;
    }
    
    
    private function processWithAI($message) {
        try {
            
            $this->saveToHistory('user', $message);
            
            
            $context = $this->buildAIContext($message);
            
            
            $ai_response = $this->gemini->generateResponse($message, $context);
            
            if ($ai_response['status'] === 'success') {
                
                $this->saveToHistory('assistant', $ai_response['message']);
                
                $result = [
                    'status' => 'success',
                    'type' => $ai_response['type'] ?? 'info',
                    'message' => $ai_response['message'],
                    'ai_powered' => true,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                
                $topic = $this->getCurrentTopic();
                if ($topic && !empty($topic['name'])) {
                    $result['topic'] = $topic['name'];
                }
                
                return $result;
            }
            
            
            return ['status' => 'error', 'ai_powered' => false];
            
        } catch (Exception $e) {
            error_log("Gemini AI Error: " . $e->getMessage());
            return ['status' => 'error', 'ai_powered' => false];
        }
    }
    
    
    private function buildAIContext($message) {
        $context = [];
        
        
        $topic = $this->getCurrentTopic();
        if ($topic) {
            $context['topic_lock'] = $topic;
        }

        
        $medicines = $this->searchProductsForAI($message);
        if (!empty($medicines)) {
            $context['medicines'] = $medicines;
        }
        
        
        if ($topic && !empty($topic['product_id'])) {
            $topic_found = false;
            foreach ($context['medicines'] ?? [] as $m) {
                if ((int)$m['id'] === (int)$topic['product_id']) {
                    $topic_found = true;
                    break;
                }
            }
            if (!$topic_found) {
                
                $tid = (int)$topic['product_id'];
                $sql = "SELECT p.*, COALESCE(SUM(s.quantity), 0) as total_stock
                        FROM product_list p
                        LEFT JOIN stock_list s ON p.id = s.product_id
                        WHERE p.id = {$tid} AND p.delete_flag = 0
                        GROUP BY p.id LIMIT 1";
                $res = $this->conn->query($sql);
                if ($res && $row = $res->fetch_assoc()) {
                    
                    $context['medicines'] = array_merge([$row], $context['medicines'] ?? []);
                }
            }
        }
        
        
        $fda_data = $this->searchFDAForAI($message);
        if (!empty($fda_data)) {
            $context['fda_data'] = $fda_data;
        }
        
        
        $user_orders = $this->getUserOrdersForAI($message);
        if (!empty($user_orders)) {
            $context['user_orders'] = $user_orders;
        }
        
        
        $this->loadConversationHistory();
        if (!empty($this->conversation_history)) {
            $context['conversation_history'] = $this->conversation_history;
        }
        
        return $context;
    }
    
    
    private function getUserOrdersForAI($message) {
        $keywords = ['order', 'status', 'track', 'where', 'my order', 'deliver', 'pickup'];
        $is_order_query = false;
        $msg_lower = strtolower($message);
        foreach ($keywords as $kw) {
            if (strpos($msg_lower, $kw) !== false) {
                $is_order_query = true;
                break;
            }
        }
        
        if (!$is_order_query) return [];
        
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $customer_id = $_SESSION['userdata']['id'] ?? null;
        if (!$customer_id) {
            return [['error' => 'Not logged in', 'message' => 'Please tell the user they must log in to check order status.']];
        }
        
        $orders = [];
        
        $sql = "SELECT id, code, status, date_created, pickup_date, pickup_time, total_amount 
                FROM order_list 
                WHERE customer_id = ? 
                ORDER BY date_created DESC LIMIT 3";
        $stmt = $this->conn->prepare($sql);
        if($stmt) {
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                
                $status_str = 'Pending';
                if($row['status'] == 1) $status_str = 'Packed/Ready for Pickup';
                if($row['status'] == 2) $status_str = 'Completed/Picked Up';
                if($row['status'] == 3) $status_str = 'Cancelled';
                
                $pickup_time_fmt = date('M d, Y', strtotime($row['pickup_date'])) . ' ' . date('h:i A', strtotime($row['pickup_time']));
                
                $orders[] = [
                    'order_code' => $row['code'],
                    'status' => $status_str,
                    'total' => '₱' . number_format($row['total_amount'], 2),
                    'pickup_time' => $pickup_time_fmt,
                    'date_created' => date('M d, Y', strtotime($row['date_created']))
                ];
            }
            if (empty($orders)) {
                return [['error' => 'No orders found', 'message' => 'Tell the user they have no recent orders.']];
            }
        }
        return $orders;
    }
    
    
    private function loadConversationHistory() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->conversation_history = $_SESSION['chatbot_history'] ?? [];
    }

    
    private function getCurrentTopic() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['chatbot_topic'])) return null;
        $topic = $_SESSION['chatbot_topic'];
        
        $age = time() - ($topic['ts'] ?? 0);
        if ($age > ($this->topic_ttl_minutes * 60)) {
            unset($_SESSION['chatbot_topic']);
            return null;
        }
        return $topic;
    }

    private function setTopic($product) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['chatbot_topic'] = [
            'product_id' => (int)($product['id'] ?? $product['product_id'] ?? 0),
            'name' => $product['name'] ?? ($product['brand'] ?? ''),
            'brand' => $product['brand'] ?? '',
            'ts' => time()
        ];
    }

    private function clearTopic() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['chatbot_topic']);
    }

    
    
    private function findBestProductMatch($message) {
        $escaped = $this->conn->real_escape_string($message);
        $search = '%' . $escaped . '%';
        
        
        $sql = "SELECT p.*, COALESCE(SUM(s.quantity), 0) as total_stock
                FROM product_list p
                LEFT JOIN stock_list s ON p.id = s.product_id
                WHERE (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?) AND p.delete_flag = 0
                GROUP BY p.id
                ORDER BY 
                    CASE 
                        WHEN LOWER(p.name) = LOWER(?) THEN 0
                        WHEN p.name LIKE ? THEN 1
                        ELSE 2 
                    END,
                    total_stock DESC, p.name ASC
                LIMIT 1";
        $exact = $escaped;
        $starts = $escaped . '%';
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssss', $search, $search, $search, $exact, $starts);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res->fetch_assoc() ?: null;
        }
        return null;
    }
    
    
    private function saveToHistory($role, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['chatbot_history'])) {
            $_SESSION['chatbot_history'] = [];
        }
        
        $_SESSION['chatbot_history'][] = [
            'role' => $role,
            'message' => substr($message, 0, 500), 
            'time' => date('H:i:s')
        ];
        
        
        if (count($_SESSION['chatbot_history']) > self::MAX_HISTORY * 2) {
            $_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -self::MAX_HISTORY * 2);
        }
    }
    
    
    private function searchProductsForAI($message) {
        $words = preg_split('/\s+/', $message);
        $skip_words = ['do', 'you', 'have', 'is', 'the', 'a', 'any', 'available', 'stock', 
                       'price', 'how', 'much', 'what', 'where', 'can', 'i', 'get', 'buy',
                       'naa', 'ba', 'pila', 'asa', 'unsa', 'mo', 'ang', 'sa', 'og'];
        
        
        $medicine_suffixes = ['er', 'sr', 'cr', 'la', 'xl', 'xr', 'ds', 'hd', 'mr'];
        
        $search_terms = [];
        foreach ($words as $word) {
            $word = trim($word, '.,?!');
            $lower = strtolower($word);
            
            if (in_array($lower, $medicine_suffixes)) {
                $search_terms[] = $this->conn->real_escape_string($word);
                continue;
            }
            if (strlen($word) < 3 || in_array($lower, $skip_words)) continue;
            $search_terms[] = $this->conn->real_escape_string($word);
        }
        
        if (empty($search_terms)) return [];
        
        
        $conditions = [];
        
        
        if (count($search_terms) > 1) {
            $phrase = implode(' ', $search_terms);
            $conditions[] = "(p.name LIKE '%{$phrase}%' OR p.brand LIKE '%{$phrase}%')";
        }
        
        
        foreach ($search_terms as $term) {
            $conditions[] = "(p.name LIKE '%{$term}%' OR p.brand LIKE '%{$term}%')";
        }
        $where = implode(' OR ', $conditions);
        
        
        $phrase_for_order = implode(' ', $search_terms);
        $sql = "SELECT p.*, COALESCE(SUM(s.quantity), 0) as total_stock
                FROM product_list p
                LEFT JOIN stock_list s ON p.id = s.product_id
                WHERE ({$where}) AND p.delete_flag = 0
                GROUP BY p.id
                ORDER BY 
                    CASE WHEN p.name LIKE '%{$phrase_for_order}%' THEN 0 ELSE 1 END,
                    total_stock DESC
                LIMIT 8";
        
        $result = $this->conn->query($sql);
        $results = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        return $results;
    }
    
    
    private function searchFDAForAI($message) {
        $words = preg_split('/\s+/', $message);
        $skip_words = ['fda', 'approved', 'safe', 'is', 'the', 'a', 'check', 'status'];
        
        $search_terms = [];
        foreach ($words as $word) {
            $word = trim($word, '.,?!');
            if (strlen($word) < 3 || in_array(strtolower($word), $skip_words)) continue;
            $search_terms[] = $this->conn->real_escape_string($word);
        }
        
        if (empty($search_terms)) return [];
        
        
        $conditions = [];
        foreach ($search_terms as $term) {
            $conditions[] = "(drug_name LIKE '%{$term}%' OR generic_name LIKE '%{$term}%' OR brand_name LIKE '%{$term}%')";
        }
        $where = implode(' OR ', $conditions);
        
        $sql = "SELECT * FROM drug_database WHERE {$where} LIMIT 3";
        
        $result = $this->conn->query($sql);
        $results = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        return $results;
    }
    
    
    private function isEmergency($message) {
        $message_lower = strtolower($message);
        $emergency_keywords = ['overdose', 'poison', 'suicide', 'chest pain', 'heart attack', 
                               'stroke', 'cant breathe', 'anaphylaxis'];
        foreach ($emergency_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) return true;
        }
        return false;
    }
    
    
    private function handleEmergency($message) {
        return $this->response(
            "🚨 **MEDICAL EMERGENCY DETECTED**\n\n" .
            "⚠️ **Please call emergency services immediately!**\n\n" .
            "📞 **Emergency: 911**\n" .
            "📞 **Poison Control: (02) 8524-1078**\n\n" .
            "🏥 Go to the nearest hospital immediately.\n\n" .
            "**Do NOT wait** - seek professional medical help right away.",
            'danger'
        );
    }
    
    
    public function setAIEnabled($enabled) {
        $this->use_ai = $enabled;
    }
    
    
    private function respondOrderStatus($is_bisaya) {
        
        $orders = $this->getUserOrdersForAI('order status');
        
        if (empty($orders)) {
            $msg = $is_bisaya ? "Wala kay active nga orders." : "You have no recent orders.";
            return $this->response("📦 **ORDER STATUS**\n\n" . $msg, 'info');
        }
        
        if (isset($orders[0]['error'])) {
            $msg = $is_bisaya ? "Palihug pag log-in para makita ang imong order status." : "Please log in to your account to check your order status.";
            return $this->response("📦 **ORDER STATUS**\n\n" . $msg, 'warning');
        }
        
        $msg = $is_bisaya ? "Kini ang status sa imong recent orders:\n\n" : "Here is the status of your recent orders:\n\n";
        
        foreach ($orders as $order) {
            $msg .= "• **Order #{$order['order_code']}**\n";
            $msg .= "  Status: **{$order['status']}**\n";
            $msg .= "  Total: {$order['total']}\n";
            $msg .= "  Pickup: {$order['pickup_time']}\n\n";
        }
        
        return $this->response("📦 **ORDER STATUS**\n\n" . $msg, 'info');
    }
    
    
    public function testAI() {
        if ($this->gemini) {
            return $this->gemini->testConnection();
        }
        return ['success' => false, 'error' => 'Gemini not initialized'];
    }
    
    
    private function validateInput($message) {
        
        $message = trim($message);
        
        
        if (empty($message)) {
            return [
                'valid' => false,
                'message' => "Please type a valid question. / Palihug pagsulat og valid nga pangutana."
            ];
        }
        
        
        if (strlen($message) < 2) {
            return [
                'valid' => false,
                'message' => "Please type a valid question. / Palihug pagsulat og valid nga pangutana."
            ];
        }
        
        
        if (strlen($message) > 1000) {
            return [
                'valid' => false,
                'message' => "Message too long. Please keep it under 1000 characters. / Taas kaayo ang message. Palihug gamay-a."
            ];
        }
        
        
        if (preg_match('/^[^a-zA-Z0-9]+$/', $message)) {
            return [
                'valid' => false,
                'message' => "Please type a valid question. / Palihug pagsulat og valid nga pangutana."
            ];
        }
        
        
        $cleaned = preg_replace('/[^\w\s\?\!\.\,\-\']/u', '', $message);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return [
            'valid' => true,
            'cleaned_message' => $cleaned
        ];
    }
    
    
    private function detectLanguage($message) {
        $message_lower = strtolower($message);
        
        
        $bisaya_words = [
            'unsa', 'asa', 'kanus-a', 'ngano', 'pila', 'kinsa',
            'naa', 'wala', 'dili', 'oo', 'unya', 'karon', 'ganina',
            'palihug', 'salamat', 'maayo', 'daot', 'sakit', 'tambal',
            'barato', 'mahal', 'gamay', 'dako', 'bag-o', 'daan',
            'ang', 'sa', 'og', 'ug', 'ko', 'ka', 'siya', 'kita', 'sila',
            'nganong', 'unsaon', 'aha', 'didto', 'dinhi', 'adto', 'anhi',
            'mangutana', 'tubag', 'tabang', 'palit', 'hatag', 'kuha',
            'oras', 'adlaw', 'gabii', 'buntag', 'hapon', 'udto',
            'buotan', 'tabangi', 'palihog', 'pwede', 'mahimo'
        ];
        
        
        $english_words = [
            'what', 'where', 'when', 'why', 'how', 'who', 'which',
            'is', 'are', 'am', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'the', 'a', 'an', 'this', 'that', 'these', 'those',
            'yes', 'no', 'please', 'thank', 'thanks', 'hello', 'hi',
            'medicine', 'drug', 'available', 'stock', 'price', 'cost',
            'prescription', 'generic', 'brand', 'dosage', 'take', 'daily'
        ];
        
        $bisaya_count = 0;
        $english_count = 0;
        
        $words = preg_split('/\s+/', $message_lower);
        
        foreach ($words as $word) {
            $word = trim($word, '.,?!');
            if (in_array($word, $bisaya_words)) {
                $bisaya_count++;
            }
            if (in_array($word, $english_words)) {
                $english_count++;
            }
        }
        
        
        if ($bisaya_count > $english_count && $bisaya_count >= 1) {
            return self::LANG_BISAYA;
        } elseif ($english_count > 0 || $bisaya_count === 0) {
            
            return self::LANG_ENGLISH;
        }
        
        return self::LANG_ENGLISH; 
    }
    
    
    private function detectIntent($message, $language) {
        $message_lower = strtolower($message);
        $activeTopic = $this->getCurrentTopic();
        
        
        $order_keywords = ['order', 'status', 'track', 'where is my order', 'deliver', 'pickup'];
        foreach ($order_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return ['type' => self::INTENT_ORDER_STATUS, 'data' => []];
            }
        }
        
        
        $greeting_keywords = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
                              'maayong', 'kumusta', 'musta', 'uy', 'hoy'];
        foreach ($greeting_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return ['type' => self::INTENT_GREETING, 'data' => []];
            }
        }
        
        
        $emergency_keywords = ['pregnant', 'pregnancy', 'child', 'baby', 'infant', 'toddler',
                               'chronic', 'diabetes', 'heart', 'kidney', 'liver', 'cancer',
                               'emergency', 'urgent', 'serious', 'severe', 'allergic', 'allergy',
                               'buntis', 'bata', 'masuso', 'grabe', 'emergency'];
        foreach ($emergency_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return ['type' => self::INTENT_EMERGENCY, 'data' => ['keyword' => $keyword]];
            }
        }
        
        
        $dosage_keywords = ['how many', 'how much', 'how often', 'daily', 'twice', 'times a day',
                           'take', 'dosage', 'dose', 'side effect', 'overdose',
                           'pila ka', 'unsaon pag', 'kapila', 'matag adlaw'];
        foreach ($dosage_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return ['type' => self::INTENT_DOSAGE_ADVICE, 'data' => []];
            }
        }
        
        
        $store_keywords = ['hour', 'open', 'close', 'location', 'address', 'where', 'contact', 
                          'phone', 'number', 'email', 'schedule', 'time',
                          'oras', 'asa', 'diin', 'lokasyon', 'abli', 'sirado'];
        foreach ($store_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return ['type' => self::INTENT_STORE_INFO, 'data' => ['query' => $keyword]];
            }
        }
        
        
        $rx_keywords = ['prescription', 'prescribe', 'doctor', 'rx', 'need prescription',
                        'require prescription', 'otc', 'over the counter',
                        'reseta', 'doktor', 'kinahanglan ba'];
        foreach ($rx_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                
                $medicine = $this->extractMedicineName($message);
                return ['type' => self::INTENT_PRESCRIPTION, 'data' => ['medicine' => $medicine]];
            }
        }
        
        
        $fda_keywords = ['fda', 'approved', 'safe', 'legitimate', 'legal', 'registered',
                         'aprubado', 'ligtas', 'tinuod'];
        foreach ($fda_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                $medicine = $this->extractMedicineName($message);
                return ['type' => self::INTENT_FDA_INQUIRY, 'data' => ['medicine' => $medicine]];
            }
        }
        
        
        $generic_keywords = ['generic', 'alternative', 'cheaper', 'substitute', 'branded',
                            'brand name', 'same as', 'equivalent',
                            'barato', 'pareho', 'alternatibo'];
        foreach ($generic_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                $medicine = $this->extractMedicineName($message);
                return ['type' => self::INTENT_GENERIC_BRANDED, 'data' => ['medicine' => $medicine]];
            }
        }
        
        
        $availability_keywords = ['available', 'stock', 'have', 'got', 'buy', 'purchase', 'price',
                                  'cost', 'how much', 'sell',
                                  'naa ba', 'pila', 'palit', 'mapalit'];
        foreach ($availability_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                $medicine = $this->extractMedicineName($message);
                
                if (empty(trim($medicine)) && $activeTopic) {
                    $medicine = $activeTopic['name'];
                }
                return ['type' => self::INTENT_MEDICINE_AVAILABILITY, 'data' => ['medicine' => $medicine]];
            }
        }
        
        
        $medicine = $this->extractMedicineName($message);
        if (empty(trim($medicine)) && $activeTopic) {
            $medicine = $activeTopic['name'];
        }
        if (!empty($medicine)) {
            return ['type' => self::INTENT_MEDICINE_AVAILABILITY, 'data' => ['medicine' => $medicine]];
        }
        
        
        return ['type' => self::INTENT_UNKNOWN, 'data' => []];
    }
    
    
    private function extractMedicineName($message) {
        
        $stop_words = ['do', 'you', 'have', 'is', 'there', 'any', 'the', 'a', 'an', 'available',
                       'in', 'stock', 'can', 'i', 'get', 'buy', 'purchase', 'need', 'prescription',
                       'for', 'generic', 'brand', 'branded', 'of', 'what', 'price', 'cost', 'how', 'much',
                       'naa', 'ba', 'mo', 'ko', 'ang', 'sa', 'og', 'ug', 'pila', 'palit',
                       
                       'fda', 'approved', 'safe', 'legitimate', 'legal', 'registered', 'aprubado', 'ligtas', 'tinuod',
                       
                       'rx', 'otc', 'over', 'counter', 'doctor', 'reseta', 'doktor', 'kinahanglan',
                       
                       'alternative', 'cheaper', 'substitute', 'equivalent', 'barato', 'pareho', 'alternatibo',
                       
                       'this', 'that', 'does', 'require', 'requires', 'it', 'please', 'thanks', 'thank'];
        
        
        $medicine_suffixes = ['er', 'sr', 'cr', 'la', 'xl', 'xr', 'ds', 'hd', 'mr'];
        
        $words = preg_split('/\s+/', $message);
        $potential_medicines = [];
        
        foreach ($words as $word) {
            $clean_word = trim($word, '.,?!');
            $lower = strtolower($clean_word);
            
            
            if (in_array($lower, $medicine_suffixes)) {
                $potential_medicines[] = $clean_word;
                continue;
            }
            
            
            if (strlen($clean_word) < 3 || in_array($lower, $stop_words)) {
                continue;
            }
            
            if (preg_match('/^[A-Z]/', $word) || preg_match('/\d+mg|\d+ml/i', $word)) {
                $potential_medicines[] = $clean_word;
            }
        }
        
        
        if (empty($potential_medicines)) {
            
            $filtered = array_filter($words, function($w) use ($stop_words, $medicine_suffixes) {
                $clean = strtolower(trim($w, '.,?!'));
                return (in_array($clean, $medicine_suffixes) || strlen($w) >= 3) 
                       && !in_array($clean, $stop_words);
            });
            $potential_medicines = array_values($filtered);
        }
        
        return implode(' ', $potential_medicines);
    }
    
    
    private function generateResponse($intent, $message, $language) {
        $is_bisaya = ($language === self::LANG_BISAYA);
        
        switch ($intent['type']) {
            
            case self::INTENT_GREETING:
                return $this->respondGreeting($is_bisaya);
            
            
            case self::INTENT_STORE_INFO:
                return $this->respondStoreInfo($intent['data'], $is_bisaya);
            
            
            case self::INTENT_MEDICINE_AVAILABILITY:
                return $this->respondMedicineAvailability($intent['data'], $is_bisaya);
            
            
            case self::INTENT_FDA_INQUIRY:
                return $this->respondFDAInquiry($intent['data'], $is_bisaya);
            
            
            case self::INTENT_PRESCRIPTION:
                return $this->respondPrescription($intent['data'], $is_bisaya);
                
            case self::INTENT_ORDER_STATUS:
                return $this->respondOrderStatus($is_bisaya);
                
            case self::INTENT_GENERIC_BRANDED:
                return $this->respondGenericBranded($intent['data'], $is_bisaya);
            
            
            case self::INTENT_DOSAGE_ADVICE:
                return $this->respondDosageAdvice($is_bisaya);
            
            
            case self::INTENT_EMERGENCY:
                return $this->respondEmergency($intent['data'], $is_bisaya);
            
            
            default:
                return $this->respondUnknown($is_bisaya);
        }
    }
    
    
    private function respondGreeting($is_bisaya) {
        if ($is_bisaya) {
            return $this->response(
                "Hi there! 👋 Maayong adlaw!\n\n" .
                "Welcome to **{$this->store_info['name']}**\n" .
                "Your trusted pharmacy partner 💊\n\n" .
                "I'm here to help you with:\n\n" .
                "🏪 Store Info (hours, location)\n" .
                "💊 Medicine availability\n" .
                "✅ FDA verification\n" .
                "📋 Prescription guidance\n" .
                "💰 Generic alternatives\n\n" .
                "What can I help you with today?",
                'success'
            );
        }
        
        return $this->response(
            "Hi there! 👋\n\n" .
            "Welcome to **{$this->store_info['name']}**\n" .
            "Your trusted pharmacy partner �\n\n" .
            "I'm here to help you with:\n\n" .
            "🏪 Store Info (hours, location)\n" .
            "💊 Medicine availability\n" .
            "✅ FDA verification\n" .
            "📋 Prescription guidance\n" .
            "💰 Generic alternatives\n\n" .
            "What can I help you with today?",
            'success'
        );
    }
    
    
    private function respondStoreInfo($data, $is_bisaya) {
        $query = strtolower($data['query'] ?? '');
        
        if ($is_bisaya) {
            $info = "📍 **{$this->store_info['name']}**\n\n";
            
            if (strpos($query, 'hour') !== false || strpos($query, 'oras') !== false || 
                strpos($query, 'open') !== false || strpos($query, 'abli') !== false) {
                $info .= "🕐 **Oras sa Pag-abli:** {$this->store_info['hours']}\n";
            }
            
            if (strpos($query, 'address') !== false || strpos($query, 'location') !== false || 
                strpos($query, 'asa') !== false || strpos($query, 'diin') !== false) {
                $info .= "📍 **Lokasyon:** {$this->store_info['address']}\n";
            }
            
            if (strpos($query, 'contact') !== false || strpos($query, 'phone') !== false || 
                strpos($query, 'number') !== false) {
                $info .= "📞 **Contact:** {$this->store_info['contact']}\n";
                if ($this->store_info['mobile'] !== 'N/A') {
                    $info .= "📱 **Mobile:** {$this->store_info['mobile']}\n";
                }
            }
            
            if (strpos($query, 'email') !== false) {
                $info .= "📧 **Email:** {$this->store_info['email']}\n";
            }
            
            
            if (strpos($info, '**Oras') === false && strpos($info, '**Lokasyon') === false) {
                $info .= "🕐 **Oras:** {$this->store_info['hours']}\n";
                $info .= "📍 **Lokasyon:** {$this->store_info['address']}\n";
                $info .= "📞 **Contact:** {$this->store_info['contact']}\n";
                $info .= "📧 **Email:** {$this->store_info['email']}\n";
            }
            
            return $this->response($info, 'info');
        }
        
        
        $info = "📍 **{$this->store_info['name']}**\n\n";
        
        if (strpos($query, 'hour') !== false || strpos($query, 'open') !== false || 
            strpos($query, 'close') !== false || strpos($query, 'time') !== false) {
            $info .= "🕐 **Store Hours:** {$this->store_info['hours']}\n";
        }
        
        if (strpos($query, 'address') !== false || strpos($query, 'location') !== false || 
            strpos($query, 'where') !== false) {
            $info .= "📍 **Location:** {$this->store_info['address']}\n";
        }
        
        if (strpos($query, 'contact') !== false || strpos($query, 'phone') !== false || 
            strpos($query, 'number') !== false) {
            $info .= "📞 **Contact:** {$this->store_info['contact']}\n";
            if ($this->store_info['mobile'] !== 'N/A') {
                $info .= "📱 **Mobile:** {$this->store_info['mobile']}\n";
            }
        }
        
        if (strpos($query, 'email') !== false) {
            $info .= "📧 **Email:** {$this->store_info['email']}\n";
        }
        
        
        if (strpos($info, '**Store Hours') === false && strpos($info, '**Location') === false) {
            $info .= "🕐 **Store Hours:** {$this->store_info['hours']}\n";
            $info .= "📍 **Location:** {$this->store_info['address']}\n";
            $info .= "📞 **Contact:** {$this->store_info['contact']}\n";
            $info .= "📧 **Email:** {$this->store_info['email']}\n";
        }
        
        return $this->response($info, 'info');
    }
    
    
    private function respondMedicineAvailability($data, $is_bisaya) {
        $medicine = $data['medicine'] ?? '';
        
        if (empty($medicine)) {
            if ($is_bisaya) {
                return $this->response(
                    "Palihug isulat ang ngalan sa tambal nga gusto nimong tan-awon.\n" .
                    "Pananglitan: \"Naa ba Biogesic?\"",
                    'info'
                );
            }
            return $this->response(
                "Please specify the medicine name you're looking for.\n" .
                "Example: \"Do you have Biogesic?\"",
                'info'
            );
        }
        
        
        $firstLetter = strtoupper(substr($medicine, 0, 1));
        $products = [];
        $medicineLength = strlen(trim($medicine));
        
        
        if ($medicineLength === 1) {
            $firstLetterSearch = $this->conn->real_escape_string($firstLetter) . '%';
            $sql = "SELECT p.*, c.name as category_name, b.branch_name as branch_name 
                    FROM product_list p 
                    LEFT JOIN category_list c ON p.category_id = c.id 
                    LEFT JOIN branch_list b ON p.branch_id = b.id
                    WHERE (UPPER(p.name) LIKE ? OR UPPER(p.brand) LIKE ?) 
                    AND p.delete_flag = 0 
                    ORDER BY p.name ASC
                    LIMIT 15";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $firstLetterSearch, $firstLetterSearch);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        } else {
            
            $search = '%' . $this->conn->real_escape_string($medicine) . '%';
            $exactSearch = $this->conn->real_escape_string($medicine) . '%';
            
            $sql = "SELECT p.*, c.name as category_name, b.branch_name as branch_name 
                    FROM product_list p 
                    LEFT JOIN category_list c ON p.category_id = c.id 
                    LEFT JOIN branch_list b ON p.branch_id = b.id
                    WHERE (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?) 
                    AND p.delete_flag = 0 
                    ORDER BY 
                        CASE 
                            WHEN p.name LIKE ? THEN 1
                            WHEN p.brand LIKE ? THEN 2
                            ELSE 3
                        END,
                        p.name ASC
                    LIMIT 10";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssss", $search, $search, $search, $exactSearch, $exactSearch);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            
            if (count($products) === 0) {
                $firstLetterSearch = $this->conn->real_escape_string($firstLetter) . '%';
                $sql2 = "SELECT p.*, c.name as category_name, b.branch_name as branch_name 
                        FROM product_list p 
                        LEFT JOIN category_list c ON p.category_id = c.id 
                        LEFT JOIN branch_list b ON p.branch_id = b.id
                        WHERE (UPPER(p.name) LIKE ? OR UPPER(p.brand) LIKE ?) 
                        AND p.delete_flag = 0 
                        ORDER BY p.name ASC
                        LIMIT 10";
                
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->bind_param("ss", $firstLetterSearch, $firstLetterSearch);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                while ($row = $result2->fetch_assoc()) {
                    $products[] = $row;
                }
            }
        }
        
        
        if (count($products) < 5 && strlen($medicine) >= 3) {
            $existingIds = array_column($products, 'id');
            $similarSearch = substr($medicine, 0, 3) . '%';
            $similarSearch = $this->conn->real_escape_string($similarSearch);
            
            $placeholders = !empty($existingIds) ? implode(',', array_fill(0, count($existingIds), '?')) : '0';
            $sql3 = "SELECT p.*, c.name as category_name, b.branch_name as branch_name 
                    FROM product_list p 
                    LEFT JOIN category_list c ON p.category_id = c.id 
                    LEFT JOIN branch_list b ON p.branch_id = b.id
                    WHERE (p.name LIKE ? OR p.brand LIKE ?) 
                    AND p.delete_flag = 0 
                    AND p.id NOT IN ($placeholders)
                    ORDER BY p.name ASC
                    LIMIT 5";
            
            $stmt3 = $this->conn->prepare($sql3);
            $types = 'ss' . str_repeat('i', count($existingIds));
            $params = array_merge([$similarSearch, $similarSearch], $existingIds);
            $stmt3->bind_param($types, ...$params);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            
            while ($row = $result3->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        
        if (count($products) > 0) {
            if ($is_bisaya) {
                $response = "✅ **Nakit-an nako ang mga tambal!**\n\n";
                if (count($products) === 1) {
                    $response = "✅ **Available ang \"{$medicine}\"!**\n\n";
                }
            } else {
                $response = "✅ **Found medicines for you!**\n\n";
                if (count($products) === 1) {
                    $response = "✅ **\"{$medicine}\" is Available!**\n\n";
                }
            }
            
            foreach ($products as $row) {
                $response .= "💊 **{$row['name']}**";
                if (!empty($row['brand'])) {
                    $response .= " ({$row['brand']})";
                }
                $response .= "\n";
                $response .= "   💰 Price: ₱" . number_format($row['price'], 2) . " per {$row['unit']}\n";
                if (!empty($row['dose'])) {
                    $response .= "   📏 Dosage: {$row['dose']}\n";
                }
                if (!empty($row['branch_name'])) {
                    $response .= "   🏪 Branch: {$row['branch_name']}\n";
                }
                $response .= "\n";
            }
            
            if ($is_bisaya) {
                $response .= "Gusto ba nimo mo-order? Pwede ka mag-add to cart o bisitaha ang among tindahan!";
            } else {
                $response .= "Would you like to order? You can add to cart or visit our store!";
            }
            
            return $this->response($response, 'success');
        }
        
        
        if ($is_bisaya) {
            return $this->response(
                "❌ **Pasensya, wala sa stock ang \"{$medicine}\".**\n\n" .
                "Pwede nimong:\n" .
                "• Mangutana sa pharmacist para sa alternatibo\n" .
                "• Tan-awa ang among uban nga mga tambal\n" .
                "• Balik ug tan-aw sa sunod nga adlaw\n\n" .
                "📞 Contact: {$this->store_info['contact']}",
                'warning'
            );
        }
        
        return $this->response(
            "❌ **Sorry, \"{$medicine}\" is currently out of stock.**\n\n" .
            "You can:\n" .
            "• Ask our pharmacist for alternatives\n" .
            "• Browse our other available medicines\n" .
            "• Check back another day\n\n" .
            "📞 Contact: {$this->store_info['contact']}",
            'warning'
        );
    }
    
    
    private function respondFDAInquiry($data, $is_bisaya) {
        $medicine = $data['medicine'] ?? '';
        
        if (empty($medicine)) {
            if ($is_bisaya) {
                return $this->response(
                    "Palihug isulat ang ngalan sa tambal nga gusto nimong i-check ang FDA status.",
                    'info'
                );
            }
            return $this->response(
                "Please specify the medicine name to check its FDA status.",
                'info'
            );
        }
        
        
        $search = '%' . $this->conn->real_escape_string($medicine) . '%';
        $sql = "SELECT * FROM drug_database 
                WHERE drug_name LIKE ? OR generic_name LIKE ? OR brand_name LIKE ? 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $drug = $result->fetch_assoc();
            
            if ($is_bisaya) {
                return $this->response(
                    "✅ **FDA Status para sa \"{$drug['drug_name']}\"**\n\n" .
                    "📋 **Status:** {$drug['fda_status']}\n" .
                    (!empty($drug['generic_name']) ? "💊 **Generic:** {$drug['generic_name']}\n" : "") .
                    (!empty($drug['manufacturer']) ? "🏭 **Manufacturer:** {$drug['manufacturer']}\n" : "") .
                    "\nKini nga tambal na-rehistro sa FDA. Para sa dugang nga kasiguroan, konsulta sa pharmacist.",
                    'success'
                );
            }
            
            return $this->response(
                "✅ **FDA Status for \"{$drug['drug_name']}\"**\n\n" .
                "📋 **Status:** {$drug['fda_status']}\n" .
                (!empty($drug['generic_name']) ? "💊 **Generic Name:** {$drug['generic_name']}\n" : "") .
                (!empty($drug['manufacturer']) ? "🏭 **Manufacturer:** {$drug['manufacturer']}\n" : "") .
                "\nThis medicine is registered with the FDA. For additional assurance, please consult our pharmacist.",
                'success'
            );
        }
        
        
        if ($is_bisaya) {
            return $this->response(
                "⚠️ **Wala makit-an ang FDA record para sa \"{$medicine}\"**\n\n" .
                "Kini wala nagpasabot nga dili kini aprubado. Palihug:\n" .
                "• Pangutana sa among pharmacist\n" .
                "• I-check ang FDA Philippines website\n" .
                "• Dad-a ang packaging para ma-verify",
                'warning'
            );
        }
        
        return $this->response(
            "⚠️ **FDA record not found for \"{$medicine}\"**\n\n" .
            "This doesn't necessarily mean it's not approved. Please:\n" .
            "• Ask our pharmacist for verification\n" .
            "• Check the FDA Philippines website\n" .
            "• Bring the packaging for verification",
            'warning'
        );
    }
    
    
    private function respondPrescription($data, $is_bisaya) {
        $medicine = $data['medicine'] ?? '';
        
        if (empty($medicine)) {
            if ($is_bisaya) {
                return $this->response(
                    "Palihug isulat ang ngalan sa tambal para ma-check kung kinahanglan ba og reseta.",
                    'info'
                );
            }
            return $this->response(
                "Please specify the medicine name to check if it requires a prescription.",
                'info'
            );
        }
        
        
        $search = '%' . $this->conn->real_escape_string($medicine) . '%';
        $sql = "SELECT * FROM drug_database 
                WHERE drug_name LIKE ? OR generic_name LIKE ? OR brand_name LIKE ? 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $drug = $result->fetch_assoc();
            
            if ($drug['is_prescription']) {
                if ($is_bisaya) {
                    return $this->response(
                        "📋 **\"{$drug['drug_name']}\" - KINAHANGLAN OG RESETA**\n\n" .
                        "⚠️ Kini nga tambal prescription drug. Kinahanglan nimo og:\n" .
                        "• Valid nga doctor's prescription\n" .
                        "• Prescription dili mosobra og 1 ka tuig ang edad\n\n" .
                        "Palihug dad-a ang imong reseta sa among pharmacy.",
                        'warning'
                    );
                }
                
                return $this->response(
                    "📋 **\"{$drug['drug_name']}\" - PRESCRIPTION REQUIRED**\n\n" .
                    "⚠️ This is a prescription medicine. You will need:\n" .
                    "• A valid doctor's prescription\n" .
                    "• Prescription must not be older than 1 year\n\n" .
                    "Please bring your prescription to our pharmacy.",
                    'warning'
                );
            } else {
                if ($is_bisaya) {
                    return $this->response(
                        "✅ **\"{$drug['drug_name']}\" - OVER-THE-COUNTER (OTC)**\n\n" .
                        "Kini nga tambal pwede mapalit nga walay reseta.\n\n" .
                        "💡 **Hinumdomi:** Bisan pa OTC, importante gihapon nga:\n" .
                        "• Basaha ang label instructions\n" .
                        "• Sunda ang recommended dosage\n" .
                        "• Konsulta sa pharmacist kung dili sigurado",
                        'success'
                    );
                }
                
                return $this->response(
                    "✅ **\"{$drug['drug_name']}\" - OVER-THE-COUNTER (OTC)**\n\n" .
                    "This medicine can be purchased without a prescription.\n\n" .
                    "💡 **Remember:** Even for OTC medicines:\n" .
                    "• Read the label instructions carefully\n" .
                    "• Follow recommended dosage\n" .
                    "• Consult our pharmacist if unsure",
                    'success'
                );
            }
        }
        
        
        if ($is_bisaya) {
            return $this->response(
                "⚠️ **Wala makit-an ang impormasyon para sa \"{$medicine}\"**\n\n" .
                "Para sa imong kasiguroan, palihug pangutana sa among pharmacist kung kinahanglan ba kini og reseta.",
                'info'
            );
        }
        
        return $this->response(
            "⚠️ **Information not found for \"{$medicine}\"**\n\n" .
            "For your safety, please ask our pharmacist whether this medicine requires a prescription.",
            'info'
        );
    }
    
    
    private function respondGenericBranded($data, $is_bisaya) {
        $medicine = $data['medicine'] ?? '';
        
        if (empty($medicine)) {
            if ($is_bisaya) {
                return $this->response(
                    "Palihug isulat ang ngalan sa tambal para makita ang generic alternatives.",
                    'info'
                );
            }
            return $this->response(
                "Please specify the medicine name to find generic alternatives.",
                'info'
            );
        }
        
        
        $search = '%' . $this->conn->real_escape_string($medicine) . '%';
        
        
        $sql = "SELECT * FROM drug_database 
                WHERE drug_name LIKE ? OR generic_name LIKE ? OR brand_name LIKE ? 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $drug = $result->fetch_assoc();
            $generic_name = $drug['generic_name'];
            
            
            if (!empty($generic_name)) {
                $generic_search = '%' . $this->conn->real_escape_string($generic_name) . '%';
                $alt_sql = "SELECT * FROM product_list 
                            WHERE (name LIKE ? OR description LIKE ?) AND delete_flag = 0 
                            LIMIT 5";
                
                $alt_stmt = $this->conn->prepare($alt_sql);
                $alt_stmt->bind_param("ss", $generic_search, $generic_search);
                $alt_stmt->execute();
                $alt_result = $alt_stmt->get_result();
                
                if ($alt_result->num_rows > 0) {
                    if ($is_bisaya) {
                        $response = "💊 **Generic Alternatives para sa \"{$medicine}\"**\n\n";
                        $response .= "Generic Name: **{$generic_name}**\n\n";
                        $response .= "Available sa among tindahan:\n";
                    } else {
                        $response = "💊 **Generic Alternatives for \"{$medicine}\"**\n\n";
                        $response .= "Generic Name: **{$generic_name}**\n\n";
                        $response .= "Available in our store:\n";
                    }
                    
                    while ($alt = $alt_result->fetch_assoc()) {
                        $response .= "• **{$alt['name']}**";
                        if (!empty($alt['brand'])) {
                            $response .= " ({$alt['brand']})";
                        }
                        $response .= " - ₱" . number_format($alt['price'], 2) . "\n";
                    }
                    
                    if ($is_bisaya) {
                        $response .= "\n💡 Ang generic nga tambal pareho ra og ingredients sa branded version pero mas barato.";
                    } else {
                        $response .= "\n💡 Generic medicines have the same active ingredients as branded versions but are more affordable.";
                    }
                    
                    return $this->response($response, 'success');
                }
            }
            
            
            if ($is_bisaya) {
                return $this->response(
                    "💊 **Impormasyon para sa \"{$drug['drug_name']}\"**\n\n" .
                    (!empty($drug['generic_name']) ? "Generic Name: **{$drug['generic_name']}**\n" : "") .
                    (!empty($drug['brand_name']) ? "Brand: {$drug['brand_name']}\n" : "") .
                    "\n⚠️ Walay available nga alternative sa among inventory karon. Palihug pangutana sa pharmacist para sa ubang options.",
                    'info'
                );
            }
            
            return $this->response(
                "💊 **Information for \"{$drug['drug_name']}\"**\n\n" .
                (!empty($drug['generic_name']) ? "Generic Name: **{$drug['generic_name']}**\n" : "") .
                (!empty($drug['brand_name']) ? "Brand: {$drug['brand_name']}\n" : "") .
                "\n⚠️ No alternatives currently available in our inventory. Please ask our pharmacist for other options.",
                'info'
            );
        }
        
        if ($is_bisaya) {
            return $this->response(
                "⚠️ Wala makit-an ang \"{$medicine}\" sa among database.\n\n" .
                "Palihug pangutana sa among pharmacist para sa generic alternatives.",
                'info'
            );
        }
        
        return $this->response(
            "⚠️ \"{$medicine}\" not found in our database.\n\n" .
            "Please ask our pharmacist for generic alternatives.",
            'info'
        );
    }
    
    
    private function respondDosageAdvice($is_bisaya) {
        if ($is_bisaya) {
            return $this->response(
                "⚠️ **Importante nga Pahinumdom**\n\n" .
                "Dili ko makahatag og specific dosage o medical advice.\n\n" .
                "Para sa imong kasiguroan, palihug:\n" .
                "• **Konsulta sa imong doktor** para sa proper dosage\n" .
                "• **Pangutana sa among pharmacist** para sa guidance\n" .
                "• **Basaha ang label** sa tambal\n" .
                "• **Ayaw sobra** sa recommended dose\n\n" .
                "📞 Contact pharmacist: {$this->store_info['contact']}",
                'warning'
            );
        }
        
        return $this->response(
            "⚠️ **Important Notice**\n\n" .
            "I cannot provide specific dosage or medical advice.\n\n" .
            "For your safety, please:\n" .
            "• **Consult your doctor** for proper dosage\n" .
            "• **Ask our pharmacist** for guidance\n" .
            "• **Read the medicine label** carefully\n" .
            "• **Never exceed** the recommended dose\n\n" .
            "📞 Contact pharmacist: {$this->store_info['contact']}",
            'warning'
        );
    }
    
    
    private function respondEmergency($data, $is_bisaya) {
        $keyword = $data['keyword'] ?? '';
        
        if ($is_bisaya) {
            return $this->response(
                "🚨 **IMPORTANTE: Kinahanglan og Professional nga Konsulta**\n\n" .
                "Ang imong pangutana naglakip og sensitive nga sitwasyon" .
                (!empty($keyword) ? " (may labot sa: {$keyword})" : "") . ".\n\n" .
                "**Palihug buhata kini:**\n" .
                "• 👨‍⚕️ Konsulta dayon sa imong doktor\n" .
                "• 💊 Pakig-storya sa among licensed pharmacist\n" .
                "• 🏥 Kung emergency, adto dayon sa pinakaduol nga hospital\n\n" .
                "**Ayaw pagself-medicate** kung:\n" .
                "• Buntis o nagpasuso\n" .
                "• Para sa bata o baby\n" .
                "• Naa kay chronic illness\n" .
                "• Nag-take og ubang mga tambal\n\n" .
                "📞 Pharmacy: {$this->store_info['contact']}\n" .
                "🚑 Emergency: 911",
                'danger'
            );
        }
        
        return $this->response(
            "🚨 **IMPORTANT: Professional Consultation Required**\n\n" .
            "Your inquiry involves a sensitive situation" .
            (!empty($keyword) ? " (related to: {$keyword})" : "") . ".\n\n" .
            "**Please do the following:**\n" .
            "• 👨‍⚕️ Consult your doctor immediately\n" .
            "• 💊 Speak with our licensed pharmacist\n" .
            "• 🏥 If emergency, go to the nearest hospital\n\n" .
            "**Do NOT self-medicate** if you are:\n" .
            "• Pregnant or breastfeeding\n" .
            "• Giving medicine to a child or infant\n" .
            "• Have a chronic disease\n" .
            "• Taking other medications\n\n" .
            "📞 Pharmacy: {$this->store_info['contact']}\n" .
            "🚑 Emergency: 911",
            'danger'
        );
    }
    
    
    private function respondUnknown($is_bisaya) {
        if ($is_bisaya) {
            return $this->response(
                "🤔 Pasensya, wala ko kasabot sa imong pangutana.\n\n" .
                "Makatabang ko nimo sa:\n" .
                "• 🏪 **Impormasyon sa tindahan** - \"Unsa ang oras?\", \"Asa mo?\"\n" .
                "• 💊 **Availability sa tambal** - \"Naa ba Biogesic?\"\n" .
                "• 📋 **FDA status** - \"Aprubado ba ang...?\"\n" .
                "• 💉 **Reseta** - \"Kinahanglan ba reseta ang...?\"\n" .
                "• 💰 **Generic** - \"Unsa ang generic sa...?\"\n\n" .
                "Para sa ubang mga concern, palihug kontaka ang among pharmacist:\n" .
                "📞 {$this->store_info['contact']}",
                'info'
            );
        }
        
        return $this->response(
            "🤔 I'm sorry, I didn't quite understand your question.\n\n" .
            "I can help you with:\n" .
            "• 🏪 **Store info** - \"What are your hours?\", \"Where are you located?\"\n" .
            "• 💊 **Medicine availability** - \"Do you have Biogesic?\"\n" .
            "• 📋 **FDA status** - \"Is [medicine] FDA approved?\"\n" .
            "• 💉 **Prescription info** - \"Does [medicine] need prescription?\"\n" .
            "• 💰 **Generic options** - \"What's the generic for [medicine]?\"\n\n" .
            "For other concerns, please contact our pharmacist:\n" .
            "📞 {$this->store_info['contact']}",
            'info'
        );
    }
    
    
    private function response($message, $type = 'info', $extra = []) {
        $response = [
            'status' => 'success',
            'type' => $type,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        
        $topic = $this->getCurrentTopic();
        if ($topic && !empty($topic['name'])) {
            $response['topic'] = $topic['name'];
        }
        
        
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        return $response;
    }
    
    
    public function getStats() {
        return $this->cache->getUsageStats();
    }
    
    
    public function searchProductsDirect($query) {
        $query = trim($query);
        if (strlen($query) < 2) return ['products' => [], 'count' => 0];
        
        
        $medicine_suffixes = ['er', 'sr', 'cr', 'la', 'xl', 'xr', 'ds', 'hd', 'mr'];
        
        $words = preg_split('/\s+/', $query);
        $search_terms = [];
        foreach ($words as $word) {
            $word = trim($word, '.,?!');
            $lower = strtolower($word);
            if (in_array($lower, $medicine_suffixes)) {
                $search_terms[] = $this->conn->real_escape_string($word);
                continue;
            }
            if (strlen($word) >= 2) {
                $search_terms[] = $this->conn->real_escape_string($word);
            }
        }
        
        if (empty($search_terms)) return ['products' => [], 'count' => 0];
        
        
        $conditions = [];
        
        
        if (count($search_terms) > 1) {
            $phrase = implode(' ', $search_terms);
            $conditions[] = "(p.name LIKE '%{$phrase}%' OR p.brand LIKE '%{$phrase}%')";
        }
        
        
        foreach ($search_terms as $term) {
            $conditions[] = "(p.name LIKE '%{$term}%' OR p.brand LIKE '%{$term}%')";
        }
        $where = implode(' OR ', $conditions);
        
        $phrase_order = implode(' ', $search_terms);
        $sql = "SELECT p.id, p.name, p.brand, p.price, p.dose,
                       COALESCE(SUM(s.quantity), 0) as total_stock
                FROM product_list p
                LEFT JOIN stock_list s ON p.id = s.product_id
                WHERE ({$where}) AND p.delete_flag = 0
                GROUP BY p.id
                ORDER BY
                    CASE WHEN p.name LIKE '%{$phrase_order}%' THEN 0 ELSE 1 END,
                    total_stock DESC, p.name ASC
                LIMIT 8";
        
        $result = $this->conn->query($sql);
        $products = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'brand' => $row['brand'],
                    'price' => number_format((float)$row['price'], 2),
                    'dose' => $row['dose'] ?? '',
                    'stock' => (int)$row['total_stock'],
                    'in_stock' => (int)$row['total_stock'] > 0
                ];
            }
        }
        
        return ['products' => $products, 'count' => count($products)];
    }
    
    
    public function seedCache() {
        return $this->cache->seedCommonQuestions();
    }
    
    
    public function clearCache() {
        return $this->cache->clearAllCache();
    }
    
    
    public function analyzeImage($imageData, $mimeType, $userMessage = '') {
        
        $rate_check = $this->cache->checkRateLimit();
        if (!$rate_check['allowed']) {
            return $this->response($rate_check['message'], 'warning', [
                'rate_limited' => true,
                'wait_seconds' => $rate_check['wait_seconds']
            ]);
        }
        
        
        if (!$this->cache->checkDailyQuota()) {
            return $this->response(
                "⚠️ Daily AI quota reached. Please try again tomorrow or contact our pharmacy directly.",
                'warning'
            );
        }
        
        
        if ($this->gemini) {
            
            $extractResponse = $this->gemini->extractMedicineFromImage($imageData, $mimeType);
            
            
            $this->cache->logAPICall();
            
            if ($extractResponse['status'] === 'success' && !empty($extractResponse['medicine_name'])) {
                $medicineName = $extractResponse['medicine_name'];
                $imageDetails = $extractResponse['details'] ?? '';
                
                
                $storeProducts = $this->searchProductsForAI($medicineName);
                
                
                $drugInfo = $this->searchFDAForAI($medicineName);
                
                
                $response = $this->buildImageProductResponse($medicineName, $imageDetails, $storeProducts, $drugInfo);
                
                
                $this->saveToHistory('user', '[Image uploaded: ' . $medicineName . ']' . ($userMessage ? ' - ' . $userMessage : ''));
                $this->saveToHistory('assistant', $response['message']);
                
                return $response;
            } elseif ($extractResponse['status'] === 'rejected') {
                
                return [
                    'status' => 'success',
                    'type' => 'warning',
                    'message' => $extractResponse['message'],
                    'ai_powered' => true
                ];
            } else {
                
                $response = $this->gemini->analyzeImage($imageData, $mimeType, $userMessage);
                $this->cache->logAPICall();
                
                if ($response['status'] === 'success') {
                    $this->saveToHistory('user', '[Image uploaded]' . ($userMessage ? ': ' . $userMessage : ''));
                    $this->saveToHistory('assistant', $response['message']);
                }
                
                return $response;
            }
        }
        
        return $this->response(
            "⚠️ Image analysis is not available right now. Please describe what you need help with.",
            'warning'
        );
    }
    
    
    private function buildImageProductResponse($medicineName, $imageDetails, $storeProducts, $drugInfo) {
        $message = "📷 **Medicine Identified:** {$medicineName}\n\n";
        
        
        if (!empty($imageDetails)) {
            $message .= "**From the image:**\n{$imageDetails}\n\n";
        }
        
        
        $message .= "---\n\n";
        if (!empty($storeProducts)) {
            $message .= "### 🏪 Store Availability\n\n";
            foreach ($storeProducts as $product) {
                $stock = isset($product['total_stock']) ? (int)$product['total_stock'] : 0;
                $stockStatus = $stock > 0 ? "✅ **IN STOCK** ({$stock} available)" : "❌ **OUT OF STOCK**";
                
                $message .= "**💊 {$product['name']}**";
                if (!empty($product['brand'])) {
                    $message .= " ({$product['brand']})";
                }
                $message .= "\n";
                $message .= "• Status: {$stockStatus}\n";
                $message .= "• Price: ₱" . number_format($product['price'], 2) . "\n";
                if (!empty($product['description'])) {
                    $message .= "• Description: {$product['description']}\n";
                }
                if (!empty($product['dose'])) {
                    $message .= "• Dosage: {$product['dose']}\n";
                }
                $message .= "\n";
            }
        } else {
            $message .= "### 🏪 Store Availability\n\n";
            $message .= "❌ **Not found in our store inventory.**\n";
            $message .= "This medicine is currently not available in our pharmacy.\n\n";
        }
        
        
        $message .= "---\n\n";
        if (!empty($drugInfo)) {
            $message .= "### 📋 Drug Information (FDA Database)\n\n";
            foreach ($drugInfo as $drug) {
                $rxRequired = !empty($drug['is_prescription']) ? "⚠️ **YES - Prescription Required**" : "✅ **No - Over-the-Counter (OTC)**";
                
                $message .= "**{$drug['drug_name']}**\n";
                if (!empty($drug['generic_name'])) {
                    $message .= "• Generic: {$drug['generic_name']}\n";
                }
                $message .= "• Prescription Needed: {$rxRequired}\n";
                if (!empty($drug['fda_status'])) {
                    $message .= "• FDA Status: {$drug['fda_status']}\n";
                }
                if (!empty($drug['manufacturer'])) {
                    $message .= "• Manufacturer: {$drug['manufacturer']}\n";
                }
                if (!empty($drug['description'])) {
                    $message .= "• Info: {$drug['description']}\n";
                }
                $message .= "\n";
            }
        } else {
            $message .= "### 📋 Drug Information\n\n";
            $message .= "ℹ️ No information found in our drug database.\n";
            $message .= "Please consult with our pharmacist for prescription requirements.\n\n";
        }
        
        
        $message .= "---\n\n";
        $message .= "⚠️ **Important:** Please bring your prescription to our pharmacy for proper verification. Contact us at {$this->store_info['contact']} for availability.";
        
        $type = !empty($storeProducts) ? 'success' : 'info';
        
        return [
            'status' => 'success',
            'type' => $type,
            'message' => $message,
            'ai_powered' => true,
            'image_analyzed' => true
        ];
    }
}

if (isset($_GET['f']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    
    $action = $_GET['f'] ?? $_POST['action'] ?? '';
    
    
    $action = preg_replace('/[^a-z_]/', '', strtolower($action));
    
    if ($action === 'chat' || $action === 'send_message') {
        $message = $_POST['message'] ?? $_GET['message'] ?? '';
        $topicHint = $_POST['topic_hint'] ?? '';
        
        $chatbot = new PharmacyChatbot();
        $response = $chatbot->processMessage($message, $topicHint);
        
        echo json_encode($response);
        exit;
    }
    
    
    if ($action === 'analyze_image') {
        $chatbot = new PharmacyChatbot();
        
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mimeType = $_FILES['image']['type'];
            
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode([
                    'status' => 'error',
                    'type' => 'warning',
                    'message' => '⚠️ Please upload a valid image file (JPEG, PNG, GIF, or WebP).'
                ]);
                exit;
            }
            
            
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                echo json_encode([
                    'status' => 'error',
                    'type' => 'warning',
                    'message' => '⚠️ Image too large. Please upload an image under 10MB.'
                ]);
                exit;
            }
            
            
            $imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
            $userMessage = $_POST['message'] ?? '';
            
            
            $response = $chatbot->analyzeImage($imageData, $mimeType, $userMessage);
            
            echo json_encode($response);
            exit;
        }
        
        
        if (isset($_POST['image_base64'])) {
            $base64Data = $_POST['image_base64'];
            $mimeType = $_POST['mime_type'] ?? 'image/jpeg';
            $userMessage = $_POST['message'] ?? '';
            
            
            if (strpos($base64Data, 'base64,') !== false) {
                $parts = explode('base64,', $base64Data);
                $base64Data = $parts[1];
                
                
                if (preg_match('/^data:([^;]+);/', $parts[0] . 'base64,', $matches)) {
                    $mimeType = $matches[1];
                }
            }
            
            
            $response = $chatbot->analyzeImage($base64Data, $mimeType, $userMessage);
            
            echo json_encode($response);
            exit;
        }
        
        echo json_encode([
            'status' => 'error',
            'type' => 'warning',
            'message' => '⚠️ No image provided. Please select an image to analyze.'
        ]);
        exit;
    }
    
    
    if ($action === 'search_products') {
        $query = $_POST['query'] ?? $_GET['query'] ?? '';
        $chatbot = new PharmacyChatbot();
        $results = $chatbot->searchProductsDirect($query);
        echo json_encode(['status' => 'success'] + $results);
        exit;
    }
    
    
    if ($action === 'stats') {
        $chatbot = new PharmacyChatbot();
        echo json_encode(['status' => 'success', 'stats' => $chatbot->getStats()]);
        exit;
    }
    
    
    if ($action === 'seed_cache') {
        $chatbot = new PharmacyChatbot();
        echo json_encode(['status' => 'success', 'message' => $chatbot->seedCache()]);
        exit;
    }
    
    
    if ($action === 'clear_cache') {
        $chatbot = new PharmacyChatbot();
        $deleted = $chatbot->clearCache();
        echo json_encode(['status' => 'success', 'message' => "Cleared {$deleted} cached responses"]);
        exit;
    }
}
?>
