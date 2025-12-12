<?php
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in FIRST - before any output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$page_title = "Profile";

$success = '';
$error = '';

// Initialize user data
$user = null;
$total_bookings = 0;
$total_reviews = 0;

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if(!$user) {
        // If user not found in database, logout
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Get account stats
    $bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $bookings_stmt->execute([$_SESSION['user_id']]);
    $total_bookings = $bookings_stmt->fetchColumn();

    $reviews_stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $reviews_stmt->execute([$_SESSION['user_id']]);
    $total_reviews = $reviews_stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Database error in profile.php: " . $e->getMessage());
    // Continue with default values
}

// Handle Profile Photo Upload - FIXED: Process this FIRST before other operations
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../assets/uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $file_name = $_FILES['profile_photo']['name'];
    $file_tmp = $_FILES['profile_photo']['tmp_name'];
    $file_size = $_FILES['profile_photo']['size'];
    $file_error = $_FILES['profile_photo']['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file
    if(in_array($file_ext, $allowed_types)) {
        if($file_size <= $max_size) {
            // Generate unique filename
            $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($file_tmp, $destination)) {
                // Delete old profile picture if it exists and is not default
                if($user['profile_picture'] && $user['profile_picture'] !== 'default.jpg') {
                    $old_file = $upload_dir . $user['profile_picture'];
                    if(file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                if($stmt->execute([$new_filename, $_SESSION['user_id']])) {
                    $success = "Profile photo updated successfully!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                } else {
                    $error = "Failed to update profile photo in database!";
                }
            } else {
                $error = "Failed to upload file!";
            }
        } else {
            $error = "File size too large! Maximum size is 2MB.";
        }
    } else {
        $error = "Invalid file type! Only JPG, JPEG, PNG, and GIF files are allowed.";
    }
}
// Handle Remove Profile Photo - FIXED: Process this separately
else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_photo'])) {
    if($user['profile_picture'] && $user['profile_picture'] !== 'default.jpg') {
        $upload_dir = '../assets/uploads/profiles/';
        $old_file = $upload_dir . $user['profile_picture'];
        
        // Delete old file
        if(file_exists($old_file)) {
            unlink($old_file);
        }
        
        // Update database to default
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = 'default.jpg', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if($stmt->execute([$_SESSION['user_id']])) {
            $success = "Profile photo removed successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } else {
            $error = "Failed to remove profile photo!";
        }
    }
}
// Handle Profile Information Update - FIXED: Only process if not a photo upload or removal
else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_name'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = $_POST['date_of_birth'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, date_of_birth = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        
        if($stmt->execute([$full_name, $phone, $date_of_birth, $_SESSION['user_id']])) {
            $_SESSION['full_name'] = $full_name;
            
            // Set success message
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
        } else {
            $error = "Failed to update profile!";
        }
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        $error = "Database error occurred while updating profile!";
    }
}

// Check for success message in session (if coming from other pages)
if(isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Function to get profile picture URL
function get_profile_picture_url($profile_picture) {
    if($profile_picture && $profile_picture !== 'default.jpg') {
        return '../assets/uploads/profiles/' . $profile_picture . '?v=' . time();
    } else {
        return '../assets/images/default-profile.jpg';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Nextflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

    <style>
        :root {
            --netflix-red: #e50914;
            --netflix-dark: #141414;
            --netflix-gray: #2f2f2f;
            --netflix-light: #f5f5f1;
        }
        
        body {
            background: var(--netflix-dark);
            color: white;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            padding-top: 70px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
        }
        
        .netflix-navbar {
            background: var(--netflix-dark) !important;
            padding: 0.8rem 0;
            border-bottom: 1px solid #333;
        }
        
        .netflix-brand {
            color: var(--netflix-red) !important;
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .nav-link {
            color: white !important;
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 1rem !important;
        }
        
        .nav-link:hover {
            color: var(--netflix-red) !important;
        }
        
        .netflix-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .netflix-card-header {
            background: linear-gradient(45deg, var(--netflix-red), #b81d24);
            border: none;
            border-radius: 15px 15px 0 0 !important;
            color: white;
            font-weight: bold;
            padding: 1.5rem !important;
        }
        
        .btn-netflix {
            background: var(--netflix-red);
            border: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-netflix:hover {
            background: #b81d24;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
            color: white;
        }
        
        .btn-outline-netflix {
            background: transparent;
            border: 2px solid var(--netflix-red);
            color: var(--netflix-red);
            font-weight: bold;
            padding: 8px 18px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-netflix:hover {
            background: var(--netflix-red);
            color: white;
            transform: translateY(-2px);
        }

        .btn-danger-netflix {
            background: #dc3545;
            border: none;
            color: white;
            font-weight: bold;
            padding: 8px 18px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-danger-netflix:hover {
            background: #c82333;
            transform: translateY(-2px);
            color: white;
        }

        /* Profile Photo Styles */
        .profile-photo-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem auto;
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--netflix-red);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
        }

        .profile-photo-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--netflix-red), #b81d24);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid var(--netflix-red);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
        }

        .profile-photo-icon {
            font-size: 3rem;
            color: white;
        }

        .photo-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--netflix-red);
            border: 2px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
        }

        .photo-upload-btn:hover {
            background: #b81d24;
            transform: scale(1.1);
        }

        .photo-upload-btn.uploading {
            background: #666;
            cursor: not-allowed;
        }

        .photo-remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dc3545;
            border: 2px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
            font-size: 0.8rem;
        }

        .photo-remove-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        /* Netflix Style Hero Banner */
        .netflix-hero {
            position: relative;
            height: 40vh;
            min-height: 300px;
            background: 
                linear-gradient(to top, var(--netflix-dark) 0%, transparent 50%),
                linear-gradient(to right, var(--netflix-dark) 0%, transparent 30%),
                url('https://cdn.mos.cms.futurecdn.net/rDJegQJaCyGaYysj2g5XWY.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            margin-bottom: 3rem;
            border: none !important;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
            padding: 0 2rem;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            line-height: 1.1;
        }

        .hero-content p {
            font-size: 1.1rem;
            color: white;
            margin-bottom: 2rem;
            font-style: italic;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }

        /* Profile Card Styles */
        .profile-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .stats-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: white;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        /* Form Styles */
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--netflix-red);
            color: white;
            box-shadow: 0 0 0 0.3rem rgba(229, 9, 20, 0.25);
        }
        
        .form-control:read-only {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: #aaa;
        }

        .form-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Alert Styles */
        .alert-success {
            background: linear-gradient(45deg, #198754, #20c997);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 1rem;
        }

        .alert-danger {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 1rem;
        }

        /* Modal Styles */
        .modal-content {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: white;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: var(--netflix-dark);
            border-radius: 15px 15px 0 0;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            background: var(--netflix-dark);
            border-radius: 0 0 15px 15px;
        }

        /* Footer Styles */
        .netflix-footer {
            background: var(--netflix-dark);
            border-top: 1px solid #333;
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer-brand {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .footer-links a {
            color: white !important;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            margin-bottom: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--netflix-red) !important;
        }

        .footer-bottom {
            border-top: 1px solid #333;
            padding-top: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .netflix-hero {
                height: 30vh;
                min-height: 200px;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }
            
            .btn-netflix {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .profile-photo-container {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 576px) {
            .netflix-hero {
                height: 25vh;
                min-height: 150px;
            }

            .hero-content h1 {
                font-size: 1.5rem;
            }

            .profile-photo-container {
                width: 100px;
                height: 100px;
            }
        }

        /* Additional text color fixes */
        .text-muted {
            color: #e0e0e0 !important;
        }

        .dropdown-menu {
            background: var(--netflix-gray);
            border: 1px solid #444;
        }

        .dropdown-item {
            color: white !important;
        }

        .dropdown-item:hover {
            background: var(--netflix-red);
            color: white !important;
        }

        .stats-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0.75rem 0;
        }

        .stats-item:last-child {
            border-bottom: none;
        }

        /* Stats Section */
        .stats-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--netflix-red);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #e0e0e0;
        }

        /* File Input Styling */
        .file-input {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Netflix Style Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark netflix-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand netflix-brand" href="dashboard.php">
                <i class="fas fa-film me-2"></i>NEXTFLIX
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">
                            <i class="fas fa-film me-1"></i>Movies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-ticket-alt me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="bookings.php"><i class="fas fa-ticket-alt me-2"></i>My Bookings</a></li>
                                <li><a class="dropdown-item" href="reviews.php"><i class="fas fa-star me-2"></i>My Reviews</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Netflix Style Hero Banner -->
        <div class="netflix-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Your Profile</h1>
                    <p>Manage your account settings and preferences</p>
                </div>
            </div>
        </div>

        <div class="container py-4">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="card-body text-center p-4">
                            <!-- Profile Photo Section -->
                            <div class="profile-photo-container">
                                <?php if($user['profile_picture'] && $user['profile_picture'] !== 'default.jpg'): ?>
                                    <img src="<?php echo get_profile_picture_url($user['profile_picture']); ?>" 
                                         alt="Profile Picture" 
                                         class="profile-photo"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="photo-remove-btn" title="Remove Photo" onclick="removeProfilePhoto()">
                                        <i class="fas fa-times"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="profile-photo-placeholder" 
                                     style="<?php echo ($user['profile_picture'] && $user['profile_picture'] !== 'default.jpg') ? 'display:none;' : ''; ?>">
                                    <i class="fas fa-user profile-photo-icon"></i>
                                </div>
                                <label for="profilePhotoInput" class="photo-upload-btn" title="Change Profile Photo" id="photoUploadBtn">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            
                            <h4 class="text-white"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-muted mb-3">
                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            
                            <!-- Stats Section -->
                            <div class="stats-section">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="stat-number"><?php echo $total_bookings; ?></div>
                                        <div class="stat-label">Bookings</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-number"><?php echo $total_reviews; ?></div>
                                        <div class="stat-label">Reviews</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-3">
                                <button class="btn btn-outline-netflix" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="netflix-card">
                        <div class="netflix-card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if($success): ?>
                                <div class="alert alert-success mb-4">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($error): ?>
                                <div class="alert alert-danger mb-4">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Profile Photo Upload Form -->
                            <form method="POST" action="" enctype="multipart/form-data" id="photoUploadForm">
                                <input type="file" name="profile_photo" id="profilePhotoInput" class="file-input" accept="image/*">
                            </form>

                            <!-- Remove Photo Form -->
                            <form method="POST" action="" id="removePhotoForm" style="display: none;">
                                <input type="hidden" name="remove_photo" value="1">
                            </form>
                            
                            <form method="POST" action="" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                               required
                                               minlength="2"
                                               maxlength="255">
                                        <small class="text-muted">Enter your full name</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" 
                                               readonly>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           readonly>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>"
                                               pattern="[0-9+\-\s()]{10,15}"
                                               title="Please enter a valid phone number">
                                        <small class="text-muted">Optional phone number</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                               value="<?php echo $user['date_of_birth']; ?>"
                                               max="<?php echo date('Y-m-d'); ?>">
                                        <small class="text-muted">Your date of birth</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo format_date($user['created_at']); ?>" 
                                           readonly>
                                </div>
                                
                                <button type="submit" class="btn btn-netflix">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-key me-2"></i>Change Password
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Password change functionality will be implemented here.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This feature is coming soon!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-netflix" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="netflix-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a href="dashboard.php" class="footer-brand">
                        <i class="fas fa-film me-2"></i>NEXTFLIX
                    </a>
                    <p class="text-muted">Your ultimate movie booking experience. Book, watch, and enjoy your favorite movies with ease.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-light mb-3">Quick Links</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">Home</a>
                        <a href="movies.php">Movies</a>
                        <a href="bookings.php">My Bookings</a>
                        <a href="profile.php">Profile</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-light mb-3">Contact Info</h6>
                    <div class="text-muted">
                        <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>123 Cinema St, Movie City</p>
                        <p class="mb-2"><i class="fas fa-phone me-2"></i>+1 234 567 8900</p>
                        <p class="mb-0"><i class="fas fa-envelope me-2"></i>info@nextflix.com</p>
                    </div>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6 class="text-light mb-3">Follow Us</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-light fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center text-muted">
                <p class="mb-0">&copy; 2024 Nextflix. All rights reserved. | Made with <i class="fas fa-heart text-danger"></i> for movie lovers</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple and effective solution to prevent duplicate uploads
        let isUploading = false;

        // Camera button click
        document.getElementById('photoUploadBtn').onclick = function() {
            if (!isUploading) {
                document.getElementById('profilePhotoInput').click();
            }
        };

        // File input change
        document.getElementById('profilePhotoInput').onchange = function() {
            if (isUploading) {
                this.value = '';
                return;
            }

            const file = this.files[0];
            if (!file) return;

            // Quick validation
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                this.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('File too large! Max 2MB allowed.');
                this.value = '';
                return;
            }

            // Set uploading flag
            isUploading = true;

            // Show loading state
            const btn = document.getElementById('photoUploadBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.pointerEvents = 'none';
            btn.classList.add('uploading');

            // Submit form
            document.getElementById('photoUploadForm').submit();
        };

        // Remove profile photo
        function removeProfilePhoto() {
            if (confirm('Are you sure you want to remove your profile photo?')) {
                document.getElementById('removePhotoForm').submit();
            }
        }

        // Reset uploading flag when page loads
        window.addEventListener('load', function() {
            isUploading = false;
        });
    </script>
</body>
</html>