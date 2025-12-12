<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Admin Profile";

// Get current admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(!empty($new_password)) {
        // Password change requested
        if(!password_verify($current_password, $admin['password'])) {
            $error = "Current password is incorrect!";
        } elseif($new_password !== $confirm_password) {
            $error = "New passwords do not match!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if($stmt->execute([$full_name, $hashed_password, $_SESSION['admin_id']])) {
                $_SESSION['admin_full_name'] = $full_name;
                $success = "Profile and password updated successfully!";
            } else {
                $error = "Failed to update profile!";
            }
        }
    } else {
        // Only update profile info
        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if($stmt->execute([$full_name, $_SESSION['admin_id']])) {
            $_SESSION['admin_full_name'] = $full_name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile!";
        }
    }
    
    // Refresh admin data
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
}

// Get admin stats
$movies_count = $pdo->query("SELECT COUNT(*) FROM movies WHERE created_by = " . $_SESSION['admin_id'])->fetchColumn();
$featured_count = $pdo->query("SELECT COUNT(*) FROM movies WHERE created_by = " . $_SESSION['admin_id'] . " AND is_featured = TRUE")->fetchColumn();
$member_since = date('M j, Y', strtotime($admin['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Nextflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --netflix-red: #e50914;
            --netflix-black: #141414;
            --netflix-dark: #181818;
            --netflix-gray: #2F2F2F;
            --netflix-light: #B3B3B3;
            --netflix-white: #ffffff;
        }
        
        body {
            background: var(--netflix-black);
            color: var(--netflix-white);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: var(--netflix-dark);
            min-height: 100vh;
            border-right: 1px solid var(--netflix-gray);
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: var(--netflix-light);
            padding: 15px 20px;
            margin: 2px 0;
            border-radius: 0;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--netflix-gray);
            color: var(--netflix-white);
            border-left: 4px solid var(--netflix-red);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            background: var(--netflix-black);
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px;
        }
        
        .navbar {
            background: var(--netflix-dark) !important;
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1rem 2rem;
        }
        
        .card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            color: var(--netflix-white);
        }
        
        .card-header {
            background: var(--netflix-gray) !important;
            border-bottom: 1px solid var(--netflix-gray);
            color: var(--netflix-white);
            padding: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
            color: var(--netflix-white);
        }
        
        .form-control {
            background: #333;
            border: 1px solid #444;
            color: var(--netflix-white);
            border-radius: 6px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: #454545;
            color: var(--netflix-white);
            border-color: var(--netflix-red);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .form-control:read-only {
            background: #2a2a2a;
            border-color: #555;
            color: #aaa;
        }
        
        .form-label {
            color: var(--netflix-white);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-text {
            color: var(--netflix-light) !important;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background: var(--netflix-red);
            border: none;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 6px;
            transition: all 0.3s;
            color: var(--netflix-white);
        }
        
        .btn-primary:hover {
            background: #f40612;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
            color: var(--netflix-white);
        }
        
        .btn-outline-secondary {
            border-color: #666;
            color: #ccc;
            font-weight: 500;
            padding: 12px 25px;
            border-radius: 6px;
        }
        
        .btn-outline-secondary:hover {
            background: #666;
            border-color: #666;
            color: var(--netflix-white);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }
        
        .alert-danger {
            background: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--netflix-red);
            color: #ff6b6b;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        hr {
            border-color: var(--netflix-gray);
            opacity: 1;
        }
        
        .stats-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 10px;
            transition: all 0.3s;
            color: var(--netflix-white);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--netflix-white);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .stat-movies { color: #007bff; }
        .stat-featured { color: #ffd700; }
        .stat-member { color: #6f42c1; }
        
        .profile-section-title {
            color: var(--netflix-white);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--netflix-red);
            display: inline-block;
        }
        
        .password-section {
            background: var(--netflix-gray);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            color: var(--netflix-white);
        }
        
        /* Fix all text-muted classes to white */
        .text-muted {
            color: var(--netflix-light) !important;
        }
        
        /* Fix small text */
        small {
            color: var(--netflix-light) !important;
        }
        
        /* Fix strong tags */
        strong {
            color: var(--netflix-white) !important;
        }
        
        /* Fix badge text */
        .badge {
            color: var(--netflix-white) !important;
        }
        
        /* Fix placeholder text */
        ::placeholder {
            color: #888 !important;
        }
        
        /* Fix all headings */
        h1, h2, h3, h4, h5, h6 {
            color: var(--netflix-white) !important;
        }
        
        /* Fix navbar brand */
        .navbar-brand {
            color: var(--netflix-white) !important;
        }
        
        /* Fix card titles */
        .card-title {
            color: var(--netflix-white) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <div class="text-center mb-4">
                        <div class="netflix-logo mb-2">
                            <i class="fas fa-film me-2"></i>NEXTFLIX
                        </div>
                        <small class="text-light">Admin Panel</small>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="fas fa-circle me-1"></i>
                                Welcome, <?php echo $_SESSION['admin_full_name']; ?>
                            </small>
                        </div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="movies.php">
                                <i class="fas fa-film me-2"></i>Movies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
    <a class="nav-link" href="bookings.php">
        <i class="fas fa-ticket-alt me-2"></i>Bookings
    </a>
</li>
</li>
<li class="nav-item">
                            <a class="nav-link" href="wishlist-stats.php">
                                <i class="fas fa-heart me-2"></i>Wishlist Stats
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
  
                        <li class="nav-item">
    <a class="nav-link" href="revenue.php">
        <i class="fas fa-chart-line me-2"></i>Revenue
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="user-revenue.php">
        <i class="fas fa-user-check me-2"></i>User Revenue
    </a>
</li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content p-0">
                <!-- Header -->
                <nav class="navbar navbar-light">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">
                            <i class="fas fa-user me-2" style="color: var(--netflix-red);"></i>Admin Profile
                        </span>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <!-- Profile Edit Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-edit me-2" style="color: var(--netflix-red);"></i>Edit Profile
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if($success): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <h6 class="profile-section-title">
                                            <i class="fas fa-user-circle me-2"></i>Basic Information
                                        </h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name *</label>
                                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                                                <div class="form-text">Username cannot be changed</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" readonly>
                                            <div class="form-text">Email cannot be changed</div>
                                        </div>

                                        <!-- Password Change Section -->
                                        <div class="password-section">
                                            <h6 class="profile-section-title mb-3">
                                                <i class="fas fa-key me-2"></i>Change Password
                                            </h6>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" name="current_password" placeholder="Leave blank to keep current password">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control" name="new_password" placeholder="Enter new password">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password">
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Admin Stats -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2" style="color: var(--netflix-red);"></i>Admin Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4 mb-3">
                                            <div class="stats-card p-4">
                                                <div class="stat-number stat-movies"><?php echo $movies_count; ?></div>
                                                <div class="stat-label">Movies Added</div>
                                                <small>Total movies you've added</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="stats-card p-4">
                                                <div class="stat-number stat-featured"><?php echo $featured_count; ?></div>
                                                <div class="stat-label">Featured Movies</div>
                                                <small>Movies marked as featured</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="stats-card p-4">
                                                <div class="stat-number stat-member"><?php echo $member_since; ?></div>
                                                <div class="stat-label">Member Since</div>
                                                <small>When you joined Nextflix</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Info -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="stats-card p-4">
                                                <h6 class="mb-3">
                                                    <i class="fas fa-info-circle me-2" style="color: var(--netflix-red);"></i>Account Information
                                                </h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <strong>Account Created:</strong>
                                                            <span class="ms-2"><?php echo format_date($admin['created_at']); ?></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>Last Updated:</strong>
                                                            <span class="ms-2"><?php echo format_date($admin['updated_at']); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <strong>Admin ID:</strong>
                                                            <span class="ms-2">#<?php echo $admin['id']; ?></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>Role:</strong>
                                                            <span class="badge bg-danger ms-2">Administrator</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            function validatePasswords() {
                if (newPassword.value && confirmPassword.value) {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.style.borderColor = '#dc3545';
                    } else {
                        confirmPassword.style.borderColor = '#28a745';
                    }
                } else {
                    confirmPassword.style.borderColor = '#444';
                }
            }
            
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        });
    </script>
</body>
</html>