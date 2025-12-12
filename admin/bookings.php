<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Manage Bookings";

// ✅ NEW: Check if specific user filter is applied
$user_filter = '';
$user_id = null;
$user_details = null;

if(isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $user_filter = " AND b.user_id = " . intval($user_id);
    
    // Get user details for the header
    $user_stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_details = $user_stmt->fetch();
}

// Handle booking status update
if(isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    // Pehle current status get karo
    $current_stmt = $pdo->prepare("SELECT status, total_amount FROM bookings WHERE id = ?");
    $current_stmt->execute([$booking_id]);
    $current_booking = $current_stmt->fetch();
    $old_status = $current_booking ? $current_booking['status'] : '';
    $booking_amount = $current_booking ? $current_booking['total_amount'] : 0;
    
    // Fix: Agar status empty hai to pending samjho
    if(empty($old_status)) {
        $old_status = 'pending';
    }
    
    // Status update karo
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if($stmt->execute([$new_status, $booking_id])) {
        
        // ✅ IMPORTANT: Revenue stats handle karo
        if($old_status == 'confirmed' && $new_status == 'cancelled') {
            // Confirmed se cancelled hone par revenue se subtract karo
            $update_stmt = $pdo->prepare("
                UPDATE revenue_stats 
                SET total_revenue = GREATEST(0, total_revenue - ?),
                    monthly_revenue = GREATEST(0, monthly_revenue - ?),
                    weekly_revenue = GREATEST(0, weekly_revenue - ?),
                    daily_revenue = GREATEST(0, daily_revenue - ?),
                    last_updated = NOW()
                WHERE id = 1
            ");
            
            // Check karo booking date ke hisaab se
            $booking_date_stmt = $pdo->prepare("SELECT booking_date FROM bookings WHERE id = ?");
            $booking_date_stmt->execute([$booking_id]);
            $booking_date_result = $booking_date_stmt->fetch();
            $booking_date = $booking_date_result ? $booking_date_result['booking_date'] : date('Y-m-d H:i:s');
            
            $is_same_month = (date('Y-m', strtotime($booking_date)) == date('Y-m'));
            $is_same_week = (date('Y-W', strtotime($booking_date)) == date('Y-W'));
            $is_same_day = (date('Y-m-d', strtotime($booking_date)) == date('Y-m-d'));
            
            $monthly_deduct = $is_same_month ? $booking_amount : 0;
            $weekly_deduct = $is_same_week ? $booking_amount : 0;
            $daily_deduct = $is_same_day ? $booking_amount : 0;
            
            $update_stmt->execute([$booking_amount, $monthly_deduct, $weekly_deduct, $daily_deduct]);
        }
        elseif($old_status != 'confirmed' && $new_status == 'confirmed') {
            // Jab pending/cancelled se confirmed ho to revenue add karo
            try {
                $update_revenue_stmt = $pdo->prepare("CALL UpdateRevenueStats()");
                $update_revenue_stmt->execute();
            } catch (Exception $e) {
                error_log("Revenue update error: " . $e->getMessage());
            }
        }
        
        $_SESSION['success'] = "Booking status updated to " . ucfirst($new_status) . " successfully!";
    } else {
        $_SESSION['error'] = "Failed to update booking status!";
    }
    header("Location: bookings.php" . ($user_id ? "?user_id=" . $user_id : ""));
    exit();
}

// ✅ UPDATED: Handle booking deletion with revenue protection
if(isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Pehle booking details get karo
    $booking_stmt = $pdo->prepare("SELECT status, total_amount, booking_date FROM bookings WHERE id = ?");
    $booking_stmt->execute([$delete_id]);
    $booking_to_delete = $booking_stmt->fetch();
    
    if($booking_to_delete) {
        $booking_status = $booking_to_delete['status'];
        $booking_amount = $booking_to_delete['total_amount'];
        $booking_date = $booking_to_delete['booking_date'];
        
        // Fix: Agar status empty hai to pending samjho
        if(empty($booking_status)) {
            $booking_status = 'pending';
        }
        
        // Agar booking confirmed hai to pehle revenue adjust karo
        if($booking_status == 'confirmed') {
            // ✅ IMPORTANT: Confirmed booking delete karte time revenue se subtract karo
            $update_stmt = $pdo->prepare("
                UPDATE revenue_stats 
                SET total_revenue = GREATEST(0, total_revenue - ?),
                    monthly_revenue = GREATEST(0, monthly_revenue - ?),
                    weekly_revenue = GREATEST(0, weekly_revenue - ?),
                    daily_revenue = GREATEST(0, daily_revenue - ?),
                    last_updated = NOW()
                WHERE id = 1
            ");
            
            // Check karo booking date ke hisaab se
            $is_same_month = (date('Y-m', strtotime($booking_date)) == date('Y-m'));
            $is_same_week = (date('Y-W', strtotime($booking_date)) == date('Y-W'));
            $is_same_day = (date('Y-m-d', strtotime($booking_date)) == date('Y-m-d'));
            
            $monthly_deduct = $is_same_month ? $booking_amount : 0;
            $weekly_deduct = $is_same_week ? $booking_amount : 0;
            $daily_deduct = $is_same_day ? $booking_amount : 0;
            
            $update_stmt->execute([$booking_amount, $monthly_deduct, $weekly_deduct, $daily_deduct]);
        }
        
        // Ab booking delete karo
        $delete_stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        if($delete_stmt->execute([$delete_id])) {
            $_SESSION['success'] = "Booking deleted successfully!" . 
                                  ($booking_status == 'confirmed' ? 
                                   " (Revenue adjusted: -$" . $booking_amount . ")" : 
                                   "");
        } else {
            $_SESSION['error'] = "Failed to delete booking!";
        }
    } else {
        $_SESSION['error'] = "Booking not found!";
    }
    
    header("Location: bookings.php" . ($user_id ? "?user_id=" . $user_id : ""));
    exit();
}

// Get all bookings with user and movie details
$query = "SELECT 
    b.*,
    u.username as user_name,
    u.email as user_email,
    m.title as movie_title,
    m.thumbnail_url as movie_thumbnail,
    m.price as movie_price,
    c.name as cinema_name,
    c.location as cinema_location,
    s.show_date,
    s.show_time,
    s.available_seats
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN shows s ON b.show_id = s.id
JOIN movies m ON s.movie_id = m.id
JOIN cinemas c ON s.cinema_id = c.id
WHERE 1=1" . $user_filter . "
ORDER BY b.booking_date DESC";

$stmt = $pdo->query($query);
$bookings = $stmt->fetchAll();

// ✅ FIXED: Statistics with user filter - CORRECTED QUERIES
$total_bookings_query = "SELECT COUNT(*) FROM bookings WHERE 1=1" . ($user_id ? " AND user_id = " . $user_id : "");
$total_bookings = $pdo->query($total_bookings_query)->fetchColumn();

$confirmed_bookings_query = "SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'" . ($user_id ? " AND user_id = " . $user_id : "");
$confirmed_bookings = $pdo->query($confirmed_bookings_query)->fetchColumn();

$cancelled_bookings_query = "SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'" . ($user_id ? " AND user_id = " . $user_id : "");
$cancelled_bookings = $pdo->query($cancelled_bookings_query)->fetchColumn();

$pending_bookings_query = "SELECT COUNT(*) FROM bookings WHERE status = '' OR status IS NULL OR status = 'pending'" . ($user_id ? " AND user_id = " . $user_id : "");
$pending_bookings = $pdo->query($pending_bookings_query)->fetchColumn();

$total_revenue_query = "SELECT SUM(total_amount) FROM bookings WHERE status = 'confirmed'" . ($user_id ? " AND user_id = " . $user_id : "");
$total_revenue = $pdo->query($total_revenue_query)->fetchColumn() ?: 0;

// ✅ NEW: Pending revenue (potential revenue)
$pending_revenue_query = "SELECT SUM(total_amount) FROM bookings WHERE status = '' OR status IS NULL OR status = 'pending'" . ($user_id ? " AND user_id = " . $user_id : "");
$pending_revenue = $pdo->query($pending_revenue_query)->fetchColumn() ?: 0;

// ✅ NEW: Get revenue from revenue_stats table (actual stored revenue)
$revenue_stats_stmt = $pdo->query("SELECT total_revenue FROM revenue_stats WHERE id = 1");
$revenue_stats_data = $revenue_stats_stmt->fetch();
$actual_revenue = $revenue_stats_data ? $revenue_stats_data['total_revenue'] : 0;

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
            --netflix-white: #ffffff;
            --status-pending: #fdcb6e;
            --status-confirmed: #00b894;
            --status-cancelled: #d63031;
        }
        
        body {
            background: var(--netflix-black);
            color: var(--netflix-white);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: var(--netflix-dark);
            min-height: 100vh;
            border-right: 1px solid var(--netflix-gray);
            position: fixed;
            width: 250px;
            z-index: 1000;
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
            color: var(--netflix-white);
            border-left: 4px solid var(--netflix-red);
        }
        
        .main-content {
            background: var(--netflix-black);
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px;
        }
        
        .navbar {
            background: var(--netflix-dark) !important;
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1rem 2rem;
        }
        
        .card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            color: var(--netflix-white);
        }
        
        .card-header {
            background: var(--netflix-gray) !important;
            border-bottom: 1px solid var(--netflix-gray);
            color: var(--netflix-white);
            padding: 1.5rem;
        }
        
        .booking-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(229, 9, 20, 0.2);
        }
        
        /* ✅ UPDATED: Status border colors */
        .booking-pending { 
            border-left-color: var(--status-pending);
            background: rgba(253, 203, 110, 0.05);
        }
        .booking-confirmed { 
            border-left-color: var(--status-confirmed);
            background: rgba(0, 184, 148, 0.05);
        }
        .booking-cancelled { 
            border-left-color: var(--status-cancelled);
            background: rgba(214, 48, 49, 0.05);
        }
        
        .movie-thumbnail {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status-badge {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        /* ✅ UPDATED: Status badges */
        .badge-pending { 
            background: rgba(253, 203, 110, 0.2); 
            color: var(--status-pending);
            border: 1px solid rgba(253, 203, 110, 0.3);
        }
        .badge-confirmed { 
            background: rgba(0, 184, 148, 0.2); 
            color: var(--status-confirmed);
            border: 1px solid rgba(0, 184, 148, 0.3);
        }
        .badge-cancelled { 
            background: rgba(214, 48, 49, 0.2); 
            color: var(--status-cancelled);
            border: 1px solid rgba(214, 48, 49, 0.3);
        }
        
        .stats-card {
            background: var(--netflix-gray);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        /* ✅ UPDATED: Stat colors */
        .stat-total { color: #6f42c1; }
        .stat-pending { color: var(--status-pending); }
        .stat-confirmed { color: var(--status-confirmed); }
        .stat-cancelled { color: var(--status-cancelled); }
        .stat-revenue { color: #ffd700; }
        .stat-pending-revenue { color: #fdcb6e; }
        .stat-actual-revenue { color: #00b894; }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* ✅ UPDATED: Status dropdown styling */
        .status-dropdown {
            background: #333;
            border: 1px solid #444;
            color: white;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .status-dropdown:focus {
            background: #454545;
            border-color: var(--netflix-red);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
            color: white;
            outline: none;
        }
        
        /* ✅ NEW: Style for select options */
        .status-dropdown option {
            background: #333;
            color: white;
            padding: 10px;
        }
        
        .status-dropdown option[value="pending"] {
            background: rgba(253, 203, 110, 0.2);
            color: var(--status-pending);
        }
        
        .status-dropdown option[value="confirmed"] {
            background: rgba(0, 184, 148, 0.2);
            color: var(--status-confirmed);
        }
        
        .status-dropdown option[value="cancelled"] {
            background: rgba(214, 48, 49, 0.2);
            color: var(--status-cancelled);
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
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .empty-state {
            background: var(--netflix-dark);
            border: 2px dashed var(--netflix-gray);
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .text-muted {
            color: var(--netflix-light) !important;
        }
        
        /* ✅ NEW: User filter header styles */
        .user-filter-header {
            background: linear-gradient(45deg, #e50914, #b81d24);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .clear-filter-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        
        .clear-filter-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        /* ✅ NEW: Warning alert for confirmed bookings deletion */
        .warning-alert {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        /* ✅ NEW: Update status button */
        .update-status-btn {
            background: transparent;
            border: 1px solid #444;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
            width: 100%;
            margin-top: 5px;
        }
        
        .update-status-btn:hover {
            background: rgba(229, 9, 20, 0.1);
            border-color: var(--netflix-red);
        }
        
        /* ✅ NEW: Tooltip for status */
        .status-tooltip {
            font-size: 0.75rem;
            color: var(--netflix-light);
            margin-top: 3px;
        }
        
        /* ✅ NEW: Delete button with warning */
        .delete-btn-warning {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        
        .delete-btn-warning:hover {
            background: rgba(231, 76, 60, 0.2);
            border-color: #e74c3c;
            color: #ff6b6b;
        }
        
        /* ✅ NEW: Revenue protection info */
        .revenue-protection {
            background: rgba(52, 152, 219, 0.2);
            border: 1px solid #3498db;
            color: #3498db;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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
                            <a class="nav-link active" href="bookings.php">
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
                            <i class="fas fa-ticket-alt me-2" style="color: var(--netflix-red);"></i>
                            <?php 
                            if($user_details) {
                                echo "Bookings - " . htmlspecialchars($user_details['full_name']);
                            } else {
                                echo "Manage Bookings";
                            }
                            ?>
                        </span>
                        <span class="badge bg-primary fs-6">
                            <?php echo $total_bookings; ?> 
                            <?php echo $user_details ? 'User Bookings' : 'Total Bookings'; ?>
                        </span>
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

                    <!-- ✅ NEW: Revenue Protection Information -->
                    <!-- <div class="revenue-protection">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Revenue Protection:</strong> 
                        Confirmed bookings contribute to revenue. If deleted, revenue will be adjusted automatically. 
                        <a href="revenue.php" class="text-white ms-2">
                            <i class="fas fa-external-link-alt me-1"></i>View Revenue Dashboard
                        </a>
                    </div> -->

                    <!-- ✅ NEW: User Filter Header -->
                    <?php if($user_details): ?>
                    <div class="user-filter-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1 text-white">
                                    <i class="fas fa-user me-2"></i>
                                    Viewing bookings for: <strong><?php echo htmlspecialchars($user_details['full_name']); ?></strong>
                                </h5>
                                <small class="text-light">
                                    <i class="fas fa-at me-1"></i>@<?php echo htmlspecialchars($user_details['username']); ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="bookings.php" class="clear-filter-btn">
                                    <i class="fas fa-times me-1"></i>Show All Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ✅ UPDATED: Statistics Cards with Actual Revenue -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-total"><?php echo $total_bookings; ?></div>
                                <div class="stat-label"><?php echo $user_details ? 'User Bookings' : 'Total Bookings'; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-pending"><?php echo $pending_bookings; ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-confirmed"><?php echo $confirmed_bookings; ?></div>
                                <div class="stat-label">Confirmed</div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-cancelled"><?php echo $cancelled_bookings; ?></div>
                                <div class="stat-label">Cancelled</div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-actual-revenue">$<?php echo number_format($actual_revenue, 2); ?></div>
                                <div class="stat-label">Actual Revenue</div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stat-number stat-pending-revenue">$<?php echo number_format($pending_revenue, 2); ?></div>
                                <div class="stat-label">Pending Revenue</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bookings List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                <?php 
                                if($user_details) {
                                    echo "Bookings for " . htmlspecialchars($user_details['full_name']);
                                } else {
                                    echo "All Bookings";
                                }
                                ?>
                            </h5>
                            <?php if($user_details): ?>
                                <a href="bookings.php" class="btn btn-sm btn-outline-danger mt-2">
                                    <i class="fas fa-times me-1"></i>Clear Filter
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if($bookings): ?>
                                <div class="row">
                                    <?php foreach($bookings as $booking): 
                                        // ✅ IMPORTANT: Determine actual display status
                                        $display_status = $booking['status'];
                                        if(empty($display_status) || $display_status == '' || $display_status == 'completed') {
                                            $display_status = 'pending';
                                        }
                                    ?>
                                    <div class="col-12 mb-4">
                                        <div class="card booking-card booking-<?php echo $display_status; ?>">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-1">
                                                        <img src="<?php echo htmlspecialchars($booking['movie_thumbnail']); ?>" 
                                                             class="movie-thumbnail"
                                                             alt="<?php echo htmlspecialchars($booking['movie_title']); ?>"
                                                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgODAgMTAwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cmVjdCB3aWR0aD0iODAiIGhlaWdodD0iMTAwIiBmaWxsPSIjMzMzIi8+CjxwYXRoIGQ9Ik0zMiA0MEg0OFY2MEgzMloiIGZpbGw9IiM2NjYiLz4KPC9zdmc+'">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($booking['movie_title']); ?></h6>
                                                        <?php if(!$user_details): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($booking['user_name']); ?>
                                                        </small><br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?php echo htmlspecialchars($booking['user_email']); ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <small class="text-muted">Cinema</small>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['cinema_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['cinema_location']); ?></small>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <small class="text-muted">Show Time</small>
                                                        <div class="fw-bold"><?php echo format_date($booking['show_date']); ?></div>
                                                        <small class="text-muted"><?php echo date('g:i A', strtotime($booking['show_time'])); ?></small>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <small class="text-muted">Seats</small>
                                                        <div class="fw-bold"><?php echo $booking['seats_booked']; ?></div>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <small class="text-muted">Amount</small>
                                                        <div class="fw-bold <?php echo $display_status == 'confirmed' ? 'text-success' : ($display_status == 'pending' ? 'text-warning' : 'text-danger'); ?>">
                                                            $<?php echo $booking['total_amount']; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <!-- ✅ UPDATED: Status dropdown with only 2 options -->
                                                        <form method="POST" action="" class="status-form">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            
                                                            <select name="status" class="status-dropdown" 
                                                                    data-booking-id="<?php echo $booking['id']; ?>"
                                                                    onchange="updateStatus(this)">
                                                                <!-- ✅ ONLY 2 OPTIONS: cancelled and confirmed -->
                                                                <option value="cancelled" <?php echo $display_status == 'cancelled' ? 'selected' : ''; ?>>
                                                                    🚫 Cancelled
                                                                </option>
                                                                <option value="confirmed" <?php echo $display_status == 'confirmed' ? 'selected' : ''; ?>>
                                                                    ✅ Confirmed
                                                                </option>
                                                            </select>
                                                            
                                                            <!-- Status display -->
                                                            <div class="mt-2 text-center">
                                                                <span class="status-badge badge-<?php echo $display_status; ?>">
                                                                    <?php if($display_status == 'pending'): ?>
                                                                        <i class="fas fa-clock me-1"></i>Pending
                                                                    <?php elseif($display_status == 'confirmed'): ?>
                                                                        <i class="fas fa-check-circle me-1"></i>Confirmed
                                                                    <?php else: ?>
                                                                        <i class="fas fa-times-circle me-1"></i>Cancelled
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <!-- Booking Details Row -->
                                                <div class="row mt-3 pt-3 border-top">
                                                    <div class="col-md-8">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            Booked on: <?php echo format_date($booking['booking_date']); ?> at 
                                                            <?php echo date('g:i A', strtotime($booking['booking_date'])); ?>
                                                        </small>
                                                        <!-- ✅ NEW: Status explanation -->
                                                        <div class="status-tooltip">
                                                            <?php if($display_status == 'pending'): ?>
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                This booking is waiting for confirmation. Update to "Confirmed" to add to revenue.
                                                            <?php elseif($display_status == 'confirmed'): ?>
                                                                <i class="fas fa-check-circle me-1 text-success"></i>
                                                                This booking is confirmed and included in revenue calculations.
                                                            <?php else: ?>
                                                                <i class="fas fa-times-circle me-1 text-danger"></i>
                                                                This booking has been cancelled and is not included in revenue.
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <!-- ✅ UPDATED: Delete button with warning for confirmed bookings -->
                                                        <button type="button" class="btn btn-sm <?php echo $display_status == 'confirmed' ? 'delete-btn-warning' : 'btn-outline-danger'; ?>" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteBookingModal<?php echo $booking['id']; ?>"
                                                                title="<?php echo $display_status == 'confirmed' ? 'Deleting confirmed booking will adjust revenue' : 'Delete booking'; ?>">
                                                            <i class="fas fa-trash me-1"></i>
                                                            <?php echo $display_status == 'confirmed' ? 'Delete (Confirmed)' : 'Delete'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ UPDATED: Delete Confirmation Modal with Revenue Warning -->
                                        <div class="modal fade" id="deleteBookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <?php echo $display_status == 'confirmed' ? '⚠️ Delete Confirmed Booking' : 'Confirm Delete Booking'; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-light">Are you sure you want to delete this booking?</p>
                                                        
                                                        <?php if($display_status == 'confirmed'): ?>
                                                        <div class="warning-alert mb-3">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>Revenue Impact:</strong> 
                                                            This booking is confirmed and contributes to revenue. 
                                                            Deleting it will reduce total revenue by <strong>$<?php echo $booking['total_amount']; ?></strong>.
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="alert <?php echo $display_status == 'confirmed' ? 'alert-warning' : 'alert-info'; ?>">
                                                            <strong>Booking Details:</strong><br>
                                                            Movie: <?php echo htmlspecialchars($booking['movie_title']); ?><br>
                                                            User: <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                                            Amount: $<?php echo $booking['total_amount']; ?><br>
                                                            Status: <span class="badge badge-<?php echo $display_status; ?>">
                                                                <?php echo ucfirst($display_status); ?>
                                                            </span><br>
                                                            Show Date: <?php echo format_date($booking['show_date']); ?> at <?php echo date('g:i A', strtotime($booking['show_time'])); ?>
                                                        </div>
                                                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="bookings.php?delete_id=<?php echo $booking['id']; ?><?php echo $user_id ? '&user_id=' . $user_id : ''; ?>" 
                                                           class="btn <?php echo $display_status == 'confirmed' ? 'btn-warning' : 'btn-danger'; ?>"
                                                           onclick="return confirm('<?php echo $display_status == 'confirmed' ? 'This will reduce revenue by $' . $booking['total_amount'] . '. Are you sure?' : 'Are you sure?'; ?>')">
                                                            <i class="fas fa-trash me-1"></i>
                                                            <?php echo $display_status == 'confirmed' ? 'Delete & Adjust Revenue' : 'Delete Booking'; ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                                    <h3 class="text-white">No Bookings Found</h3>
                                    <p class="text-muted">
                                        <?php 
                                        if($user_details) {
                                            echo htmlspecialchars($user_details['full_name']) . " hasn't made any bookings yet.";
                                        } else {
                                            echo "No bookings have been made yet.";
                                        }
                                        ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ✅ UPDATED: Status update function with confirmation and revenue warning
        function updateStatus(selectElement) {
            const bookingId = selectElement.getAttribute('data-booking-id');
            const newStatus = selectElement.value;
            const currentStatus = selectElement.options[selectElement.selectedIndex]?.text.includes('✅') ? 'confirmed' : 
                                 selectElement.options[selectElement.selectedIndex]?.text.includes('🚫') ? 'cancelled' : 'pending';
            
            // Get booking amount from the row
            const amountElement = selectElement.closest('.row').querySelector('.fw-bold.text-success, .fw-bold.text-warning, .fw-bold.text-danger');
            const bookingAmount = amountElement ? amountElement.textContent.replace('$', '').trim() : '0';
            
            let message = "";
            if(newStatus === 'confirmed') {
                message = "Confirm this booking? This will add $" + bookingAmount + " to revenue.";
            } else if(newStatus === 'cancelled' && currentStatus === 'confirmed') {
                message = "Cancel this confirmed booking? This will reduce revenue by $" + bookingAmount + ".";
            } else if(newStatus === 'cancelled') {
                message = "Cancel this booking?";
            }
            
            if(message && confirm(message)) {
                selectElement.closest('form').submit();
            } else {
                // Don't submit if cancelled
                event.preventDefault();
                return false;
            }
        }
        
        // ✅ NEW: Auto-submit form when status changes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-dropdown').forEach(select => {
                select.addEventListener('change', function(event) {
                    // Let the updateStatus function handle confirmation
                    // If it returns false, prevent form submission
                    return updateStatus(this);
                });
            });
        });
    </script>
</body>
</html>