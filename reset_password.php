<?php
// Password Reset Utility
// This script allows you to reset passwords for existing users

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    
    $username = trim($_POST['username'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    
    if ($username && $newPassword) {
        try {
            $conn = getDbConnection();
            
            // Check if user exists
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
                $stmt->bind_param('ss', $hashedPassword, $username);
                $stmt->execute();
                
                $success = "Password updated successfully for user: " . htmlspecialchars($username);
            } else {
                $error = "User not found: " . htmlspecialchars($username);
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please provide both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - LedgerLink Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-container {
            background: #ffffff;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #10b981, #8b5cf6);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo {
            font-size: 2.5rem;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
        
        .title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
        
        .reset-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }
        
        .reset-btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .reset-btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #6b7280;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }
        
        .back-link:hover {
            color: #3b82f6;
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #d1fae5;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #0369a1;
        }
        
        .password-requirements h4 {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .password-requirements ul {
            margin-left: 1.2rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="title">Reset Password</h1>
            <p class="subtitle">Update your account password securely</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user" style="margin-right: 0.5rem; color: #6b7280;"></i>
                    Username
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input"
                    required 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    placeholder="Enter your username"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock" style="margin-right: 0.5rem; color: #6b7280;"></i>
                    New Password
                </label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        required
                        placeholder="Enter new password"
                        minlength="6"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <div class="password-requirements">
                    <h4><i class="fas fa-info-circle"></i> Password Requirements:</h4>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Use a strong, unique password</li>
                        <li>Consider using letters, numbers, and symbols</li>
                    </ul>
                </div>
            </div>
            
            <button type="submit" class="reset-btn">
                <i class="fas fa-sync-alt" style="margin-right: 0.5rem;"></i>
                Reset Password
            </button>
        </form>
        
        <a href="public/login.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Login
        </a>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>