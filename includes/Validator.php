<?php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public static function string($value, $maxLen = 255) {
        if (!is_string($value)) return false;
        $value = trim($value);
        if (strlen($value) > $maxLen) return false;
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function phone($phone) {
        return preg_match('/^[0-9+\-\s\(\)]+$/', $phone) ? $phone : false;
    }

    public static function required($value) {
        return !empty(trim($value));
    }

    public static function minLength($value, $min) {
        return strlen(trim($value)) >= $min;
    }

    public static function maxLength($value, $max) {
        return strlen(trim($value)) <= $max;
    }

    public static function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
?>