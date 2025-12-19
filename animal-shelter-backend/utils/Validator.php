<?php
/**
 * Input Validation Helper
 */

class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Check if field is required
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "$field is required";
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Invalid email format";
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "$field must be at least $length characters";
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "$field must not exceed $length characters";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "$field must be a number";
        }
        return $this;
    }

    /**
     * Validate value is in array
     */
    public function inArray($field, $allowed, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed)) {
            $this->errors[$field] = $message ?? "$field must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "Invalid date format for $field";
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get sanitized value
     */
    public function getValue($field, $default = null) {
        return isset($this->data[$field]) ? htmlspecialchars(trim($this->data[$field]), ENT_QUOTES, 'UTF-8') : $default;
    }
}