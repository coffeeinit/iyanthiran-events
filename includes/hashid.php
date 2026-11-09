<?php
// HashID Functions for encoding/decoding IDs
require_once 'config.php';

// Simple base62 encoding/decoding for ID obfuscation
class SimpleHashId {
    private static $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private static $base = 62;
    
    public static function encode($num) {
        if ($num === 0) return self::$alphabet[0];
        
        $encoded = '';
        while ($num > 0) {
            $encoded = self::$alphabet[$num % self::$base] . $encoded;
            $num = intval($num / self::$base);
        }
        
        // Add salt to make it less predictable
        $salt = substr(md5(HASH_SALT . $num), 0, 2);
        return $salt . $encoded;
    }
    
    public static function decode($encoded) {
        if (strlen($encoded) < 3) return false;
        
        // Remove salt
        $encoded = substr($encoded, 2);
        
        $decoded = 0;
        $length = strlen($encoded);
        
        for ($i = 0; $i < $length; $i++) {
            $position = strpos(self::$alphabet, $encoded[$i]);
            if ($position === false) return false;
            $decoded = $decoded * self::$base + $position;
        }
        
        return $decoded;
    }
}

// Encode ID for URLs
function encodeId($id) {
    return SimpleHashId::encode($id);
}

// Decode ID from URLs
function decodeId($encoded) {
    return SimpleHashId::decode($encoded);
}

// Generate URL-safe slug for events
function generateSlug($title, $id = null) {
    // Convert to lowercase and remove special characters
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Limit length
    $slug = substr($slug, 0, 50);
    
    // Add encoded ID for uniqueness
    if ($id) {
        $slug .= '-' . encodeId($id);
    }
    
    return $slug;
}

// Extract ID from slug
function getIdFromSlug($slug) {
    // Extract the encoded ID from the end of the slug
    $parts = explode('-', $slug);
    $encoded_id = end($parts);
    
    return decodeId($encoded_id);
}

// Generate secure random token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Generate QR code data for event/registration
function generateQRData($type, $id) {
    $data = [
        'type' => $type,
        'id' => $id,
        'site' => SITE_URL,
        'timestamp' => time()
    ];
    
    return base64_encode(json_encode($data));
}

// Verify QR code data
function verifyQRData($qr_data) {
    try {
        $data = json_decode(base64_decode($qr_data), true);
        
        if (!$data || !isset($data['type'], $data['id'], $data['site'])) {
            return false;
        }
        
        // Verify site matches
        if ($data['site'] !== SITE_URL) {
            return false;
        }
        
        return $data;
        
    } catch (Exception $e) {
        return false;
    }
}
?>