<?php
session_start();
require_once 'includes/Database.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: register.php');
    exit;
}

// Check if user is coming from registration
if (!isset($_SESSION['registration'])) {
    // Show access form for returning users
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key'])) {
        $db = Database::getInstance()->getConnection();
        $apiKey = filter_var($_POST['api_key'], FILTER_SANITIZE_STRING);
        
        $stmt = $db->prepare("SELECT id, email, website, api_key FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['registration'] = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'website' => $user['website'],
                'api_key' => $user['api_key'],
                'existing_user' => true
            ];
        } else {
            $error = "Invalid API key";
        }
    }
    
    // If still not in session, show access form
    if (!isset($_SESSION['registration'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Your API Dashboard</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }
                .access-container { max-width: 500px; margin: 100px auto; }
                .card { 
                    border-radius: 15px; 
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    border: none;
                }
                .btn-access {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    font-weight: bold;
                    border-radius: 10px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="access-container">
                    <div class="card">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Access Your Dashboard</h2>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="api_key" class="form-label">Enter Your API Key</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="api_key" name="api_key" 
                                           placeholder="Your 64-character API key" required>
                                    <div class="form-text">
                                        Lost your API key? <a href="register.php">Register again</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-access w-100 py-3">
                                    Access Dashboard
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <a href="register.php" class="btn btn-outline-secondary">Get New API Key</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$credentials = $_SESSION['registration'];
$showSecret = isset($credentials['api_secret']) && !$credentials['existing_user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Like Button API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        body { 
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { 
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 1.5rem;
            font-weight: 600;
        }
        .api-key-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 15px 0;
            border-left: 4px solid #4299e1;
        }
        .badge-free {
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        .tab-content {
            padding: 25px;
            background: white;
            border-radius: 0 0 12px 12px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 25px;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: transparent;
        }
        .copy-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .instruction-step {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        .step-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 15px;
        }
        .donation-box {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid dashboard-container">
            <a class="navbar-brand" href="#">
                <span style="color: var(--primary); font-weight: bold;">Like Button API</span>
            </a>
            <div class="d-flex align-items-center">
                <span class="badge-free me-3">FREE</span>
                <a href="?logout" class="btn btn-outline-secondary btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2">Welcome to Your Dashboard</h1>
                <p class="text-muted">Your API key is ready to use</p>
            </div>
            <div>
                <a href="#donation" class="btn btn-warning">☕ Support Project</a>
            </div>
        </div>

        <!-- API Key Display -->
        <div class="api-key-box position-relative">
            <button class="copy-btn" onclick="copyToClipboard('<?= $credentials['api_key'] ?>')">
                📋 Copy
            </button>
            <h4 class="mb-3">Your API Key</h4>
            <div class="mb-3">
                <code style="background: rgba(255,255,255,0.2); padding: 10px 15px; border-radius: 5px; word-break: break-all;">
                    <?= htmlspecialchars($credentials['api_key']) ?>
                </code>
            </div>
            <?php if ($showSecret): ?>
            <div class="mt-4">
                <h5>API Secret (Save this now - won't be shown again)</h5>
                <div class="alert alert-warning mt-2">
                    <code style="word-break: break-all;"><?= htmlspecialchars($credentials['api_secret']) ?></code>
                </div>
                <p class="small">⚠️ This secret is only shown once. Save it securely.</p>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="download-key.php?type=txt" class="btn btn-light me-2">📥 Download TXT</a>
                <a href="download-key.php?type=json" class="btn btn-light">📥 Download JSON</a>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="instructions-tab" data-bs-toggle="tab" 
                        data-bs-target="#instructions" type="button">
                    📚 Instructions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="api-tab" data-bs-toggle="tab" 
                        data-bs-target="#api" type="button">
                    🔧 API Reference
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="widget-tab" data-bs-toggle="tab" 
                        data-bs-target="#widget" type="button">
                    ⚡ Widget
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="examples-tab" data-bs-toggle="tab" 
                        data-bs-target="#examples" type="button">
                    💡 Examples
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Instructions Tab -->
            <div class="tab-pane fade show active" id="instructions" role="tabpanel">
                <h4 class="mb-4">Quick Start Guide</h4>
                
                <div class="instruction-step">
                    <div class="d-flex align-items-center mb-3">
                        <span class="step-number">1</span>
                        <h5 class="mb-0">Copy Your API Key</h5>
                    </div>
                    <p>Copy the API key above. This is your unique identifier.</p>
                </div>

                <div class="instruction-step">
                    <div class="d-flex align-items-center mb-3">
                        <span class="step-number">2</span>
                        <h5 class="mb-0">Choose Integration Method</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6>⚡ Easy Method (Recommended)</h6>
                                    <p>Use our JavaScript widget for instant integration.</p>
                                    <a href="#widget" class="btn btn-sm btn-primary">See Widget</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6>🔧 API Method</h6>
                                    <p>Direct API calls for custom implementations.</p>
                                    <a href="#api" class="btn btn-sm btn-primary">See API Docs</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="d-flex align-items-center mb-3">
                        <span class="step-number">3</span>
                        <h5 class="mb-0">Add to Your Website</h5>
                    </div>
                    <p>Copy the code from the Widget or API section and paste it into your HTML.</p>
                </div>

                <div class="instruction-step">
                    <div class="d-flex align-items-center mb-3">
                        <span class="step-number">4</span>
                        <h5 class="mb-0">Test Your Integration</h5>
                    </div>
                    <p>Visit your website and test the like button. Likes should be recorded immediately.</p>
                </div>

                <div class="alert alert-info mt-4">
                    <h6>💡 Pro Tips</h6>
                    <ul class="mb-0">
                        <li>Each page URL should be unique for tracking</li>
                        <li>The widget automatically detects the current page URL</li>
                        <li>You can have unlimited like buttons across unlimited pages</li>
                        <li>API rate limit: 100 requests per minute per IP</li>
                    </ul>
                </div>
            </div>

            <!-- API Reference Tab -->
            <div class="tab-pane fade" id="api" role="tabpanel">
                <h4 class="mb-4">API Reference</h4>
                <p>Base URL: <code>https://<?= $_SERVER['HTTP_HOST'] ?>/api.php</code></p>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Record a Like</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Endpoint:</strong> <code>POST /api.php?action=like</code></p>
                        <p><strong>Headers:</strong></p>
                        <div class="code-block">
X-API-Key: <?= htmlspecialchars($credentials['api_key']) ?>
Content-Type: application/json
                        </div>
                        <p><strong>Request Body:</strong></p>
                        <div class="code-block">
{
    "page_url": "https://yourwebsite.com/page1"
}
                        </div>
                        <p><strong>Response:</strong></p>
                        <div class="code-block">
{
    "success": true,
    "like_id": 123,
    "total_likes": 5,
    "message": "Like recorded successfully"
}
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="copyCode('api-like')">📋 Copy Example</button>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">2. Get Like Count</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Endpoint:</strong> <code>GET /api.php?action=count</code></p>
                        <p><strong>Headers:</strong></p>
                        <div class="code-block">
X-API-Key: <?= htmlspecialchars($credentials['api_key']) ?>
                        </div>
                        <p><strong>Parameters:</strong></p>
                        <div class="code-block">
page_url = https://yourwebsite.com/page1
                        </div>
                        <p><strong>Example URL:</strong></p>
                        <div class="code-block">
https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=count&page_url=https://yourwebsite.com/page1
                        </div>
                        <p><strong>Response:</strong></p>
                        <div class="code-block">
{
    "page_url": "https://yourwebsite.com/page1",
    "like_count": 5,
    "success": true
}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">3. Get Stats</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Endpoint:</strong> <code>GET /api.php?action=stats</code></p>
                        <p><strong>Headers:</strong></p>
                        <div class="code-block">
X-API-Key: <?= htmlspecialchars($credentials['api_key']) ?>
                        </div>
                        <p><strong>Response:</strong></p>
                        <div class="code-block">
{
    "stats": {
        "total_likes": 42,
        "unique_pages": 3
    },
    "recent_pages": [
        {
            "page_url": "https://yourwebsite.com/page1",
            "likes": 15,
            "last_like": "2024-01-15 10:30:00"
        }
    ]
}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget Tab -->
            <div class="tab-pane fade" id="widget" role="tabpanel">
                <h4 class="mb-4">JavaScript Widget</h4>
                <p>The easiest way to add like buttons to your site. Just add this code:</p>
                
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Basic Integration</h5>
                    </div>
                    <div class="card-body">
                        <p>Add this code to your HTML where you want the like button:</p>
                        <div class="code-block">
&lt;!-- Like Button Widget --&gt;
&lt;script src="https://<?= $_SERVER['HTTP_HOST'] ?>/widget.js?key=<?= $credentials['api_key'] ?>"&gt;&lt;/script&gt;
&lt;div class="like-button" data-page-url="https://test.com"&gt;&lt;/div&gt;
                        </div>
                        
                        <h6 class="mt-4">Div Attributes you can add:</h6>
                        <div class="code-block">
&lt;div class="like-button" 
     data-page-url="current-page-url"
     data-button-text="Like this!"
     data-liked-text="You liked this!"
     data-show-count="true"
     data-position="left"&gt;
&lt;/div&gt;
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Custom Styling</h5>
                    </div>
                    <div class="card-body">
                        <p>Add custom CSS to style the button:</p>
                        <div class="code-block">
&lt;style&gt;
.like-button button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.like-button button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.like-button button.liked {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
&lt;/style&gt;
                        </div>
                    </div>
                </div>
            </div>

            <!-- Examples Tab -->
            <div class="tab-pane fade" id="examples" role="tabpanel">
                <h4 class="mb-4">Integration Examples</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5>PHP Example</h5>
                            </div>
                            <div class="card-body">
                                <div class="code-block">
&lt;?php
$apiKey = '<?= $credentials['api_key'] ?>';
$pageUrl = 'https://yourwebsite.com/page1';

// Get like count
$url = 'https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=count&page_url=' . urlencode($pageUrl);
$context = stream_context_create([
    'http' => [
        'header' => "X-API-Key: $apiKey\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

if ($data['success']) {
    echo "This page has " . $data['like_count'] . " likes";
}
?&gt;
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5>JavaScript Fetch Example</h5>
                            </div>
                            <div class="card-body">
                                <div class="code-block">
const apiKey = '<?= $credentials['api_key'] ?>';
const pageUrl = window.location.href;

// Get like count
fetch(`https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=count&page_url=${encodeURIComponent(pageUrl)}`, {
    headers: {
        'X-API-Key': apiKey
    }
})
.then(response => response.json())
.then(data => {
    document.getElementById('like-count').textContent = data.like_count;
});

// Record a like
document.getElementById('like-btn').addEventListener('click', () => {
    fetch('https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=like', {
        method: 'POST',
        headers: {
            'X-API-Key': apiKey,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ page_url: pageUrl })
    });
});
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>WordPress Integration</h5>
                    </div>
                    <div class="card-body">
                        <div class="code-block">
&lt;?php
// Add to functions.php or create a plugin
function add_like_button($content) {
    if (is_single()) {
        $api_key = 'YOUR_API_KEY_HERE';
        $page_url = get_permalink();
        
        $button = '
        &lt;div class="like-button-widget"&gt;
            &lt;script src="https://<?= $_SERVER['HTTP_HOST'] ?>/widget.js?key=' . $api_key . '"&gt;&lt;/script&gt;
            &lt;div class="like-button" data-page-url="' . $page_url . '"&gt;&lt;/div&gt;
        &lt;/div&gt;
        ';
        
        return $content . $button;
    }
    return $content;
}
add_filter('the_content', 'add_like_button');
?&gt;
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>React Component Example</h5>
                    </div>
                    <div class="card-body">
                        <div class="code-block">
import React, { useState, useEffect } from 'react';

function LikeButton({ apiKey, pageUrl }) {
    const [likes, setLikes] = useState(0);
    const [liked, setLiked] = useState(false);

    useEffect(() => {
        fetch(`https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=count&page_url=${encodeURIComponent(pageUrl)}`, {
            headers: { 'X-API-Key': apiKey }
        })
        .then(res => res.json())
        .then(data => setLikes(data.like_count));
    }, [apiKey, pageUrl]);

    const handleLike = () => {
        if (!liked) {
            fetch('https://<?= $_SERVER['HTTP_HOST'] ?>/api.php?action=like', {
                method: 'POST',
                headers: {
                    'X-API-Key': apiKey,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ page_url: pageUrl })
            })
            .then(() => {
                setLiked(true);
                setLikes(likes + 1);
            });
        }
    };

    return (
        &lt;button onClick={handleLike} className={`like-btn ${liked ? 'liked' : ''}`}&gt;
            {liked ? '❤️ Liked' : '👍 Like'} ({likes})
        &lt;/button&gt;
    );
}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donation Section -->
        <div id="donation" class="donation-box mt-5">
            <h4>❤️ Support This Project</h4>
            <p class="mb-3">This service is completely free to use. If you find it valuable, consider supporting its development.</p>
            
            <!-- Your Buy Me a Coffee Button -->
            <div class="mb-3">
    <script type="text/javascript" src="https://cdnjs.buymeacoffee.com/1.0.0/button.prod.min.js" data-name="bmc-button" data-slug="offm" data-color="#FFDD00" data-emoji="" data-font="Bree" data-text="Buy me a coffee" data-outline-color="#000000" data-font-color="#000000" data-coffee-color="#ffffff" ></script>
            </div>
            
            <p class="small text-muted mb-0">
                Your support helps keep this service free and running smoothly for everyone.
                Even $1 makes a difference!
            </p>
        </div>

        <!-- FAQ -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">❓ Frequently Asked Questions</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is this really free?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <strong>Yes, completely free!</strong> There are no hidden fees, no rate limits, and no premium tiers. 
                                The service is supported by optional donations from users who find it valuable.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I track likes for multiple pages?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Each unique page URL automatically gets its own like counter. 
                                Just use the same API key on all pages - the system tracks them separately.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What happens if I lose my API key?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You can always generate a new API key by registering again with the same email. 
                                However, your old likes will still be associated with your old API key, 
                                so it's best to save your API key when you first get it.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script>
        // Initialize syntax highlighting
        hljs.highlightAll();
        
        // Copy API key to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('API key copied to clipboard!');
            });
        }
        
        // Copy code examples
        function copyCode(elementId) {
            const code = document.getElementById(elementId)?.textContent;
            if (code) {
                navigator.clipboard.writeText(code);
                alert('Code copied to clipboard!');
            }
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-expand code blocks on click
        document.querySelectorAll('.code-block').forEach(block => {
            block.addEventListener('click', function() {
                this.classList.toggle('expanded');
                if (this.classList.contains('expanded')) {
                    this.style.maxHeight = 'none';
                } else {
                    this.style.maxHeight = '200px';
                }
            });
        });
    </script>
</body>
</html>
<?php
// Clear secret from session if shown
if ($showSecret) {
    unset($_SESSION['registration']['api_secret']);
    $_SESSION['registration']['existing_user'] = true;
}
?>
