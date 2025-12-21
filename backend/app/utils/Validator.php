<?php
/**
 * Input Validation Helper Class
 * 
 * @package AnimalShelter
 */

class Validator {
    /**
     * @var array Validation errors
     */
    private $errors = [];
    
    /**
     * @var array Data to validate
     */
    private $data = [];

    /**
     * Constructor
     * 
     * @param array|null $data Data to validate
     */
    public function __construct($data = null) {
        $this->data = $data ?? [];
    }

    /**
     * Set data to validate
     * 
     * @param array $data
     * @return self
     */
    public function setData($data) {
        $this->data = $data;
        $this->errors = [];
        return $this;
    }

    /**
     * Validate required field
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function required($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " is required");
        }
        
        return $this;
    }

    /**
     * Validate email format
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function email($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "Invalid email format");
        }
        
        return $this;
    }

    /**
     * Validate minimum string length
     * 
     * @param string $field Field name
     * @param int $length Minimum length
     * @param string|null $message Custom error message
     * @return self
     */
    public function minLength($field, $length, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && strlen($value) < $length) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be at least {$length} characters");
        }
        
        return $this;
    }

    /**
     * Validate maximum string length
     * 
     * @param string $field Field name
     * @param int $length Maximum length
     * @param string|null $message Custom error message
     * @return self
     */
    public function maxLength($field, $length, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && strlen($value) > $length) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must not exceed {$length} characters");
        }
        
        return $this;
    }

    /**
     * Validate numeric value
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function numeric($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be a number");
        }
        
        return $this;
    }

    /**
     * Validate integer value
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function integer($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be an integer");
        }
        
        return $this;
    }

    /**
     * Validate positive number
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function positive($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && (!is_numeric($value) || $value <= 0)) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be a positive number");
        }
        
        return $this;
    }

    /**
     * Validate non-negative number
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function nonNegative($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && (!is_numeric($value) || $value < 0)) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be zero or greater");
        }
        
        return $this;
    }

    /**
     * Validate value is in allowed array
     * 
     * @param string $field Field name
     * @param array $allowed Allowed values
     * @param string|null $message Custom error message
     * @return self
     */
    public function inArray($field, $allowed, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be one of: " . implode(', ', $allowed));
        }
        
        return $this;
    }

    /**
     * Validate date format
     * 
     * @param string $field Field name
     * @param string $format Expected date format
     * @param string|null $message Custom error message
     * @return self
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->addError($field, $message ?? "Invalid date format. Expected format: {$format}");
            }
        }
        
        return $this;
    }

    /**
     * Validate datetime format
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function datetime($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            // Try multiple formats
            $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:s.u', 'Y-m-d\TH:i:sP'];
            $valid = false;
            
            foreach ($formats as $format) {
                $d = DateTime::createFromFormat($format, $value);
                if ($d !== false) {
                    $valid = true;
                    break;
                }
            }
            
            // Also try general strtotime
            if (!$valid && strtotime($value) !== false) {
                $valid = true;
            }
            
            if (!$valid) {
                $this->addError($field, $message ?? "Invalid datetime format");
            }
        }
        
        return $this;
    }

    /**
     * Validate date is in the future
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function futureDate($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = strtotime($value);
            if ($date && $date <= time()) {
                $this->addError($field, $message ?? $this->formatFieldName($field) . " must be a future date");
            }
        }
        
        return $this;
    }

    /**
     * Validate date is in the past
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function pastDate($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = strtotime($value);
            if ($date && $date >= time()) {
                $this->addError($field, $message ?? $this->formatFieldName($field) . " must be a past date");
            }
        }
        
        return $this;
    }

    /**
     * Validate minimum value
     * 
     * @param string $field Field name
     * @param float $minValue Minimum value
     * @param string|null $message Custom error message
     * @return self
     */
    public function min($field, $minValue, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && is_numeric($value) && $value < $minValue) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must be at least {$minValue}");
        }
        
        return $this;
    }

    /**
     * Validate maximum value
     * 
     * @param string $field Field name
     * @param float $maxValue Maximum value
     * @param string|null $message Custom error message
     * @return self
     */
    public function max($field, $maxValue, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && is_numeric($value) && $value > $maxValue) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " must not exceed {$maxValue}");
        }
        
        return $this;
    }

    /**
     * Validate regex pattern
     * 
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @param string|null $message Custom error message
     * @return self
     */
    public function pattern($field, $pattern, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, $message ?? $this->formatFieldName($field) . " format is invalid");
        }
        
        return $this;
    }

    /**
     * Validate phone number (Philippine format)
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function phone($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            // Remove common separators
            $cleaned = preg_replace('/[\s\-\(\)\+]/', '', $value);
            
            // Check Philippine mobile formats
            // 09XXXXXXXXX or 639XXXXXXXXX or +639XXXXXXXXX
            if (!preg_match('/^(0|63|)9\d{9}$/', $cleaned)) {
                $this->addError($field, $message ?? "Invalid phone number format");
            }
        }
        
        return $this;
    }

    /**
     * Validate URL format
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function url($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $message ?? "Invalid URL format");
        }
        
        return $this;
    }

    /**
     * Validate confirmed field (e.g., password confirmation)
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function confirmed($field, $message = null) {
        $value = $this->getValue($field);
        $confirmValue = $this->getValue($field . '_confirmation');
        
        if ($value !== null && $value !== $confirmValue) {
            $this->addError($field . '_confirmation', $message ?? $this->formatFieldName($field) . " confirmation does not match");
        }
        
        return $this;
    }

    /**
     * Validate boolean value
     * 
     * @param string $field Field name
     * @param string|null $message Custom error message
     * @return self
     */
    public function boolean($field, $message = null) {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $allowed = [true, false, 0, 1, '0', '1', 'true', 'false'];
            if (!in_array($value, $allowed, true)) {
                $this->addError($field, $message ?? $this->formatFieldName($field) . " must be a boolean value");
            }
        }
        
        return $this;
    }

    /**
     * Custom validation with callback
     * 
     * @param string $field Field name
     * @param callable $callback Validation callback
     * @param string $message Error message if validation fails
     * @return self
     */
    public function custom($field, callable $callback, $message) {
        $value = $this->getValue($field);
        
        if (!$callback($value, $this->data)) {
            $this->addError($field, $message);
        }
        
        return $this;
    }

    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string|null
     */
    public function getFirstError() {
        return reset($this->errors) ?: null;
    }

    /**
     * Get error for specific field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }

    /**
     * Get value from data (supports nested keys with dot notation)
     * 
     * @param string $field Field name
     * @return mixed
     */
    private function getValue($field) {
        // Support dot notation for nested fields
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $value = $this->data;
            
            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    return null;
                }
                $value = $value[$key];
            }
            
            return $value;
        }
        
        return $this->data[$field] ?? null;
    }

    /**
     * Add error message
     * 
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError($field, $message) {
        // Only add first error for each field
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Format field name for display
     * 
     * @param string $field Field name
     * @return string
     */
    private function formatFieldName($field) {
        // Remove array notation
        $field = preg_replace('/\[\d+\]/', '', $field);
        
        // Replace dots, underscores, dashes with spaces
        $field = str_replace(['.', '_', '-'], ' ', $field);
        
        // Capitalize first letter of each word
        return ucwords($field);
    }

    /**
     * Get sanitized value
     * 
     * @param string $field Field name
     * @param mixed $default Default value if field is empty
     * @return mixed
     */
    public function getSanitized($field, $default = null) {
        $value = $this->getValue($field);
        
        if ($value === null || $value === '') {
            return $default;
        }
        
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }

    /**
     * Get all sanitized data
     * 
     * @return array
     */
    public function getAllSanitized() {
        $sanitized = [];
        
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Static method to quickly validate data
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return Validator
     */
    public static function make($data, $rules) {
        $validator = new self($data);
        
        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            
            foreach ($ruleList as $rule) {
                // Parse rule name and parameters
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                } else {
                    $ruleName = $rule;
                }
                
                // Apply rule
                switch ($ruleName) {
                    case 'required':
                        $validator->required($field);
                        break;
                    case 'email':
                        $validator->email($field);
                        break;
                    case 'min':
                        $validator->minLength($field, (int)($params[0] ?? 0));
                        break;
                    case 'max':
                        $validator->maxLength($field, (int)($params[0] ?? 255));
                        break;
                    case 'numeric':
                        $validator->numeric($field);
                        break;
                    case 'integer':
                        $validator->integer($field);
                        break;
                    case 'positive':
                        $validator->positive($field);
                        break;
                    case 'in':
                        $validator->inArray($field, $params);
                        break;
                    case 'date':
                        $validator->date($field, $params[0] ?? 'Y-m-d');
                        break;
                    case 'datetime':
                        $validator->datetime($field);
                        break;
                    case 'phone':
                        $validator->phone($field);
                        break;
                    case 'url':
                        $validator->url($field);
                        break;
                    case 'boolean':
                        $validator->boolean($field);
                        break;
                    case 'confirmed':
                        $validator->confirmed($field);
                        break;
                }
            }
        }
        
        return $validator;
    }
}