<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "User Revenue Report";

// Search functionality
$search = '';
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Build query with search - FIXED: Added proper joins for bookings
$query = "
    SELECT 
        u.id,
        u.full_name,
        u.username,
        u.email,
        u.profile_picture,
        u.created_at,
        COUNT(b.id) as total_bookings,
        COALESCE(SUM(b.total_amount), 0) as total_revenue
    FROM users u 
    LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed'
";

if(!empty($search)) {
    $query .= " WHERE u.full_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search";
}

$query .= " GROUP BY u.id ORDER BY total_revenue DESC";

try {
    $stmt = $pdo->prepare($query);
    
    if(!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("User revenue query error: " . $e->getMessage());
    $users = [];
}

// Total statistics - FIXED: Correct query
$total_stats_stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(b.id) as total_bookings,
        COALESCE(SUM(b.total_amount), 0) as total_revenue
    FROM users u 
    LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed'
");
$total_stats = $total_stats_stmt->fetch(PDO::FETCH_ASSOC);
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
            --netflix-white: #FFFFFF;
        }
        
        body {
            background: var(--netflix-black);
            color: var(--netflix-white);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .sidebar {
            background: var(--netflix-dark);
            min-height: 100vh;
            border-right: 1px solid var(--netflix-gray);
            padding: 0;
        }
        
        .sidebar .nav-link {
            color: var(--netflix-light);
            padding: 15px 20px;
            margin: 2px 0;
            border-radius: 0;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--netflix-gray);
            color: var(--netflix-white);
            border-left: 4px solid var(--netflix-red);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            background: var(--netflix-black);
            min-height: 100vh;
        }
        
        .navbar {
            background: var(--netflix-dark) !important;
            border-bottom: 1px solid var(--netflix-gray);
            padding: 1rem;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, var(--netflix-dark) 0%, var(--netflix-gray) 100%);
            border: 1px solid var(--netflix-gray);
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: var(--netflix-white);
        }
        
        .revenue-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .revenue-card.total::before { background: linear-gradient(90deg, #e17055, #d63031); }
        .revenue-card.users::before { background: linear-gradient(90deg, #00b894, #00cec9); }
        .revenue-card.bookings::before { background: linear-gradient(90deg, #0984e3, #74b9ff); }
        .revenue-card.avg::before { background: linear-gradient(90deg, #a29bfe, #6c5ce7); }
        
        .revenue-card:hover {
            transform: translateY(-5px);
            border-color: var(--netflix-red);
            box-shadow: 0 10px 30px rgba(229, 9, 20, 0.2);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--netflix-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--netflix-light);
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid var(--netflix-red);
        }
        
        .table {
            color: var(--netflix-white);
            background: var(--netflix-dark);
        }
        
        .table th {
            background: var(--netflix-gray);
            border-color: var(--netflix-gray);
            color: var(--netflix-white);
            font-weight: 600;
            padding: 1rem;
        }
        
        .table td {
            border-color: var(--netflix-gray);
            background: var(--netflix-dark);
            vertical-align: middle;
            padding: 1rem;
            color: var(--netflix-white);
        }
        
        .table-hover tbody tr:hover {
            background: var(--netflix-gray);
            color: var(--netflix-white);
            transform: scale(1.01);
            transition: all 0.2s;
        }
        
        .revenue-badge {
            background: linear-gradient(45deg, #00b894, #00cec9);
            color: var(--netflix-white);
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            border: none;
        }
        
        .bookings-badge {
            background: linear-gradient(45deg, #0984e3, #74b9ff);
            color: var(--netflix-white);
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            border: none;
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .search-box {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            color: var(--netflix-white);
            border-radius: 25px;
            padding: 10px 20px;
        }
        
        .search-box::placeholder {
            color: var(--netflix-light);
        }
        
        .search-box:focus {
            background: var(--netflix-dark);
            border-color: var(--netflix-red);
            color: var(--netflix-white);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--netflix-light);
        }
        
        .user-row {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-row:hover {
            background: var(--netflix-gray) !important;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--netflix-white);
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: var(--netflix-light);
        }
        
        .card {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            color: var(--netflix-white);
        }
        
        .card-header {
            background: var(--netflix-gray) !important;
            border-bottom: 1px solid var(--netflix-gray);
            color: var(--netflix-white);
            padding: 1rem 1.5rem;
        }
        
        .btn-danger {
            background: var(--netflix-red);
            border: none;
            color: var(--netflix-white);
        }
        
        .btn-danger:hover {
            background: #b81d24;
            color: var(--netflix-white);
        }
        
        .btn-outline-danger {
            border: 1px solid var(--netflix-red);
            color: var(--netflix-red);
            background: transparent;
        }
        
        .btn-outline-danger:hover {
            background: var(--netflix-red);
            color: var(--netflix-white);
        }
        
        .text-muted {
            color: var(--netflix-light) !important;
        }
        
        .text-light {
            color: var(--netflix-light) !important;
        }
        
        .text-white {
            color: var(--netflix-white) !important;
        }
        
        /* FIXED: Ensure all text is visible */
        .table td, .table th,
        .card-body, .card-header,
        .navbar-brand, .stats-number,
        .revenue-card, .user-avatar {
            color: var(--netflix-white) !important;
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
                            <a class="nav-link" href="revenue.php">
                                <i class="fas fa-chart-line me-2"></i>Revenue
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user-revenue.php">
                                <i class="fas fa-user-check me-2"></i>User Revenue
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
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
                            <i class="fas fa-user-check me-2" style="color: var(--netflix-red);"></i>User Revenue Report
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="text-light">
                                <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                </nav>

                <!-- Statistics Cards -->
                <div class="container-fluid mt-4">
                    <div class="row g-4">
                        <!-- Total Users -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card users text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="stats-number"><?php echo $total_stats['total_users'] ?? 0; ?></div>
                                            <div class="stats-label">Total Users</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x" style="color: #00b894;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Bookings -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card bookings text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="stats-number"><?php echo $total_stats['total_bookings'] ?? 0; ?></div>
                                            <div class="stats-label">Confirmed Bookings</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-ticket-alt fa-2x" style="color: #0984e3;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card total text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="stats-number">$<?php echo number_format($total_stats['total_revenue'] ?? 0, 2); ?></div>
                                            <div class="stats-label">Total Revenue</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-dollar-sign fa-2x" style="color: #e17055;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Revenue Per User -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card revenue-card avg text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <?php
                                            $avg_revenue = 0;
                                            if(isset($total_stats['total_users']) && $total_stats['total_users'] > 0) {
                                                $avg_revenue = $total_stats['total_revenue'] / $total_stats['total_users'];
                                            }
                                            ?>
                                            <div class="stats-number">$<?php echo number_format($avg_revenue, 2); ?></div>
                                            <div class="stats-label">Avg. Revenue Per User</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-bar fa-2x" style="color: #a29bfe;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Users Table -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-white">
                                        <i class="fas fa-list me-2" style="color: var(--netflix-red);"></i>Users Revenue Summary
                                    </h5>
                                    <form method="GET" action="" class="d-flex">
                                        <input type="text" name="search" class="form-control search-box me-2" 
                                               placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if(!empty($search)): ?>
                                            <a href="user-revenue.php" class="btn btn-outline-danger ms-2">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($users)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th class="text-white">User</th>
                                                        <th class="text-white">Member Since</th>
                                                        <th class="text-white">Total Bookings</th>
                                                        <th class="text-white">Total Revenue</th>
                                                        <th class="text-white">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($users as $user): ?>
                                                    <tr class="user-row">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if(!empty($user['profile_picture']) && $user['profile_picture'] != 'default.jpg'): ?>
                                                                    <img src="../assets/images/profiles/<?php echo $user['profile_picture']; ?>" 
                                                                         class="user-avatar me-3" 
                                                                         alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                         onerror="this.style.display='none';">
                                                                <?php endif; ?>
                                                                <div class="user-avatar me-3">
                                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                                </div>
                                                                <div>
                                                                    <div class="text-white fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                                    <small class="text-light">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                                    <br>
                                                                    <small class="text-light"><?php echo htmlspecialchars($user['email']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-white">
                                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                        </td>
                                                        <td>
                                                            <span class="bookings-badge">
                                                                <i class="fas fa-ticket-alt me-1"></i>
                                                                <?php echo $user['total_bookings']; ?> bookings
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="revenue-badge">
                                                                <i class="fas fa-dollar-sign me-1"></i>
                                                                $<?php echo number_format($user['total_revenue'], 2); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <!-- FIXED: Changed to user bookings page that exists -->
                                                            <a href="bookings.php?user_id=<?php echo $user['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-eye me-1"></i>View Bookings
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <h4 class="text-white">No Users Found</h4>
                                            <p class="text-light">
                                                <?php echo !empty($search) ? 'No users match your search criteria.' : 'No users found in the system.'; ?>
                                            </p>
                                            <?php if(!empty($search)): ?>
                                                <a href="user-revenue.php" class="btn btn-danger mt-2">
                                                    <i class="fas fa-undo me-2"></i>Clear Search
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
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
        // FIXED: Row click functionality - only navigate if View Bookings link exists
        document.querySelectorAll('.user-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on buttons, links, or badges
                if(!e.target.closest('a') && !e.target.closest('button') && !e.target.closest('.badge')) {
                    const viewBtn = this.querySelector('a.btn');
                    if(viewBtn) {
                        window.location.href = viewBtn.href;
                    }
                }
            });
        });

        // Auto-focus search box
        document.addEventListener('DOMContentLoaded', function() {
            const searchBox = document.querySelector('input[name="search"]');
            if(searchBox) {
                searchBox.focus();
            }
        });
    </script>
</body>
</html>