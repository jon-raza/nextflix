<?php
session_start(); // Yeh add karna ensure karo
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php'; // Updated file use karo

$page_title = "Admin Dashboard";
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
        }
        
        .sidebar {
            background: var(--netflix-dark);
            min-height: 100vh;
            border-right: 1px solid var(--netflix-gray);
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
        }
        
        .navbar {
            background: var(--netflix-dark) !important;
            border-bottom: 1px solid var(--netflix-gray);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--netflix-dark) 0%, var(--netflix-gray) 100%);
            border: 1px solid var(--netflix-gray);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--netflix-red);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.15);
        }
        
        .card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 8px;
        }
        
        .card-header {
            background: var(--netflix-gray) !important;
            border-bottom: 1px solid var(--netflix-gray);
            color: white;
        }
        
        .table {
            color: white;
        }
        
        .table th {
            background: var(--netflix-gray);
            border-color: var(--netflix-gray);
            color: var(--netflix-light);
        }
        
        .table td {
            border-color: var(--netflix-gray);
            background: var(--netflix-dark);
        }
        
        .table-hover tbody tr:hover {
            background: var(--netflix-gray);
            color: white;
        }
        
        .btn-primary {
            background: var(--netflix-red);
            border: none;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #f40612;
            transform: scale(1.02);
        }
        
        .btn-outline-primary {
            border-color: var(--netflix-red);
            color: var(--netflix-red);
        }
        
        .btn-outline-primary:hover {
            background: var(--netflix-red);
            border-color: var(--netflix-red);
        }
        
        .badge {
            font-weight: 500;
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
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
                            <a class="nav-link active" href="dashboard.php">
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
            <main class="col-md-9 col-lg-10 main-content p-0">
                <!-- Header -->
                <nav class="navbar navbar-light">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1 text-white">
                            <i class="fas fa-tachometer-alt me-2" style="color: var(--netflix-red);"></i>Dashboard Overview
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="text-light me-3">
                                <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                </nav>

                <!-- Stats Cards -->
                <div class="container-fluid mt-4">
                    <div class="row g-4">
                        <!-- Total Movies -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-light">Total Movies</h6>
                                            <?php
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM movies");
                                            $total_movies = $stmt->fetchColumn();
                                            ?>
                                            <h2 class="mb-0"><?php echo $total_movies; ?></h2>
                                            <small class="text-success">+5% from last month</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-film fa-2x" style="color: var(--netflix-red);"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Users -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-light">Total Users</h6>
                                            <?php
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                                            $total_users = $stmt->fetchColumn();
                                            ?>
                                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                            <small class="text-success">+12% from last month</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x" style="color: #00a8ff;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Bookings -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-light">Total Bookings</h6>
                                            <?php
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
                                            $total_bookings = $stmt->fetchColumn();
                                            ?>
                                            <h2 class="mb-0"><?php echo $total_bookings; ?></h2>
                                            <small class="text-success">+8% from last month</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-ticket-alt fa-2x" style="color: #fbc531;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Wishlist Items -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-light">Wishlist Items</h6>
                                            <?php
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM wishlist");
                                            $total_wishlist = $stmt->fetchColumn();
                                            ?>
                                            <h2 class="mb-0"><?php echo $total_wishlist; ?></h2>
                                            <small class="text-success">Active user favorites</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-heart fa-2x" style="color: #e84393;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2" style="color: var(--netflix-red);"></i>Recent Bookings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $stmt = $pdo->query("SELECT b.*, u.username, m.title, s.show_date, s.show_time 
                                                        FROM bookings b 
                                                        JOIN users u ON b.user_id = u.id 
                                                        JOIN shows s ON b.show_id = s.id 
                                                        JOIN movies m ON s.movie_id = m.id 
                                                        ORDER BY b.booking_date DESC 
                                                        LIMIT 6");
                                    $recent_bookings = $stmt->fetchAll();
                                    ?>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Movie</th>
                                                    <th>Show Date</th>
                                                    <th>Seats</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($recent_bookings as $booking): ?>
                                                <tr>
                                                    <td class="text-light"><?php echo $booking['username']; ?></td>
                                                    <td class="text-light"><?php echo $booking['title']; ?></td>
                                                    <td class="text-light"><?php echo format_date($booking['show_date']); ?></td>
                                                    <td class="text-light"><?php echo $booking['seats_booked']; ?></td>
                                                    <td class="text-success">$<?php echo $booking['total_amount']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $booking['status'] == 'confirmed' ? 'success' : 
                                                                 ($booking['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2" style="color: #00a8ff;"></i>Quick Stats
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Featured movies count
                                    $featured_stmt = $pdo->query("SELECT COUNT(*) FROM movies WHERE is_featured = TRUE");
                                    $featured_movies = $featured_stmt->fetchColumn();
                                    
                                    // Today's bookings
                                    $today_stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) = CURDATE()");
                                    $today_bookings = $today_stmt->fetchColumn();
                                    
                                    // Total reviews
                                    $reviews_stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
                                    $total_reviews = $reviews_stmt->fetchColumn();

                                    // Total revenue
                                    $revenue_stmt = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status = 'confirmed'");
                                    $total_revenue = $revenue_stmt->fetchColumn() ?: 0;

                                    // Active shows
                                    $active_stmt = $pdo->query("SELECT COUNT(*) FROM shows WHERE show_date >= CURDATE()");
                                    $active_shows = $active_stmt->fetchColumn();
                                    ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Featured Movies</span>
                                        <span class="badge bg-danger"><?php echo $featured_movies; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Today's Bookings</span>
                                        <span class="badge bg-success"><?php echo $today_bookings; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Total Reviews</span>
                                        <span class="badge bg-primary"><?php echo $total_reviews; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Total Revenue</span>
                                        <span class="badge bg-success">$<?php echo number_format($total_revenue, 0); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Active Shows</span>
                                        <span class="badge bg-warning text-dark"><?php echo $active_shows; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-2">
                                        <span class="text-light">Wishlist Items</span>
                                        <span class="badge bg-info"><?php echo $total_wishlist; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card shadow-sm mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt me-2" style="color: #fbc531;"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="add-movie.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add New Movie
                                        </a>
                                        <a href="movies.php" class="btn btn-outline-primary">
                                            <i class="fas fa-film me-2"></i>Manage Movies
                                        </a>
                                        <a href="users.php" class="btn btn-outline-primary">
                                            <i class="fas fa-users me-2"></i>Manage Users
                                        </a>
                                        <a href="wishlist-stats.php" class="btn btn-outline-primary">
                                            <i class="fas fa-heart me-2"></i>Wishlist Stats
                                        </a>
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
</body>
</html>