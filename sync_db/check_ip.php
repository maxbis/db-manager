<?php
/**
 * Quick IP Check Tool
 * 
 * This simple script shows you what IP address you're connecting from.
 * Use this to know which IP to add to the remote server's ipAllowed.txt
 */

require_once __DIR__ . '/../login/auth_check.php';

// Use getClientIP() function from auth_check.php (already included)
$myLocalIP = getClientIP();

// Get remote server URL from cookie or default
$remoteServerUrl = $_COOKIE['db_sync_remoteUrl'] ?? '';
$errorMessage = '';
$myPublicIP = '';

// If we have a remote URL, try to get IP from our remote server
if (!empty($remoteServerUrl)) {
    // Extract base URL and append our get_ip.php endpoint
    $parsedUrl = parse_url($remoteServerUrl);
    if ($parsedUrl && isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
        // Build URL to get_ip.php on remote server
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $baseUrl .= ':' . $parsedUrl['port'];
        }
        
        // Determine the path - replace api.php with get_ip.php
        if (isset($parsedUrl['path'])) {
            $path = dirname($parsedUrl['path']) . '/get_ip.php';
        } else {
            $path = '/db-manager/sync_db/get_ip.php';
        }
        
        $getIpUrl = $baseUrl . $path;
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'DB-Manager-IP-Check/1.0',
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($getIpUrl, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['success']) && $data['success'] && isset($data['ip'])) {
                    $myPublicIP = $data['ip'];
                } else {
                    $errorMessage = 'Invalid response from remote server';
                }
            } else {
                $errorMessage = 'Could not connect to remote server. Make sure get_ip.php is uploaded.';
            }
        } catch (Exception $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
        }
    }
}

// Determine which IP to show prominently
$isLocalhost = in_array($myLocalIP, ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1']);
$displayIP = $isLocalhost && $myPublicIP ? $myPublicIP : $myLocalIP;

// Page configuration for template
$pageConfig = [
    'id' => 'check_ip',
    'title' => 'Check My IP',
    'icon' => '🌐',
    'controls_html' => ''
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageConfig['icon']; ?> <?php echo htmlspecialchars($pageConfig['title']); ?></title>
    <link rel="stylesheet" href="../styles/common.css">
    <style>
        .ip-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .ip-card {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 20px;
            text-align: center;
        }
        .ip-display {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primary);
            background: var(--color-primary-pale);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            word-break: break-all;
        }
        .copy-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #06457F 0%, #04324D 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            border: 2px solid #06457F;
            box-shadow: 0 2px 8px rgba(6, 69, 127, 0.3);
        }
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 69, 127, 0.5);
        }
        .instructions {
            text-align: left;
            margin-top: 20px;
            padding: 20px;
            background: var(--color-bg-lighter);
            border-radius: 8px;
            font-size: 14px;
        }
        .instructions h3 {
            color: var(--color-primary);
            margin-top: 0;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: var(--color-bg-lighter);
            color: var(--color-primary);
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid var(--color-border-light);
            font-weight: 600;
        }
        .back-link:hover {
            background: var(--color-bg-white);
            border-color: var(--color-primary);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>

    <div class="ip-container">
        <div class="ip-card">
            <h2 style="margin-top: 0;">🌐 Your IP Address</h2>
            
            <?php if (empty($remoteServerUrl)): ?>
                <div style="background: var(--color-info-pale); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--color-info);">
                    <strong>ℹ️ Setup Required:</strong> Configure your remote server URL in the <a href="index.php">sync page</a> first, then return here to see your public IP as detected by your remote server.
                </div>
                
                <div class="ip-display" id="ipAddress"><?php echo htmlspecialchars($myLocalIP); ?></div>
                <p style="margin-top: 10px; color: var(--color-text-secondary);">Current local IP only (not public IP)</p>
            <?php elseif (!empty($errorMessage)): ?>
                <div style="background: var(--color-warning-pale); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--color-warning);">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                    <br><br>
                    <strong>Solution:</strong> Upload <code>sync_db/get_ip.php</code> to your remote server at the same location as <code>api.php</code>
                </div>
                
                <p style="margin: 15px 0;">Falling back to local IP:</p>
                <div class="ip-display" id="ipAddress"><?php echo htmlspecialchars($myLocalIP); ?></div>
            <?php elseif ($isLocalhost && $myPublicIP): ?>
                <div style="background: var(--color-warning-pale); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--color-warning);">
                    <strong>⚠️ Note:</strong> You're accessing from localhost. The remote server will see your <strong>public IP</strong>, not localhost.
                </div>
                
                <h3 style="color: var(--color-success); margin-bottom: 10px;">✅ Your Public IP (Add this to whitelist):</h3>
                <div class="ip-display" id="ipAddress"><?php echo htmlspecialchars($myPublicIP); ?></div>
                
                <button class="copy-btn" onclick="copyIP()">📋 Copy Public IP</button>
                
                <div style="margin-top: 20px; padding: 15px; background: #E8F5E9; border-radius: 8px; border-left: 4px solid var(--color-success);">
                    <p style="margin: 0; font-size: 13px;">
                        <strong>✓ IP detected by your remote server</strong><br>
                        <span style="color: var(--color-text-secondary);">
                            Server: <?php echo htmlspecialchars(parse_url($remoteServerUrl, PHP_URL_HOST)); ?><br>
                            Local IP: <code><?php echo htmlspecialchars($myLocalIP); ?></code> (localhost)<br>
                            Public IP: <code><?php echo htmlspecialchars($myPublicIP); ?></code> ← Use this one!
                        </span>
                    </p>
                </div>
            <?php else: ?>
                <h3 style="color: var(--color-success); margin-bottom: 10px;">✅ Your Public IP (detected by remote server):</h3>
                <div class="ip-display" id="ipAddress"><?php echo htmlspecialchars($displayIP); ?></div>
                <button class="copy-btn" onclick="copyIP()">📋 Copy IP Address</button>
                
                <div style="margin-top: 20px; padding: 15px; background: #E8F5E9; border-radius: 8px; border-left: 4px solid var(--color-success);">
                    <p style="margin: 0; font-size: 13px;">
                        <strong>✓ IP detected by your remote server</strong><br>
                        <span style="color: var(--color-text-secondary);">
                            Server: <?php echo htmlspecialchars(parse_url($remoteServerUrl, PHP_URL_HOST)); ?><br>
                            <?php if ($myPublicIP && $myPublicIP !== $myLocalIP): ?>
                            Local IP: <code><?php echo htmlspecialchars($myLocalIP); ?></code><br>
                            Public IP: <code><?php echo htmlspecialchars($myPublicIP); ?></code> ← Use this one!
                            <?php else: ?>
                            IP Address: <code><?php echo htmlspecialchars($displayIP); ?></code>
                            <?php endif; ?>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="instructions">
                <h3>📝 How to Use This IP</h3>
                <ol>
                    <li>Copy the IP address above (<?php echo htmlspecialchars($displayIP); ?>)</li>
                    <li>Log in to your <strong>remote server</strong> (e.g., wijs.ovh)</li>
                    <li>Open <code>login/ipAllowed.txt</code></li>
                    <li>Add this IP on a new line</li>
                    <li>Save the file</li>
                    <li>Return to the <a href="index.php">sync page</a> and try again</li>
                </ol>
                
                <p><strong>Important:</strong> Always use your <strong>public IP address</strong> for the whitelist, not localhost (127.0.0.1 or ::1).</p>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="index.php" class="back-link">← Back to Sync Page</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../templates/footer.php'; ?>
    
    <script>
        function copyIP() {
            const ipText = document.getElementById('ipAddress').textContent;
            navigator.clipboard.writeText(ipText).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copied!';
                btn.style.background = 'linear-gradient(135deg, #27AE60 0%, #229954 100%)';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(135deg, #06457F 0%, #04324D 100%)';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>
</html>

