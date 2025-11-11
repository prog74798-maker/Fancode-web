<?php
session_start();

// Vercel session configuration
ini_set('session.save_path', '/tmp');
ini_set('session.gc_probability', 1);

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: /api/index.php");
    exit();
}

// Include config
require_once 'config.php';

// Get base URL for Vercel
$currentUrl = "https://" . $_SERVER['HTTP_HOST'];
$playlistUrl = $currentUrl . "/api/playlist.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mac2m3u - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            min-height: 100vh;
            color: #e0e0e0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
        }

        .header h1 {
            color: #00d4ff;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .header p {
            color: #a0a0a0;
            font-size: 1.1em;
        }

        .user-info {
            background: rgba(34, 40, 49, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #00d4ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info .welcome {
            font-size: 1.2em;
        }

        .user-info .logout-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .user-info .logout-btn:hover {
            background: linear-gradient(45deg, #e74c3c, #d63031);
            transform: translateY(-2px);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background: rgba(34, 40, 49, 0.9);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(0, 212, 255, 0.3);
        }

        .card i {
            font-size: 3em;
            color: #00d4ff;
            margin-bottom: 20px;
        }

        .card h3 {
            color: #00d4ff;
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .card p {
            color: #a0a0a0;
            margin-bottom: 20px;
            line-height: 1.5;
            flex-grow: 1;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(45deg, #0077b6, #023e8a);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }

        .btn:hover {
            background: linear-gradient(45deg, #0096c7, #0353a4);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 150, 199, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .btn-secondary:hover {
            background: linear-gradient(45deg, #868e96, #5a6268);
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #34ce57, #26de81);
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: #a0a0a0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-connected {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-disconnected {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }

            .user-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .quick-actions {
                flex-direction: column;
            }

            .quick-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📺 mac2m3u Dashboard</h1>
            <p>Convert your Stalker Portal to M3U Playlist</p>
        </div>

        <div class="user-info">
            <div class="welcome">
                Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>! 
                <span style="color: #a0a0a0; font-size: 0.9em;">
                    (Logged in at <?php echo date('H:i', $_SESSION['login_time']); ?>)
                </span>
            </div>
            <a href="/api/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="grid">
            <div class="card">
                <i class="fas fa-cog"></i>
                <h3>Portal Configuration</h3>
                <p>Set up your Stalker Portal connection details including URL, MAC address, and device information.</p>
                <a href="/api/config.php" class="btn">
                    <i class="fas fa-sliders-h"></i> Configure Portal
                </a>
            </div>

            <div class="card">
                <i class="fas fa-filter"></i>
                <h3>Channel Filters</h3>
                <p>Select which channel groups to include in your playlist and customize your channel selection.</p>
                <a href="/api/filter.php" class="btn">
                    <i class="fas fa-check-circle"></i> Manage Filters
                </a>
            </div>

            <div class="card">
                <i class="fas fa-list"></i>
                <h3>Playlist Generator</h3>
                <p>Generate and download your M3U playlist for use with IPTV players like VLC, Tivimate, or IPTV Smarters.</p>
                <a href="/api/playlist.php" class="btn btn-success" target="_blank">
                    <i class="fas fa-download"></i> Get Playlist
                </a>
            </div>

            <div class="card">
                <i class="fas fa-satellite-dish"></i>
                <h3>Quick Actions</h3>
                <p>Test connection, refresh tokens, or manage your portal settings quickly.</p>
                
                <?php
                // Check if portal is configured
                $jsonFile = '/tmp/data/data.json';
                $isConfigured = file_exists($jsonFile) && filesize($jsonFile) > 0;
                ?>
                
                <div class="status-indicator <?= $isConfigured ? 'status-connected' : 'status-disconnected' ?>">
                    <i class="fas <?= $isConfigured ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                    <?= $isConfigured ? 'Portal Configured' : 'Not Configured' ?>
                </div>
                
                <div class="quick-actions">
                    <a href="/api/config.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <?php if ($isConfigured): ?>
                    <a href="/api/playlist.php?refresh=1" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Refresh
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>Coded with ❤️ by RKDYIPTV</strong></p>
            <p>Powered by Vercel • PHP Runtime • <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animations to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A') return;
                    const link = this.querySelector('a.btn');
                    if (link) {
                        link.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            link.style.transform = '';
                        }, 150);
                    }
                });
            });

            // Auto-refresh status every 30 seconds
            setInterval(() => {
                // You can add AJAX status checks here if needed
                console.log('Dashboard auto-refresh check');
            }, 30000);
        });
    </script>
</body>
</html>
