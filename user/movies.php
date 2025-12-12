<?php
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Manual auth check
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Movies";

// Search and filter functionality
$search = $_GET['search'] ?? '';

// Use same query as admin/movies.php for consistent ordering
$query = "SELECT m.*, a.username as created_by_admin 
          FROM movies m 
          LEFT JOIN admins a ON m.created_by = a.id 
          WHERE 1=1";
$params = [];

if(!empty($search)) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Same ordering as admin page
$query .= " ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll();

// Movie-specific thumbnails - Same as admin
$movie_thumbnails = [
    'The Dark Knight' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
    'Inception' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
    'The Shawshank Redemption' => 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg'
];

// Function to check if movie is in wishlist
function isInWishlist($movie_id, $user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$user_id, $movie_id]);
    return $stmt->rowCount() > 0;
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
        
        /* Wishlist Button Styles */
        .wishlist-btn {
            position: absolute;
            top: 10px;
            right: 50px;
            z-index: 3;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .wishlist-btn:hover {
            background: rgba(229, 9, 20, 0.9);
            transform: scale(1.1);
        }
        
        .wishlist-btn.added {
            color: #ff6b6b;
        }
        
        .wishlist-btn.added:hover {
            color: #ff4757;
        }

        /* Movie Card Styles - Full thumbnails */
        .movie-card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1.5rem;
            width: 100%;
            max-width: 350px; /* Fixed width for consistent sizing */
        }
        
        .movie-card:hover {
            transform: translateY(-8px);
            border-color: var(--netflix-red);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.25);
        }
        
        .movie-poster-container {
            height: 500px; /* Increased height for full thumbnails */
            overflow: hidden;
            position: relative;
        }
        
        /* Full thumbnails - no cropping */
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Changed from cover to contain for full image */
            transition: transform 0.3s ease;
            background: #1a1a1a; /* Dark background for transparency */
        }
        
        .movie-card:hover .movie-poster {
            transform: scale(1.03);
        }
        
        /* User-specific overlay (View Details) */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        
        .movie-card:hover .card-overlay {
            opacity: 1;
        }
        
        .featured-badge {
            background: linear-gradient(135deg, #ffd700, #ff6b00);
            color: #000;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 3;
        }
        
        .price-badge {
            background: var(--netflix-red);
            font-size: 0.85rem;
            font-weight: 700;
            padding: 6px 12px;
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 3;
        }
        
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .movie-title {
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .movie-description {
            color: var(--netflix-light);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 0.9rem;
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
        
        .badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 1rem;
        }
        
        .card-footer {
            background: var(--netflix-gray);
            border-top: 1px solid #444;
            padding: 1.25rem 1.5rem;
        }
        
        .btn-netflix {
            background: var(--netflix-red);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-netflix:hover {
            background: #f40612;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
            color: white;
        }

        /* Netflix Style Hero Banner */
        .netflix-hero {
            position: relative;
            height: 70vh;
            min-height: 500px;
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
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
            padding: 0 2rem;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            line-height: 1.1;
        }

        .hero-content p {
            font-size: 1.3rem;
            color: white;
            margin-bottom: 2rem;
            font-style: italic;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }

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

        .netflix-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }
        
        .netflix-card-header {
            background: linear-gradient(45deg, var(--netflix-red), #b81d24);
            border: none;
            border-radius: 15px 15px 0 0 !important;
            color: white;
            font-weight: bold;
            padding: 1.5rem !important;
        }

        /* Improved Grid System with Centering for single items in last row */
        .movies-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .movie-item {
            flex: 0 0 calc(33.333% - 1.5rem);
            display: flex;
            justify-content: center;
        }
        
        /* FIXED: Center second row with 2 movies */
        .movie-item:nth-child(4),
        .movie-item:nth-child(5) {
            flex: 0 0 calc(50% - 1.5rem);
            max-width: calc(50% - 1.5rem);
        }
        
        @media (max-width: 1199px) {
            .movie-item {
                flex: 0 0 calc(50% - 1.5rem);
            }
            
            .movie-item:nth-child(4),
            .movie-item:nth-child(5) {
                flex: 0 0 calc(50% - 1.5rem);
                max-width: calc(50% - 1.5rem);
            }
        }
        
        @media (max-width: 767px) {
            .movie-item {
                flex: 0 0 100%;
                max-width: 350px;
            }
            
            .movie-item:nth-child(4),
            .movie-item:nth-child(5) {
                flex: 0 0 100%;
                max-width: 350px;
            }
            
            .movie-card {
                max-width: 100%;
            }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--netflix-dark);
            border: 2px dashed var(--netflix-gray);
            border-radius: 16px;
            margin: 2rem 0;
            width: 100%;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
            color: var(--netflix-red);
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

        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .movie-poster-container {
                height: 400px;
            }
            
            .netflix-hero {
                height: 50vh;
                min-height: 400px;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .movie-title {
                font-size: 1.1rem;
            }
            
            .movie-description {
                font-size: 0.85rem;
                -webkit-line-clamp: 2;
            }
        }

        @media (max-width: 576px) {
            .movie-poster-container {
                height: 350px;
            }

            .netflix-hero {
                height: 40vh;
                min-height: 300px;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }
            
            body {
                padding-top: 60px;
            }
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
                        <a class="nav-link active" href="movies.php">
                            <i class="fas fa-film me-1"></i>Movies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-ticket-alt me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="wishlist.php">
                            <i class="fas fa-heart me-1"></i>Wishlist
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
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>My Wishlist</a></li>
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
                    <h1>Discover Amazing Movies</h1>
                    <p>Explore our collection of blockbuster hits and hidden gems</p>
                </div>
            </div>
        </div>

        <div class="container-fluid py-5">
            <!-- Search Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="netflix-card">
                        <div class="card-header netflix-card-header py-3">
                            <h4 class="mb-0"><i class="fas fa-search me-2"></i>Find Your Movie</h4>
                        </div>
                        <div class="card-body p-4">
                            <div class="search-container">
                                <form method="GET" action="">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search movies by title..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-netflix" style="width: auto; padding: 12px 20px;">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movies Grid with Centered Layout -->
            <div class="movies-container">
                <?php if($movies): ?>
                    <?php foreach($movies as $index => $movie): 
                        $avg_rating = calculate_average_rating($movie['id'], $pdo);
                        
                        // Use fixed thumbnails for specific movies, otherwise use database thumbnail
                        $poster_url = $movie['thumbnail_url'];
                        $movie_title = $movie['title'];
                        
                        // If this is one of our sample movies, use the fixed thumbnail
                        if (isset($movie_thumbnails[$movie_title])) {
                            $poster_url = $movie_thumbnails[$movie_title];
                        }
                        
                        // Fallback for other movies (Saiyara movie will use its original thumbnail)
                        if (empty($poster_url) || strpos($poster_url, 'example.com') !== false) {
                            $poster_url = "https://via.placeholder.com/300x450/2f2f2f/ffffff?text=" . urlencode($movie_title);
                        }

                        $is_in_wishlist = isInWishlist($movie['id'], $_SESSION['user_id'], $pdo);
                    ?>
                    <div class="movie-item">
                        <div class="movie-card">
                            <div class="position-relative movie-poster-container">
                                <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="movie-poster"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjQ1MCIgdmlld0JveD0iMCAwIDMwMCA0NTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iNDUwIiBmaWxsPSIjMmEyYTJhIi8+CjxwYXRoIGQ9Ik0xMjUgMTc1SDE3NVYyNzVIMTI1VjE3NVoiIGZpbGw9IiM0YTZhNmEiLz4KPHN2Zz4K';">
                                
                                <!-- Wishlist Button -->
                                <button class="wishlist-btn <?php echo $is_in_wishlist ? 'added' : ''; ?>" 
                                        onclick="toggleWishlist(<?php echo $movie['id']; ?>, this)">
                                    <i class="fas fa-heart"></i>
                                </button>
                                
                                <!-- User-specific overlay for View Details -->
                                <div class="card-overlay">
                                    <div class="overlay-content">
                                        <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-netflix" style="width: auto; padding: 10px 20px;">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                    </div>
                                </div>
                                
                                <?php if($movie['is_featured']): ?>
                                    <span class="featured-badge">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                <?php endif; ?>
                                
                                <span class="price-badge">
                                    $<?php echo $movie['price']; ?>
                                </span>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                <p class="movie-description">
                                    <?php echo substr(htmlspecialchars($movie['description']), 0, 120); ?>...
                                </p>
                                
                                <div class="badges-container">
                                    <span class="badge genre-badge"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                    <span class="badge year-badge"><?php echo $movie['release_year']; ?></span>
                                    <span class="badge duration-badge"><?php echo htmlspecialchars($movie['duration']); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="rating-stars">
                                        <?php echo get_star_rating($avg_rating); ?>
                                        <small class="text-muted ms-1">(<?php echo $avg_rating; ?>)</small>
                                    </div>
                                    <small class="text-muted">
                                        <?php 
                                        $reviews_count = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE movie_id = ?");
                                        $reviews_count->execute([$movie['id']]);
                                        echo $reviews_count->fetchColumn() . ' reviews';
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="d-grid">
                                    <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-netflix">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-film"></i>
                        <h3 class="text-light mb-3">No Movies Found</h3>
                        <p class="text-muted mb-4">Try adjusting your search criteria</p>
                        <a href="movies.php" class="btn btn-netflix" style="width: auto; padding: 10px 20px;">
                            <i class="fas fa-redo me-2"></i>View All Movies
                        </a>
                    </div>
                <?php endif; ?>
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
                    <p class="">Your ultimate movie booking experience. Book, watch, and enjoy your favorite movies with ease.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-light mb-3">Quick Links</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">Home</a>
                        <a href="movies.php">Movies</a>
                        <a href="bookings.php">My Bookings</a>
                        <a href="wishlist.php">My Wishlist</a>
                        <a href="profile.php">Profile</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-light mb-3">Contact Info</h6>
                    <div class="">
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
            <div class="footer-bottom text-center">
                <p class="mb-0">&copy; 2024 Nextflix. All rights reserved. | Made with <i class="fas fa-heart text-danger"></i> for movie lovers</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced image error handling
        document.addEventListener('DOMContentLoaded', function() {
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
            });
        });

        // Wishlist functionality
        function toggleWishlist(movieId, button) {
            fetch(`toggle-wishlist.php?movie_id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'added') {
                            button.classList.add('added');
                            button.innerHTML = '<i class="fas fa-heart"></i>';
                            showToast('Added to wishlist!', 'success');
                        } else {
                            button.classList.remove('added');
                            button.innerHTML = '<i class="fas fa-heart"></i>';
                            showToast('Removed from wishlist!', 'info');
                        }
                    } else {
                        showToast('Operation failed!', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error!', 'error');
                });
        }

        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0 position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it hides
            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toast);
            });
        }
    </script>
</body>
</html>