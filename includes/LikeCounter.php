<?php
class LikeCounter {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function recordLike($userId, $pageUrl) {
        // Validate URL
        if (!filter_var($pageUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid page URL');
        }
        
        // Limit page URL length
        $pageUrl = substr($pageUrl, 0, 1000);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Optional: Prevent duplicate likes from same IP within 1 YEAR
        // Uncomment if you want to prevent spam
        
        $stmt = $this->db->prepare("
            SELECT id FROM likes 
            WHERE user_id = ? AND page_url = ? AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 YEAR)
            LIMIT 1
        ");
        
        $stmt->execute([$userId, $pageUrl, $ip]);
        
        if ($stmt->fetch()) {
            throw new Exception('You already liked this page');
        }
        
        
        $stmt = $this->db->prepare("
            INSERT INTO likes (user_id, page_url, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $pageUrl, $ip, substr($userAgent, 0, 500)]);
        
        return $this->db->lastInsertId();
    }
    
    public function getCount($userId, $pageUrl) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE user_id = ? AND page_url = ?
        ");
        
        $stmt->execute([$userId, $pageUrl]);
        $result = $stmt->fetch();
        
        return (int)$result['count'] ?? 0;
    }
}
?>
