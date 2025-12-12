<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nextflix - Your Ultimate Movie Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/Nextflixfavicon.png">

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
        
        /* Netflix Style Navigation */
        .netflix-navbar {
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%) !important;
            padding: 1rem 0;
            transition: background 0.3s;
            z-index: 1000;
        }
        
        .netflix-navbar.scrolled {
            background: var(--netflix-black) !important;
        }
        
        .netflix-brand {
            color: var(--netflix-red) !important;
            font-weight: bold;
            font-size: 1.8rem;
            text-decoration: none;
        }
        
        .nav-link {
            color: var(--netflix-white) !important;
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 1rem !important;
        }
        
        .nav-link:hover {
            color: var(--netflix-red) !important;
        }
        
        .btn-netflix {
            background: var(--netflix-red);
            border: none;
            color: var(--netflix-white);
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .btn-netflix:hover {
            background: #f40612;
            transform: translateY(-2px);
            color: var(--netflix-white);
        }
        
        /* Netflix Style Hero Section */
        .netflix-hero {
            position: relative;
            height: 100vh;
            min-height: 700px;
            background: 
                linear-gradient(to top, var(--netflix-black) 0%, transparent 50%),
                linear-gradient(to right, var(--netflix-black) 0%, transparent 30%),
                url('https://cdn.mos.cms.futurecdn.net/rDJegQJaCyGaYysj2g5XWY.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                to bottom,
                rgba(0,0,0,0.3) 0%,
                rgba(0,0,0,0.7) 50%,
                var(--netflix-black) 100%
            );
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 650px;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            line-height: 1.1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: var(--netflix-white);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }
        
        .hero-buttons .btn {
            padding: 12px 30px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 4px;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }
        
        .hero-stats {
            margin-top: 3rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--netflix-red);
            margin-bottom: 0.5rem;
        }
        
        .stat-item p {
            color: var(--netflix-light);
            font-size: 1rem;
            margin: 0;
        }
        
        /* EXACT MOVIE CARD STYLING FROM MOVIES.PHP */
        .movie-card {
            background: var(--netflix-black);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1.5rem;
            width: 100%;
            max-width: 350px;
        }
        
        .movie-card:hover {
            transform: translateY(-8px);
            border-color: var(--netflix-red);
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.25);
        }
        
        .movie-poster-container {
            height: 500px;
            overflow: hidden;
            position: relative;
        }
        
        /* Full thumbnails - no cropping - EXACT SAME AS MOVIES.PHP */
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
            background: #1a1a1a;
        }
        
        .movie-card:hover .movie-poster {
            transform: scale(1.03);
        }
        
        /* User-specific overlay (View Details) - EXACT SAME AS MOVIES.PHP */
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
        
        .trending-badge {
            background: linear-gradient(135deg, #ff6b00, #ffd700);
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
        
        .btn-netflix-card {
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
        
        .btn-netflix-card:hover {
            background: #f40612;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
            color: white;
        }

        /* Movies Grid - EXACT SAME AS USER/MOVIES.PHP */
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

        /* Movies Section */
        .section-padding {
            padding: 5rem 0;
        }
        
        .section-header {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--netflix-white);
            margin-bottom: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: var(--netflix-light);
        }
        
        /* Features Section */
        .feature-card {
            padding: 2rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--netflix-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: var(--netflix-white);
        }
        
        .feature-card h4 {
            color: var(--netflix-white);
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .feature-card p {
            color: var(--netflix-light);
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--netflix-red) 0%, #b81d24 100%);
            padding: 4rem 0;
            text-align: center;
        }
        
        .cta-section h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .cta-section .btn-light {
            background: var(--netflix-white);
            color: var(--netflix-black);
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .cta-section .btn-light:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        /* Footer */
        .netflix-footer {
            background: var(--netflix-dark);
            padding: 3rem 0 1rem;
            border-top: 1px solid var(--netflix-gray);
        }
        
        .footer-brand {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
            text-decoration: none;
            margin-bottom: 1rem;
            display: block;
        }
        
        .netflix-footer p {
            color: var(--netflix-light);
            line-height: 1.6;
        }
        
        .netflix-footer h5,
        .netflix-footer h6 {
            color: var(--netflix-white);
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .netflix-footer ul li {
            margin-bottom: 0.5rem;
        }
        
        .netflix-footer a {
            color: var(--netflix-light);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .netflix-footer a:hover {
            color: var(--netflix-red);
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: var(--netflix-gray);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--netflix-red);
            transform: translateY(-2px);
        }
        
        .footer-bottom {
            border-top: 1px solid var(--netflix-gray);
            padding-top: 1rem;
            margin-top: 2rem;
            color: var(--netflix-light);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .netflix-hero {
                height: 80vh;
                min-height: 500px;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .hero-buttons .btn {
                display: block;
                width: 100%;
                margin-right: 0;
            }
            
            .movie-poster-container {
                height: 400px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .netflix-hero {
                height: 70vh;
                min-height: 400px;
            }
            
            .section-padding {
                padding: 3rem 0;
            }
            
            .movie-poster-container {
                height: 350px;
            }
        }
        
        /* Text color fixes */
        .text-muted {
            color: var(--netflix-light) !important;
        }
        
        .bg-light {
            background: var(--netflix-dark) !important;
            color: var(--netflix-white);
        }
        
        /* Movie thumbnail fallback */
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
            <a class="navbar-brand netflix-brand" href="index.php">
                <i class="fas fa-film me-2"></i>NEXTFLIX
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#movies">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-netflix ms-2" href="user/register.php">
                                <i class="fas fa-user-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Netflix Style Hero Section -->
    <section id="home" class="netflix-hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Experience Movies Like Never Before</h1>
                <p class="hero-subtitle">Book your favorite movies, choose your seats, and enjoy the ultimate cinema experience with Nextflix.</p>
                <div class="hero-buttons">
                    <a href="#movies" class="btn btn-netflix btn-lg">
                        <i class="fas fa-play me-2"></i>Browse Movies
                    </a>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="user/register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Now
                        </a>
                    <?php else: ?>
                        <a href="user/dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-item">
                                <h3>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM movies");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p>Movies</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p>Users</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p>Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Movies Section -->
    <section id="movies" class="section-padding">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Featured Movies</h2>
                <p class="section-subtitle">Discover our handpicked selection of blockbuster movies</p>
            </div>

            <div class="movies-container">
                <?php
                // Get all movies from database (same as admin)
                $stmt = $pdo->query("SELECT * FROM movies ORDER BY created_at DESC");
                $movies = $stmt->fetchAll();
                
                // Movie-specific thumbnails - EXACT SAME AS MOVIES.PHP
                $movie_thumbnails = [
                    'The Dark Knight' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
                    'Inception' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
                    'The Shawshank Redemption' => 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg'
                ];
                
                if(count($movies) > 0):
                    foreach($movies as $index => $movie):
                        $avg_rating = calculate_average_rating($movie['id'], $pdo);
                        
                        // Use fixed thumbnails for specific movies, otherwise use database thumbnail - EXACT SAME AS MOVIES.PHP
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
                ?>
                <div class="movie-item">
                    <div class="movie-card">
                        <div class="position-relative movie-poster-container">
                            <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 class="movie-poster"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjQ1MCIgdmlld0JveD0iMCAwIDMwMCA0NTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iNDUwIiBmaWxsPSIjMmEyYTJhIi8+CjxwYXRoIGQ9Ik0xMjUgMTc1SDE3NVYyNzVIMTI1VjE3NVoiIGZpbGw9IiM0YTZhNmEiLz4KPHN2Zz4K';">
                            
                            <!-- User-specific overlay for View Details - EXACT SAME AS MOVIES.PHP -->
                            <div class="card-overlay">
                                <div class="overlay-content">
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <a href="user/movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-netflix" style="width: auto; padding: 10px 20px;">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                    <?php else: ?>
                                        <a href="user/login.php" class="btn btn-netflix" style="width: auto; padding: 10px 20px;">
                                            <i class="fas fa-sign-in-alt me-1"></i>Login to Book
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if($movie['title'] == 'Saiyaara'): ?>
                                <span class="trending-badge">
                                    <i class="fas fa-fire me-1"></i>Trending
                                </span>
                            <?php elseif($movie['is_featured']): ?>
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
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="user/movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-netflix-card">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="user/register.php" class="btn btn-netflix-card">
                                        <i class="fas fa-user-plus me-2"></i>Sign Up to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-film fa-3x mb-3"></i>
                            <h4>No Movies Available</h4>
                            <p>Check back later for new movie additions.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="user/movies.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-film me-2"></i>View All Movies
                    </a>
                <?php else: ?>
                    <a href="user/register.php" class="btn btn-netflix btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Sign Up to Explore More
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="about" class="section-padding bg-dark">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Why Choose Nextflix?</h2>
                <p class="section-subtitle">Experience the best movie booking platform</p>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h4>Easy Booking</h4>
                        <p>Book your favorite movies in just a few clicks with our intuitive booking system.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chair"></i>
                        </div>
                        <h4>Choose Seats</h4>
                        <p>Select your preferred seats with our interactive seat selection feature.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Rate & Review</h4>
                        <p>Share your experience by rating and reviewing movies you've watched.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Secure Payment</h4>
                        <p>Enjoy safe and secure payment processing for all your bookings.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3>Ready to Start Your Movie Journey?</h3>
                    <p class="mb-0">Join thousands of movie lovers and experience cinema like never before.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="user/dashboard.php" class="btn btn-light btn-lg">
                            <i class="fas fa-rocket me-2"></i>Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="user/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Get Started Free
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="netflix-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a href="index.php" class="footer-brand">
                        <i class="fas fa-film me-2"></i>NEXTFLIX
                    </a>
                    <p>Your ultimate movie booking experience. Book, watch, and enjoy your favorite movies with ease.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#movies">Movies</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>User Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="user/login.php">Login</a></li>
                        <li><a href="user/register.php">Register</a></li>
                        <li><a href="user/movies.php">Browse Movies</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6>Contact Info</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Cinema St, Movie City</li>
                        <li><i class="fas fa-phone me-2"></i> +1 234 567 8900</li>
                        <li><i class="fas fa-envelope me-2"></i> info@nextflix.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p class="mb-0">&copy; 2024 Nextflix. All rights reserved. | Made with <i class="fas fa-heart text-danger"></i> for movie lovers</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.netflix-navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced image error handling - EXACT SAME AS MOVIES.PHP
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