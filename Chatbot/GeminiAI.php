<?php

class GeminiAI {
    
    
    private $api_key;
    
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';
    
    
    private $system_context;
    
    
    private $conn;
    
    
    private $store_info;
    
    
    public function __construct($conn = null, $store_info = []) {
        $this->conn = $conn;
        $this->store_info = $store_info;
        
        
        $this->api_key = $this->loadApiKey();
        
        $this->initializeContext();
    }
    
    
    private function loadApiKey() {
        
        if ($this->conn) {
            $result = $this->conn->query("SELECT meta_value FROM system_info WHERE meta_field = 'gemini_api_key' LIMIT 1");
            if ($result && $row = $result->fetch_assoc()) {
                if (!empty($row['meta_value'])) {
                    return $row['meta_value'];
                }
            }
        }
        
        
        $env_key = getenv('GEMINI_API_KEY');
        if ($env_key) {
            return $env_key;
        }
        
        
        return '';
    }
    
    
    
    private function getSymptomGuide() {
        return "

=== COMPREHENSIVE MILD SYMPTOM GUIDE ===
Provide safe, OTC recommendations for MILD symptoms only. This guidance does NOT replace consultation with a licensed healthcare professional.

### SYMPTOM: HEADACHE 🤕
**Possible Causes:** Tension, dehydration, minor stress, eye strain
**OTC Remedies:**
- Adults: Paracetamol 500mg or Ibuprofen 200-400mg
- Children (6-12 yrs): Paracetamol syrup (follow package dosing by weight)
- Children under 6: Consult doctor first
**Supportive Care:** Rest in dark quiet room, cold compress on forehead, stay hydrated
**Diet:** Avoid caffeine, alcohol; eat regular meals
**Hydration:** Drink 8 glasses of water daily
**🚨 RED FLAGS - See Doctor If:** Severe/sudden headache, fever with stiff neck, vision changes, head injury, worst headache of life

### SYMPTOM: MILD FEVER (37.5-38°C) 🌡️
**Possible Causes:** Viral infection, mild flu, body fighting infection
**OTC Remedies:**
- Adults: Paracetamol 500-1000mg every 4-6 hours (max 4g/day)
- Children: Paracetamol syrup based on weight (10-15mg/kg per dose)
- Infants under 3 months: DO NOT self-medicate, see doctor immediately
**Supportive Care:** Light clothing, tepid sponge bath, rest
**Diet:** Light meals, soups, fruits rich in Vitamin C
**Hydration:** Increase fluids - water, juice, electrolyte drinks
**🚨 RED FLAGS - See Doctor If:** Fever >38.5°C, fever >3 days, difficulty breathing, rash, confusion, infant with any fever

### SYMPTOM: RUNNY NOSE / COLDS 🤧
**Possible Causes:** Common cold, allergies, weather change
**OTC Remedies:**
- Adults: Antihistamines (Cetirizine, Loratadine), decongestants, saline spray
- Children (6-12 yrs): Children's antihistamine syrup, saline drops
- Children under 6: Saline drops only, no decongestants
**Supportive Care:** Steam inhalation, elevate head while sleeping
**Diet:** Warm soups, ginger tea, avoid dairy if mucus increases
**Hydration:** Warm fluids help thin mucus
**🚨 RED FLAGS - See Doctor If:** Symptoms >10 days, thick green/yellow discharge, facial pain, fever

### SYMPTOM: MILD COUGH 😷
**Possible Causes:** Dry throat, postnasal drip, minor cold
**OTC Remedies:**
- Adults: Lozenges, honey with warm water, cough syrup (dextromethorphan for dry cough)
- Children (1-12 yrs): Honey (1 tsp for 1-5 yrs, 2 tsp for 6-12 yrs), NOT for infants <1 year
- Infants under 1 year: NO honey, NO OTC cough medicine - consult doctor
**Supportive Care:** Humidifier, avoid smoke/dust, gargle warm salt water
**Diet:** Warm liquids, avoid cold drinks, citrus fruits
**Hydration:** Stay well hydrated to thin mucus
**🚨 RED FLAGS - See Doctor If:** Cough >2 weeks, blood in mucus, difficulty breathing, wheezing, chest pain

### SYMPTOM: SORE THROAT 😣
**Possible Causes:** Viral infection, dry air, minor irritation
**OTC Remedies:**
- Adults: Lozenges, throat spray, Paracetamol for pain
- Children (6-12 yrs): Children's lozenges, warm salt water gargle
- Children under 6: Warm fluids only, no lozenges (choking hazard)
**Supportive Care:** Gargle warm salt water (1/4 tsp salt in 1 cup water), humidifier
**Diet:** Soft warm foods, avoid spicy/acidic foods
**Hydration:** Warm water, herbal tea with honey
**🚨 RED FLAGS - See Doctor If:** Severe pain, difficulty swallowing/breathing, fever >38°C, white patches on throat, swollen lymph nodes

### SYMPTOM: MILD NAUSEA 🤢
**Possible Causes:** Indigestion, motion sickness, mild food reaction
**OTC Remedies:**
- Adults: Antacids, Ginger capsules, Meclizine (for motion sickness)
- Children: Ginger tea (mild), avoid OTC anti-nausea without doctor advice
- Pregnant women: Ginger, Vitamin B6 - consult OB for other medications
**Supportive Care:** Rest, fresh air, avoid strong smells
**Diet:** BRAT diet (Banana, Rice, Applesauce, Toast), small frequent meals
**Hydration:** Small sips of clear fluids, ginger ale
**🚨 RED FLAGS - See Doctor If:** Vomiting blood, severe abdominal pain, signs of dehydration, persistent >24 hours

### SYMPTOM: MILD DIARRHEA 💧
**Possible Causes:** Food change, mild infection, stress
**OTC Remedies:**
- Adults: ORS (Oral Rehydration Salts), Loperamide (if no fever/blood)
- Children: ORS only - do NOT give Loperamide to children
- Infants: ORS, continue breastfeeding, see doctor early
**Supportive Care:** Rest, frequent small sips of fluids
**Diet:** BRAT diet, avoid dairy/fatty/spicy foods
**Hydration:** ORS solution, clear broths, coconut water - CRITICAL to prevent dehydration
**🚨 RED FLAGS - See Doctor If:** Blood in stool, severe cramps, fever >38°C, signs of dehydration (dry mouth, no tears, dark urine), >2 days duration

### SYMPTOM: MILD MUSCLE ACHE 💪
**Possible Causes:** Overexertion, minor strain, sleeping position
**OTC Remedies:**
- Adults: Paracetamol, Ibuprofen, topical pain relief (menthol rubs)
- Children: Paracetamol only, gentle massage
- Elderly: Paracetamol preferred (easier on stomach)
**Supportive Care:** RICE method (Rest, Ice, Compression, Elevation), warm compress after 48 hours
**Diet:** Anti-inflammatory foods (fish, nuts, leafy greens)
**Hydration:** Adequate water prevents muscle cramps
**🚨 RED FLAGS - See Doctor If:** Severe pain, swelling, inability to move, pain after injury, muscle weakness, dark urine

### SYMPTOM: FATIGUE / TIREDNESS 😴
**Possible Causes:** Lack of sleep, dehydration, stress, recovering from illness
**OTC Remedies:**
- Generally: Multivitamins, Iron supplements (if diet-deficient), Vitamin B complex
- Note: Fatigue often doesn't need medication - lifestyle changes first
**Supportive Care:** Regular sleep schedule (7-9 hours), light exercise, stress management
**Diet:** Balanced meals, reduce sugar, increase protein and complex carbs
**Hydration:** Dehydration causes fatigue - drink plenty of water
**🚨 RED FLAGS - See Doctor If:** Fatigue >2 weeks, unexplained weight loss, fever, shortness of breath, chest pain

=== GENERAL DOSING REMINDERS ===
- ALWAYS check product labels for exact dosing
- Weight-based dosing is more accurate for children than age
- When in doubt, ASK THE PHARMACIST
- Never exceed maximum daily doses
- Space doses properly (usually 4-6 hours apart)

=== HYDRATION GUIDE ===
- Adults: 8-10 glasses (2-2.5 liters) daily
- Children: Based on weight - roughly 1-1.5 liters for school-age
- During illness: Increase by 50%
- Signs of dehydration: Dark urine, dry lips, dizziness, headache

SYMPTOM RESPONSE RULES:
- For mild symptoms: Suggest OTC remedies with age-appropriate dosing
- ALWAYS include supportive care (rest, diet, hydration)
- ALWAYS list RED FLAG signs that need immediate medical attention
- NEVER diagnose - only suggest general remedies
- If symptoms are SEVERE, tell them to see a doctor IMMEDIATELY
- For children under 2 and elderly: Be extra cautious, recommend doctor consultation
- For pregnant/breastfeeding: Always recommend consulting doctor first

RESPONSE STYLE:
- Be warm and professional
- Use bullet points for lists
- Bold important information
- Include relevant emoji for visual appeal
- Always end with safety disclaimer

=== CONVERSATIONAL CONTINUITY RULES ===
You MUST maintain conversational continuity:
1. Use the chat history to interpret follow-up questions
2. Do NOT ask the user to repeat product names if they are clearly referring to products you just mentioned
3. If the user says 'describe it', 'ano ito', 'details', 'yung sinabi mo', 'yung una', 'yung pangalawa', interpret as referring to the most recently mentioned product
4. Store context of recommended products mentally as 'last_recommended_products' and 'active_product'
5. If user asks a follow-up without naming a product (e.g., 'ano side effects?', 'description?', 'paano gamitin?'), answer using the active/last mentioned product
6. Only ask 'Which product?' if you recommended multiple products AND the question cannot be answered for all of them at once

=== LANGUAGE BEHAVIOR ===
- If the user speaks Filipino/Tagalog, respond in Filipino with simple, clear wording
- If the user mixes English/Tagalog (Taglish), match their language mix
- Bisaya/Cebuano is also supported - respond in the same language they use

MANDATORY DISCLAIMER (Include in EVERY symptom response):
'⚠️ **Important:** This is general guidance only and does NOT replace consultation with a licensed healthcare professional. If symptoms persist, worsen, or you experience any red flag signs, please seek medical attention immediately.'";
    }

    
    private function isSymptomRelated($message) {
        $symptom_keywords = ['headache', 'fever', 'cough', 'cold', 'sore throat', 'nausea',
            'diarrhea', 'muscle', 'fatigue', 'tired', 'sick', 'pain', 'ache',
            'sakit', 'ubo', 'hilanat', 'sip-on', 'labad', 'init', 'bugnaw',
            'runny nose', 'vomit', 'stomach', 'flu', 'allergy'];
        $msg = strtolower($message);
        foreach ($symptom_keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    private function initializeContext() {
        $store_name = $this->store_info['name'] ?? 'PharmaCare';
        $store_address = $this->store_info['address'] ?? 'Toril, Davao City';
        $store_hours = $this->store_info['hours'] ?? '8:00 AM - 9:00 PM (Mon-Sat)';
        $store_contact = $this->store_info['contact'] ?? 'N/A';
        
        $this->system_context = "You are a helpful pharmacy assistant chatbot for '{$store_name}', a licensed pharmacy located at {$store_address}.

STORE INFO: {$store_name} | {$store_address} | Hours: {$store_hours} | Contact: {$store_contact}

RULES:
1. Pharmacy-related questions ONLY. Redirect off-topic politely.
2. Check medicine availability, FDA status, pricing from provided data.
3. CRITICAL: Only say available if stock > 0. If stock = 0, say OUT OF STOCK.
4. NEVER give specific dosage advice - recommend consulting a doctor/pharmacist.
5. Support English, Bisaya/Cebuano, and Filipino/Tagalog - respond in user's language.
6. For emergencies/pregnant/children/chronic illness: recommend professional consultation.
7. Keep responses SHORT and concise (max 3-4 short paragraphs). Use bullet points.
8. Use emojis sparingly: 💊 ✅ ⚠️ ❌
9. Always include disclaimer for medical advice.
10. Use conversation history to understand follow-up questions.

STOCK RULES: stock > 0 = '✅ Available (X units)' | stock = 0 = '❌ Out of stock'

RESPONSE STYLE: Be concise. No long paragraphs. Use bullet points. Max 150 words per response.";
    }
    
    
    public function generateResponse($user_message, $context = []) {
        try {
            
            $prompt = $this->buildPrompt($user_message, $context);
            
            
            $response = $this->callGeminiAPI($prompt);
            
            if ($response['success']) {
                return [
                    'status' => 'success',
                    'message' => $response['text'],
                    'type' => $this->determineResponseType($response['text']),
                    'ai_powered' => true
                ];
            } else {
                
                $friendly_error = "I'm having trouble connecting right now. Please try again or contact our pharmacy directly at " . ($this->store_info['contact'] ?? 'our store') . ".";
                
                
                if (isset($response['error']) && strpos($response['error'], 'Invalid API Key') !== false) {
                    $friendly_error = $response['error'];
                }
                
                return [
                    'status' => 'error',
                    'message' => $friendly_error,
                    'type' => 'warning',
                    'ai_powered' => false,
                    'error' => $response['error'] ?? 'Unknown Error'
                ];
            }
        } catch (Exception $e) {
            error_log("GeminiAI Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => "Sorry, I encountered an error. Please try again.",
                'type' => 'danger',
                'ai_powered' => false
            ];
        }
    }
    
    
    private function buildPrompt($user_message, $context = []) {
        $prompt = $this->system_context . "\n\n";
        
        
        if ($this->isSymptomRelated($user_message)) {
            $prompt .= $this->getSymptomGuide() . "\n\n";
        }
        
        
        if (!empty($context['medicines'])) {
            $meds = array_slice($context['medicines'], 0, 8);
            $prompt .= "STORE INVENTORY:\n";
            foreach ($meds as $med) {
                $stock = isset($med['total_stock']) ? (int)$med['total_stock'] : 0;
                $status = $stock > 0 ? "IN STOCK ({$stock})" : "OUT OF STOCK";
                $prompt .= "- {$med['name']} ({$med['brand']}): ₱{$med['price']} - {$status}\n";
            }
            $prompt .= "Only items above with stock > 0 are available.\n\n";
        }
        
        
        if (!empty($context['fda_data'])) {
            $fda = array_slice($context['fda_data'], 0, 3);
            $prompt .= "FDA REFERENCE (not actual stock):\n";
            foreach ($fda as $drug) {
                $rx = $drug['is_prescription'] ? 'Rx' : 'OTC';
                $prompt .= "- {$drug['drug_name']} ({$drug['generic_name']}): {$drug['fda_status']}, {$rx}\n";
            }
            $prompt .= "\n";
        }
        
        
        if (!empty($context['user_orders'])) {
            $prompt .= "CUSTOMER RECENT ORDERS:\n";
            foreach ($context['user_orders'] as $order) {
                if (isset($order['error'])) {
                    $prompt .= "- " . $order['message'] . "\n";
                    break;
                }
                $prompt .= "- Order #{$order['order_code']}: {$order['status']}, Total: {$order['total']}, Pickup: {$order['pickup_time']}\n";
            }
            $prompt .= "\n";
        }
        
        
        if (!empty($context['conversation_history'])) {
            $history = array_slice($context['conversation_history'], -3);
            $prompt .= "RECENT CHAT:\n";
            foreach ($history as $entry) {
                $role = $entry['role'] === 'user' ? 'Customer' : 'Assistant';
                $prompt .= "{$role}: " . substr($entry['message'], 0, 200) . "\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "USER: {$user_message}\n";
        $prompt .= "Reply concisely (max 150 words). Use bullet points. If medicine only in FDA but not inventory, say we don't carry it.";
        
        return $prompt;
    }
    
    
    private function callGeminiAPI($prompt, $retries = 1) {
        $url = $this->api_url . '?key=' . $this->api_key;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false 
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("Gemini cURL Error: " . $curl_error);
            
            if ($retries > 0) {
                usleep(200000); 
                return $this->callGeminiAPI($prompt, $retries - 1);
            }
            return ['success' => false, 'error' => $curl_error];
        }
        
        $result = json_decode($response, true);
        
        if ($http_code !== 200) {
            $error_message = $result['error']['message'] ?? 'API Error';
            error_log("Gemini API Error (HTTP {$http_code}): " . $error_message);
            
            
            if ($http_code === 400 || $http_code === 403) {
                if (strpos(strtolower($error_message), 'api key') !== false || strpos(strtolower($error_message), 'key invalid') !== false || $http_code === 400) {
                     return [
                         'success' => false,
                         'error' => "Invalid API Key. Please ensure your Gemini API Key starts with 'AIzaSy' and is configured correctly in settings."
                     ];
                }
            }
            
            
            if (($http_code === 429 || $http_code >= 500) && $retries > 0) {
                usleep(300000); 
                return $this->callGeminiAPI($prompt, $retries - 1);
            }
            
            return [
                'success' => false, 
                'error' => $error_message,
                'http_code' => $http_code
            ];
        }
        
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'text' => $result['candidates'][0]['content']['parts'][0]['text']
            ];
        }
        
        return ['success' => false, 'error' => 'No response generated'];
    }
    
    
    private function determineResponseType($text) {
        $text_lower = strtolower($text);
        
        
        if (preg_match('/emergency|urgent|immediately|hospital|911|danger/i', $text_lower)) {
            return 'danger';
        }
        
        
        if (preg_match('/warning|caution|consult|doctor|prescription required|⚠️/i', $text_lower)) {
            return 'warning';
        }
        
        
        if (preg_match('/available|found|yes|✅|in stock/i', $text_lower)) {
            return 'success';
        }
        
        
        return 'info';
    }
    
    
    public function searchMedicines($query) {
        if (!$this->conn) return [];
        
        $search = '%' . $this->conn->real_escape_string($query) . '%';
        $sql = "SELECT id, name, brand, description, price, dose 
                FROM product_list 
                WHERE (name LIKE ? OR brand LIKE ? OR description LIKE ?) 
                AND delete_flag = 0 
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $medicines = [];
        while ($row = $result->fetch_assoc()) {
            $medicines[] = $row;
        }
        
        return $medicines;
    }
    
    
    public function searchFDADatabase($query) {
        if (!$this->conn) return [];
        
        $search = '%' . $this->conn->real_escape_string($query) . '%';
        $sql = "SELECT drug_name, generic_name, brand_name, manufacturer, 
                       fda_status, is_prescription, dosage_form, strength, category, description
                FROM drug_database 
                WHERE drug_name LIKE ? 
                   OR generic_name LIKE ? 
                   OR brand_name LIKE ?
                LIMIT 5";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $drugs = [];
        while ($row = $result->fetch_assoc()) {
            $drugs[] = $row;
        }
        
        return $drugs;
    }
    
    
    public function getContextForMessage($message) {
        $context = [];
        
        
        $words = preg_split('/\s+/', $message);
        $potential_medicines = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array(strtolower($word), [
                'what', 'where', 'when', 'how', 'have', 'does', 'available',
                'stock', 'price', 'medicine', 'drug', 'tablet', 'capsule',
                'naa', 'pila', 'asa', 'unsa', 'kanus-a'
            ]);
        });
        
        
        foreach ($potential_medicines as $term) {
            $medicines = $this->searchMedicines($term);
            if (!empty($medicines)) {
                $context['medicines'] = array_merge($context['medicines'] ?? [], $medicines);
            }
            
            $fda_data = $this->searchFDADatabase($term);
            if (!empty($fda_data)) {
                $context['fda_data'] = array_merge($context['fda_data'] ?? [], $fda_data);
            }
        }
        
        
        if (!empty($context['medicines'])) {
            $context['medicines'] = array_unique($context['medicines'], SORT_REGULAR);
        }
        if (!empty($context['fda_data'])) {
            $context['fda_data'] = array_unique($context['fda_data'], SORT_REGULAR);
        }
        
        return $context;
    }
    
    
    public function testConnection() {
        $response = $this->callGeminiAPI("Say 'Hello! Gemini AI is working!' in exactly those words.");
        return $response;
    }
    
    
    public function analyzeImage($imageData, $mimeType = 'image/jpeg', $userMessage = '') {
        try {
            
            $prompt = $this->buildImagePrompt($userMessage);
            
            
            $response = $this->callGeminiVisionAPI($prompt, $imageData, $mimeType);
            
            if ($response['success']) {
                return [
                    'status' => 'success',
                    'message' => $response['text'],
                    'type' => $this->determineResponseType($response['text']),
                    'ai_powered' => true,
                    'image_analyzed' => true
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => "I couldn't analyze the image. Please try again or describe what you need help with.",
                    'type' => 'warning',
                    'ai_powered' => false,
                    'error' => $response['error']
                ];
            }
        } catch (Exception $e) {
            error_log("GeminiAI Image Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => "Sorry, I encountered an error analyzing the image.",
                'type' => 'danger',
                'ai_powered' => false
            ];
        }
    }
    
    
    public function validatePrescription($imageData, $mimeType = 'image/jpeg') {
        try {
            
            $prompt = "You are a MEDICAL prescription validator for a PHARMACY. Analyze this image and determine if it is a REAL MEDICAL PRESCRIPTION for ACTUAL MEDICATIONS/DRUGS.

IMPORTANT: This is for a PHARMACY that dispenses REAL MEDICATIONS. We need prescriptions for ACTUAL DRUGS like antibiotics, pain medications, maintenance drugs, etc.

IMMEDIATELY REJECT if the image is:
- Selfie or photo of a person
- Food, meals, or drinks  
- Animals or pets
- Nature, scenery, buildings
- Screenshot of social media/chat
- Medicine bottle or box ALONE (no prescription paper)
- Random photo not related to medical documents
- Blurry or completely unreadable image
- Diagrams, flowcharts, or system designs
- NOVELTY/JOKE prescriptions (e.g. 'Prescription for Happiness', 'Prescription for Love', wellness advice, motivational content)
- Prescriptions that list NON-MEDICATIONS like 'spread love', 'watch sunset', 'call a friend', 'take deep breaths', 'go for a walk', etc.
- Any document that does NOT contain ACTUAL PHARMACEUTICAL DRUG NAMES

A VALID prescription MUST have:
1. Doctor's name, signature, license number, clinic/hospital info, or Rx symbol
2. Patient name or details
3. REAL MEDICATION/DRUG NAMES (pharmaceutical names like Amoxicillin, Paracetamol, Metformin, etc.) with dosages (mg, ml, tablets)

NOTE: Do NOT reject based on prescription date or expiration date. Many valid medical prescriptions do not specify an expiry date or have handwritten dates. As long as it is an authentic medical prescription for real medications, APPROVE it.

RESPONSE FORMAT (use exactly these lines):
STATUS: APPROVED or REJECTED
DETAILS: [doctor, patient, medicines, and reason]

Only approve prescriptions with REAL PHARMACEUTICAL DRUG NAMES. Reject anything else.";
            
            $response = $this->callGeminiVisionAPI($prompt, $imageData, $mimeType);
            
            if ($response['success']) {
                $text = $response['text'];
                
                
                error_log('Gemini Prescription Check Response: ' . $text);
                
                preg_match('/STATUS:\s*(APPROVED|REJECTED)/i', $text, $statusMatch);
                preg_match('/DETAILS:\s*(.*)/is', $text, $detailsMatch);

                $statusValue = strtoupper($statusMatch[1] ?? '');
                $details = trim($detailsMatch[1] ?? '');

                
                if ($statusValue === 'APPROVED' || stripos($text, 'STATUS: APPROVED') !== false) {
                    return [
                        'status' => 'success',
                        'valid' => true,
                        'confidence' => 85,
                        'message' => 'Valid prescription detected',
                        'details' => $details
                    ];
                }
                
                
                if ($statusValue === 'REJECTED' || stripos($text, 'STATUS: REJECTED') !== false) {
                    $reason = $details ?: 'This does not appear to be a valid medical prescription.';
                    
                    return [
                        'status' => 'success',
                        'valid' => false,
                        'message' => 'Invalid prescription image',
                        'reason' => $reason
                    ];
                }
                
                
                $rejection_phrases = [
                    'sorry, i can only help',
                    'not a prescription',
                    'not a medical',
                    'not related to medicine',
                    'cannot help with this',
                    'this is a photo of',
                    'this appears to be a',
                    'this is not',
                    'please upload',
                    'selfie',
                    'food',
                    'animal',
                    'landscape',
                    'screenshot',
                    'cannot identify',
                    'unable to'
                ];
                
                $text_lower = strtolower($text);
                foreach ($rejection_phrases as $phrase) {
                    if (strpos($text_lower, $phrase) !== false) {
                        error_log('Prescription rejected due to phrase: ' . $phrase);
                        return [
                            'status' => 'success',
                            'valid' => false,
                            'message' => 'Invalid prescription image',
                            'reason' => 'This image does not appear to be a valid medical prescription. ' . substr($text, 0, 150)
                        ];
                    }
                }
                
                
                error_log('Prescription rejected - no clear APPROVED response: ' . $text);
                return [
                    'status' => 'success',
                    'valid' => false,
                    'message' => 'Unable to validate prescription',
                    'reason' => 'Could not confirm this is a valid prescription. Please upload a clear photo of a prescription document showing doctor name, patient name, medications, and date.'
                ];
                
            } else {
                error_log('Gemini API call failed for prescription validation: ' . json_encode($response));
                return [
                    'status' => 'error',
                    'valid' => false,
                    'message' => 'Validation service unavailable',
                    'reason' => 'Please try again later'
                ];
            }
        } catch (Exception $e) {
            error_log("GeminiAI Prescription Validation Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'valid' => false,
                'message' => 'Validation failed',
                'reason' => 'Technical error occurred'
            ];
        }
    }
    
    
    public function extractMedicationSchedule($imageData, $mimeType = 'image/jpeg') {
        try {
            $prompt = "You are a pharmacy assistant. Analyze this prescription image and extract ALL medications with their dosing schedule.

For EACH medication found, provide:
1. Medicine name (generic or brand)
2. Dosage (e.g., 500mg, 10ml)
3. Frequency (e.g., once daily, twice daily, three times daily, every 8 hours)
4. When to take (e.g., morning, after meals, before bedtime)
5. Duration/Days supply (e.g., 7 days, 14 days, 30 days)
6. Special instructions (e.g., take with food, avoid alcohol)

FORMAT YOUR RESPONSE AS JSON ARRAY:
[
  {
    \"medicine_name\": \"Amoxicillin\",
    \"dosage\": \"500mg\",
    \"frequency\": \"3 times daily\",
    \"times\": [\"08:00\", \"14:00\", \"20:00\"],
    \"duration_days\": 7,
    \"instructions\": \"Take after meals\"
  },
  {
    \"medicine_name\": \"Paracetamol\",
    \"dosage\": \"500mg\",
    \"frequency\": \"Every 6 hours as needed\",
    \"times\": [\"06:00\", \"12:00\", \"18:00\", \"22:00\"],
    \"duration_days\": 5,
    \"instructions\": \"For fever and pain\"
  }
]

RULES:
- times array should contain suggested reminder times in 24-hour format (HH:MM)
- If frequency is 'once daily', suggest morning time like 08:00
- If 'twice daily', suggest 08:00 and 20:00
- If 'three times daily', suggest 08:00, 14:00, 20:00
- If 'after meals', suggest 08:00, 13:00, 19:00
- If 'before bedtime', suggest 21:00 or 22:00
- If duration not specified, estimate based on medication type (antibiotics: 7 days, chronic: 30 days)
- Return ONLY the JSON array, no other text

If you cannot read the prescription clearly, return:
{\"error\": \"Cannot read prescription clearly\", \"medications\": []}";

            $response = $this->callGeminiVisionAPI($prompt, $imageData, $mimeType);
            
            if ($response['success']) {
                $text = trim($response['text']);
                
                
                error_log('Medication Extraction Response: ' . $text);
                
                
                $text = preg_replace('/```json\s*/', '', $text);
                $text = preg_replace('/```\s*/', '', $text);
                $text = trim($text);
                
                
                $medications = json_decode($text, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    
                    if (isset($medications['error'])) {
                        return [
                            'success' => false,
                            'message' => $medications['error'],
                            'medications' => []
                        ];
                    }
                    
                    
                    $cleaned = [];
                    foreach ($medications as $med) {
                        if (!empty($med['medicine_name'])) {
                            $cleaned[] = [
                                'medicine_name' => $med['medicine_name'] ?? 'Unknown',
                                'dosage' => $med['dosage'] ?? '',
                                'frequency' => $med['frequency'] ?? 'As directed',
                                'times' => $med['times'] ?? ['08:00'],
                                'duration_days' => intval($med['duration_days'] ?? 7),
                                'instructions' => $med['instructions'] ?? ''
                            ];
                        }
                    }
                    
                    return [
                        'success' => true,
                        'medications' => $cleaned,
                        'count' => count($cleaned)
                    ];
                } else {
                    error_log('Failed to parse medication JSON: ' . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Could not parse medication details',
                        'medications' => [],
                        'raw_response' => $text
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'AI service unavailable',
                    'medications' => []
                ];
            }
        } catch (Exception $e) {
            error_log("GeminiAI Medication Extraction Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error extracting medication details',
                'medications' => []
            ];
        }
    }
    
    
    public function extractMedicineFromImage($imageData, $mimeType = 'image/jpeg') {
        try {
            $prompt = $this->buildExtractionPrompt();
            $response = $this->callGeminiVisionAPI($prompt, $imageData, $mimeType);
            
            if ($response['success']) {
                $text = $response['text'];
                
                
                if (stripos($text, 'REJECTED:') !== false || stripos($text, 'NOT_MEDICINE') !== false) {
                    return [
                        'status' => 'rejected',
                        'message' => "😔 **Sorry, I can only help with prescription and medicine-related images.**\n\nPlease upload:\n• 📋 A medical prescription\n• 💊 Medicine packaging or label\n• 🧾 Pharmacy receipt with medicine names\n\nIf you have questions about medicines, just type your question instead!",
                        'medicine_name' => null
                    ];
                }
                
                
                $medicineName = '';
                $details = '';
                
                
                if (preg_match('/MEDICINE_NAME:\s*(.+?)(?:\n|$)/i', $text, $matches)) {
                    $medicineName = trim($matches[1]);
                }
                
                
                if (preg_match('/DETAILS:\s*(.+)/is', $text, $matches)) {
                    $details = trim($matches[1]);
                }
                
                
                if (empty($medicineName)) {
                    
                    $lines = explode("\n", $text);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line) && strlen($line) > 2 && strlen($line) < 100) {
                            
                            if (!preg_match('/^(sorry|i can|please|the image|this is)/i', $line)) {
                                $medicineName = preg_replace('/[*_#]/', '', $line);
                                $medicineName = trim($medicineName);
                                break;
                            }
                        }
                    }
                    $details = $text;
                }
                
                if (!empty($medicineName)) {
                    return [
                        'status' => 'success',
                        'medicine_name' => $medicineName,
                        'details' => $details
                    ];
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Could not identify medicine from image',
                    'medicine_name' => null
                ];
            }
            
            return [
                'status' => 'error',
                'message' => $response['error'] ?? 'Failed to analyze image',
                'medicine_name' => null
            ];
            
        } catch (Exception $e) {
            error_log("GeminiAI Extract Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'medicine_name' => null
            ];
        }
    }
    
    
    private function buildExtractionPrompt() {
        $prompt = "Analyze this image and determine if it shows a medicine, prescription, or drug-related content.\n\n";
        
        $prompt .= "STEP 1 - VALIDATE IMAGE TYPE:\n";
        $prompt .= "✅ ACCEPT: Medicine packaging, prescription, drug label, pharmacy receipt\n";
        $prompt .= "❌ REJECT: Diagrams, flowcharts, random photos, non-medicine images\n\n";
        
        $prompt .= "IF THE IMAGE IS NOT MEDICINE-RELATED:\n";
        $prompt .= "Respond with exactly: REJECTED: NOT_MEDICINE\n\n";
        
        $prompt .= "IF THE IMAGE IS MEDICINE-RELATED:\n";
        $prompt .= "Respond in this EXACT format:\n";
        $prompt .= "MEDICINE_NAME: [Primary medicine name - use brand name if visible, otherwise generic name]\n";
        $prompt .= "DETAILS:\n";
        $prompt .= "• Brand: [brand name if visible]\n";
        $prompt .= "• Generic: [generic/scientific name if visible]\n";
        $prompt .= "• Strength: [dosage strength if visible, e.g., 500mg]\n";
        $prompt .= "• Form: [tablet, capsule, syrup, etc.]\n";
        $prompt .= "• Manufacturer: [company name if visible]\n";
        $prompt .= "• Expiry: [expiration date if visible]\n\n";
        
        $prompt .= "IMPORTANT:\n";
        $prompt .= "- Extract the EXACT medicine name as shown on the packaging\n";
        $prompt .= "- Include both brand and generic names if both are visible\n";
        $prompt .= "- Be precise with the medicine name for database lookup\n";
        
        return $prompt;
    }
    
    
    private function buildPrescriptionValidationPrompt() {
        return "
=== STRICT PRESCRIPTION IMAGE VALIDATION ===

You are a STRICT medical prescription validator for a pharmacy system.
Your PRIMARY goal is to REJECT non-prescription images and ONLY accept legitimate medical prescriptions.

⚠️ BE EXTREMELY STRICT - When in doubt, REJECT the image.

REQUIRED ELEMENTS (ALL must be present for VALID):
1. ✅ Doctor's full name AND signature/stamp (BOTH required)
2. ✅ Medical license number OR clinic/hospital letterhead
3. ✅ Patient name (full name required)
4. ✅ Medication names with specific dosages (e.g., \"Amoxicillin 500mg\")
5. ✅ Date of prescription (must be visible)
6. ✅ Rx symbol OR official prescription format
7. ✅ Doctor's contact info OR clinic stamp/seal

AUTOMATICALLY REJECT (99% confidence these are NOT prescriptions):
❌ Selfies, faces, people photos
❌ Food, meals, drinks
❌ Nature, scenery, buildings
❌ Animals, pets
❌ Screenshots of social media
❌ Text messages or chat conversations
❌ Medicine bottles/boxes ALONE (without prescription document)
❌ Shopping lists or handwritten notes (unless official prescription pad)
❌ Blurry/unclear images where text cannot be read
❌ Partial/cut-off documents
❌ Lab results, medical reports (NOT prescriptions)
❌ Magazine/newspaper articles
❌ Computer screens or monitors
❌ Blank paper or empty forms

RESPONSE FORMAT (MANDATORY):
If image is clearly NOT a prescription (selfie, food, random photo, etc):
INVALID_PRESCRIPTION: This is [what you see, e.g., \"a photo of food\", \"a selfie\", \"a random image\"]. This is NOT a medical prescription. Please upload an actual prescription document from a licensed doctor.

If image appears to be a prescription but missing key elements:
INVALID_PRESCRIPTION: This appears to be [what it is], but it's missing [list missing required elements]. A valid prescription must contain all required elements.

If image is blurry/unreadable:
INVALID_PRESCRIPTION: The image is too blurry or unclear to validate. Please upload a clear, well-lit photo of the prescription where all text is readable.

If image is a VALID prescription with all elements:
VALID_PRESCRIPTION: [Brief description - Doctor name: X, Patient: Y, Medications: Z, Date: D]
CONFIDENCE: [70-100]%

⚠️ IMPORTANT RULES:
- If you can't see clear doctor signature/name = INVALID
- If you can't read patient name = INVALID  
- If you can't identify specific medications = INVALID
- If it's obviously not a medical document = INVALID
- If confidence is below 70% = INVALID
- When unsure = INVALID (better safe than sorry)

Be strict and protect patient safety by rejecting questionable images.
";
    }
    
    
    private function buildImagePrompt($userMessage = '') {
        $store_name = $this->store_info['name'] ?? 'PharmaCare';
        
        $prompt = "You are a pharmacy assistant for '{$store_name}'. You ONLY help with REAL medical prescriptions and actual pharmaceutical medicines.\n\n";
        
        $prompt .= "FIRST, DETERMINE THE IMAGE TYPE:\n";
        $prompt .= "✅ ACCEPTED IMAGES (respond helpfully):\n";
        $prompt .= "   - REAL medical prescriptions with ACTUAL DRUG/MEDICATION NAMES (like Amoxicillin, Paracetamol, Metformin, etc.)\n";
        $prompt .= "   - Medicine boxes, bottles, or packaging with pharmaceutical products\n";
        $prompt .= "   - Drug labels with actual medicine information\n";
        $prompt .= "   - Pharmacy receipts with real medicine names\n\n";
        
        $prompt .= "❌ REJECTED IMAGES (politely decline):\n";
        $prompt .= "   - NOVELTY/JOKE prescriptions (e.g., 'Prescription for Happiness', 'Prescription for Love', wellness tips)\n";
        $prompt .= "   - Documents with NON-MEDICATION items like 'spread love', 'watch sunset', 'take deep breaths', 'call a friend', 'go for a walk'\n";
        $prompt .= "   - Wellness advice or motivational content disguised as prescriptions\n";
        $prompt .= "   - Diagrams, flowcharts, system designs\n";
        $prompt .= "   - Random photos, selfies, screenshots\n";
        $prompt .= "   - Documents that are NOT real medical prescriptions\n";
        $prompt .= "   - Food, animals, landscapes, people\n";
        $prompt .= "   - Any image NOT related to ACTUAL pharmaceutical medicine\n\n";
        
        $prompt .= "IF THE IMAGE IS NOT A REAL MEDICAL PRESCRIPTION OR ACTUAL MEDICINE:\n";
        $prompt .= "Respond ONLY with this message:\n";
        $prompt .= "\"😔 **Sorry, I can only help with REAL medical prescriptions and actual pharmaceutical medicines.**\n\n";
        $prompt .= "This appears to be a novelty/joke prescription or non-medical document.\n\n";
        $prompt .= "Please upload:\n";
        $prompt .= "• 📋 A REAL medical prescription from a doctor with actual drug names (e.g., Amoxicillin, Paracetamol)\n";
        $prompt .= "• 💊 Actual medicine packaging or pharmaceutical label\n";
        $prompt .= "• 🧾 Pharmacy receipt with real medicine names\n\n";
        $prompt .= "If you have questions about medicines, just type your question instead!\"\n\n";
        
        $prompt .= "IF THE IMAGE IS A VALID REAL PRESCRIPTION WITH ACTUAL DRUG NAMES:\n";
        $prompt .= "1. Extract medicine names, dosages, and instructions if visible\n";
        $prompt .= "2. Note the doctor's name if present\n";
        $prompt .= "3. Check for date and validity\n";
        $prompt .= "4. List the medicines clearly\n";
        $prompt .= "5. ALWAYS include this disclaimer at the end:\n";
        $prompt .= "   \"⚠️ **Important:** I cannot verify if this prescription is authentic or from a licensed doctor. Please bring the original prescription to our pharmacy for proper verification by our licensed pharmacist.\"\n\n";
        
        $prompt .= "IF THE IMAGE IS A MEDICINE LABEL/PACKAGING:\n";
        $prompt .= "1. Identify the medicine name and brand\n";
        $prompt .= "2. Note dosage form and strength\n";
        $prompt .= "3. Check expiration date if visible\n";
        $prompt .= "4. Read any warnings or instructions\n";
        $prompt .= "5. Mention if it requires prescription\n\n";
        
        if (!empty($userMessage)) {
            $prompt .= "USER'S QUESTION: {$userMessage}\n\n";
        }
        
        $prompt .= "Now analyze the image. If it's a novelty prescription, wellness advice, or not a REAL medical prescription with actual pharmaceutical drug names, use the rejection message above. Be STRICT.";
        
        return $prompt;
    }
    
    
    public function matchPrescriptionWithCart($imageData, $mimeType, $cartProducts) {
        try {
            
            $productList = implode(", ", $cartProducts);
            
            $prompt = "You are a pharmacy verification assistant. Your task is to compare a prescription image with a list of products in a shopping cart.

**CART PRODUCTS:** {$productList}

**YOUR TASK:**
1. Extract ALL medicine names from the prescription image
2. Compare each prescribed medicine with the cart products
3. Determine if the cart matches the prescription

**IMPORTANT PHARMACY RULES:**
- A customer is ALLOWED to buy any subset of their prescribed medications (e.g., buying only 1 medicine even if the prescription lists 3).
- As long as the medicine(s) in the cart ARE listed on the prescription, this is a VALID MATCH (\"matched\").
- Unpurchased medicines on the prescription do NOT cause a mismatch.
- Generic names match brand names (e.g., Paracetamol matches Biogesic, Amoxicillin matches Amoxil).
- Brand names match generics.
- Ignore minor spelling variations and consider dosage forms (tablet, capsule, syrup) as matching.

**OUTPUT FORMAT (JSON ONLY):**
{
  \"status\": \"matched\" or \"mismatch\" or \"partial\",
  \"prescribed_medicines\": [\"Medicine 1\", \"Medicine 2\", ...],
  \"matched_products\": [\"Product 1\", ...],
  \"missing_from_cart\": [\"Prescribed medicine not bought in this order\", ...],
  \"extra_in_cart\": [\"Cart product NOT found on prescription\", ...],
  \"confidence\": 0.0 to 1.0,
  \"notes\": \"Brief explanation of the match result\"
}

**STATUS DEFINITIONS:**
- \"matched\": All products currently in the cart are found on the prescription (even if the prescription has additional items the customer is not buying today).
- \"partial\": Most cart products match the prescription with minor variations.
- \"mismatch\": The product in the cart is NOT on the prescription at all.

Return ONLY the JSON object, no markdown formatting or extra text.

If the image is not a valid prescription, return:
{\"status\": \"invalid\", \"prescribed_medicines\": [], \"notes\": \"Not a valid prescription image\"}";

            $response = $this->callGeminiVisionAPI($prompt, $imageData, $mimeType);
            
            if ($response['success']) {
                $text = trim($response['text']);
                
                
                error_log('Prescription Match Response: ' . $text);
                
                
                $text = preg_replace('/```json\s*/', '', $text);
                $text = preg_replace('/```\s*/', '', $text);
                $text = trim($text);
                
                
                $result = json_decode($text, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($result['status'])) {
                    return [
                        'success' => true,
                        'validation' => $result['status'], 
                        'prescribed_medicines' => $result['prescribed_medicines'] ?? [],
                        'matched_products' => $result['matched_products'] ?? [],
                        'missing_from_cart' => $result['missing_from_cart'] ?? [],
                        'extra_in_cart' => $result['extra_in_cart'] ?? [],
                        'confidence' => floatval($result['confidence'] ?? 0.5),
                        'notes' => $result['notes'] ?? '',
                        'raw_response' => $text
                    ];
                } else {
                    error_log('Failed to parse prescription match JSON: ' . json_last_error_msg());
                    return [
                        'success' => false,
                        'validation' => 'error',
                        'message' => 'Could not parse AI response',
                        'raw_response' => $text
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'validation' => 'error',
                    'message' => 'AI service unavailable'
                ];
            }
        } catch (Exception $e) {
            error_log("GeminiAI Prescription Match Error: " . $e->getMessage());
            return [
                'success' => false,
                'validation' => 'error',
                'message' => 'Error matching prescription with cart'
            ];
        }
    }
    
    
    private function callGeminiVisionAPI($prompt, $imageData, $mimeType, $retries = 2) {
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $this->api_key;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 2048,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60, 
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("Gemini Vision cURL Error: " . $curl_error);
            if ($retries > 0) {
                usleep(500000);
                return $this->callGeminiVisionAPI($prompt, $imageData, $mimeType, $retries - 1);
            }
            return ['success' => false, 'error' => $curl_error];
        }
        
        $result = json_decode($response, true);
        
        if ($http_code !== 200) {
            $error_message = $result['error']['message'] ?? 'API Error';
            error_log("Gemini Vision API Error (HTTP {$http_code}): " . $error_message);
            
            if (($http_code === 429 || $http_code >= 500) && $retries > 0) {
                usleep(1000000);
                return $this->callGeminiVisionAPI($prompt, $imageData, $mimeType, $retries - 1);
            }
            
            return [
                'success' => false,
                'error' => $error_message,
                'http_code' => $http_code
            ];
        }
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'text' => $result['candidates'][0]['content']['parts'][0]['text']
            ];
        }
        
        return ['success' => false, 'error' => 'No response generated from image analysis'];
    }
}
?>
