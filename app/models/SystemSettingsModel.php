<?php

require_once __DIR__ . '/BaseModel.php';

class SystemSettingsModel extends BaseModel {
    
    
    public function loadSystemInfo() {
        try {
            $stmt = $this->db->query("SELECT * FROM system_info");
            $settings = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['meta_field']] = $row['meta_value'];
            }
            
            return $settings;
            
        } catch (PDOException $e) {
            error_log("SystemSettings loadSystemInfo error: " . $e->getMessage());
            return [];
        }
    }
    
    
    public function getSetting($field) {
        try {
            $stmt = $this->db->prepare("SELECT meta_value FROM system_info WHERE meta_field = ?");
            $stmt->execute([$field]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['meta_value'] : null;
            
        } catch (PDOException $e) {
            error_log("SystemSettings getSetting error: " . $e->getMessage());
            return null;
        }
    }
    
    
    public function updateSetting($field, $value) {
        try {
            
            $existing = $this->getSetting($field);
            
            if ($existing !== null) {
                
                $stmt = $this->db->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
                return $stmt->execute([$value, $field]);
            } else {
                
                $stmt = $this->db->prepare("INSERT INTO system_info (meta_field, meta_value) VALUES (?, ?)");
                return $stmt->execute([$field, $value]);
            }
            
        } catch (PDOException $e) {
            error_log("SystemSettings updateSetting error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function updateSettings($settings) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $field => $value) {
                $this->updateSetting($field, $value);
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("SystemSettings updateSettings error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function uploadLogo($file) {
        try {
            $uploadDir = base_app . 'uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedExt = ['jpg', 'jpeg', 'png'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedExt)) {
                return false;
            }
            
            $fileName = 'logo.png';
            $filePath = $uploadDir . $fileName;
            
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $logoPath = 'uploads/' . $fileName . '?v=' . time();
                $this->updateSetting('logo', $logoPath);
                return $logoPath;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("SystemSettings uploadLogo error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function uploadCover($file) {
        try {
            $uploadDir = base_app . 'uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedExt = ['jpg', 'jpeg', 'png'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedExt)) {
                return false;
            }
            
            $fileName = 'cover.png';
            $filePath = $uploadDir . $fileName;
            
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $coverPath = 'uploads/' . $fileName . '?v=' . time();
                $this->updateSetting('cover', $coverPath);
                return $coverPath;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("SystemSettings uploadCover error: " . $e->getMessage());
            return false;
        }
    }
}
