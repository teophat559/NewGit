<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - BVOTE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .not-found-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .not-found-icon {
            width: 80px;
            height: 80px;
            background: #ffa726;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }

        .not-found-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .not-found-message {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .not-found-url {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: monospace;
            color: #495057;
            margin-bottom: 30px;
            font-size: 14px;
            word-break: break-all;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .search-box {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .search-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .search-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #95a5a6;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .not-found-container {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="not-found-container">
        <div class="not-found-icon">🔍</div>

        <h1 class="not-found-title">Page Not Found</h1>

        <p class="not-found-message">
            The page you're looking for doesn't exist or has been moved.
        </p>

        <div class="not-found-url">
            <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/'); ?>
        </div>

        <div class="search-box">
            <h3>Looking for something specific?</h3>
            <input type="text" class="search-input" placeholder="Search BVOTE..." id="searchInput">
            <button class="search-btn" onclick="performSearch()">Search</button>
        </div>

        <div class="action-buttons">
            <a href="/" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="footer">
            <p>BVOTE Voting System</p>
            <p>If you believe this is an error, please contact support</p>
        </div>
    </div>

    <script>
        // Search functionality
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = '/search?q=' + encodeURIComponent(query);
            }
        }

        // Enter key support for search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Auto-focus search input
        document.getElementById('searchInput').focus();

        // Log 404 for analytics
        console.log('404 error occurred:', {
            url: window.location.href,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        });
    </script>
</body>
</html>
