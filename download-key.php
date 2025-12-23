<?php
session_start();
require_once 'includes/Database.php';

if (!isset($_SESSION['registration'])) {
    // Try to get from GET parameters
    if (isset($_GET['api_key'])) {
        $db = Database::getInstance()->getConnection();
        $apiKey = filter_var($_GET['api_key'], FILTER_SANITIZE_STRING);
        
        $stmt = $db->prepare("SELECT id, email, website, api_key FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        
        if ($user) {
            $credentials = [
                'api_key' => $user['api_key'],
                'email' => $user['email'],
                'website' => $user['website']
            ];
        } else {
            header('Location: register.php');
            exit;
        }
    } else {
        header('Location: register.php');
        exit;
    }
} else {
    $credentials = $_SESSION['registration'];
}

$type = $_GET['type'] ?? 'txt';

if ($type === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="like-button-api-credentials.json"');
    echo json_encode([
        'api_key' => $credentials['api_key'],
        'website' => $credentials['website'],
        'email' => $credentials['email'],
        'generated' => date('Y-m-d H:i:s'),
        'endpoint' => 'https://' . $_SERVER['HTTP_HOST'] . '/api.php',
        'widget_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/widget.js?key=' . $credentials['api_key']
    ], JSON_PRETTY_PRINT);
} else {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="like-button-api-key.txt"');
    echo "===========================================\n";
    echo "      LIKE BUTTON API CREDENTIALS\n";
    echo "===========================================\n\n";
    echo "API Key: " . $credentials['api_key'] . "\n";
    echo "Website: " . $credentials['website'] . "\n";
    echo "Email: " . ($credentials['email'] ?? 'Not provided') . "\n\n";
    echo "API Endpoint: https://" . $_SERVER['HTTP_HOST'] . "/api.php\n";
    echo "Widget URL: https://" . $_SERVER['HTTP_HOST'] . "/widget.js?key=" . $credentials['api_key'] . "\n\n";
    echo "===========================================\n";
    echo "USAGE EXAMPLES:\n";
    echo "===========================================\n\n";
    echo "1. JavaScript Widget (Easiest):\n";
    echo "   <script src=\"https://" . $_SERVER['HTTP_HOST'] . "/widget.js?key=" . $credentials['api_key'] . "\"></script>\n";
    echo "   <div class=\"like-button\" data-page-url=\"YOUR_PAGE_URL\"></div>\n\n";
    echo "2. Record a like via API:\n";
    echo "   POST https://" . $_SERVER['HTTP_HOST'] . "/api.php?action=like\n";
    echo "   Header: X-API-Key: " . $credentials['api_key'] . "\n";
    echo "   Body: {\"page_url\": \"YOUR_PAGE_URL\"}\n\n";
    echo "3. Get like count:\n";
    echo "   GET https://" . $_SERVER['HTTP_HOST'] . "/api.php?action=count&page_url=YOUR_PAGE_URL\n";
    echo "   Header: X-API-Key: " . $credentials['api_key'] . "\n\n";
    echo "===========================================\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "Support: https://buymeacoffee.com/offm\n";
    echo "Dashboard: https://" . $_SERVER['HTTP_HOST'] . "/dashboard.php\n";
    echo "===========================================\n";
}
exit;
?>
