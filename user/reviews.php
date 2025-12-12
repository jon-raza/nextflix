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

$page_title = "My Reviews";

// Get user's reviews
$stmt = $pdo->prepare("SELECT r.*, m.title, m.thumbnail_url, m.genre 
                      FROM reviews r 
                      JOIN movies m ON r.movie_id = m.id 
                      WHERE r.user_id = ? 
                      ORDER BY r.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll();

// Sample posters for fallback
$sample_posters = [
    'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
    'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
    'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg'
];
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
            padding: 8px 16px;
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
            padding: 6px 12px;
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

        /* Review Card Styles */
        .review-card {
            background: var(--netflix-gray);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .review-card:hover {
            transform: translateY(-8px);
            border-color: var(--netflix-red);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.25);
        }
        
        .movie-poster {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        
        .review-content {
            padding: 1.5rem;
        }
        
        .movie-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
            line-height: 1.3;
        }
        
        .movie-genre {
            color: var(--netflix-light);
            font-size: 0.85rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .review-text {
            color: var(--netflix-light);
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .review-date {
            color: #aaa;
            font-size: 0.8rem;
        }
        
        .updated-badge {
            background: rgba(255,215,0,0.2);
            color: #ffd700;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
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

        /* Section Title */
        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: white;
            border-left: 4px solid var(--netflix-red);
            padding-left: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .movie-poster {
                height: 150px;
            }
            
            .review-content {
                padding: 1rem;
            }
            
            .movie-title {
                font-size: 1.1rem;
            }
            
            .btn-netflix {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        /* Additional Styles */
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

        .review-count-badge {
            background: var(--netflix-red);
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
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
                        <a class="nav-link active" href="reviews.php">
                            <i class="fas fa-star me-1"></i>My Reviews
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
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="netflix-card">
                        <div class="netflix-card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-star me-2"></i>My Reviews</h4>
                            <?php if($reviews): ?>
                                <span class="review-count-badge">
                                    <i class="fas fa-list me-1"></i><?php echo count($reviews); ?> Reviews
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4">
                            <?php if($reviews): ?>
                                <div class="row">
                                    <?php foreach($reviews as $index => $review): 
                                        // Use movie thumbnail or fallback to sample posters
                                        $poster_url = $review['thumbnail_url'];
                                        if (empty($poster_url) || strpos($poster_url, 'example.com') !== false) {
                                            $poster_url = $sample_posters[$index % count($sample_posters)];
                                        }
                                    ?>
                                    <div class="col-xl-4 col-lg-6 mb-4">
                                        <div class="review-card">
                                            <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                                                 alt="<?php echo htmlspecialchars($review['title']); ?>" 
                                                 class="movie-poster"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjMmYyZjJmIi8+CjxwYXRoIGQ9Ik0xMjUgODVIMTc1VjExNUgxMjVWODVaIiBmaWxsPSIjNGE2YTZhIi8+Cjwvc3ZnPg=='">
                                            
                                            <div class="review-content">
                                                <h5 class="movie-title"><?php echo htmlspecialchars($review['title']); ?></h5>
                                                <p class="movie-genre"><?php echo htmlspecialchars($review['genre']); ?></p>
                                                
                                                <div class="rating-stars">
                                                    <?php echo get_star_rating($review['rating']); ?>
                                                    <span class="text-muted ms-2">(<?php echo $review['rating']; ?>/5)</span>
                                                </div>
                                                
                                                <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                                
                                                <div class="review-meta">
                                                    <div>
                                                        <small class="review-date">
                                                            <?php echo format_date($review['created_at']); ?>
                                                            <?php if($review['updated_at'] != $review['created_at']): ?>
                                                                <br><span class="updated-badge">Updated</span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                    <a href="movie-details.php?id=<?php echo $review['movie_id']; ?>" class="btn-outline-netflix">
                                                        <i class="fas fa-eye me-1"></i>View Movie
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <h3 class="text-white mb-3">No Reviews Yet</h3>
                                    <p class="text-muted mb-4">You haven't reviewed any movies yet.</p>
                                    <a href="movies.php" class="btn-netflix">
                                        <i class="fas fa-film me-2"></i>Browse Movies
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        <a href="reviews.php">My Reviews</a>
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
    </script>
</body>
</html>