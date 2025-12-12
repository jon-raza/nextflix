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

$page_title = "My Bookings";

// SIMPLE & GUARANTEED WORKING QUERY
try {
    // First get all bookings for this user
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $all_bookings = $stmt->fetchAll();
    
    $bookings = [];
    
    // For each booking, get details
    foreach($all_bookings as $booking) {
        $booking_id = $booking['id'];
        $show_id = $booking['show_id'];
        
        // Get show details
        $show_stmt = $pdo->prepare("SELECT s.*, m.title, m.thumbnail_url, m.price, c.name as cinema_name, c.location 
                                  FROM shows s 
                                  JOIN movies m ON s.movie_id = m.id 
                                  JOIN cinemas c ON s.cinema_id = c.id 
                                  WHERE s.id = ?");
        $show_stmt->execute([$show_id]);
        $show_details = $show_stmt->fetch();
        
        if($show_details) {
            $bookings[] = array_merge($booking, $show_details);
        } else {
            // If show details not found, still show basic booking info
            $bookings[] = $booking;
        }
    }
    
} catch (PDOException $e) {
    error_log("Bookings error: " . $e->getMessage());
    $bookings = [];
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
            --netflix-red-dark: #b81d24;
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

        /* Table Styles */
        .table-netflix {
            background: var(--netflix-gray);
            color: white;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .table-netflix thead th {
            background: var(--netflix-red);
            border: none;
            padding: 1.2rem;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-netflix tbody td {
            border-color: rgba(255,255,255,0.1);
            padding: 1.2rem;
            vertical-align: middle;
            color: white;
        }
        
        .table-netflix tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .table-netflix tbody tr:hover {
            background: rgba(255,255,255,0.05);
            transform: translateX(5px);
        }
        
        .table-netflix tbody tr:last-child {
            border-bottom: none;
        }

        /* Movie Poster */
        .movie-poster {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .movie-poster:hover {
            transform: scale(1.05);
            border-color: var(--netflix-red);
        }

        /* Badge Styles */
        .badge-netflix {
            background: var(--netflix-red);
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .badge-pending {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: black; /* FIXED: Black text for better visibility */
        }
        
        .badge-confirmed {
            background: linear-gradient(45deg, #198754, #20c997);
            color: white;
        }
        
        .badge-cancelled {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
            color: white;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .table-netflix thead {
                display: none;
            }
            
            .table-netflix tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: var(--netflix-gray);
                border-radius: 10px;
                padding: 1rem;
            }
            
            .table-netflix tbody td {
                display: block;
                text-align: right;
                padding: 0.5rem 1rem;
                border: none;
                position: relative;
            }
            
            .table-netflix tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-weight: 600;
                color: #aaa;
            }
            
            .btn-netflix {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        /* FIXED: Text colors for better visibility */
        .text-muted {
            color: #e0e0e0 !important; /* Light gray instead of muted */
        }

        .text-warning {
            color: #ffc107 !important; /* Yellow warning text */
        }

        .text-success {
            color: #20c997 !important; /* Green success text */
        }

        .text-danger {
            color: #dc3545 !important; /* Red danger text */
        }

        /* FIXED: White space text colors */
        .text-white {
            color: white !important;
        }

        .text-light {
            color: #f8f9fa !important;
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

        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: white;
            border-left: 4px solid var(--netflix-red);
            padding-left: 1rem;
        }
        
        .booking-details {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .debug-info {
            background: #ffc107;
            color: black !important; /* FIXED: Black text on yellow background */
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }

        /* FIXED: Seats display styling */
        .seats-display {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px 12px;
            margin-top: 5px;
            border-left: 3px solid var(--netflix-red);
        }

        .seats-count {
            font-weight: bold;
            color: var(--netflix-red);
        }

        .seats-list {
            font-size: 0.8rem;
            color: #e0e0e0;
            margin-top: 3px;
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
                        <a class="nav-link active" href="bookings.php">
                            <i class="fas fa-ticket-alt me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
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
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    
                    <?php if(isset($_GET['debug'])): ?>
                    <div class="debug-info">
                        <strong>Debug Info:</strong><br>
                        User ID: <?php echo $_SESSION['user_id']; ?><br>
                        Total Bookings Found: <?php echo count($bookings); ?><br>
                        Query Used: Simple Guaranteed Query
                    </div>
                    <?php endif; ?>
                    
                    <div class="netflix-card">
                        <div class="netflix-card-header">
                            <h4 class="mb-0"><i class="fas fa-history me-2"></i>My Bookings</h4>
                            <?php if($bookings && count($bookings) > 0): ?>
                                <small class="float-end">Total: <?php echo count($bookings); ?> booking(s)</small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if($bookings && count($bookings) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-netflix">
                                        <thead>
                                            <tr>
                                                <th>Movie</th>
                                                <th>Cinema</th>
                                                <th>Show Details</th>
                                                <th>Seats</th>
                                                <th>Amount</th>
                                                <th>Booking Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($bookings as $booking): ?>
                                            <tr>
                                                <td data-label="Movie">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo $booking['thumbnail_url'] ?? 'https://via.placeholder.com/60x80/2f2f2f/ffffff?text=Poster'; ?>" 
                                                             alt="<?php echo $booking['title'] ?? 'Movie'; ?>" 
                                                             class="movie-poster me-3"
                                                             onerror="this.src='https://via.placeholder.com/60x80/2f2f2f/ffffff?text=Poster'">
                                                        <div>
                                                            <strong class="text-dark"><?php echo $booking['title'] ?? 'Unknown Movie'; ?></strong>
                                                            <br><small class="text-dark">ID: #<?php echo $booking['id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label="Cinema" class="text-dark fw-bold">
                                                    <?php echo $booking['cinema_name'] ?? 'Unknown Cinema'; ?>
                                                    <br>
                                                    <small class=""><?php echo $booking['location'] ?? 'Location not available'; ?></small>
                                                </td>
                                                <td data-label="Show Details" class="text-dark">
                                                    <div class="booking-details">
                                                        <?php if(isset($booking['show_date'])): ?>
                                                            <strong><?php echo format_date($booking['show_date']); ?></strong>
                                                            <br>
                                                            <small class=""><?php echo format_time($booking['show_time'] ?? '00:00:00'); ?></small>
                                                        <?php else: ?>
                                                            <small class="">Show details not available</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td data-label="Seats">
                                                    <!-- FIXED: Enhanced seats display -->
                                                    <div class="seats-display">
                                                        <div class="seats-count">
                                                            <?php echo $booking['seats_booked']; ?> seat<?php echo $booking['seats_booked'] > 1 ? 's' : ''; ?>
                                                        </div>
                                                        <?php if(!empty($booking['selected_seats'])): ?>
                                                            <div class="seats-list text-dark fw-bold">
                                                                Seats: <?php echo $booking['selected_seats']; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="seats-list ">
                                                                Seats not specified
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td data-label="Amount" class="text-dark">
                                                    <strong>$<?php echo $booking['total_amount']; ?></strong>
                                                </td>
                                                <td data-label="Booking Date" class="text-dark">
                                                    <?php 
                                                    if(isset($booking['booking_date'])) {
                                                        echo format_date($booking['booking_date']);
                                                    } else {
                                                        echo "N/A";
                                                    }
                                                    ?>
                                                </td>
                                                <td data-label="Status">
                                                    <?php 
                                                    $status = $booking['status'];
                                                    $status_class = '';
                                                    if($status == 'confirmed') {
                                                        $status_class = 'badge-confirmed';
                                                    } elseif($status == 'cancelled') {
                                                        $status_class = 'badge-cancelled';
                                                    } else {
                                                        $status_class = 'badge-pending';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                    <?php if($status == 'pending'): ?>
                                                        <br>
                                                        <small class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>Waiting for admin approval
                                                        </small>
                                                    <?php elseif($status == 'confirmed'): ?>
                                                        <br>
                                                        <small class="text-success">
                                                            <i class="fas fa-check me-1"></i>Approved by admin
                                                        </small>
                                                    <?php elseif($status == 'cancelled'): ?>
                                                        <br>
                                                        <small class="text-danger">
                                                            <i class="fas fa-times me-1"></i>Cancelled
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-ticket-alt"></i>
                                    <h3 class="text-white mb-3">No Bookings Yet</h3>
                                    <p class="text-muted mb-4">You haven't made any bookings yet. Start by exploring our movies!</p>
                                    <a href="movies.php" class="btn btn-netflix">
                                        <i class="fas fa-film me-2"></i>Browse Movies
                                    </a>
                                    <br><br>
                                    <small class="text-muted">
                                        <a href="bookings.php?debug=1" class="text-warning">Debug Info</a> | 
                                        User ID: <?php echo $_SESSION['user_id']; ?>
                                    </small>
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
</body>
</html>