<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Edit Movie";

if(!isset($_GET['id'])) {
    header("Location: movies.php");
    exit();
}

$movie_id = $_GET['id'];

// Get movie data
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch();

if(!$movie) {
    header("Location: movies.php");
    exit();
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $genre = sanitize_input($_POST['genre']);
    $duration = sanitize_input($_POST['duration']);
    $release_year = $_POST['release_year'];
    $thumbnail_url = sanitize_input($_POST['thumbnail_url']);
    $trailer_embed_code = $_POST['trailer_embed_code'];
    $price = $_POST['price'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if(empty($title) || empty($description) || empty($genre)) {
        $error = "Please fill in all required fields!";
    } else {
        $stmt = $pdo->prepare("UPDATE movies SET title = ?, description = ?, genre = ?, duration = ?, release_year = ?, thumbnail_url = ?, trailer_embed_code = ?, price = ?, is_featured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        
        if($stmt->execute([$title, $description, $genre, $duration, $release_year, $thumbnail_url, $trailer_embed_code, $price, $is_featured, $movie_id])) {
            $success = "Movie updated successfully!";
            // Refresh movie data
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
            $stmt->execute([$movie_id]);
            $movie = $stmt->fetch();
        } else {
            $error = "Failed to update movie! Please try again.";
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
            <link rel="icon" type="image/x-icon" href="../assets/images/Nextflixfavicon.png">

    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #3498db;
            color: white;
        }
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
                        <i class="fas fa-film fa-2x text-warning mb-2"></i>
                        <h4 class="text-white">Nextflix Admin</h4>
                        <small class="text-light">Welcome, <?php echo $_SESSION['admin_full_name']; ?></small>
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
                <nav class="navbar navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">
                            <i class="fas fa-edit me-2 text-warning"></i>Edit Movie
                        </span>
                        <div>
                            <a href="movies.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Movies
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="form-container p-4">
                                <?php if($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Movie Title *</label>
                                            <input type="text" class="form-control" name="title" value="<?php echo $movie['title']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Genre *</label>
                                            <input type="text" class="form-control" name="genre" value="<?php echo $movie['genre']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea class="form-control" name="description" rows="4" required><?php echo $movie['description']; ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Duration</label>
                                            <input type="text" class="form-control" name="duration" value="<?php echo $movie['duration']; ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Release Year</label>
                                            <input type="number" class="form-control" name="release_year" value="<?php echo $movie['release_year']; ?>" min="1900" max="<?php echo date('Y'); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Price ($) *</label>
                                            <input type="number" class="form-control" name="price" value="<?php echo $movie['price']; ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Thumbnail URL *</label>
                                        <input type="url" class="form-control" name="thumbnail_url" value="<?php echo $movie['thumbnail_url']; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">YouTube Trailer Embed Code *</label>
                                        <textarea class="form-control" name="trailer_embed_code" rows="3" required><?php echo $movie['trailer_embed_code']; ?></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo $movie['is_featured'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_featured">
                                                Feature this movie on homepage
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="movies.php" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save me-2"></i>Update Movie
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Movie Stats -->
                            <div class="card mt-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Movie Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3 mb-3">
                                            <?php
                                            $bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN shows s ON b.show_id = s.id WHERE s.movie_id = ?");
                                            $bookings_stmt->execute([$movie_id]);
                                            $total_bookings = $bookings_stmt->fetchColumn();
                                            ?>
                                            <div class="border rounded p-3 bg-primary text-white">
                                                <h4 class="mb-1"><?php echo $total_bookings; ?></h4>
                                                <small>Total Bookings</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <?php
                                            $reviews_stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE movie_id = ?");
                                            $reviews_stmt->execute([$movie_id]);
                                            $total_reviews = $reviews_stmt->fetchColumn();
                                            ?>
                                            <div class="border rounded p-3 bg-success text-white">
                                                <h4 class="mb-1"><?php echo $total_reviews; ?></h4>
                                                <small>Total Reviews</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <?php
                                            $avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE movie_id = ?");
                                            $avg_rating_stmt->execute([$movie_id]);
                                            $avg_rating = $avg_rating_stmt->fetchColumn();
                                            ?>
                                            <div class="border rounded p-3 bg-warning text-dark">
                                                <h4 class="mb-1"><?php echo $avg_rating ? number_format($avg_rating, 1) : '0.0'; ?></h4>
                                                <small>Average Rating</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="border rounded p-3 bg-info text-white">
                                                <h4 class="mb-1">$<?php echo number_format($movie['price'], 2); ?></h4>
                                                <small>Ticket Price</small>
                                            </div>
                                        </div>
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