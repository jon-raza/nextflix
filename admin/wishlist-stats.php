<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Wishlist.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php'; // Correct admin auth check

$page_title = "Wishlist Statistics";

// Get wishlist statistics
$sql = "SELECT 
            m.title,
            COUNT(w.id) as wishlist_count,
            m.id as movie_id
        FROM wishlist w
        JOIN movies m ON w.movie_id = m.id
        GROUP BY w.movie_id
        ORDER BY wishlist_count DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$wishlistStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total wishlist items
$totalWishlistSql = "SELECT COUNT(*) as total FROM wishlist";
$totalWishlistStmt = $pdo->prepare($totalWishlistSql);
$totalWishlistStmt->execute();
$totalWishlist = $totalWishlistStmt->fetch(PDO::FETCH_ASSOC);

// Total unique movies in wishlists
$uniqueMoviesSql = "SELECT COUNT(DISTINCT movie_id) as unique_movies FROM wishlist";
$uniqueMoviesStmt = $pdo->prepare($uniqueMoviesSql);
$uniqueMoviesStmt->execute();
$uniqueMovies = $uniqueMoviesStmt->fetch(PDO::FETCH_ASSOC);

// Most wishlisted movie
$mostWishlistedSql = "SELECT m.title, COUNT(w.id) as count 
                      FROM wishlist w 
                      JOIN movies m ON w.movie_id = m.id 
                      GROUP BY w.movie_id 
                      ORDER BY count DESC 
                      LIMIT 1";
$mostWishlistedStmt = $pdo->prepare($mostWishlistedSql);
$mostWishlistedStmt->execute();
$mostWishlisted = $mostWishlistedStmt->fetch(PDO::FETCH_ASSOC);
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
        
        .stats-card {
            background: linear-gradient(135deg, var(--netflix-dark) 0%, var(--netflix-gray) 100%);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            border-color: var(--netflix-red);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.15);
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .badge {
            font-size: 0.9rem;
            padding: 6px 12px;
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
                            <a class="nav-link active" href="wishlist-stats.php">
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
                            <i class="fas fa-heart me-2" style="color: var(--netflix-red);"></i>Wishlist Statistics
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="text-light me-3">
                                <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <!-- Total Wishlist Items -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Total Wishlist Items</h6>
                                            <h2 class="mb-1"><?php echo $totalWishlist['total']; ?></h2>
                                            <small class="text-success">All user wishlists</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-heart fa-2x" style="color: #e84393;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unique Movies -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Unique Movies</h6>
                                            <h2 class="mb-1"><?php echo $uniqueMovies['unique_movies']; ?></h2>
                                            <small class="text-success">Different movies in wishlists</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-film fa-2x" style="color: #00a8ff;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Most Wishlisted -->
                        <div class="col-xl-6 col-md-12">
                            <div class="card stats-card text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Most Wishlisted Movie</h6>
                                            <?php if ($mostWishlisted): ?>
                                                <h4 class="mb-1"><?php echo htmlspecialchars($mostWishlisted['title']); ?></h4>
                                                <small class="text-success">
                                                    <?php echo $mostWishlisted['count']; ?> users added to wishlist
                                                </small>
                                            <?php else: ?>
                                                <h4 class="mb-1">No data available</h4>
                                                <small class="text-muted">No wishlist items found</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-crown fa-2x" style="color: #fbc531;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wishlist Statistics Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2" style="color: var(--netflix-red);"></i>Most Wishlisted Movies
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($wishlistStats)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                                    <h4 class="text-white">No Wishlist Data Available</h4>
                                    <p class="text-muted">No movies have been added to wishlists yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Movie Title</th>
                                                <th>Wishlist Count</th>
                                                <th>Popularity</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($wishlistStats as $index => $stat): 
                                                $percentage = ($stat['wishlist_count'] / $totalWishlist['total']) * 100;
                                            ?>
                                                <tr>
                                                    <td class="fw-bold text-white">#<?php echo $index + 1; ?></td>
                                                    <td class="text-light"><?php echo htmlspecialchars($stat['title']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary fs-6">
                                                            <?php echo $stat['wishlist_count']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-danger"  
                                                                 style="width: <?php echo min($percentage, 100); ?>%">
                                                            </div>
                                                        </div>
                                                        <small class=" text-white"><?php echo number_format($percentage, 1); ?>%</small>
                                                    </td>
                                                    <td>
                                                        <a href="../user/movie-details.php?id=<?php echo $stat['movie_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-external-link-alt me-1"></i>View Movie
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Insights -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2" style="color: #00a8ff;"></i>Wishlist Distribution
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($wishlistStats)): ?>
                                        <p class="text-muted">No data available for chart.</p>
                                    <?php else: ?>
                                        <p class="text-light">
                                            Top 5 movies account for 
                                            <?php
                                            $top5Count = 0;
                                            for ($i = 0; $i < min(5, count($wishlistStats)); $i++) {
                                                $top5Count += $wishlistStats[$i]['wishlist_count'];
                                            }
                                            $top5Percentage = ($top5Count / $totalWishlist['total']) * 100;
                                            echo number_format($top5Percentage, 1);
                                            ?>% of all wishlist items.
                                        </p>
                                        <p class="text-light">
                                            Average wishlists per movie: 
                                            <?php echo number_format($totalWishlist['total'] / $uniqueMovies['unique_movies'], 1); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-lightbulb me-2" style="color: #fbc531;"></i>Insights
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled text-light">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Total wishlist items: <strong><?php echo $totalWishlist['total']; ?></strong>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Unique movies in wishlists: <strong><?php echo $uniqueMovies['unique_movies']; ?></strong>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Most popular movie: 
                                            <strong>
                                                <?php echo $mostWishlisted ? htmlspecialchars($mostWishlisted['title']) : 'N/A'; ?>
                                            </strong>
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Data last updated: <strong><?php echo date('F j, Y g:i A'); ?></strong>
                                        </li>
                                    </ul>
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