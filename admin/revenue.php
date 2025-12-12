<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Revenue Dashboard";

// Initialize default values to prevent errors
$revenue_stats = [
    'total_revenue' => 0,
    'daily_revenue' => 0,
    'weekly_revenue' => 0,
    'monthly_revenue' => 0,
    'last_updated' => date('Y-m-d H:i:s')
];

try {
    // Revenue stats update karo
    $update_revenue_stmt = $pdo->prepare("CALL UpdateRevenueStats()");
    $update_revenue_stmt->execute();

    // Current revenue stats get karo with error handling
    $revenue_stmt = $pdo->query("SELECT * FROM revenue_stats WHERE id = 1");
    $revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revenue_data) {
        $revenue_stats = $revenue_data;
    } else {
        // Insert initial record if it doesn't exist
        $init_stmt = $pdo->prepare("
            INSERT INTO revenue_stats (id, total_revenue, daily_revenue, weekly_revenue, monthly_revenue, last_updated) 
            VALUES (1, 0, 0, 0, 0, NOW())
        ");
        $init_stmt->execute();
    }
} catch (Exception $e) {
    error_log("Revenue stats error: " . $e->getMessage());
}

// Revenue growth calculations with error handling
$yesterday_revenue = 0;
$last_week_revenue = 0;
$last_month_revenue = 0;

try {
    $yesterday_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'confirmed' AND DATE(booking_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $yesterday_data = $yesterday_stmt->fetch(PDO::FETCH_ASSOC);
    $yesterday_revenue = $yesterday_data ? $yesterday_data['revenue'] : 0;

    $last_week_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'confirmed' AND YEARWEEK(booking_date) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))");
    $last_week_data = $last_week_stmt->fetch(PDO::FETCH_ASSOC);
    $last_week_revenue = $last_week_data ? $last_week_data['revenue'] : 0;

    $last_month_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM bookings WHERE status = 'confirmed' AND YEAR(booking_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(booking_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $last_month_data = $last_month_stmt->fetch(PDO::FETCH_ASSOC);
    $last_month_revenue = $last_month_data ? $last_month_data['revenue'] : 0;
} catch (Exception $e) {
    error_log("Revenue growth calculation error: " . $e->getMessage());
}

// Growth percentages calculate karo with safe calculations
$daily_growth = 0;
$weekly_growth = 0;
$monthly_growth = 0;

if ($yesterday_revenue > 0) {
    $daily_growth = (($revenue_stats['daily_revenue'] - $yesterday_revenue) / $yesterday_revenue) * 100;
}

if ($last_week_revenue > 0) {
    $weekly_growth = (($revenue_stats['weekly_revenue'] - $last_week_revenue) / $last_week_revenue) * 100;
}

if ($last_month_revenue > 0) {
    $monthly_growth = (($revenue_stats['monthly_revenue'] - $last_month_revenue) / $last_month_revenue) * 100;
}

// Recent revenue transactions
$recent_transactions = [];
try {
    $recent_transactions_stmt = $pdo->query("
        SELECT b.*, u.username, u.full_name, m.title, m.thumbnail_url 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN shows s ON b.show_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        WHERE b.status = 'confirmed' 
        ORDER BY b.booking_date DESC 
        LIMIT 10
    ");
    $recent_transactions = $recent_transactions_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Recent transactions error: " . $e->getMessage());
}

// Monthly revenue data for chart - ALL DATA (not just current year)
$monthly_data = [];
try {
    $monthly_data_stmt = $pdo->query("
        SELECT 
            MONTHNAME(booking_date) as month_name,
            MONTH(booking_date) as month_num,
            YEAR(booking_date) as year,
            SUM(total_amount) as monthly_revenue
        FROM bookings 
        WHERE status = 'confirmed'
        GROUP BY YEAR(booking_date), MONTH(booking_date), MONTHNAME(booking_date)
        ORDER BY year, month_num
        LIMIT 12
    ");
    $monthly_data = $monthly_data_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Monthly data error: " . $e->getMessage());
}

// If no monthly data, create demo data for chart
if (empty($monthly_data)) {
    $monthly_data = [
        ['month_name' => 'January', 'monthly_revenue' => 1500],
        ['month_name' => 'February', 'monthly_revenue' => 2200],
        ['month_name' => 'March', 'monthly_revenue' => 1800],
        ['month_name' => 'April', 'monthly_revenue' => 2500],
        ['month_name' => 'May', 'monthly_revenue' => 3000],
        ['month_name' => 'June', 'monthly_revenue' => 2800],
        ['month_name' => 'July', 'monthly_revenue' => 3200],
        ['month_name' => 'August', 'monthly_revenue' => 3500],
        ['month_name' => 'September', 'monthly_revenue' => 3800],
        ['month_name' => 'October', 'monthly_revenue' => 4000],
        ['month_name' => 'November', 'monthly_revenue' => 4200],
        ['month_name' => 'December', 'monthly_revenue' => 4500]
    ];
}

// Additional revenue insights with error handling
$avg_booking = 0;
$top_movie = ['title' => 'No data', 'revenue' => 0];
$confirmed_bookings_count = 0;

try {
    $avg_booking_stmt = $pdo->query("SELECT COALESCE(AVG(total_amount), 0) as avg_booking FROM bookings WHERE status = 'confirmed'");
    $avg_booking_data = $avg_booking_stmt->fetch(PDO::FETCH_ASSOC);
    $avg_booking = $avg_booking_data ? $avg_booking_data['avg_booking'] : 0;
    
    $top_movie_stmt = $pdo->query("
        SELECT m.title, COALESCE(SUM(b.total_amount), 0) as revenue 
        FROM bookings b 
        JOIN shows s ON b.show_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        WHERE b.status = 'confirmed' 
        GROUP BY m.id 
        ORDER BY revenue DESC 
        LIMIT 1
    ");
    $top_movie_data = $top_movie_stmt->fetch(PDO::FETCH_ASSOC);
    if ($top_movie_data) {
        $top_movie = $top_movie_data;
    }
    
    $confirmed_stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
    $confirmed_data = $confirmed_stmt->fetch(PDO::FETCH_ASSOC);
    $confirmed_bookings_count = $confirmed_data ? $confirmed_data['count'] : 0;
} catch (Exception $e) {
    error_log("Revenue insights error: " . $e->getMessage());
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

    <style>
        :root {
            --netflix-red: #e50914;
            --netflix-black: #141414;
            --netflix-dark: #181818;
            --netflix-gray: #2F2F2F;
            --netflix-light: #B3B3B3;
            --revenue-green: #00b894;
            --revenue-blue: #0984e3;
            --revenue-purple: #a29bfe;
            --revenue-orange: #fdcb6e;
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
        
        .revenue-card {
            background: linear-gradient(135deg, var(--netflix-dark) 0%, var(--netflix-gray) 100%);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .revenue-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--revenue-green), var(--revenue-blue));
        }
        
        .revenue-card:hover {
            transform: translateY(-5px);
            border-color: var(--netflix-red);
            box-shadow: 0 10px 30px rgba(229, 9, 20, 0.2);
        }
        
        .revenue-card.total::before { background: linear-gradient(90deg, #e17055, #d63031); }
        .revenue-card.daily::before { background: linear-gradient(90deg, #00b894, #00cec9); }
        .revenue-card.weekly::before { background: linear-gradient(90deg, #0984e3, #74b9ff); }
        .revenue-card.monthly::before { background: linear-gradient(90deg, #a29bfe, #6c5ce7); }
        
        .growth-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        .growth-positive {
            background: rgba(0, 184, 148, 0.2);
            color: #00b894;
            border: 1px solid rgba(0, 184, 148, 0.3);
        }
        
        .growth-negative {
            background: rgba(214, 48, 49, 0.2);
            color: #d63031;
            border: 1px solid rgba(214, 48, 49, 0.3);
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
        
        .transaction-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--netflix-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--netflix-light);
            font-weight: bold;
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .revenue-chart-container {
            background: var(--netflix-dark);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--netflix-gray);
        }
        
        .last-updated {
            font-size: 0.8rem;
            color: var(--netflix-light);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Enhanced Chart Styles */
        .chart-tooltip {
            background: rgba(24, 24, 24, 0.95) !important;
            border: 2px solid #e50914 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
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
                            <a class="nav-link active" href="revenue.php">
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
                            <i class="fas fa-chart-line me-2" style="color: var(--netflix-red);"></i>Revenue Dashboard
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="text-light me-3">
                                <i class="fas fa-sync-alt me-2"></i>
                                <span class="last-updated">Updated: <?php echo date('M j, Y g:i A', strtotime($revenue_stats['last_updated'])); ?></span>
                            </span>
                            <span class="text-light">
                                <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                </nav>

                <!-- Revenue Stats Cards -->
                <div class="container-fluid mt-4">
                    <div class="row g-4">
                        <!-- Total Revenue -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card total text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Total Revenue</h6>
                                            <h2 class="mb-1">$<?php echo number_format($revenue_stats['total_revenue'], 2); ?></h2>
                                            <small class="text-success">All-time confirmed bookings</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-dollar-sign fa-2x" style="color: #e17055;"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge growth-badge <?php echo $monthly_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="fas fa-<?php echo $monthly_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                            <?php echo number_format(abs($monthly_growth), 1); ?>%
                                        </span>
                                        <small class="text-light ms-2">vs last month</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Revenue -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card daily text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Today's Revenue</h6>
                                            <h2 class="mb-1">$<?php echo number_format($revenue_stats['daily_revenue'], 2); ?></h2>
                                            <small class="text-success">Confirmed bookings today</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-day fa-2x" style="color: #00b894;"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge growth-badge <?php echo $daily_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="fas fa-<?php echo $daily_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                            <?php echo number_format(abs($daily_growth), 1); ?>%
                                        </span>
                                        <small class="text-light ms-2">vs yesterday</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Revenue -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card weekly text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Weekly Revenue</h6>
                                            <h2 class="mb-1">$<?php echo number_format($revenue_stats['weekly_revenue'], 2); ?></h2>
                                            <small class="text-success">This week's confirmed bookings</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-week fa-2x" style="color: #0984e3;"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge growth-badge <?php echo $weekly_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="fas fa-<?php echo $weekly_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                            <?php echo number_format(abs($weekly_growth), 1); ?>%
                                        </span>
                                        <small class="text-light ms-2">vs last week</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Revenue -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card monthly text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title text-light">Monthly Revenue</h6>
                                            <h2 class="mb-1">$<?php echo number_format($revenue_stats['monthly_revenue'], 2); ?></h2>
                                            <small class="text-success">This month's confirmed bookings</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-alt fa-2x" style="color: #a29bfe;"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge growth-badge <?php echo $monthly_growth >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <i class="fas fa-<?php echo $monthly_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                            <?php echo number_format(abs($monthly_growth), 1); ?>%
                                        </span>
                                        <small class="text-light ms-2">vs last month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Transactions -->
                    <div class="row mt-4">
                        <!-- Revenue Chart -->
                        <div class="col-md-8">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2" style="color: var(--netflix-red);"></i>Monthly Revenue Trend
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="revenue-chart-container">
                                        <div class="chart-container">
                                            <canvas id="revenueChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tachometer-alt me-2" style="color: #e50914;"></i>Revenue Insights
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Avg. Booking Value</span>
                                        <span class="badge bg-info">$<?php echo number_format($avg_booking, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Top Movie</span>
                                        <span class="badge bg-success">$<?php echo number_format($top_movie['revenue'], 0); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <small class="text-light"><?php echo htmlspecialchars($top_movie['title']); ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color: var(--netflix-gray) !important;">
                                        <span class="text-light">Confirmed Bookings</span>
                                        <span class="badge bg-primary">
                                            <?php echo $confirmed_bookings_count; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-2">
                                        <span class="text-light">Revenue Growth</span>
                                        <span class="badge <?php echo $monthly_growth >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo number_format($monthly_growth, 1); ?>%
                                        </span>
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
                                        <a href="bookings.php" class="btn btn-danger">
                                            <i class="fas fa-ticket-alt me-2"></i>Manage Bookings
                                        </a>
                                        <a href="movies.php" class="btn btn-outline-danger">
                                            <i class="fas fa-film me-2"></i>View Movies
                                        </a>
                                        <a href="dashboard.php" class="btn btn-outline-danger">
                                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-receipt me-2" style="color: var(--netflix-red);"></i>Recent Revenue Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Movie</th>
                                                    <th>Date</th>
                                                    <th>Seats</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($recent_transactions)): ?>
                                                    <?php foreach($recent_transactions as $transaction): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="transaction-avatar me-2">
                                                                    <?php echo strtoupper(substr($transaction['full_name'] ?? 'U', 0, 1)); ?>
                                                                </div>
                                                                <div>
                                                                    <div class="text-light"><?php echo htmlspecialchars($transaction['full_name'] ?? 'Unknown User'); ?></div>
                                                                    <small class="text-light">@<?php echo htmlspecialchars($transaction['username'] ?? 'unknown'); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-light"><?php echo htmlspecialchars($transaction['title'] ?? 'Unknown Movie'); ?></td>
                                                        <td class="text-light"><?php echo format_date($transaction['booking_date'] ?? date('Y-m-d H:i:s')); ?></td>
                                                        <td class="text-light"><?php echo $transaction['seats_booked'] ?? 0; ?></td>
                                                        <td class="text-success fw-bold">$<?php echo $transaction['total_amount'] ?? '0.00'; ?></td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Confirmed
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4">
                                                            <i class="fas fa-receipt fa-2x mb-2"></i>
                                                            <p>No recent transactions found</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
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
    <script>
        // Enhanced Professional Line Chart - SMOOTH AND BEAUTIFUL
        const monthlyLabels = [<?php 
            if (!empty($monthly_data)) {
                $labels = [];
                foreach($monthly_data as $item) {
                    $labels[] = "'" . $item['month_name'] . "'";
                }
                echo implode(',', $labels);
            } else {
                echo "'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'";
            }
        ?>];
        
        const monthlyRevenue = [<?php 
            if (!empty($monthly_data)) {
                $revenues = [];
                foreach($monthly_data as $item) {
                    $revenues[] = $item['monthly_revenue'];
                }
                echo implode(',', $revenues);
            } else {
                echo "1500, 2200, 1800, 2500, 3000, 2800, 3200, 3500, 3800, 4000, 4200, 4500";
            }
        ?>];

        // Create enhanced line chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Revenue Trend ($)',
                    data: monthlyRevenue,
                    backgroundColor: 'rgba(229, 9, 20, 0.15)',
                    borderColor: '#e50914',
                    borderWidth: 4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#e50914',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHoverBackgroundColor: '#e50914',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    cubicInterpolationMode: 'monotone'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#B3B3B3',
                            font: {
                                size: 13,
                                weight: 'bold',
                                family: "'Helvetica Neue', Helvetica, Arial, sans-serif"
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(24, 24, 24, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#e50914',
                        borderWidth: 2,
                        padding: 15,
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return `Revenue: $${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#B3B3B3',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#B3B3B3',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            padding: 10
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear'
                    }
                }
            }
        });

        // Auto-refresh revenue data every 2 minutes
        setInterval(() => {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>