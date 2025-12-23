<?php
class ApiKeyGenerator {
    public static function generateKeyPair() {
        // Generate API Key (public identifier)
        $apiKey = bin2hex(random_bytes(32));
        
        // Generate API Secret (private, for signing)
        $apiSecret = bin2hex(random_bytes(64));
        
        // Create a hashed version for storage
        $hashedSecret = password_hash($apiSecret, PASSWORD_BCRYPT);
        
        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'hashed_secret' => $hashedSecret
        ];
    }
    
    public static function verifySignature($apiSecret, $data, $signature) {
        $expected = hash_hmac('sha256', $data, $apiSecret);
        return hash_equals($expected, $signature);
    }
}
?>
