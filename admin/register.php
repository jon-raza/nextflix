<?php
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);

    if($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if admin already exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if($stmt->rowCount() > 0) {
            $error = "Admin with this email or username already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$username, $email, $hashed_password, $full_name, $phone])) {
                $success = "Admin registration successful! Please login.";
                header("refresh:2;url=index.php");
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - Nextflix</title>
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
        
        .register-container {
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
        
        .register-card {
            background: rgba(0, 0, 0, 0.85);
            border-radius: 8px;
            padding: 60px 68px 40px;
            max-width: 500px;
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
        
        .btn-register {
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
        
        .btn-register:hover {
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
        
        .login-now {
            color: #737373;
            margin-top: 16px;
            font-size: 16px;
        }
        
        .login-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link:hover {
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
        
        .alert-success {
            background: #28a745;
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
            .register-card {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .register-container {
                background: linear-gradient(
                    rgba(0, 0, 0, 0.9), 
                    rgba(0, 0, 0, 0.9)
                ), url('https://cdn.mos.cms.futurecdn.net/rDJegQJaCyGaYysj2g5XWY.jpg');
            }
        }
        
        @media (max-width: 576px) {
            .register-card {
                padding: 30px 20px;
            }
            
            .netflix-logo {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <!-- Netflix Logo -->
                    <div class="text-center mb-4">
                        <a href="../index.php" class="netflix-logo text-decoration-none">
                            <i class="fas fa-film me-2"></i>NEXTFLIX
                        </a>
                    </div>

                    <!-- Register Card -->
                    <div class="register-card">
                        <h2 class="text-white mb-4 text-center">Admin Registration</h2>

                        <?php if($error): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($success): ?>
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" required 
                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                           placeholder="Enter full name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" required 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           placeholder="Choose username">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       placeholder="Enter email address">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password *</label>
                                    <div class="form-group">
                                        <input type="password" class="form-control with-icon" name="password" id="password" required 
                                               placeholder="Create password">
                                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <div class="form-group">
                                        <input type="password" class="form-control with-icon" name="confirm_password" id="confirm_password" required 
                                               placeholder="Confirm password">
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirmPasswordToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                       placeholder="Phone number">
                            </div>
                            
                            <div class="form-help">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to terms & conditions
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-register">
                                <i class="fas fa-user-plus me-2"></i>Create Admin Account
                            </button>
                        </form>

                        <div class="login-now text-center">
                            <span>Already have an account? </span>
                            <a href="index.php" class="login-link">Login here</a>.
                        </div>
                    </div>

                    <!-- Demo Info -->
                    <!-- <div class="demo-credentials">
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Admin Registration
                        </h6>
                        <div class="text-light">
                            <small>
                                <strong>Note:</strong> Admin accounts require special permissions. 
                                Contact system administrator for access approval.
                            </small>
                        </div>
                    </div> -->
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

        // Form validation
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (!agreeTerms) {
                e.preventDefault();
                alert('Please agree to the terms and conditions!');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        });

        // Prevent page reload issue
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>