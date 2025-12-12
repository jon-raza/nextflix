<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Manage Users";

// Handle user deletion
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete user's reviews
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt->execute([$delete_id]);
        
        // Delete user's bookings
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
        $stmt->execute([$delete_id]);
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "User deleted successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}

// Get all users with their stats
$stmt = $pdo->query("SELECT u.*, 
                     COUNT(DISTINCT b.id) as total_bookings,
                     COUNT(DISTINCT r.id) as total_reviews
                     FROM users u 
                     LEFT JOIN bookings b ON u.id = b.user_id 
                     LEFT JOIN reviews r ON u.id = r.user_id 
                     GROUP BY u.id 
                     ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();

// Success/Error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
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
            --netflix-black: #141414;
            --netflix-dark: #181818;
            --netflix-gray: #2F2F2F;
            --netflix-light: #B3B3B3;
        }
        
        body {
            background: var(--netflix-black);
            color: white;
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
            color: white;
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
        
        .user-card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .user-card:hover {
            transform: translateY(-8px);
            border-color: var(--netflix-red);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.25);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .profile-img-container {
            position: relative;
            width: 80px;
            height: 80px;
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 3px solid var(--netflix-gray);
            border-radius: 50%;
        }
        
        .profile-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            border: 3px solid var(--netflix-gray);
        }
        
        .card-title {
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }
        
        .username {
            color: var(--netflix-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: var(--netflix-light);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .stats-container {
            background: var(--netflix-gray);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.5rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--netflix-light);
        }
        
        .user-details {
            color: var(--netflix-light);
            font-size: 0.8rem;
            line-height: 1.6;
        }
        
        .user-details i {
            width: 16px;
            color: var(--netflix-red);
        }
        
        .card-footer {
            background: var(--netflix-gray);
            border-top: 1px solid #444;
            padding: 1.25rem 1.5rem;
        }
        
        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            font-weight: 600;
            padding: 8px 16px;
        }
        
        .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
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
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
            border-radius: 8px;
        }
        
        .modal-content {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            color: white;
            border-radius: 12px;
        }
        
        .modal-header {
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--netflix-gray);
            padding: 1.5rem;
        }
        
        .btn-close-white {
            filter: invert(1);
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .empty-state {
            background: var(--netflix-dark);
            border: 2px dashed var(--netflix-gray);
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .total-users-badge {
            background: var(--netflix-red);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .stat-bookings { color: #007bff; }
        .stat-reviews { color: #28a745; }
        .stat-days { color: #6f42c1; }
        
        .initials {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
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
                            <a class="nav-link active" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                         <li class="nav-item">
    <a class="nav-link" href="bookings.php">
        <i class="fas fa-ticket-alt me-2"></i>Bookings
    </a>
</li>
 <li class="nav-item">
                            <a class="nav-link" href="wishlist-stats.php">
                                <i class="fas fa-heart me-2"></i>Wishlist Stats
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
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
                        <span class="navbar-brand mb-0 h1 text-white">
                            <i class="fas fa-users me-2" style="color: var(--netflix-red);"></i>Manage Users
                        </span>
                        <span class="total-users-badge">
                            <i class="fas fa-users me-1"></i><?php echo count($users); ?> Users
                        </span>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
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

                    <!-- Users Grid -->
                    <div class="row">
                        <?php if($users): ?>
                            <?php foreach($users as $user): 
                                $member_days = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                $member_days = $member_days > 0 ? $member_days : 1;
                                $age = $user['date_of_birth'] ? date_diff(date_create($user['date_of_birth']), date_create('today'))->y : null;
                                
                                // Generate initials for placeholder
                                $initials = '';
                                $name_parts = explode(' ', $user['full_name']);
                                if (count($name_parts) >= 2) {
                                    $initials = strtoupper($name_parts[0][0] . $name_parts[1][0]);
                                } else {
                                    $initials = strtoupper(substr($user['full_name'], 0, 2));
                                }
                                
                                // Check if profile picture exists and is valid
                                $profile_picture = $user['profile_picture'];
                                $has_valid_image = false;
                                
                                if ($profile_picture && $profile_picture !== 'default.jpg') {
                                    $image_path = '../assets/images/' . $profile_picture;
                                    if (file_exists($image_path)) {
                                        $has_valid_image = true;
                                    }
                                }
                            ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card user-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="flex-shrink-0 profile-img-container">
                                                <?php if($has_valid_image): ?>
                                                    <img src="../assets/images/<?php echo htmlspecialchars($profile_picture); ?>" 
                                                         class="profile-img"
                                                         alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="profile-placeholder" style="display: none;">
                                                        <span class="initials"><?php echo $initials; ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="profile-placeholder">
                                                        <span class="initials"><?php echo $initials; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="card-title"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                                <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>

                                        <!-- User Stats -->
                                        <div class="stats-container">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <div class="stat-number stat-bookings"><?php echo $user['total_bookings']; ?></div>
                                                        <div class="stat-label">Bookings</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <div class="stat-number stat-reviews"><?php echo $user['total_reviews']; ?></div>
                                                        <div class="stat-label">Reviews</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <div class="stat-number stat-days"><?php echo $member_days; ?></div>
                                                        <div class="stat-label">Days</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- User Details -->
                                        <div class="user-details">
                                            <?php if($user['phone']): ?>
                                                <div class="mb-2">
                                                    <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if($user['date_of_birth'] && $age): ?>
                                                <div class="mb-2">
                                                    <i class="fas fa-birthday-cake me-2"></i>
                                                    <?php echo format_date($user['date_of_birth']); ?>
                                                    <span class="">(<?php echo $age; ?> years)</span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                Member since: <?php echo format_date($user['created_at']); ?>
                                            </div>
                                            
                                            <div>
                                                <i class="fas fa-clock me-2"></i>
                                                Last updated: <?php echo format_date($user['updated_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash me-1"></i>Delete User
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title text-white">Confirm Delete User</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-light">Are you sure you want to delete user "<strong class="text-white"><?php echo htmlspecialchars($user['full_name']); ?></strong>"?</p>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    This will also delete:
                                                    <ul class="mb-0 mt-2">
                                                        <li><?php echo $user['total_bookings']; ?> bookings</li>
                                                        <li><?php echo $user['total_reviews']; ?> reviews</li>
                                                    </ul>
                                                </div>
                                                <p class="text-danger"><small>This action cannot be undone.</small></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete User
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h3 class="text-white">No Users Found</h3>
                                    <p class="text-muted">No users have registered yet.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced image error handling
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.profile-img');
            
            images.forEach(img => {
                img.onerror = function() {
                    console.log('Profile image failed to load:', this.src);
                    this.style.display = 'none';
                    const placeholder = this.nextElementSibling;
                    if (placeholder && placeholder.classList.contains('profile-placeholder')) {
                        placeholder.style.display = 'flex';
                    }
                };
            });
        });
    </script>
</body>
</html>