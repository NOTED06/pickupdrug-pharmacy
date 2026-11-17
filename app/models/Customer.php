<?php

require_once __DIR__ . '/BaseModel.php';

class Customer extends BaseModel {
    
    protected $table = 'customer_list';
    protected $primaryKey = 'id';
    protected $fillable = [
        'firstname', 'middlename', 'lastname', 'gender', 'contact',
        'birthdate', 'email', 'password', 'avatar', 'code',
        'status', 'delete_flag', 'date_created', 'date_updated'
    ];
    
    
    public function findByEmail($email) {
        return $this->where('email', $email)
                    ->where('delete_flag', 0)
                    ->first();
    }
    
    
    public function findByCode($code) {
        return $this->where('code', $code)
                    ->where('delete_flag', 0)
                    ->first();
    }
    
    
    public function createCustomer($data) {
        
        if (isset($data['password'])) {
            $data['password'] = SecurityHelper::hashPassword($data['password']);
        }
        
        
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }
        
        
        $data['date_created'] = date('Y-m-d H:i:s');
        $data['status'] = 1;
        $data['delete_flag'] = 0;
        
        return $this->create($data);
    }
    
    
    public function authenticate($email, $password) {
        $customer = $this->findByEmail($email);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        
        if ($customer['status'] != 1) {
            return [
                'success' => false,
                'message' => 'Account is inactive'
            ];
        }
        
        
        $passwordValid = $this->verifyPassword($password, $customer['password']);
        
        if (!$passwordValid) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        
        if ($this->isLegacyPassword($customer['password'])) {
            $this->updatePassword($customer['id'], $password);
        }
        
        return [
            'success' => true,
            'customer' => $customer,
            'message' => 'Login successful'
        ];
    }
    
    
    public function updatePassword($customerId, $newPassword) {
        $hashedPassword = SecurityHelper::hashPassword($newPassword);
        return $this->update($customerId, [
            'password' => $hashedPassword,
            'date_updated' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    private function verifyPassword($password, $hash) {
        if ($this->isLegacyPassword($hash)) {
            return md5($password) === $hash;
        }
        return SecurityHelper::verifyPassword($password, $hash);
    }
    
    
    private function isLegacyPassword($hash) {
        return strlen($hash) === 32 && ctype_xdigit($hash);
    }
    
    
    private function generateUniqueCode() {
        do {
            $code = date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->where('code', $code)->count() > 0;
        } while ($exists);
        
        return $code;
    }
    
    
    public function emailExists($email, $excludeId = null) {
        $query = $this->where('email', $email)->where('delete_flag', 0);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->count() > 0;
    }
    
    
    public function getSafeCustomer($id) {
        $customer = $this->find($id);
        if ($customer) {
            unset($customer['password']);
        }
        return $customer;
    }
    
    
    public static function calculateAge($birthdate) {
        $birth = new DateTime($birthdate);
        $now = new DateTime();
        return $now->diff($birth)->y;
    }
    
}
