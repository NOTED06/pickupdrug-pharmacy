<?php

class ChatbotCache {
    
    private $conn;
    private $cache_dir;
    private $rate_limit_file;
    
    
    const CACHE_EXPIRY_HOURS = 24;           
    const RATE_LIMIT_PER_MINUTE = 30;        
    const RATE_LIMIT_PER_HOUR = 1000;        
    const RATE_LIMIT_PER_DAY = 10000;        
    const DAILY_API_QUOTA = 1400;            
    const SIMILARITY_THRESHOLD = 0.92;       
    
    
    public function __construct($conn = null) {
        $this->conn = $conn;
        $this->cache_dir = __DIR__ . '/cache/';
        $this->rate_limit_file = __DIR__ . '/cache/rate_limits.json';
        
        
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        
        if ($this->conn) {
            $this->initializeDatabaseTables();
        }
    }
    
    
    private function initializeDatabaseTables() {
        
        $sql = "CREATE TABLE IF NOT EXISTS `chatbot_cache` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `question_hash` VARCHAR(64) NOT NULL,
            `question_normalized` VARCHAR(500) NOT NULL,
            `response` TEXT NOT NULL,
            `response_type` VARCHAR(20) DEFAULT 'info',
            `hit_count` INT(11) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `expires_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_question_hash` (`question_hash`),
            INDEX `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS `chatbot_rate_limits` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_identifier` VARCHAR(100) NOT NULL,
            `request_count_minute` INT(11) DEFAULT 0,
            `request_count_hour` INT(11) DEFAULT 0,
            `request_count_day` INT(11) DEFAULT 0,
            `last_request` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `minute_reset` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `hour_reset` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `day_reset` DATE DEFAULT (CURRENT_DATE),
            `is_blocked` TINYINT(1) DEFAULT 0,
            `block_reason` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_user` (`user_identifier`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS `chatbot_api_usage` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `usage_date` DATE NOT NULL,
            `api_calls` INT(11) DEFAULT 0,
            `cached_responses` INT(11) DEFAULT 0,
            `rate_limited` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_date` (`usage_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
    }
    
    
    public function checkRateLimit($user_id = null) {
        $identifier = $this->getUserIdentifier($user_id);
        
        if ($this->conn) {
            return $this->checkRateLimitDB($identifier);
        } else {
            return $this->checkRateLimitFile($identifier);
        }
    }
    
    
    private function checkRateLimitDB($identifier) {
        $now = new DateTime();
        
        
        $stmt = $this->conn->prepare("SELECT * FROM chatbot_rate_limits WHERE user_identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        
        if (!$record) {
            
            $stmt = $this->conn->prepare("INSERT INTO chatbot_rate_limits (user_identifier, request_count_minute, request_count_hour, request_count_day, minute_reset, hour_reset, day_reset) VALUES (?, 1, 1, 1, ?, ?, ?)");
            $minute_reset = $now->format('Y-m-d H:i:s');
            $hour_reset = $now->format('Y-m-d H:i:s');
            $day_reset = $now->format('Y-m-d');
            $stmt->bind_param("ssss", $identifier, $minute_reset, $hour_reset, $day_reset);
            $stmt->execute();
            
            return ['allowed' => true, 'message' => 'OK', 'wait_seconds' => 0];
        }
        
        
        if ($record['is_blocked']) {
            return [
                'allowed' => false, 
                'message' => $record['block_reason'] ?? 'You have been temporarily blocked due to excessive requests.',
                'wait_seconds' => 3600
            ];
        }
        
        
        $minute_reset = new DateTime($record['minute_reset']);
        $hour_reset = new DateTime($record['hour_reset']);
        $day_reset = new DateTime($record['day_reset']);
        
        $minute_diff = $now->getTimestamp() - $minute_reset->getTimestamp();
        $hour_diff = $now->getTimestamp() - $hour_reset->getTimestamp();
        $day_diff = $now->format('Y-m-d') !== $record['day_reset'];
        
        $count_minute = $record['request_count_minute'];
        $count_hour = $record['request_count_hour'];
        $count_day = $record['request_count_day'];
        
        
        if ($minute_diff >= 60) {
            $count_minute = 0;
            $minute_reset = $now;
        }
        
        
        if ($hour_diff >= 3600) {
            $count_hour = 0;
            $hour_reset = $now;
        }
        
        
        if ($day_diff) {
            $count_day = 0;
            $day_reset = $now;
        }
        
        
        if ($count_minute >= self::RATE_LIMIT_PER_MINUTE) {
            $wait = 60 - $minute_diff;
            $this->logRateLimited($identifier);
            return [
                'allowed' => false,
                'message' => "⏳ Please slow down! You can send another message in {$wait} seconds.",
                'wait_seconds' => max(1, $wait)
            ];
        }
        
        if ($count_hour >= self::RATE_LIMIT_PER_HOUR) {
            $wait = 3600 - $hour_diff;
            $wait_mins = ceil($wait / 60);
            $this->logRateLimited($identifier);
            return [
                'allowed' => false,
                'message' => "⏳ You've reached the hourly limit. Please try again in {$wait_mins} minutes.",
                'wait_seconds' => max(1, $wait)
            ];
        }
        
        if ($count_day >= self::RATE_LIMIT_PER_DAY) {
            $this->logRateLimited($identifier);
            return [
                'allowed' => false,
                'message' => "📅 You've reached your daily chat limit. Please come back tomorrow!",
                'wait_seconds' => 86400
            ];
        }
        
        
        $count_minute++;
        $count_hour++;
        $count_day++;
        
        $stmt = $this->conn->prepare("UPDATE chatbot_rate_limits SET 
            request_count_minute = ?, request_count_hour = ?, request_count_day = ?,
            minute_reset = ?, hour_reset = ?, day_reset = ?, last_request = NOW()
            WHERE user_identifier = ?");
        $minute_str = $minute_reset->format('Y-m-d H:i:s');
        $hour_str = $hour_reset->format('Y-m-d H:i:s');
        $day_str = $day_reset->format('Y-m-d');
        $stmt->bind_param("iiissss", $count_minute, $count_hour, $count_day, $minute_str, $hour_str, $day_str, $identifier);
        $stmt->execute();
        
        return ['allowed' => true, 'message' => 'OK', 'wait_seconds' => 0];
    }
    
    
    private function checkRateLimitFile($identifier) {
        $data = $this->loadRateLimitFile();
        $now = time();
        
        if (!isset($data[$identifier])) {
            $data[$identifier] = [
                'minute' => ['count' => 1, 'reset' => $now + 60],
                'hour' => ['count' => 1, 'reset' => $now + 3600],
                'day' => ['count' => 1, 'reset' => strtotime('tomorrow')]
            ];
            $this->saveRateLimitFile($data);
            return ['allowed' => true, 'message' => 'OK', 'wait_seconds' => 0];
        }
        
        $user = &$data[$identifier];
        
        
        if ($now >= $user['minute']['reset']) {
            $user['minute'] = ['count' => 0, 'reset' => $now + 60];
        }
        if ($now >= $user['hour']['reset']) {
            $user['hour'] = ['count' => 0, 'reset' => $now + 3600];
        }
        if ($now >= $user['day']['reset']) {
            $user['day'] = ['count' => 0, 'reset' => strtotime('tomorrow')];
        }
        
        
        if ($user['minute']['count'] >= self::RATE_LIMIT_PER_MINUTE) {
            $wait = $user['minute']['reset'] - $now;
            return ['allowed' => false, 'message' => "⏳ Please slow down! Try again in {$wait} seconds.", 'wait_seconds' => $wait];
        }
        
        if ($user['hour']['count'] >= self::RATE_LIMIT_PER_HOUR) {
            $wait = ceil(($user['hour']['reset'] - $now) / 60);
            return ['allowed' => false, 'message' => "⏳ Hourly limit reached. Try again in {$wait} minutes.", 'wait_seconds' => $wait * 60];
        }
        
        if ($user['day']['count'] >= self::RATE_LIMIT_PER_DAY) {
            return ['allowed' => false, 'message' => "📅 Daily limit reached. Come back tomorrow!", 'wait_seconds' => 86400];
        }
        
        
        $user['minute']['count']++;
        $user['hour']['count']++;
        $user['day']['count']++;
        $this->saveRateLimitFile($data);
        
        return ['allowed' => true, 'message' => 'OK', 'wait_seconds' => 0];
    }
    
    
    public function getCachedResponse($question) {
        $normalized = $this->normalizeQuestion($question);
        $hash = $this->hashQuestion($normalized);
        
        if ($this->conn) {
            return $this->getCachedResponseDB($hash, $normalized);
        } else {
            return $this->getCachedResponseFile($hash);
        }
    }
    
    
    private function getCachedResponseDB($hash, $normalized) {
        
        $stmt = $this->conn->prepare("SELECT * FROM chatbot_cache WHERE question_hash = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            
            $this->conn->query("UPDATE chatbot_cache SET hit_count = hit_count + 1 WHERE id = " . $row['id']);
            $this->logCacheHit();
            
            return [
                'message' => $row['response'],
                'type' => $row['response_type'],
                'cached' => true,
                'cache_hits' => $row['hit_count']
            ];
        }
        
        
        $stmt = $this->conn->prepare("SELECT * FROM chatbot_cache WHERE expires_at > NOW() ORDER BY hit_count DESC LIMIT 100");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $similarity = $this->calculateSimilarity($normalized, $row['question_normalized']);
            if ($similarity >= self::SIMILARITY_THRESHOLD) {
                $this->conn->query("UPDATE chatbot_cache SET hit_count = hit_count + 1 WHERE id = " . $row['id']);
                $this->logCacheHit();
                
                return [
                    'message' => $row['response'],
                    'type' => $row['response_type'],
                    'cached' => true,
                    'similarity' => $similarity
                ];
            }
        }
        
        return null;
    }
    
    
    private function getCachedResponseFile($hash) {
        $cache_file = $this->cache_dir . 'responses.json';
        if (!file_exists($cache_file)) return null;
        
        $cache = json_decode(file_get_contents($cache_file), true);
        if (!$cache) return null;
        
        if (isset($cache[$hash]) && $cache[$hash]['expires'] > time()) {
            return [
                'message' => $cache[$hash]['response'],
                'type' => $cache[$hash]['type'],
                'cached' => true
            ];
        }
        
        return null;
    }
    
    
    public function cacheResponse($question, $response, $type = 'info') {
        $normalized = $this->normalizeQuestion($question);
        $hash = $this->hashQuestion($normalized);
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::CACHE_EXPIRY_HOURS . ' hours'));
        
        if ($this->conn) {
            $stmt = $this->conn->prepare("INSERT INTO chatbot_cache (question_hash, question_normalized, response, response_type, expires_at) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE response = VALUES(response), response_type = VALUES(response_type), expires_at = VALUES(expires_at), hit_count = hit_count + 1");
            $stmt->bind_param("sssss", $hash, $normalized, $response, $type, $expires);
            $stmt->execute();
        } else {
            $cache_file = $this->cache_dir . 'responses.json';
            $cache = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
            $cache[$hash] = [
                'question' => $normalized,
                'response' => $response,
                'type' => $type,
                'expires' => strtotime('+' . self::CACHE_EXPIRY_HOURS . ' hours')
            ];
            file_put_contents($cache_file, json_encode($cache, JSON_PRETTY_PRINT));
        }
    }
    
    
    public function checkDailyQuota() {
        if (!$this->conn) return true;
        
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT api_calls FROM chatbot_api_usage WHERE usage_date = ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['api_calls'] < self::DAILY_API_QUOTA;
        }
        
        return true;
    }
    
    
    public function logAPICall() {
        if (!$this->conn) return;
        
        $today = date('Y-m-d');
        $this->conn->query("INSERT INTO chatbot_api_usage (usage_date, api_calls) VALUES ('$today', 1) 
            ON DUPLICATE KEY UPDATE api_calls = api_calls + 1");
    }
    
    
    private function logCacheHit() {
        if (!$this->conn) return;
        
        $today = date('Y-m-d');
        $this->conn->query("INSERT INTO chatbot_api_usage (usage_date, cached_responses) VALUES ('$today', 1) 
            ON DUPLICATE KEY UPDATE cached_responses = cached_responses + 1");
    }
    
    
    private function logRateLimited($identifier) {
        if (!$this->conn) return;
        
        $today = date('Y-m-d');
        $this->conn->query("INSERT INTO chatbot_api_usage (usage_date, rate_limited) VALUES ('$today', 1) 
            ON DUPLICATE KEY UPDATE rate_limited = rate_limited + 1");
    }
    
    
    public function getUsageStats() {
        if (!$this->conn) {
            return ['api_calls' => 'N/A', 'cached' => 'N/A', 'rate_limited' => 'N/A'];
        }
        
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT * FROM chatbot_api_usage WHERE usage_date = ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $total = $row['api_calls'] + $row['cached_responses'];
            $cache_rate = $total > 0 ? round(($row['cached_responses'] / $total) * 100, 1) : 0;
            
            return [
                'api_calls' => $row['api_calls'],
                'cached_responses' => $row['cached_responses'],
                'rate_limited' => $row['rate_limited'],
                'cache_hit_rate' => $cache_rate . '%',
                'quota_remaining' => self::DAILY_API_QUOTA - $row['api_calls']
            ];
        }
        
        return [
            'api_calls' => 0,
            'cached_responses' => 0,
            'rate_limited' => 0,
            'cache_hit_rate' => '0%',
            'quota_remaining' => self::DAILY_API_QUOTA
        ];
    }
    
    
    private function normalizeQuestion($question) {
        
        $question = strip_tags($question);
        $question = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
        
        $q = strtolower(trim($question));
        
        
        $q = preg_replace('/\s+/', ' ', $q);
        
        
        $q = preg_replace('/[^\w\s]/', '', $q);
        
        
        $fillers = ['please', 'can you', 'could you', 'would you', 'i want to', 'i need to', 'tell me', 'what is', 'what are', 'how do i', 'hey', 'hi', 'hello'];
        foreach ($fillers as $filler) {
            $q = str_replace($filler, '', $q);
        }
        
        
        $q = trim(preg_replace('/\s+/', ' ', $q));
        
        return $q;
    }
    
    
    private function hashQuestion($normalized) {
        return hash('sha256', $normalized);
    }
    
    
    private function calculateSimilarity($str1, $str2) {
        
        $symptomWords = ['fever', 'headache', 'cough', 'cold', 'flu', 'pain', 'ache', 'sore', 'throat', 
                         'stomach', 'diarrhea', 'vomiting', 'nausea', 'allergy', 'rash', 'itch', 
                         'dizzy', 'fatigue', 'tired', 'insomnia', 'sleep', 'anxiety', 'stress',
                         'toothache', 'backache', 'muscle', 'joint', 'arthritis', 'hypertension',
                         'diabetes', 'asthma', 'infection', 'wound', 'cut', 'burn', 'bite',
                         'runny', 'nose', 'congestion', 'sneezing', 'eye', 'ear', 'skin'];
        
        
        $symptoms1 = array_filter($symptomWords, fn($w) => stripos($str1, $w) !== false);
        $symptoms2 = array_filter($symptomWords, fn($w) => stripos($str2, $w) !== false);
        
        
        if (!empty($symptoms1) || !empty($symptoms2)) {
            $commonSymptoms = array_intersect($symptoms1, $symptoms2);
            if (empty($commonSymptoms)) {
                return 0; 
            }
        }
        
        
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
    
    
    private function getUserIdentifier($user_id = null) {
        if ($user_id) return 'user_' . $user_id;
        
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['userdata']['id'])) {
            return 'customer_' . $_SESSION['userdata']['id'];
        }
        
        if (session_status() === PHP_SESSION_ACTIVE && session_id()) {
            return 'session_' . session_id();
        }
        
        return 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
    
    
    private function loadRateLimitFile() {
        if (!file_exists($this->rate_limit_file)) return [];
        $data = json_decode(file_get_contents($this->rate_limit_file), true);
        return $data ?: [];
    }
    
    
    private function saveRateLimitFile($data) {
        file_put_contents($this->rate_limit_file, json_encode($data));
    }
    
    
    public function cleanExpiredCache() {
        if ($this->conn) {
            $this->conn->query("DELETE FROM chatbot_cache WHERE expires_at < NOW()");
        }
        
        
        $cache_file = $this->cache_dir . 'responses.json';
        if (file_exists($cache_file)) {
            $cache = json_decode(file_get_contents($cache_file), true);
            if ($cache) {
                $now = time();
                $cache = array_filter($cache, function($item) use ($now) {
                    return $item['expires'] > $now;
                });
                file_put_contents($cache_file, json_encode($cache, JSON_PRETTY_PRINT));
            }
        }
    }
    
    
    public function clearAllCache() {
        $deleted = 0;
        
        if ($this->conn) {
            $this->conn->query("DELETE FROM chatbot_cache");
            $deleted = $this->conn->affected_rows;
        }
        
        
        $cache_file = $this->cache_dir . 'responses.json';
        if (file_exists($cache_file)) {
            file_put_contents($cache_file, '{}');
        }
        
        return $deleted;
    }
    
    
    public function seedCommonQuestions() {
        $common_qa = [
            
            'what are your store hours' => [
                'response' => "🏪 **Store Hours**\n\nWe're open:\n• Monday-Saturday: 8:00 AM - 9:00 PM\n• Sunday: 9:00 AM - 6:00 PM\n\n📍 Visit us at Toril, Davao City!",
                'type' => 'info'
            ],
            'where is your pharmacy located' => [
                'response' => "📍 **Our Location**\n\nWe're located at **Toril, Davao City**\n\n🕐 Open daily from 8AM-9PM (Mon-Sat)\n📞 Call us for directions!",
                'type' => 'info'
            ],
            'what is your contact number' => [
                'response' => "📞 **Contact Us**\n\n• Phone: 456-987-1231\n• Visit: Toril, Davao City\n\nWe're happy to help with any pharmacy questions!",
                'type' => 'info'
            ],
            
            
            'do you have paracetamol' => [
                'response' => "💊 **Paracetamol**\n\nYes! Paracetamol is commonly available.\n\nCommon brands:\n• Biogesic\n• Panadol\n• Tempra\n\n💰 Prices vary by brand.\n\n⚠️ *Please consult our pharmacist for proper dosage.*",
                'type' => 'success'
            ],
            'what medicine for headache' => [
                'response' => "💊 **For Headache Relief**\n\nCommon OTC options:\n• **Paracetamol** (Biogesic) - ₱5-10\n• **Ibuprofen** (Advil) - ₱8-15\n• **Mefenamic Acid** (Dolfenal) - ₱8-12\n\n⚠️ *Consult our pharmacist if headaches persist or are severe.*",
                'type' => 'info'
            ],
            'what medicine for fever' => [
                'response' => "🌡️ **For Fever**\n\nRecommended:\n• **Paracetamol** - Safe for most people\n• **Ibuprofen** - Also reduces inflammation\n\n⚠️ *If fever persists more than 3 days, please see a doctor.*\n\n👶 For children, consult our pharmacist for proper dosing.",
                'type' => 'info'
            ],
            
            
            'is aspirin safe' => [
                'response' => "💊 **Aspirin Safety**\n\n✅ Generally safe for adults when used as directed.\n\n⚠️ **Cautions:**\n• Not for children under 12\n• Avoid if you have stomach ulcers\n• Can interact with blood thinners\n\n*Always consult your doctor or pharmacist before taking aspirin regularly.*",
                'type' => 'warning'
            ],
            'need prescription for antibiotics' => [
                'response' => "💊 **Antibiotics Require Prescription**\n\n✅ Yes, all antibiotics in the Philippines require a valid prescription from a licensed physician.\n\n**Why?**\n• Prevent antibiotic resistance\n• Ensure proper treatment\n• Avoid side effects\n\n📋 Please bring your prescription to our pharmacy.",
                'type' => 'warning'
            ]
        ];
        
        foreach ($common_qa as $question => $answer) {
            $this->cacheResponse($question, $answer['response'], $answer['type']);
        }
        
        return count($common_qa) . ' common Q&A cached successfully!';
    }
}
