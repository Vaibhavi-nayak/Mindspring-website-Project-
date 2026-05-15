<?php
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple admin credentials (in production, use database with hashed passwords)
    $admin_username = 'admin';
    $admin_password = 'admin123'; // Change this!
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MindSpring Clinic</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_css.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-calm);
            padding: 2rem;
        }
        
        .admin-login-box {
            background: var(--white);
            padding: 3rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 450px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .admin-login-box {
                padding: 2rem;
                margin: 1rem;
            }

            .admin-login-header h1 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 576px) {
            .admin-login-box {
                padding: 1.5rem;
            }

            .admin-login-header h1 {
                font-size: 1.5rem;
            }

            .form-group input {
                padding: 0.75rem;
            }
        }
        
        .admin-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-login-header h1 {
            font-size: 2rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .admin-login-header p {
            color: var(--gray);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(186, 223, 219, 0.15);
        }
        
        .login-btn {
            width: 100%;
            background: var(--gradient-primary);
            color: var(--white);
            padding: 1.2rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--shadow-md);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-login-header">
                <h1>🔒 Admin Login</h1>
                <p>MindSpring Clinic Administration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="back-link">
                <a href="index.html">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>