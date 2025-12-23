<?php
// Simple instructions page that can be linked from your documentation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Like Button API - Full Instructions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1>Like Button API - Complete Instructions</h1>
            <p class="lead">Everything you need to know to add like buttons to your website</p>
            <a href="register.php" class="btn btn-primary btn-lg">Get Your Free API Key</a>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">🚀 Quick Start</h5>
                        <ol>
                            <li>Get your free API key</li>
                            <li>Add the widget script to your HTML</li>
                            <li>Add like-button elements</li>
                            <li>You're done!</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">🔧 Integration Methods</h5>
                        <ul>
                            <li>JavaScript Widget (easiest)</li>
                            <li>Direct API calls</li>
                            <li>PHP integration</li>
                            <li>WordPress plugin</li>
                            <li>React component</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">⭐ Features</h5>
                        <ul>
                            <li>Unlimited like buttons</li>
                            <li>Free forever</li>
                            <li>No rate limits</li>
                            <li>Automatic page tracking</li>
                            <li>Customizable design</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h4>Complete Step-by-Step Guide</h4>
                <div class="mt-4">
                    <h5>Step 1: Get Your API Key</h5>
                    <p>Visit <a href="register.php">register.php</a> and enter your email and website. You'll get your API key immediately.</p>
                    
                    <h5 class="mt-4">Step 2: Choose Your Integration Method</h5>
                    <p><strong>Option A: JavaScript Widget (Recommended)</strong></p>
                    <pre><code>&lt;script src="https://ilike.classicosdeleitura.com/widget.js?key=YOUR_API_KEY"&gt;&lt;/script&gt;
&lt;div class="like-button" data-page-url="current-page-url"&gt;&lt;/div&gt;</code></pre>
                    
                    <p><strong>Option B: Direct API Integration</strong></p>
                    <pre><code>// Get like count
fetch('https://ilike.classicosdeleitura.com/api.php?action=count&page_url=URL', {
    headers: { 'X-API-Key': 'YOUR_API_KEY' }
});

// Record a like
fetch('https://ilike.classicosdeleitura.com/api.php?action=like', {
    method: 'POST',
    headers: {
        'X-API-Key': 'YOUR_API_KEY',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ page_url: 'URL' })
});</code></pre>
                    
                    <h5 class="mt-4">Step 3: Customize (Optional)</h5>
                    <p>Customize the button appearance with CSS:</p>
                    <pre><code>.like-button button {
    /* Your custom styles */
}</code></pre>
                    
                    <h5 class="mt-4">Step 4: Test and Deploy</h5>
                    <p>Test on your website and deploy to production!</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="register.php" class="btn btn-success btn-lg">Get Started Free</a>
            <p class="mt-3">
                <small class="text-muted">No credit card required • No hidden fees • Free forever</small>
            </p>
        </div>
    </div>
</body>
</html>
