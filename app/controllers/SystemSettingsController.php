<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/SystemSettingsModel.php';

class SystemSettingsController extends BaseController {
    
    private $settingsModel;
    
    public function __construct() {
        parent::__construct();
        
        $db = new DBConnection();
        $this->settingsModel = new SystemSettingsModel($db->conn);
    }
    
    
    public function updateSettings() {
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $settings = [];
            
            
            foreach ($_POST as $key => $value) {
                if (!in_array($key, ['csrf_token', 'content'])) {
                    $settings[$key] = $value;
                }
            }
            
            
            $result = $this->settingsModel->updateSettings($settings);
            
            
            if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
                $logoPath = $this->settingsModel->uploadLogo($_FILES['img']);
                if ($logoPath) {
                    $_SESSION['system_info']['logo'] = $logoPath;
                }
            }
            
            
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
                $coverPath = $this->settingsModel->uploadCover($_FILES['cover']);
                if ($coverPath) {
                    $_SESSION['system_info']['cover'] = $coverPath;
                }
            }
            
            
            if (isset($_POST['content'])) {
                foreach ($_POST['content'] as $key => $value) {
                    $filePath = base_app . $key . '.html';
                    file_put_contents($filePath, $value);
                }
            }
            
            
            $_SESSION['system_info'] = $this->settingsModel->loadSystemInfo();
            
            if ($result) {
                return $this->jsonResponse('success', 'System settings successfully updated!');
            } else {
                return $this->jsonResponse('failed', 'Failed to update system settings');
            }
            
        } catch (Exception $e) {
            $this->logError('SystemSettingsController::updateSettings', $e);
            return $this->jsonResponse('failed', 'An error occurred while updating settings');
        }
    }
    
    
    public function getSettings() {
        try {
            $settings = $this->settingsModel->loadSystemInfo();
            
            return $this->jsonResponse('success', 'Settings retrieved successfully', $settings);
            
        } catch (Exception $e) {
            $this->logError('SystemSettingsController::getSettings', $e);
            return $this->jsonResponse('failed', 'Error retrieving settings');
        }
    }
    
    
    public function getSetting() {
        try {
            $field = $_GET['field'] ?? '';
            
            if (empty($field)) {
                return $this->jsonResponse('failed', 'Field name is required');
            }
            
            $value = $this->settingsModel->getSetting($field);
            
            if ($value !== null) {
                return $this->jsonResponse('success', 'Setting retrieved successfully', [$field => $value]);
            } else {
                return $this->jsonResponse('failed', 'Setting not found');
            }
            
        } catch (Exception $e) {
            $this->logError('SystemSettingsController::getSetting', $e);
            return $this->jsonResponse('failed', 'Error retrieving setting');
        }
    }
}
