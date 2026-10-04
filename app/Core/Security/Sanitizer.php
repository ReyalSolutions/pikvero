<?php
namespace App\Core\Security;

class Sanitizer {

    /**
     * Recursively sanitize any input (string, array, numeric)
     *
     * @param mixed $data Input data (string, array, etc.)
     * @param array $skipKeys Array of keys to skip htmlspecialchars encoding (e.g. passwords, base64)
     * @return mixed Sanitized data
     */
    public static function clean($data, array $skipKeys = ['pass', 'cpass', 'password', 'password_confirmation', 'base64']) {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleanKey = self::cleanString((string)$key);
                if (in_array((string)$key, $skipKeys, true)) {
                    $cleaned[$cleanKey] = is_string($value) ? self::cleanPassword($value) : $value;
                } else {
                    $cleaned[$cleanKey] = self::clean($value, $skipKeys);
                }
            }
            return $cleaned;
        }

        if (is_string($data)) {
            return self::cleanString($data);
        }

        return $data;
    }

    /**
     * Sanitize a single string (strip tags, remove null bytes, trim, htmlspecialchars)
     */
    public static function cleanString(string $value): string {
        $value = trim($value);
        $value = str_replace("\0", '', $value); // Remove null bytes
        $value = strip_tags($value); // Strip HTML & script tags to prevent XSS
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize an email address
     */
    public static function cleanEmail(string $email): string {
        $email = trim(strtolower($email));
        return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitize a phone number (keep digits, plus sign, hyphens)
     */
    public static function cleanPhone(string $phone): string {
        return preg_replace('/[^\d\+\-\s\(\)]/', '', trim($phone));
    }

    /**
     * Sanitize password string safely (remove null bytes & trim, preserve special chars)
     */
    public static function cleanPassword(string $password): string {
        return str_replace("\0", '', trim($password));
    }
}
