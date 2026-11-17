<?php

class OrderStatus {
    
    
    
    const PENDING = 0;           
    const PACKED = 1;            
    const READY_FOR_PICKUP = 2;  
    const COMPLETED = 3;         
    const CANCELLED = 4;         
    const REJECTED = 5;          
    
    
    
    
    private static $labels = [
        self::PENDING => 'Pending',
        self::PACKED => 'Packed',
        self::READY_FOR_PICKUP => 'Ready for Pick Up',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::REJECTED => 'Rejected'
    ];
    
    
    
    
    private static $cssClasses = [
        self::PENDING => 'badge-warning',
        self::PACKED => 'badge-info',
        self::READY_FOR_PICKUP => 'badge-primary',
        self::COMPLETED => 'badge-success',
        self::CANCELLED => 'badge-danger',
        self::REJECTED => 'badge-dark'
    ];
    
    
    
    
    private static $notifications = [
        self::PENDING => 'Your order is now pending processing.',
        self::PACKED => 'Your order has been packed and is being prepared.',
        self::READY_FOR_PICKUP => 'Great news! Your order is ready for pickup.',
        self::COMPLETED => 'Your order has been completed. Thank you!',
        self::CANCELLED => 'Your order has been cancelled.',
        self::REJECTED => 'Your order has been rejected.'
    ];
    
    
    
    
    private static $backwardNotifications = [
        self::PENDING => 'We sincerely apologize for the inconvenience. Your order has been moved back to Pending status due to an update from the pharmacy. We are working to process it as soon as possible. Thank you for your patience.',
        self::PACKED => 'Sorry for the inconvenience. Your order status has been reverted back to Packed. The pharmacy is re-verifying your order. We appreciate your understanding and patience.',
        self::READY_FOR_PICKUP => 'We apologize for the inconvenience. Your order has been moved back to Ready for Pick Up status. Please check with the pharmacy for any updates regarding your order. Thank you for your understanding.',
        self::COMPLETED => 'We apologize for the confusion. Your order status has been updated back to Completed. If you have any concerns, please contact the pharmacy. Thank you.',
        self::CANCELLED => 'We are sorry for the inconvenience. Your order has been cancelled. If you believe this is a mistake, please contact the pharmacy immediately. We apologize for any trouble caused.',
        self::REJECTED => 'We sincerely apologize for the inconvenience. Your order has been rejected. Please contact the pharmacy for more details. We are sorry for any trouble this may have caused.'
    ];
    
    
    public static function getLabel($status) {
        return self::$labels[$status] ?? 'Unknown';
    }
    
    
    public static function getAllLabels() {
        return self::$labels;
    }
    
    
    public static function getCssClass($status) {
        return self::$cssClasses[$status] ?? 'badge-secondary';
    }
    
    
    public static function getNotificationMessage($status) {
        return self::$notifications[$status] ?? '';
    }
    
    
    public static function getBackwardNotificationMessage($status) {
        return self::$backwardNotifications[$status] ?? '';
    }
    
    
    public static function isBackwardTransition($oldStatus, $newStatus) {
        return (int)$newStatus < (int)$oldStatus;
    }
    
    
    public static function getExcludedForSales() {
        return [self::CANCELLED, self::REJECTED];
    }
    
    
    public static function getExcludedForSalesSQL() {
        return implode(',', self::getExcludedForSales());
    }
    
    
    public static function getActiveStatuses() {
        return [self::PENDING, self::PACKED, self::READY_FOR_PICKUP];
    }
    
    
    public static function getActiveStatusesSQL() {
        return implode(',', self::getActiveStatuses());
    }
    
    
    public static function isValid($status) {
        return array_key_exists($status, self::$labels);
    }
    
    
    public static function isActive($status) {
        return in_array($status, self::getActiveStatuses());
    }
    
    
    public static function isFinal($status) {
        return in_array($status, [self::COMPLETED, self::CANCELLED, self::REJECTED]);
    }
}

class PrescriptionStatus {
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    
    private static $labels = [
        self::PENDING => 'Pending Review',
        self::APPROVED => 'Approved',
        self::REJECTED => 'Rejected'
    ];
    
    private static $cssClasses = [
        self::PENDING => 'badge-warning',
        self::APPROVED => 'badge-success',
        self::REJECTED => 'badge-danger'
    ];
    
    public static function getLabel($status) {
        return self::$labels[$status] ?? 'Unknown';
    }
    
    public static function getCssClass($status) {
        return self::$cssClasses[$status] ?? 'badge-secondary';
    }
    
    public static function isValid($status) {
        return array_key_exists($status, self::$labels);
    }
}

class EntityStatus {
    const INACTIVE = 0;
    const ACTIVE = 1;
    
    public static function isActive($status) {
        return (int)$status === self::ACTIVE;
    }
}
