<?php

class ValidationHelper {
    
    private static $errors = [];
    
    
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    
    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    
    public static function float($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    
    public static function numeric($value) {
        return is_numeric($value);
    }
    
    
    public static function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return !empty($value);
        }
        return $value !== null && $value !== '';
    }
    
    
    public static function minLength($value, $length) {
        return mb_strlen($value) >= $length;
    }
    
    
    public static function maxLength($value, $length) {
        return mb_strlen($value) <= $length;
    }
    
    
    public static function min($value, $min) {
        return is_numeric($value) && $value >= $min;
    }
    
    
    public static function max($value, $max) {
        return is_numeric($value) && $value <= $max;
    }
    
    
    public static function in($value, $options) {
        return in_array($value, $options);
    }
    
    
    public static function regex($value, $pattern) {
        return preg_match($pattern, $value) === 1;
    }
    
    
    public static function phone($phone) {
        
        $pattern = '/^(\+639|09)\d{9}$/';
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match($pattern, $clean) === 1;
    }
    
    
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    
    public static function age($birthdate, $minAge = 18) {
        $birth = new DateTime($birthdate);
        $now = new DateTime();
        $age = $now->diff($birth)->y;
        return $age >= $minAge;
    }
    
    
    public static function passwordStrength($password, $requirements = []) {
        $defaults = [
            'min_length' => 8,
            'uppercase' => true,
            'lowercase' => true,
            'number' => true,
            'special' => false
        ];
        $requirements = array_merge($defaults, $requirements);
        
        $errors = [];
        
        if (strlen($password) < $requirements['min_length']) {
            $errors[] = "Password must be at least {$requirements['min_length']} characters";
        }
        
        if ($requirements['uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($requirements['lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($requirements['number'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($requirements['special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $rulesList = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($rulesList as $rule) {
                $ruleParts = explode(':', $rule, 2);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                $valid = true;
                $errorMsg = '';
                
                switch ($ruleName) {
                    case 'required':
                        $valid = self::required($value);
                        $errorMsg = ucfirst($field) . ' is required';
                        break;
                        
                    case 'email':
                        $valid = self::email($value);
                        $errorMsg = ucfirst($field) . ' must be a valid email';
                        break;
                        
                    case 'numeric':
                        $valid = self::numeric($value);
                        $errorMsg = ucfirst($field) . ' must be a number';
                        break;
                        
                    case 'integer':
                        $valid = self::integer($value);
                        $errorMsg = ucfirst($field) . ' must be an integer';
                        break;
                        
                    case 'min':
                        if (is_numeric($value)) {
                            $valid = self::min($value, $ruleParam);
                            $errorMsg = ucfirst($field) . " must be at least {$ruleParam}";
                        } else {
                            $valid = self::minLength($value, $ruleParam);
                            $errorMsg = ucfirst($field) . " must be at least {$ruleParam} characters";
                        }
                        break;
                        
                    case 'max':
                        if (is_numeric($value)) {
                            $valid = self::max($value, $ruleParam);
                            $errorMsg = ucfirst($field) . " must not exceed {$ruleParam}";
                        } else {
                            $valid = self::maxLength($value, $ruleParam);
                            $errorMsg = ucfirst($field) . " must not exceed {$ruleParam} characters";
                        }
                        break;
                        
                    case 'in':
                        $options = explode(',', $ruleParam);
                        $valid = self::in($value, $options);
                        $errorMsg = ucfirst($field) . ' has an invalid value';
                        break;
                        
                    case 'phone':
                        $valid = self::phone($value);
                        $errorMsg = ucfirst($field) . ' must be a valid phone number';
                        break;
                        
                    case 'date':
                        $valid = self::date($value, $ruleParam ?? 'Y-m-d');
                        $errorMsg = ucfirst($field) . ' must be a valid date';
                        break;
                        
                    case 'url':
                        $valid = self::url($value);
                        $errorMsg = ucfirst($field) . ' must be a valid URL';
                        break;
                }
                
                if (!$valid) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }
                    $errors[$field][] = $errorMsg;
                }
            }
        }
        
        return $errors;
    }
    
    
    public static function hasErrors($errors) {
        return !empty($errors);
    }
    
    
    public static function firstError($errors, $field) {
        return $errors[$field][0] ?? null;
    }
    
    
    public static function formatErrors($errors) {
        if (empty($errors)) {
            return '';
        }
        
        $html = '<ul class="validation-errors">';
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $html .= '<li>' . SecurityHelper::escape($error) . '</li>';
            }
        }
        $html .= '</ul>';
        
        return $html;
    }
}
