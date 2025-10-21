<?php
/**
 * Quick IP Check Tool
 * 
 * This simple script shows you what IP address you're connecting from.
 * Use this to know which IP to add to the remote server's ipAllowed.txt
 */

require_once __DIR__ . '/../login/auth_check.php';

/**
 * Get the client's real IP address
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    return $ip;
}

$myIP = getClientIP();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check My IP</title>
    <link rel="stylesheet" href="../styles/common.css">
    <style>
        .ip-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        .ip-card {
            background: var(--color-bg-white);
            border: 2px solid var(--color-border-light);
            border-radius: 12px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
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
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .instructions {
            text-align: left;
            margin-top: 30px;
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
    </style>
</head>
<body>
    <div class="ip-container">
        <div class="ip-card">
            <h1>🌐 Your IP Address</h1>
            <p>This is the IP address your local server is connecting from:</p>
            
            <div class="ip-display" id="ipAddress"><?php echo htmlspecialchars($myIP); ?></div>
            
            <button class="copy-btn" onclick="copyIP()">📋 Copy IP Address</button>
            
            <div class="instructions">
                <h3>📝 How to Use This IP</h3>
                <ol>
                    <li>Copy the IP address above</li>
                    <li>Log in to your <strong>remote server</strong> (wijs.ovh)</li>
                    <li>Open <code>login/ipAllowed.txt</code></li>
                    <li>Add this IP on a new line</li>
                    <li>Save the file</li>
                    <li>Return to the <a href="index.php">sync page</a> and try again</li>
                </ol>
                
                <p><strong>Note:</strong> If you're behind a proxy or NAT, the IP that reaches the remote server might be different. Check the remote server logs if the connection still fails.</p>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="index.php" style="text-decoration: none; color: var(--color-primary); font-weight: 600;">← Back to Sync Page</a>
            </div>
        </div>
    </div>
    
    <script>
        function copyIP() {
            const ipText = document.getElementById('ipAddress').textContent;
            navigator.clipboard.writeText(ipText).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copied!';
                btn.style.background = 'linear-gradient(135deg, var(--color-success) 0%, var(--color-success-dark) 100%)';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%)';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>
</html>

