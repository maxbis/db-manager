<?php
/**
 * Session Management Page
 * 
 * Allows users to view and revoke their active remember-me tokens
 */

require_once 'remember_tokens.php';

session_start();

// Check if user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$message = '';
$messageType = '';

// Handle token revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'revoke_all') {
            $count = revokeAllUserTokens($username);
            $message = "Successfully revoked $count active session(s).";
            $messageType = 'success';
            
            // Clear the cookie if it exists
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            }
        } elseif ($_POST['action'] === 'cleanup') {
            $count = cleanupExpiredTokens();
            $message = "Cleaned up $count expired token(s).";
            $messageType = 'success';
        }
    }
}

// Get user's active tokens
$activeTokens = getUserTokens($username);
$currentTokenHash = isset($_COOKIE['remember_token']) ? hash('sha256', $_COOKIE['remember_token']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sessions - Database Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
            color: #262B40;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(6, 69, 127, 0.10);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #0474C4;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #8A9BA8;
            font-size: 14px;
        }
        
        .nav-links {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            padding: 8px 16px;
            background: #E8F2FF;
            color: #0474C4;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #0474C4;
            color: white;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #E8F5E9;
            border-left: 4px solid #2C444C;
            color: #2C444C;
        }
        
        .message.error {
            background: #FFEBEE;
            border-left: 4px solid #C44704;
            color: #C44704;
        }
        
        .content-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(6, 69, 127, 0.10);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .content-box h2 {
            color: #262B40;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #E0E8F0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #F5F8FC;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #0474C4;
        }
        
        .info-card .label {
            color: #8A9BA8;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .info-card .value {
            color: #262B40;
            font-size: 20px;
            font-weight: 600;
        }
        
        .token-list {
            margin-top: 20px;
        }
        
        .token-item {
            background: #F5F8FC;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #5379AE;
        }
        
        .token-item.current {
            border-left-color: #2C444C;
            background: #E8F5E9;
        }
        
        .token-item .token-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .token-item .token-badge {
            background: #2C444C;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .token-item .token-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            color: #2C444C;
            font-size: 13px;
        }
        
        .token-item .token-details div {
            display: flex;
            flex-direction: column;
        }
        
        .token-item .token-details .label {
            color: #8A9BA8;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #FFB3A6 0%, #F08F70 100%);
            color: #C44704;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #F08F70 0%, #C44704 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #E8ECF1;
            color: #262B40;
        }
        
        .btn-secondary:hover {
            background: #D1D8E0;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #8A9BA8;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Session Management</h1>
            <p>Manage your active "Remember Me" sessions across devices</p>
            <div class="nav-links">
                <a href="../table_data.php">← Back to Dashboard</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="content-box">
            <h2>Overview</h2>
            <div class="info-grid">
                <div class="info-card">
                    <div class="label">Username</div>
                    <div class="value"><?php echo htmlspecialchars($username); ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Active Sessions</div>
                    <div class="value"><?php echo count($activeTokens); ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Current Device</div>
                    <div class="value"><?php echo $currentTokenHash ? 'Remembered' : 'Session Only'; ?></div>
                </div>
            </div>
        </div>
        
        <div class="content-box">
            <h2>Active "Remember Me" Sessions</h2>
            
            <?php if (empty($activeTokens)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📱</div>
                    <p>No active "Remember Me" sessions found.</p>
                    <p style="font-size: 12px; margin-top: 10px;">Check "Remember me" on the login page to stay logged in for 90 days.</p>
                </div>
            <?php else: ?>
                <div class="token-list">
                    <?php foreach ($activeTokens as $token): ?>
                        <?php 
                        $isCurrent = ($token['token_hash'] === $currentTokenHash);
                        $createdDate = date('M d, Y H:i', $token['created']);
                        $expiryDate = date('M d, Y H:i', $token['expiry']);
                        $lastUsedDate = date('M d, Y H:i', $token['last_used']);
                        $daysRemaining = ceil(($token['expiry'] - time()) / (24 * 60 * 60));
                        ?>
                        <div class="token-item <?php echo $isCurrent ? 'current' : ''; ?>">
                            <div class="token-header">
                                <strong><?php echo $isCurrent ? '🟢 This Device' : '📱 Other Device'; ?></strong>
                                <?php if ($isCurrent): ?>
                                    <span class="token-badge">CURRENT SESSION</span>
                                <?php endif; ?>
                            </div>
                            <div class="token-details">
                                <div>
                                    <span class="label">Created</span>
                                    <span><?php echo htmlspecialchars($createdDate); ?></span>
                                </div>
                                <div>
                                    <span class="label">Last Used</span>
                                    <span><?php echo htmlspecialchars($lastUsedDate); ?></span>
                                </div>
                                <div>
                                    <span class="label">Expires</span>
                                    <span><?php echo htmlspecialchars($expiryDate); ?> (<?php echo $daysRemaining; ?> days)</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="actions">
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to revoke all active sessions? You will need to log in again on all devices.');">
                        <input type="hidden" name="action" value="revoke_all">
                        <button type="submit" class="btn btn-danger">
                            🚫 Revoke All Sessions
                        </button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cleanup">
                        <button type="submit" class="btn btn-secondary">
                            🧹 Cleanup Expired
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="content-box">
            <h2>ℹ️ About "Remember Me"</h2>
            <p style="color: #2C444C; line-height: 1.6; font-size: 14px;">
                When you check "Remember me" on the login page, a secure token is created and stored on your device. 
                This allows you to stay logged in for 90 days without entering your password again.
            </p>
            <br>
            <p style="color: #2C444C; line-height: 1.6; font-size: 14px;">
                <strong>Security Features:</strong>
            </p>
            <ul style="margin-left: 20px; margin-top: 10px; color: #2C444C; font-size: 14px; line-height: 1.8;">
                <li>Each device gets a unique token</li>
                <li>Tokens are hashed and cannot be reused if stolen</li>
                <li>Device fingerprinting prevents token theft</li>
                <li>Tokens expire after 90 days automatically</li>
                <li>You can revoke access anytime from this page</li>
            </ul>
        </div>
    </div>
</body>
</html>

