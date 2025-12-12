<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Manage Movies";

// Handle movie deletion
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
    if($stmt->execute([$delete_id])) {
        $_SESSION['success'] = "Movie deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete movie!";
    }
    header("Location: movies.php");
    exit();
}

// Movie-specific thumbnails - Saari movies ke liye fixed thumbnails
$movie_thumbnails = [
    'The Dark Knight' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
    'Inception' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
    'The Shawshank Redemption' => 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg'
];

// Get all movies
$stmt = $pdo->query("SELECT m.*, a.username as created_by_admin 
                     FROM movies m 
                     LEFT JOIN admins a ON m.created_by = a.id 
                     ORDER BY m.created_at DESC");
$movies = $stmt->fetchAll();

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
        
        /* Sidebar Styles - Mobile Responsive */
        .sidebar {
            background: var(--netflix-dark);
            min-height: 100vh;
            border-right: 1px solid var(--netflix-gray);
            position: fixed;
            width: 250px;
            z-index: 1000;
            transition: transform 0.3s ease;
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
            transition: margin-left 0.3s ease;
        }
        
        /* Mobile Header */
        .mobile-header {
            display: none;
            background: var(--netflix-dark);
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }
        
        .mobile-menu-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 0.5rem;
        }
        
        .navbar {
            background: var(--netflix-dark) !important;
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1rem 2rem;
        }
        
        /* Movie Card Styles - Mobile Responsive */
        .movie-card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1.5rem;
        }
        
        .movie-card:hover {
            transform: translateY(-8px);
            border-color: var(--netflix-red);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.25);
        }
        
        .movie-poster-container {
            height: 400px;
            overflow: hidden;
            position: relative;
        }
        
        /* Mobile Poster Height */
        @media (max-width: 768px) {
            .movie-poster-container {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
            .movie-poster-container {
                height: 250px;
            }
        }
        
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .movie-card:hover .movie-poster {
            transform: scale(1.05);
        }
        
        .poster-placeholder {
            background: linear-gradient(45deg, #2a2a2a, #3a3a3a);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            flex-direction: column;
        }
        
        .featured-badge {
            background: linear-gradient(135deg, #ffd700, #ff6b00);
            color: #000;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .price-badge {
            background: var(--netflix-red);
            font-size: 0.85rem;
            font-weight: 700;
            padding: 6px 12px;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Mobile Card Body Padding */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }
        
        .card-title {
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        /* Mobile Title Size */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.1rem;
            }
        }
        
        .card-text {
            color: var(--netflix-light);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Mobile Description */
        @media (max-width: 768px) {
            .card-text {
                font-size: 0.85rem;
                -webkit-line-clamp: 2;
            }
        }
        
        .genre-badge {
            background: #404040;
            color: #ccc;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 8px;
            margin: 2px;
        }
        
        .year-badge {
            background: #0066cc;
            color: white;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 8px;
            margin: 2px;
        }
        
        .duration-badge {
            background: #666;
            color: white;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 8px;
            margin: 2px;
        }
        
        .star-rating {
            color: #ffd700;
            font-size: 0.9rem;
        }
        
        .card-footer {
            background: var(--netflix-gray);
            border-top: 1px solid #444;
            padding: 1.25rem 1.5rem;
        }
        
        /* Mobile Card Footer */
        @media (max-width: 768px) {
            .card-footer {
                padding: 1rem;
            }
        }
        
        .btn-outline-primary {
            border-color: var(--netflix-red);
            color: var(--netflix-red);
            font-weight: 600;
            padding: 8px 16px;
        }
        
        .btn-outline-primary:hover {
            background: var(--netflix-red);
            border-color: var(--netflix-red);
            color: white;
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
        
        /* Mobile Button Sizes */
        @media (max-width: 576px) {
            .btn-outline-primary,
            .btn-outline-danger {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
        }
        
        .btn-primary {
            background: var(--netflix-red);
            border: none;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 6px;
        }
        
        .btn-primary:hover {
            background: #f40612;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
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
        
        /* Mobile Logo */
        @media (max-width: 768px) {
            .netflix-logo {
                font-size: 1.3rem;
            }
        }
        
        .empty-state {
            background: var(--netflix-dark);
            border: 2px dashed var(--netflix-gray);
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .admin-info {
            color: var(--netflix-light);
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        
        .movie-stats {
            background: var(--netflix-gray);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
        
        .badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 1rem;
        }
        
        /* Mobile Grid System */
        @media (max-width: 1200px) {
            .col-xl-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        @media (max-width: 768px) {
            .col-xl-4,
            .col-lg-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
        /* Mobile Sidebar Handling */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .navbar {
                padding: 1rem;
            }
        }
        
        /* Mobile Main Content Padding */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                margin-top: 70px;
            }
            
            .container-fluid.mt-4 {
                margin-top: 1rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
                margin-top: 60px;
            }
        }
        
        /* Mobile Modal */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-header,
            .modal-footer {
                padding: 1rem;
            }
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="netflix-logo">
            <i class="fas fa-film me-2"></i>NEXTFLIX
        </div>
        <div style="width: 40px;"></div> <!-- Spacer for alignment -->
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-0" id="sidebar">
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
                            <a class="nav-link active" href="movies.php">
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
            <main class="col-md-9 col-lg-10 main-content p-0" id="mainContent">
                <!-- Header -->
                <nav class="navbar navbar-light">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1 text-white">
                            <i class="fas fa-film me-2" style="color: var(--netflix-red);"></i>Manage Movies
                            <small class="text-muted ms-2 fs-6">(<?php echo count($movies); ?> movies)</small>
                        </span>
                        <a href="add-movie.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Movie
                        </a>
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

                    <!-- Movies Grid -->
                    <div class="row">
                        <?php if($movies): ?>
                            <?php foreach($movies as $movie): 
                                $avg_rating = calculate_average_rating($movie['id'], $pdo);
                                
                                // Use fixed thumbnails for specific movies, otherwise use database thumbnail
                                $poster_url = $movie['thumbnail_url'];
                                $movie_title = $movie['title'];
                                
                                // If this is one of our sample movies, use the fixed thumbnail
                                if (isset($movie_thumbnails[$movie_title])) {
                                    $poster_url = $movie_thumbnails[$movie_title];
                                }
                                
                                // Fallback for other movies
                                if (empty($poster_url) || strpos($poster_url, 'example.com') !== false) {
                                    $poster_url = "https://via.placeholder.com/300x450/2f2f2f/ffffff?text=" . urlencode($movie_title);
                                }
                            ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card movie-card">
                                    <div class="position-relative movie-poster-container">
                                        <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                             class="movie-poster"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjQ1MCIgdmlld0JveD0iMCAwIDMwMCA0NTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iNDUwIiBmaWxsPSIjMmEyYTJhIi8+CjxwYXRoIGQ9Ik0xMjUgMTc1SDE3NVYyNzVIMTI1VjE3NVoiIGZpbGw9IiM0YTZhNmEiLz4KPHN2Zz4K';">
                                        <?php if($movie['is_featured']): ?>
                                            <span class="position-absolute top-0 start-0 featured-badge px-3 py-1 m-3">
                                                <i class="fas fa-star me-1"></i>Featured
                                            </span>
                                        <?php endif; ?>
                                        <div class="position-absolute top-0 end-0 m-3">
                                            <span class="badge price-badge">$<?php echo $movie['price']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                        <p class="card-text">
                                            <?php echo substr(htmlspecialchars($movie['description']), 0, 120); ?>...
                                        </p>
                                        
                                        <div class="badges-container">
                                            <span class="badge genre-badge"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                            <span class="badge year-badge"><?php echo $movie['release_year']; ?></span>
                                            <span class="badge duration-badge"><?php echo htmlspecialchars($movie['duration']); ?></span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="star-rating">
                                                <?php echo get_star_rating($avg_rating); ?>
                                                <small class="text-muted ms-1">(<?php echo $avg_rating; ?>)</small>
                                            </div>
                                        </div>

                                        <div class="movie-stats">
                                            <div class="admin-info">
                                                <i class="fas fa-user me-1"></i>Added by: <?php echo htmlspecialchars($movie['created_by_admin']); ?>
                                            </div>
                                            <div class="admin-info">
                                                <i class="fas fa-calendar me-1"></i>Created: <?php echo format_date($movie['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="edit-movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $movie['id']; ?>">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $movie['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title text-white">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-light">Are you sure you want to delete "<strong class="text-white"><?php echo htmlspecialchars($movie['title']); ?></strong>"?</p>
                                                <p class="text-danger"><small>This action cannot be undone.</small></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="movies.php?delete_id=<?php echo $movie['id']; ?>" class="btn btn-danger">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-film fa-4x text-muted mb-3"></i>
                                    <h3 class="text-white">No Movies Found</h3>
                                    <p class="text-muted">Get started by adding your first movie to the catalog.</p>
                                    <a href="add-movie.php" class="btn btn-primary btn-lg mt-3">
                                        <i class="fas fa-plus me-2"></i>Add New Movie
                                    </a>
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
        // Mobile sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');

            function toggleSidebar() {
                sidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            }

            mobileMenuBtn.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);

            // Enhanced image error handling
            const images = document.querySelectorAll('.movie-poster');
            
            images.forEach(img => {
                img.onerror = function() {
                    console.log('Image failed to load:', this.src);
                    // Show placeholder if image fails to load
                    this.style.display = 'none';
                    const placeholder = document.createElement('div');
                    placeholder.className = 'poster-placeholder';
                    placeholder.innerHTML = `
                        <i class="fas fa-film fa-3x mb-3 text-muted"></i>
                        <span class="text-muted">Poster Not Available</span>
                    `;
                    this.parentElement.appendChild(placeholder);
                };
                
                // Check if image loads successfully
                img.onload = function() {
                    console.log('Image loaded successfully:', this.src);
                };
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>