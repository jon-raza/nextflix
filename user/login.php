<?php 
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {     
    session_start(); 
}

// ADD THIS LINE - Cache Control
header("Cache-Control: no-cache, no-store, must-revalidate");

require_once '../config/database.php'; 
require_once '../includes/functions.php';  

$page_title = "Login";  
$error = '';  

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {     
    header("Location: dashboard.php");     
    exit(); 
}  

if($_SERVER['REQUEST_METHOD'] == 'POST') {     
    $username = sanitize_input($_POST['username']);     
    $password = $_POST['password'];          

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");     
    $stmt->execute([$username, $username]);     
    $user = $stmt->fetch();          

    if($user && password_verify($password, $user['password'])) {         
        $_SESSION['user_id'] = $user['id'];         
        $_SESSION['username'] = $user['username'];         
        $_SESSION['full_name'] = $user['full_name'];         
        $_SESSION['email'] = $user['email'];                  
        header("Location: dashboard.php");         
        exit();     
    } else {         
        $error = "Invalid username/email or password!";     
    } 
} 
?>  

<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ADD THIS LINE -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    
    <title><?php echo $page_title; ?> - Nextflix</title>     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">     
    <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

    <style>
        :root {
            --netflix-red: #e50914;
            --netflix-black: #141414;
            --netflix-dark: #181818;
            --netflix-gray: #2F2F2F;
        }
        
        body {
            background: var(--netflix-black);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .login-container {
            background: linear-gradient(
                rgba(0, 0, 0, 0.8), 
                rgba(0, 0, 0, 0.8)
            ), url('https://cdn.mos.cms.futurecdn.net/rDJegQJaCyGaYysj2g5XWY.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            background: rgba(0, 0, 0, 0.85);
            border-radius: 8px;
            padding: 60px 68px 40px;
            max-width: 450px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            display: block;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .form-control {
            background: #333;
            border: none;
            border-radius: 4px;
            color: white;
            padding: 12px 15px;
            margin-bottom: 16px;
            font-size: 16px;
        }
        
        .form-control:focus {
            background: #454545;
            color: white;
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.3);
            border: none;
        }
        
        .form-label {
            color: #8c8c8c;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .btn-login {
            background: var(--netflix-red);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-weight: bold;
            margin-top: 24px;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #f40612;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
        }
        
        .form-help {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 13px;
            color: #b3b3b3;
        }
        
        .form-check-input {
            background: #737373;
            border: none;
        }
        
        .form-check-input:checked {
            background-color: var(--netflix-red);
            border-color: var(--netflix-red);
        }
        
        .signup-now {
            color: #737373;
            margin-top: 16px;
            font-size: 16px;
        }
        
        .signup-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .signup-link:hover {
            text-decoration: underline;
            color: white;
        }
        
        .demo-credentials {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid var(--netflix-red);
            backdrop-filter: blur(10px);
        }
        
        .alert-danger {
            background: #e87c03;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            font-size: 14px;
        }
        
        .text-warning {
            color: #e87c03 !important;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .form-group {
            position: relative;
        }

        .form-control.with-icon {
            padding-right: 45px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .login-card {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .login-container {
                background: linear-gradient(
                    rgba(0, 0, 0, 0.9), 
                    rgba(0, 0, 0, 0.9)
                ), url('https://cdn.mos.cms.futurecdn.net/rDJegQJaCyGaYysj2g5XWY.jpg');
            }
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .netflix-logo {
                font-size: 2rem;
            }
        }
    </style>
</head> 

<body>     
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <!-- Netflix Logo -->
                    <div class="text-center mb-4">
                        <a href="../index.php" class="netflix-logo text-decoration-none">
                            <i class="fas fa-film me-2"></i>NEXTFLIX
                        </a>
                    </div>

                    <!-- Login Card -->
                    <div class="login-card">
                        <h2 class="text-white mb-4 text-center">Sign In</h2>

                        <?php if($error): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- FORM CHANGED HERE - added autocomplete="off" -->
                        <form method="POST" action="" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <!-- INPUT CHANGED HERE - added autocomplete="off" -->
                                <input type="text" class="form-control" name="username" required autocomplete="off"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       placeholder="Enter username or email">
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label">Password</label>
                                <div class="form-group">
                                    <!-- INPUT CHANGED HERE - added autocomplete="off" -->
                                    <input type="password" class="form-control with-icon" name="password" id="password" required autocomplete="off"
                                           placeholder="Enter your password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-help">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                <a href="#" class="text-decoration-none text-light">Need help?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>

                        <div class="signup-now text-center">
                            <span>New to Nextflix? </span>
                            <a href="register.php" class="signup-link">Sign up now</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle functionality
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + 'ToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Form submission handler
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
        });

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]')?.focus();
        });

        // ADD THIS NEW SCRIPT - Prevent form cache on back button
        window.onpageshow = function(event) {
            if (event.persisted) {
                // Clear form fields when page is loaded from cache
                document.querySelector('form').reset();
                
                // Clear username field specifically
                var usernameField = document.querySelector('input[name="username"]');
                if (usernameField) {
                    usernameField.value = '';
                }
                
                // Clear password field
                var passwordField = document.querySelector('input[name="password"]');
                if (passwordField) {
                    passwordField.value = '';
                }
            }
        };

        // Clear form on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelector('form').reset();
            }, 100);
        });

        // Prevent page reload issue
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>