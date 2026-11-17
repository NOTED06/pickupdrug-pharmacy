<?php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'firstname', 'lastname', 'username', 'password', 'email',
        'contact', 'avatar', 'type', 'status', 'date_added', 'date_updated'
    ];
    
    
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }
    
    
    public function findByUsername($username) {
        return $this->findBy('username', $username);
    }
    
    
    public function createUser($data) {
        
        if (isset($data['password'])) {
            $data['password'] = SecurityHelper::hashPassword($data['password']);
        }
        
        
        $data['date_added'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = SecurityHelper::hashPassword($newPassword);
        return $this->update($userId, [
            'password' => $hashedPassword,
            'date_updated' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        
        $passwordValid = $this->verifyPassword($password, $user['password']);
        
        if (!$passwordValid) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        
        if ($this->isLegacyPassword($user['password'])) {
            $this->updatePassword($user['id'], $password);
        }
        
        return [
            'success' => true,
            'user' => $user,
            'message' => 'Login successful'
        ];
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
    
    
    public function getSafeUser($id) {
        $user = $this->find($id);
        if ($user) {
            unset($user['password']);
        }
        return $user;
    }
    
    
    public function emailExists($email, $excludeId = null) {
        $query = $this->where('email', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->count() > 0;
    }
    
    
    public function usernameExists($username, $excludeId = null) {
        $query = $this->where('username', $username);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->count() > 0;
    }
    
    
    public function getActiveUsers() {
        return $this->where('status', 1)
                    ->orderBy('firstname', 'ASC')
                    ->get();
    }
    
    
    public function getUsersByType($type) {
        return $this->where('type', $type)
                    ->where('status', 1)
                    ->orderBy('firstname', 'ASC')
                    ->get();
    }
}
