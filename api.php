<?php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, return JSON instead

// Set CORS headers FIRST - Allow ANY domain to access this API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: false');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start output buffering to catch any stray output
ob_start();

try {
    // Check if includes directory exists
    if (!is_dir(__DIR__ . '/includes')) {
        throw new Exception('Includes directory not found');
    }
    
    // Include database class
    require_once __DIR__ . '/includes/Database.php';
    
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Get action parameter
    $action = $_GET['action'] ?? '';
    
    // Get API key from headers or GET parameter
    $apiKey = '';
    
    // Check for X-API-Key header
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    } 
    // Check for Authorization header (Bearer token)
    elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $apiKey = substr($authHeader, 7);
        }
    }
    // Check for GET parameter
    elseif (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    }
    // Check for POST parameter
    elseif (isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    }
    
    // Clean API key
    $apiKey = trim($apiKey);
    
    // If no action specified, show API info
    if (empty($action)) {
        echo json_encode([
            'api_name' => 'Like Button API',
            'version' => '2.0',
            'status' => 'active',
            'pricing' => 'free',
            'base_url' => 'https://ilike.classicosdeleitura.com/api.php',
            'endpoints' => [
                'GET like count' => '/api.php?action=count&api_key=YOUR_KEY&page_url=URL',
                'POST record like' => '/api.php?action=like&api_key=YOUR_KEY&page_url=URL',
                'GET user stats' => '/api.php?action=stats&api_key=YOUR_KEY'
            ],
            'cors' => 'enabled',
            'rate_limit' => '100 requests per minute',
            'documentation' => 'https://ilike.classicosdeleitura.com/dashboard.php'
        ]);
        exit;
    }
    
    // Validate API key for actions that require it
    if (in_array($action, ['like', 'count', 'stats'])) {
        if (empty($apiKey)) {
            http_response_code(401);
            echo json_encode([
                'error' => 'API key required',
                'code' => 'MISSING_API_KEY',
                'hint' => 'Add ?api_key=your_key parameter or X-API-Key header'
            ]);
            exit;
        }
        
        // Check if API key exists in database
        $stmt = $db->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Invalid API key',
                'code' => 'INVALID_API_KEY',
                'hint' => 'Get a free API key at https://ilike.classicosdeleitura.com/register.php'
            ]);
            exit;
        }
        
        $userId = $user['id'];
    }
    
    // Handle different actions
    switch ($action) {
        case 'like':
            // Get page_url from GET, POST, or JSON
            $pageUrl = $_GET['page_url'] ?? $_POST['page_url'] ?? '';
            
            if (empty($pageUrl)) {
                // Try to get from JSON body
                $jsonInput = json_decode(file_get_contents('php://input'), true);
                $pageUrl = $jsonInput['page_url'] ?? '';
            }
            
            // Clean and validate URL
            $pageUrl = filter_var($pageUrl, FILTER_SANITIZE_URL);
            
            if (empty($pageUrl) || !filter_var($pageUrl, FILTER_VALIDATE_URL)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Valid page_url required',
                    'code' => 'MISSING_PAGE_URL',
                    'hint' => 'Add ?page_url=https://example.com parameter'
                ]);
                exit;
            }
            
            // Get IP and user agent
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Optional: Check for duplicate likes (same IP within 24 hours)
            $stmt = $db->prepare("
                SELECT id FROM likes 
                WHERE user_id = ? AND page_url = ? AND ip_address = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 YEAR)
                LIMIT 1
            ");
            $stmt->execute([$userId, $pageUrl, $ip]);
            
            $duplicate = $stmt->fetch();
            if ($duplicate) {
                // Return success but don't insert duplicate
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND page_url = ?");
                $stmt->execute([$userId, $pageUrl]);
                $result = $stmt->fetch();
                $count = $result['count'] ?? 0;
                
                echo json_encode([
                    'success' => true,
                    'already_liked' => true,
                    'total_likes' => $count,
                    'message' => 'You already liked this page'
                ]);
                exit;
            }
            
            // Insert the like
            $stmt = $db->prepare("
                INSERT INTO likes (user_id, page_url, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $pageUrl, $ip, substr($userAgent, 0, 500)]);
            $likeId = $db->lastInsertId();
            
            // Get updated count
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND page_url = ?");
            $stmt->execute([$userId, $pageUrl]);
            $result = $stmt->fetch();
            $count = $result['count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'like_id' => $likeId,
                'total_likes' => $count,
                'message' => 'Like recorded successfully'
            ]);
            break;
            
        case 'count':
            $pageUrl = filter_var($_GET['page_url'] ?? '', FILTER_SANITIZE_URL);
            
            if (empty($pageUrl) || !filter_var($pageUrl, FILTER_VALIDATE_URL)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Valid page_url required',
                    'code' => 'MISSING_PAGE_URL',
                    'hint' => 'Add ?page_url=https://example.com parameter'
                ]);
                exit;
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND page_url = ?");
            $stmt->execute([$userId, $pageUrl]);
            $result = $stmt->fetch();
            $count = $result['count'] ?? 0;
            
            echo json_encode([
                'page_url' => $pageUrl,
                'like_count' => (int)$count,
                'success' => true,
                'api_key' => substr($apiKey, 0, 8) . '...'
            ]);
            break;
            
        case 'stats':
            // Get total likes and pages for user
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_likes,
                    COUNT(DISTINCT page_url) as unique_pages
                FROM likes 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
            
            // Get top pages
            $stmt = $db->prepare("
                SELECT page_url, COUNT(*) as likes, MAX(created_at) as last_like
                FROM likes 
                WHERE user_id = ?
                GROUP BY page_url
                ORDER BY likes DESC, last_like DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $topPages = $stmt->fetchAll();
            
            echo json_encode([
                'user_id' => $userId,
                'stats' => [
                    'total_likes' => (int)($stats['total_likes'] ?? 0),
                    'unique_pages' => (int)($stats['unique_pages'] ?? 0)
                ],
                'top_pages' => $topPages,
                'success' => true
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid action',
                'code' => 'INVALID_ACTION',
                'valid_actions' => ['like', 'count', 'stats'],
                'hint' => 'Use ?action=count or ?action=like'
            ]);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Return JSON error
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR',
        'message' => 'Something went wrong. Please try again later.'
    ]);
}

// Clean any output buffers
if (ob_get_level() > 0) {
    ob_end_flush();
}
exit;
?>
