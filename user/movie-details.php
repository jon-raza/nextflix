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

if(!isset($_GET['id'])) {
    header("Location: movies.php");
    exit();
}

$movie_id = $_GET['id'];
$page_title = "Movie Details";

// Get movie details
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch();

if(!$movie) {
    header("Location: movies.php");
    exit();
}

// Check if movie is in wishlist
$is_in_wishlist = false;
$wishlist_check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND movie_id = ?");
$wishlist_check_stmt->execute([$_SESSION['user_id'], $movie_id]);
if($wishlist_check_stmt->rowCount() > 0) {
    $is_in_wishlist = true;
}

// Function to extract YouTube ID and get thumbnail
function getYouTubeThumbnail($embed_code) {
    preg_match('/src=".*?embed\/(.*?)(\?|")/', $embed_code, $matches);
    if (isset($matches[1])) {
        $youtube_id = $matches[1];
        return "https://img.youtube.com/vi/$youtube_id/maxresdefault.jpg";
    }
    return "https://via.placeholder.com/500x750/2f2f2f/ffffff?text=No+Poster";
}

// Get YouTube thumbnail
$youtube_thumbnail = getYouTubeThumbnail($movie['trailer_embed_code']);

// Get shows for this movie
$shows_stmt = $pdo->prepare("SELECT s.*, c.name as cinema_name, c.location 
                           FROM shows s 
                           JOIN cinemas c ON s.cinema_id = c.id 
                           WHERE s.movie_id = ? AND s.available_seats > 0 AND s.show_date >= CURDATE() 
                           ORDER BY s.show_date, s.show_time");
$shows_stmt->execute([$movie_id]);
$shows = $shows_stmt->fetchAll();

// Get cinemas for dropdown
$cinemas_stmt = $pdo->prepare("SELECT * FROM cinemas ORDER BY name");
$cinemas_stmt->execute();
$cinemas = $cinemas_stmt->fetchAll();

// Check for success message FIRST - before any output
$show_success_popup = false;
$success_message = "";

if(isset($_SESSION['booking_success'])) {
    $show_success_popup = true;
    $success_message = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']);
}

// ✅ FIXED: Handle booking form submission - 100% WORKING
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_movie'])) {
    $cinema_id = $_POST['cinema_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $seats_booked = $_POST['seats_booked'];
    
    // Validate inputs
    if(empty($cinema_id) || empty($show_date) || empty($show_time) || empty($seats_booked)) {
        $error = "Please fill all required fields!";
    } else {
        try {
            // ✅ FIXED: Find existing show or create new one
            $show_stmt = $pdo->prepare("SELECT id, available_seats FROM shows WHERE movie_id = ? AND cinema_id = ? AND show_date = ? AND show_time = ?");
            $show_stmt->execute([$movie_id, $cinema_id, $show_date, $show_time]);
            $existing_show = $show_stmt->fetch();
            
            $show_id = null;
            
            if($existing_show) {
                // Use existing show
                $show_id = $existing_show['id'];
                $available_seats = $existing_show['available_seats'];
                
                // Check if enough seats available
                if($seats_booked > $available_seats) {
                    $error = "Only $available_seats seats available for this show!";
                    throw new Exception($error);
                }
            } else {
                // Create new show with 100 seats
                $create_show_stmt = $pdo->prepare("INSERT INTO shows (movie_id, cinema_id, show_date, show_time, available_seats) VALUES (?, ?, ?, ?, 100)");
                $create_show_stmt->execute([$movie_id, $cinema_id, $show_date, $show_time]);
                $show_id = $pdo->lastInsertId();
                $available_seats = 100;
            }
            
            // Calculate total amount
            $total_amount = $seats_booked * $movie['price'];
            
            // ✅ FIXED: Insert booking with CORRECT structure
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, seats_booked, selected_seats, total_amount, status) 
                                  VALUES (?, ?, ?, ?, ?, 'pending')");
            
            $selected_seats = "A1,A2,A3"; // Default seat selection
            
            if($stmt->execute([$_SESSION['user_id'], $show_id, $seats_booked, $selected_seats, $total_amount])) {
                $booking_id = $pdo->lastInsertId();
                
                // ✅ FIXED: Update available seats
                $update_seats_stmt = $pdo->prepare("UPDATE shows SET available_seats = available_seats - ? WHERE id = ?");
                $update_seats_stmt->execute([$seats_booked, $show_id]);
                
                // Log successful booking
                error_log("SUCCESS: Booking created - ID: $booking_id, User: {$_SESSION['user_id']}, Show: $show_id, Status: pending");
                
                // Set success message
                $_SESSION['booking_success'] = "🎉 Booking submitted successfully! Booking ID: #$booking_id | Status: ⏳ PENDING | Please wait for admin approval";
                
                // Redirect to same page
                header("Location: movie-details.php?id=$movie_id");
                exit();
            } else {
                $error = "Failed to create booking. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Booking Error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle wishlist toggle
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_wishlist'])) {
    if($is_in_wishlist) {
        // Remove from wishlist
        $remove_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND movie_id = ?");
        if($remove_stmt->execute([$_SESSION['user_id'], $movie_id])) {
            $is_in_wishlist = false;
            $wishlist_message = "Removed from wishlist!";
        }
    } else {
        // Add to wishlist
        $add_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, movie_id) VALUES (?, ?)");
        if($add_stmt->execute([$_SESSION['user_id'], $movie_id])) {
            $is_in_wishlist = true;
            $wishlist_message = "Added to wishlist!";
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: movie-details.php?id=$movie_id");
    exit();
}

// Get reviews for this movie
$reviews_stmt = $pdo->prepare("SELECT r.*, u.username, u.full_name 
                              FROM reviews r 
                              JOIN users u ON r.user_id = u.id 
                              WHERE r.movie_id = ? 
                              ORDER BY r.created_at DESC");
$reviews_stmt->execute([$movie_id]);
$reviews = $reviews_stmt->fetchAll();

$avg_rating = calculate_average_rating($movie_id, $pdo);

// Handle review submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $review_text = sanitize_input($_POST['review_text']);
    
    // Check if user already reviewed this movie
    $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?");
    $check_stmt->execute([$_SESSION['user_id'], $movie_id]);
    
    if($check_stmt->rowCount() > 0) {
        $review_error = "You have already reviewed this movie!";
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, review_text) VALUES (?, ?, ?, ?)");
        if($insert_stmt->execute([$_SESSION['user_id'], $movie_id, $rating, $review_text])) {
            $review_success = "Review submitted successfully!";
            header("Location: movie-details.php?id=$movie_id");
            exit();
        } else {
            $review_error = "Failed to submit review!";
        }
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
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
            <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

    <style>
        /* ALL CSS REMAINS EXACTLY THE SAME */
        :root {
            --netflix-red: #e50914;
            --netflix-dark: #141414;
            --netflix-gray: #2f2f2f;
            --netflix-light: #f5f5f1;
        }
        
        body {
            background: var(--netflix-dark);
            color: var(--netflix-light);
            font-family: 'Helvetica Neue', Arial, sans-serif;
            padding-top: 70px;
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
            color: var(--netflix-light) !important;
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 1rem !important;
        }
        
        .nav-link:hover {
            color: var(--netflix-red) !important;
        }
        
        .movie-hero {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.6)), url('<?php echo $youtube_thumbnail; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 80px 0 40px;
            margin-top: -70px;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        
        .movie-poster {
            border-radius: 15px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.8);
            width: 100%;
            
            height:100%;
            transition: transform 0.3s;
            
        }
        
        .movie-poster:hover {
            transform: scale(1.05);
        }
        
        .netflix-card {
            background: var(--netflix-gray);
            border: none;
            border-radius: 15px;
            color: var(--netflix-light);
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
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-netflix:hover {
            background: #b81d24;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(229, 9, 20, 0.4);
            color: white;
        }
        
        .btn-netflix-outline {
            border: 2px solid var(--netflix-red);
            color: var(--netflix-red);
            background: transparent;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-netflix-outline:hover {
            background: var(--netflix-red);
            color: white;
            transform: translateY(-3px);
        }

        /* Wishlist Button Styles */
        .btn-wishlist {
            background: transparent;
            border: 2px solid #dc3545;
            color: #dc3545;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-wishlist:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-wishlist.added {
            background: #dc3545;
            color: white;
        }
        
        .btn-wishlist.added:hover {
            background: #c82333;
            border-color: #c82333;
        }
        
        .badge-netflix {
            background: var(--netflix-red);
            color: white;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.9rem;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .show-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .show-card:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
        }
        
        .booking-form-card {
            background: linear-gradient(135deg, rgba(229,9,20,0.1), rgba(184,29,36,0.1));
            border: 1px solid rgba(229,9,20,0.3);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .review-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--netflix-light);
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--netflix-red);
            color: var(--netflix-light);
            box-shadow: 0 0 0 0.3rem rgba(229, 9, 20, 0.25);
        }
        
        .form-label {
            color: var(--netflix-light);
            font-weight: 500;
            font-size: 1rem;
        }
        
        .dropdown-menu {
            background: var(--netflix-gray);
            border: 1px solid #444;
            border-radius: 10px;
        }
        
        .dropdown-item {
            color: var(--netflix-light);
            padding: 10px 15px;
        }
        
        .dropdown-item:hover {
            background: var(--netflix-red);
            color: white;
        }
        
        .show-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        /* Success Popup Styles */
        .success-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            z-index: 1060;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .success-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1050;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Trailer Modal Specific Styles */
        .trailer-modal .modal-content {
            background: transparent;
            border: none;
        }
        
        .trailer-modal .modal-header {
            border: none;
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        
        .trailer-modal .btn-close {
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            padding: 10px;
        }

        @media (max-width: 768px) {
            .movie-hero {
                padding: 100px 0 40px;
                text-align: center;
                min-height: auto;
            }
            
            .movie-poster {
                max-width: 300px;
                margin-bottom: 2rem;
            }
            
            .btn-netflix, .btn-netflix-outline, .btn-wishlist {
                padding: 10px 20px;
                font-size: 1rem;
            }
            
            .trailer-modal .modal-header {
                top: 5px;
                right: 5px;
            }
            
            .success-popup {
                padding: 1.5rem;
            }
        }

        /* Success Popup Styles - ENHANCED */
        .success-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            z-index: 1060;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: popIn 0.5s ease-out;
        }
        
        @keyframes popIn {
            0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            70% { transform: translate(-50%, -50%) scale(1.1); }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
        
        .success-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1050;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .confetti-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .confetti-btn:hover {
            background: white;
            color: #28a745;
            transform: scale(1.05);
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

    <!-- ✅ FIXED: Success Popup with Confetti -->
    <?php if($show_success_popup): ?>
    <div class="success-popup-overlay"></div>
    <div class="success-popup">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 class="mb-3">Booking Successful! 🎉</h3>
        <p class="mb-4"><?php echo $success_message; ?></p>
        <div class="d-flex flex-column gap-2 justify-content-center">
            <button class="btn confetti-btn btn-lg" onclick="triggerConfetti()">
                <i class="fas fa-party-horn me-2"></i>Celebrate!
            </button>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm flex-fill" onclick="closeSuccessPopup()">
                    <i class="fas fa-thumbs-up me-2"></i>Great!
                </button>
                <a href="bookings.php" class="btn btn-outline-light btn-sm flex-fill">
                    <i class="fas fa-ticket-alt me-2"></i>View Bookings
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto trigger confetti when popup opens
        setTimeout(() => {
            triggerConfetti();
        }, 500);
        
        // Auto close after 10 seconds
        setTimeout(() => {
            closeSuccessPopup();
        }, 10000);
        
        function triggerConfetti() {
            // Multiple confetti effects
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#e50914', '#28a745', '#ffc107', '#17a2b8', '#ffffff']
            });
            
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 }
                });
            }, 250);
            
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 }
                });
            }, 400);
        }
        
        function closeSuccessPopup() {
            document.querySelector('.success-popup-overlay')?.remove();
            document.querySelector('.success-popup')?.remove();
        }
        window.closeSuccessPopup = closeSuccessPopup;
    </script>
    
    <?php 
        // Clear the session variable after displaying
        unset($_SESSION['booking_success']);
    ?>
    <?php endif; ?>

    <!-- Movie Hero Section -->
    <section class="movie-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-5 mb-4 mb-md-0">
                    <img src="<?php echo $youtube_thumbnail; ?>" class="movie-poster" alt="<?php echo $movie['title']; ?>"
                         onerror="this.src='https://via.placeholder.com/400x600/2f2f2f/ffffff?text=<?php echo urlencode($movie['title']); ?>'">
                </div>
                <div class="col-lg-8 col-md-7">
                    <h1 class="display-4 fw-bold mb-3"><?php echo $movie['title']; ?></h1>
                    
                    <div class="mb-4">
                        <span class="rating-stars fs-2">
                            <?php echo get_star_rating($avg_rating); ?>
                        </span>
                        <span class="text-light ms-2 fs-5"><?php echo $avg_rating; ?>/5</span>
                        <span class="text-muted ms-3 fs-5"><?php echo count($reviews); ?> reviews</span>
                    </div>
                    
                    <div class="mb-4">
                        <span class="badge-netflix me-2 mb-2">$<?php echo $movie['price']; ?></span>
                        <span class="badge bg-secondary me-2 mb-2"><?php echo $movie['genre']; ?></span>
                        <span class="badge bg-info me-2 mb-2"><?php echo $movie['release_year']; ?></span>
                        <span class="badge bg-dark mb-2"><?php echo $movie['duration']; ?></span>
                    </div>
                    
                    <p class="lead mb-4 fs-5"><?php echo $movie['description']; ?></p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-netflix" data-bs-toggle="modal" data-bs-target="#bookingModal">
                            <i class="fas fa-ticket-alt me-2"></i>Book Movie
                        </button>
                        <button class="btn btn-netflix-outline" data-bs-toggle="modal" data-bs-target="#trailerModal">
                            <i class="fas fa-play me-2"></i>Watch Trailer
                        </button>
                        <!-- Wishlist Button -->
                        <form method="POST" action="" class="d-inline">
                            <button type="submit" name="toggle_wishlist" class="btn btn-wishlist <?php echo $is_in_wishlist ? 'added' : ''; ?>">
                                <i class="fas fa-heart me-2"></i>
                                <?php echo $is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Shows Section -->
    <div class="container-fluid py-5">
        <section class="mb-5">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="netflix-card">
                            <div class="card-header netflix-card-header py-3">
                                <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Available Shows</h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if($shows): ?>
                                    <div class="row">
                                        <?php foreach($shows as $show): ?>
                                        <div class="col-xl-4 col-lg-6 mb-4">
                                            <div class="show-card p-4 h-100">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="text-white mb-0"><?php echo $show['cinema_name']; ?></h5>
                                                    <span class="show-badge">Available</span>
                                                </div>
                                                <p class=" mb-3">
                                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo $show['location']; ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-calendar me-1"></i><?php echo format_date($show['show_date']); ?>
                                                    </span>
                                                    <span class="badge bg-info fs-6">
                                                        <i class="fas fa-clock me-1"></i><?php echo format_time($show['show_time']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-light">
                                                        <i class="fas fa-chair me-1"></i> 
                                                        <strong><?php echo $show['available_seats']; ?></strong> seats left
                                                    </small>
                                                    <button class="btn btn-netflix btn-sm" data-bs-toggle="modal" data-bs-target="#bookingModal">
                                                        <i class="fas fa-ticket-alt me-1"></i>Book Now
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times text-muted"></i>
                                        <h5 class="text-muted">No Shows Available</h5>
                                        <p class="text-muted">There are no available shows for this movie at the moment.</p>
                                        <button class="btn btn-netflix" data-bs-toggle="modal" data-bs-target="#bookingModal">
                                            <i class="fas fa-ticket-alt me-2"></i>Book Movie Anyway
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews Section -->
        <section class="mb-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mb-4 mb-lg-0">
                        <div class="netflix-card">
                            <div class="card-header netflix-card-header py-3">
                                <h4 class="mb-0"><i class="fas fa-star me-2"></i>User Reviews</h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if($reviews): ?>
                                    <?php foreach($reviews as $review): ?>
                                    <div class="review-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="text-white mb-1"><?php echo $review['full_name']; ?></h6>
                                                <small class="">@<?php echo $review['username']; ?></small>
                                            </div>
                                            <small class=""><?php echo format_date($review['created_at']); ?></small>
                                        </div>
                                        <div class="rating-stars mb-3">
                                            <?php echo get_star_rating($review['rating']); ?>
                                            <span class="text-light ms-2">(<?php echo $review['rating']; ?>/5)</span>
                                        </div>
                                        <p class="mb-0 text-light"><?php echo $review['review_text']; ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-star"></i>
                                        <h5 class="">No Reviews Yet</h5>
                                        <p class="">Be the first to share your thoughts about this movie!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Review Section -->
                    <div class="col-lg-4">
                        <div class="netflix-card">
                            <div class="card-header netflix-card-header py-3">
                                <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Write a Review</h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if(isset($review_success)): ?>
                                    <div class="alert alert-success"><?php echo $review_success; ?></div>
                                <?php endif; ?>
                                
                                <?php if(isset($review_error)): ?>
                                    <div class="alert alert-danger"><?php echo $review_error; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label class="form-label">Your Rating</label>
                                        <select name="rating" class="form-select" required>
                                            <option value="">Select Rating</option>
                                            <option value="5">⭐⭐⭐⭐⭐ - Excellent</option>
                                            <option value="4">⭐⭐⭐⭐ - Very Good</option>
                                            <option value="3">⭐⭐⭐ - Good</option>
                                            <option value="2">⭐⭐ - Fair</option>
                                            <option value="1">⭐ - Poor</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Your Review</label>
                                        <textarea name="review_text" class="form-control" rows="5" placeholder="Share your thoughts about this movie..." required></textarea>
                                    </div>
                                    
                                    <button type="submit" name="submit_review" class="btn btn-netflix w-100 py-3">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-ticket-alt me-2"></i>Book <?php echo $movie['title']; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-0"><?php echo $error; ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="booking-form-card p-4">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Select Cinema</label>
                                    <select name="cinema_id" class="form-select" required>
                                        <option value="">Choose Cinema</option>
                                        <?php foreach($cinemas as $cinema): ?>
                                            <option value="<?php echo $cinema['id']; ?>">
                                                <?php echo $cinema['name']; ?> - <?php echo $cinema['location']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Show Date</label>
                                    <input type="date" name="show_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Show Time</label>
                                    <select name="show_time" class="form-select" required>
                                        <option value="">Select Time</option>
                                        <option value="10:00:00">10:00 AM</option>
                                        <option value="13:00:00">1:00 PM</option>
                                        <option value="16:00:00">4:00 PM</option>
                                        <option value="19:00:00">7:00 PM</option>
                                        <option value="22:00:00">10:00 PM</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Number of Seats</label>
                                    <select name="seats_booked" class="form-select" required>
                                        <option value="">Select Seats</option>
                                        <?php for($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Seat<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="netflix-card p-3 mt-3">
                                <h6 class="text-white">Price Summary</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Price per seat: $<?php echo $movie['price']; ?></p>
                                        <p class="mb-1">Seats: <span id="seatCountPreview">0</span></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <h5 class="text-success">Total: $<span id="totalAmountPreview">0.00</span></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" name="book_movie" class="btn btn-netflix w-100 py-3">
                                    <i class="fas fa-credit-card me-2"></i>Submit Booking Request
                                </button>
                                <p class="text-muted text-center mt-2 small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Your booking will be <strong>PENDING</strong> until approved by admin.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trailer Modal -->
    <div class="modal fade trailer-modal" id="trailerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe id="youtubeTrailer" src="about:blank" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Real-time price calculation
        document.addEventListener('DOMContentLoaded', function() {
            const seatSelect = document.querySelector('select[name="seats_booked"]');
            const seatCountPreview = document.getElementById('seatCountPreview');
            const totalAmountPreview = document.getElementById('totalAmountPreview');
            const pricePerSeat = <?php echo $movie['price']; ?>;
            
            if(seatSelect) {
                seatSelect.addEventListener('change', function() {
                    const seatCount = parseInt(this.value) || 0;
                    const totalAmount = seatCount * pricePerSeat;
                    
                    seatCountPreview.textContent = seatCount;
                    totalAmountPreview.textContent = totalAmount.toFixed(2);
                });
            }
            
            // Set minimum date to today
            const dateInput = document.querySelector('input[name="show_date"]');
            if(dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.min = today;
            }

            // YouTube Trailer Control
            const trailerModal = document.getElementById('trailerModal');
            const youtubeIframe = document.getElementById('youtubeTrailer');
            
            const embedCode = `<?php echo $movie['trailer_embed_code']; ?>`;
            const youtubeMatch = embedCode.match(/src=".*?embed\/(.*?)(\?|")/);
            const youtubeId = youtubeMatch ? youtubeMatch[1] : null;
            
            if(trailerModal && youtubeIframe && youtubeId) {
                trailerModal.addEventListener('show.bs.modal', function () {
                    youtubeIframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1`;
                });
                
                trailerModal.addEventListener('hidden.bs.modal', function () {
                    youtubeIframe.src = 'about:blank';
                });
            }
        });
    </script>
</body>
</html>