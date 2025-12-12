<?php
// Session start at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in manually
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Dashboard";

// Movie-specific thumbnails - EXACT SAME AS MOVIES.PHP
$movie_thumbnails = [
    'The Dark Knight' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
    'Inception' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
    'The Shawshank Redemption' => 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_FMjpg_UX1000_.jpg'
];

// Get all data at once to prevent multiple queries
try {
    $total_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $total_bookings_stmt->execute([$_SESSION['user_id']]);
    $total_bookings = $total_bookings_stmt->fetchColumn();

    $total_reviews_stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $total_reviews_stmt->execute([$_SESSION['user_id']]);
    $total_reviews = $total_reviews_stmt->fetchColumn();

    $upcoming_shows_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                                        JOIN shows s ON b.show_id = s.id 
                                        WHERE b.user_id = ? AND s.show_date >= CURDATE()");
    $upcoming_shows_stmt->execute([$_SESSION['user_id']]);
    $upcoming_shows = $upcoming_shows_stmt->fetchColumn();

    // Get wishlist count
    $wishlist_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $wishlist_count_stmt->execute([$_SESSION['user_id']]);
    $wishlist_count = $wishlist_count_stmt->fetchColumn();

    // Get EXACTLY 3 featured movies - EXCLUDING SAIYAARA
    $featured_movies_stmt = $pdo->prepare("SELECT * FROM movies WHERE is_featured = TRUE AND title != 'Saiyaara' ORDER BY created_at DESC LIMIT 3");
    $featured_movies_stmt->execute();
    $featured_movies = $featured_movies_stmt->fetchAll();

    // If less than 3 featured movies, get other movies to complete 3 (excluding Saiyaara)
    if(count($featured_movies) < 3) {
        $remaining = 3 - count($featured_movies);
        $additional_movies_stmt = $pdo->prepare("SELECT * FROM movies WHERE title != 'Saiyaara' AND id NOT IN (SELECT id FROM movies WHERE is_featured = TRUE AND title != 'Saiyaara') ORDER BY created_at DESC LIMIT ?");
        $additional_movies_stmt->bindValue(1, $remaining, PDO::PARAM_INT);
        $additional_movies_stmt->execute();
        $additional_movies = $additional_movies_stmt->fetchAll();
        $featured_movies = array_merge($featured_movies, $additional_movies);
    }

    $recent_bookings_stmt = $pdo->prepare("SELECT b.*, m.title, s.show_date, s.show_time, c.name as cinema_name 
                                         FROM bookings b 
                                         JOIN shows s ON b.show_id = s.id 
                                         JOIN movies m ON s.movie_id = m.id 
                                         JOIN cinemas c ON s.cinema_id = c.id 
                                         WHERE b.user_id = ? 
                                         ORDER BY b.booking_date DESC 
                                         LIMIT 5");
    $recent_bookings_stmt->execute([$_SESSION['user_id']]);
    $recent_bookings = $recent_bookings_stmt->fetchAll();
    
    // Get Saiyaara movie ID and available show
    $saiyaara_stmt = $pdo->prepare("SELECT m.id as movie_id, s.id as show_id 
                                   FROM movies m 
                                   LEFT JOIN shows s ON m.id = s.movie_id 
                                   WHERE m.title = 'Saiyaara' 
                                   AND (s.available_seats > 0 AND s.show_date >= CURDATE())
                                   LIMIT 1");
    $saiyaara_stmt->execute();
    $saiyaara_data = $saiyaara_stmt->fetch();
    $saiyaara_show_id = $saiyaara_data ? $saiyaara_data['show_id'] : null;
    $saiyaara_movie_id = $saiyaara_data ? $saiyaara_data['movie_id'] : null;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_bookings = 0;
    $total_reviews = 0;
    $upcoming_shows = 0;
    $wishlist_count = 0;
    $featured_movies = [];
    $recent_bookings = [];
    $saiyaara_show_id = null;
    $saiyaara_movie_id = null;
}

// Function to get movie thumbnail - SAME AS USER/MOVIES.PHP
function getMovieThumbnail($movie, $movie_thumbnails) {
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
    return $poster_url;
}

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

        /* Netflix Style Hero Banner */
        .netflix-hero {
            position: relative;
            height: 60vh;
            min-height: 400px;
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
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            line-height: 1.1;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: white;
            margin-bottom: 2rem;
            font-style: italic;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }

        /* Stats Cards */
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
        
        .stats-card .card-body {
            padding: 1.5rem;
        }
        
        .stats-card i {
            opacity: 0.8;
        }

        /* Big Vertical Poster Section - IMPROVED SPACING */
        .trending-movie-section {
            margin-bottom: 4rem;
            padding: 3rem 0;
            background: linear-gradient(135deg, rgba(20,20,20,0.9) 0%, rgba(40,40,40,0.7) 100%);
            border-radius: 20px;
        }

        .big-vertical-poster-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 3rem auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .big-vertical-poster-container:hover {
            transform: scale(1.05);
            box-shadow: 0 35px 70px rgba(229, 9, 20, 0.4);
        }

        .big-vertical-poster {
            width: 100%;
            height: 600px;
            object-fit: cover;
            display: block;
        }

        .trending-content {
            text-align: center;
            padding: 0 2rem;
        }

        .trending-badge {
            background: linear-gradient(135deg, #ff6b00, #ffd700);
            color: #000;
            font-weight: 800;
            font-size: 0.9rem;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .trending-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            background: linear-gradient(45deg, #fff, #e50914);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }

        .trending-description {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #e0e0e0;
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .trending-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .meta-badge {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .trending-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-trending {
            background: var(--netflix-red);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-trending:hover {
            background: #b81d24;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(229, 9, 20, 0.4);
        }

        .btn-trending-outline {
            background: transparent;
            border: 2px solid var(--netflix-red);
            color: var(--netflix-red);
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-trending-outline:hover {
            background: var(--netflix-red);
            color: white;
            transform: translateY(-3px);
        }

        /* Movie Card Styles - EXACT FROM USER/MOVIES.PHP */
        .movie-card {
            background: var(--netflix-dark);
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
        
        /* EXACT SAME AS MOVIES.PHP - Full thumbnails - no cropping */
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

        /* Table Styles */
        .table-dark-custom {
            background: var(--netflix-gray);
            color: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table-dark-custom th {
            background: var(--netflix-red);
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table-dark-custom td {
            border-color: rgba(255,255,255,0.1);
            padding: 1rem;
            vertical-align: middle;
            color: white;
        }
        
        .table-dark-custom tbody tr:hover {
            background: rgba(255,255,255,0.05);
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
                height: 50vh;
                min-height: 300px;
            }

            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }
            
            .btn-netflix {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .big-vertical-poster-container {
                max-width: 300px;
                height: 450px;
            }

            .big-vertical-poster {
                height: 450px;
            }

            .trending-title {
                font-size: 2.5rem;
            }

            .trending-description {
                font-size: 1rem;
            }

            .trending-actions {
                flex-direction: column;
                align-items: center;
            }

            .btn-trending, .btn-trending-outline {
                width: 100%;
                max-width: 250px;
            }

            .movie-poster-container {
                height: 400px;
            }
        }

        @media (max-width: 576px) {
            .netflix-hero {
                height: 40vh;
                min-height: 250px;
            }

            .hero-content h1 {
                font-size: 1.8rem;
            }

            .big-vertical-poster-container {
                max-width: 250px;
                height: 400px;
            }

            .big-vertical-poster {
                height: 400px;
            }

            .trending-title {
                font-size: 2rem;
            }

            .movie-poster-container {
                height: 350px;
            }
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: white;
            border-left: 4px solid var(--netflix-red);
            padding-left: 1rem;
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

        .card-title {
            color: white !important;
        }

        .card-text {
            color: #e0e0e0 !important;
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
                        <a class="nav-link active" href="dashboard.php">
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
                    <h1>Welcome back, <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?>! 👋</h1>
                    <p>Ready to book your next movie experience?</p>
                    <a href="movies.php" class="btn btn-netflix">
                        <i class="fas fa-film me-2"></i>Explore Movies
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid py-4">
            <!-- Stats Cards -->
            <div class="row mb-5">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title text-muted">Total Bookings</h6>
                                    <h2 class="mb-0 text-white"><?php echo $total_bookings; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-ticket-alt fa-2x" style="color: var(--netflix-red);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title text-muted">Reviews Given</h6>
                                    <h2 class="mb-0 text-white"><?php echo $total_reviews; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-star fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title text-muted">Upcoming Shows</h6>
                                    <h2 class="mb-0 text-white"><?php echo $upcoming_shows; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title text-muted">Wishlist Items</h6>
                                    <h2 class="mb-0 text-white"><?php echo $wishlist_count; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-heart fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trending Movie - Big Vertical Poster CENTERED (SAIYAARA - UNCHANGED) -->
            <div class="row trending-movie-section mb-5">
                <div class="col-12">
                    <h3 class="section-title text-center">Trending Now</h3>
                    
                    <!-- Centered Big Vertical Poster -->
                    <div class="big-vertical-poster-container" data-bs-toggle="modal" data-bs-target="#saiyaaraModal">
                        <img src="https://img.vwassets.com/oakscenter.net/vertical_8f347a74-106a-4da3-a1e6-468f113b8efe.jpg" 
                             alt="Saiyaara" 
                             class="big-vertical-poster"
                             onerror="this.src='https://via.placeholder.com/400x600/2f2f2f/ffffff?text=Saiyaara+Poster'">
                    </div>
                    
                    <!-- Movie Details -->
                    <div class="trending-content">
                        <span class="trending-badge">
                            <i class="fas fa-fire me-2"></i>#1 Trending
                        </span>
                        <h1 class="trending-title">Saiyaara</h1>
                        <p class="trending-description">
                            A heartwarming tale of love, sacrifice, and destiny. Saiyaara takes you on an emotional journey through the lives of two souls destined to be together against all odds. Experience the magic of true love in this cinematic masterpiece.
                        </p>
                        <div class="trending-meta">
                            <span class="meta-badge">Romance</span>
                            <span class="meta-badge">Drama</span>
                            <span class="meta-badge">Musical</span>
                            <span class="meta-badge">2h 28m</span>
                            <span class="meta-badge">2024</span>
                        </div>
                        <div class="trending-actions">
                            <button class="btn btn-trending" data-bs-toggle="modal" data-bs-target="#saiyaaraModal">
                                <i class="fas fa-play me-2"></i>Watch Trailer
                            </button>
                            <?php if($saiyaara_show_id): ?>
                                <a href="seats-booking.php?show_id=<?php echo $saiyaara_show_id; ?>" class="btn btn-trending-outline">
                                    <i class="fas fa-ticket-alt me-2"></i>Book Tickets
                                </a>
                            <?php else: ?>
                                <a href="movie-details.php?id=<?php echo $saiyaara_movie_id; ?>" class="btn btn-trending-outline">
                                    <i class="fas fa-info-circle me-2"></i>View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Movies - EXACT SAME STYLING AS USER/MOVIES.PHP -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="section-title">Featured Movies</h3>
                        <a href="movies.php" class="btn btn-outline-netflix">View All Movies</a>
                    </div>
                    
                    <div class="movies-container">
                        <?php 
                        if($featured_movies): 
                            foreach($featured_movies as $index => $movie): 
                                $poster_url = getMovieThumbnail($movie, $movie_thumbnails);
                                $avg_rating = calculate_average_rating($movie['id'], $pdo);
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
                                    
                                    <!-- User-specific overlay for View Details - EXACT SAME AS MOVIES.PHP -->
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
                                        <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-netflix-card">
                                            <i class="fas fa-ticket-alt me-2"></i>Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-film fa-4x text-muted mb-3"></i>
                            <h4 class="text-white">No Featured Movies Available</h4>
                            <p class="text-muted">Check back later for featured movies.</p>
                        </div>
                        <?php endif; ?>
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
                        <a href="wishlist.php">My Wishlist</a>
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

    <!-- Saiyaara Trailer Modal -->
    <div class="modal fade" id="saiyaaraModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-play-circle me-2"></i>Saiyaara - Official Trailer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <!-- YouTube Trailer Embed Code with ID for JavaScript control -->
                        <iframe id="saiyaaraTrailer" width="560" height="315" src="https://www.youtube.com/embed/9r-tT5IN0vg?si=_pNWQhDTz1GwpmV3" 
                                title="YouTube video player" frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                
                // Check if image loads successfully
                img.onload = function() {
                    console.log('Image loaded successfully:', this.src);
                };
            });
            
            // Clear browser cache and prevent form resubmission
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Add click effect to big vertical poster
            const bigPoster = document.querySelector('.big-vertical-poster-container');
            if(bigPoster) {
                bigPoster.addEventListener('click', function() {
                    const modal = new bootstrap.Modal(document.getElementById('saiyaaraModal'));
                    modal.show();
                });
            }

            // Fix YouTube trailer stop on modal close
            const trailerModal = document.getElementById('saiyaaraModal');
            const trailerIframe = document.getElementById('saiyaaraTrailer');
            
            if(trailerModal && trailerIframe) {
                trailerModal.addEventListener('hidden.bs.modal', function () {
                    // Stop the video by reloading the iframe
                    const trailerSrc = trailerIframe.src;
                    trailerIframe.src = '';
                    setTimeout(() => {
                        trailerIframe.src = trailerSrc;
                    }, 100);
                });
                
                trailerModal.addEventListener('show.bs.modal', function () {
                    // Ensure video plays when modal opens
                    if(trailerIframe.src === '') {
                        trailerIframe.src = 'https://www.youtube.com/embed/9r-tT5IN0vg?si=_pNWQhDTz1GwpmV3';
                    }
                });
            }
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
                            // Update wishlist count in stats
                            updateWishlistCount();
                        } else {
                            button.classList.remove('added');
                            button.innerHTML = '<i class="fas fa-heart"></i>';
                            showToast('Removed from wishlist!', 'info');
                            // Update wishlist count in stats
                            updateWishlistCount();
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

        function updateWishlistCount() {
            // Reload the page to update the wishlist count
            setTimeout(() => {
                location.reload();
            }, 1000);
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