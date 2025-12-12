<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'admin-auth-check.php';

$page_title = "Add New Movie";

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
    
    // Validate inputs
    if(empty($title) || empty($description) || empty($genre)) {
        $error = "Please fill in all required fields!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, duration, release_year, thumbnail_url, trailer_embed_code, price, is_featured, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if($stmt->execute([$title, $description, $genre, $duration, $release_year, $thumbnail_url, $trailer_embed_code, $price, $is_featured, $_SESSION['admin_id']])) {
            $success = "Movie added successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error = "Failed to add movie! Please try again.";
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
        
        .form-container {
            background: var(--netflix-dark);
            border: 1px solid var(--netflix-gray);
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .form-control {
            background: #333;
            border: 1px solid #444;
            color: white;
            border-radius: 4px;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            background: #454545;
            color: white;
            border-color: var(--netflix-red);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .form-label {
            color: var(--netflix-light);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-text {
            color: #8c8c8c !important;
        }
        
        .btn-success {
            background: var(--netflix-red);
            border: none;
            font-weight: 600;
            padding: 12px 30px;
        }
        
        .btn-success:hover {
            background: #f40612;
            transform: scale(1.02);
        }
        
        .btn-outline-secondary {
            border-color: #666;
            color: #666;
        }
        
        .btn-outline-secondary:hover {
            background: #666;
            border-color: #666;
            color: white;
        }
        
        .btn-outline-primary {
            border-color: var(--netflix-red);
            color: var(--netflix-red);
        }
        
        .btn-outline-primary:hover {
            background: var(--netflix-red);
            border-color: var(--netflix-red);
            color: white;
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
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }
        
        .alert-danger {
            background: rgba(229, 9, 20, 0.2);
            border: 1px solid var(--netflix-red);
            color: #ff6b6b;
        }
        
        .form-check-input {
            background: #333;
            border: 1px solid #666;
        }
        
        .form-check-input:checked {
            background: var(--netflix-red);
            border-color: var(--netflix-red);
        }
        
        .form-check-label {
            color: var(--netflix-light);
        }
        
        .netflix-logo {
            color: var(--netflix-red);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .required::after {
            content: " *";
            color: var(--netflix-red);
        }
        
        .preview-placeholder {
            background: var(--netflix-gray);
            border: 2px dashed #666;
            border-radius: 8px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--netflix-light);
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
                            <i class="fas fa-plus-circle me-2" style="color: var(--netflix-red);"></i>Add New Movie
                        </span>
                        <a href="movies.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Movies
                        </a>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="form-container p-4">
                                <?php if($success): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label required">Movie Title</label>
                                            <input type="text" class="form-control" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" required placeholder="Enter movie title">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label required">Genre</label>
                                            <input type="text" class="form-control" name="genre" value="<?php echo $_POST['genre'] ?? ''; ?>" required placeholder="e.g., Action, Drama, Comedy">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">Description</label>
                                        <textarea class="form-control" name="description" rows="4" required placeholder="Enter movie description"><?php echo $_POST['description'] ?? ''; ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Duration</label>
                                            <input type="text" class="form-control" name="duration" value="<?php echo $_POST['duration'] ?? ''; ?>" placeholder="e.g., 2h 30min">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Release Year</label>
                                            <input type="number" class="form-control" name="release_year" value="<?php echo $_POST['release_year'] ?? ''; ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="YYYY">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label required">Price ($)</label>
                                            <input type="number" class="form-control" name="price" value="<?php echo $_POST['price'] ?? '9.99'; ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">Thumbnail URL</label>
                                        <input type="url" class="form-control" name="thumbnail_url" value="<?php echo $_POST['thumbnail_url'] ?? ''; ?>" required placeholder="https://example.com/movie-poster.jpg">
                                        <div class="form-text">Enter direct image URL for movie thumbnail</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">YouTube Trailer Embed Code</label>
                                        <textarea class="form-control" name="trailer_embed_code" rows="3" required placeholder='<iframe width="560" height="315" src="https://www.youtube.com/embed/VIDEO_ID" frameborder="0" allowfullscreen></iframe>'><?php echo $_POST['trailer_embed_code'] ?? ''; ?></textarea>
                                        <div class="form-text">Paste the embed code from YouTube</div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_featured">
                                                <i class="fas fa-star me-1 text-warning"></i>Feature this movie on homepage
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-redo me-2"></i>Reset Form
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Add Movie
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Preview Section -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0 text-white">
                                        <i class="fas fa-eye me-2" style="color: var(--netflix-red);"></i>Live Preview
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div id="thumbnailPreview" class="text-center">
                                                <div class="preview-placeholder">
                                                    <i class="fas fa-image fa-2x"></i>
                                                    <p class="mt-2 mb-0">Thumbnail preview</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <h5 id="titlePreview" class="text-muted">Movie Title</h5>
                                            <span id="genrePreview" class="badge bg-danger mb-2">Genre</span>
                                            <p id="descriptionPreview" class="text-light">Movie description will appear here...</p>
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-outline-primary" onclick="testTrailer()">
                                                    <i class="fas fa-play me-1"></i>Test Trailer
                                                </button>
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

    <!-- Trailer Test Modal -->
    <div class="modal fade" id="trailerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">Trailer Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="trailerPreview" class="text-center"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.querySelector('input[name="title"]');
            const genreInput = document.querySelector('input[name="genre"]');
            const descriptionInput = document.querySelector('textarea[name="description"]');
            const thumbnailInput = document.querySelector('input[name="thumbnail_url"]');
            const trailerInput = document.querySelector('textarea[name="trailer_embed_code"]');
            
            const titlePreview = document.getElementById('titlePreview');
            const genrePreview = document.getElementById('genrePreview');
            const descriptionPreview = document.getElementById('descriptionPreview');
            const thumbnailPreview = document.getElementById('thumbnailPreview');
            
            function updatePreview() {
                // Title
                titlePreview.textContent = titleInput.value || 'Movie Title';
                titlePreview.className = titleInput.value ? 'text-white' : 'text-muted';
                
                // Genre
                genrePreview.textContent = genreInput.value || 'Genre';
                genrePreview.className = 'badge ' + (genreInput.value ? 'bg-danger' : 'bg-secondary');
                
                // Description
                descriptionPreview.textContent = descriptionInput.value || 'Movie description will appear here...';
                descriptionPreview.className = descriptionInput.value ? 'text-light' : 'text-muted';
                
                // Thumbnail
                if(thumbnailInput.value) {
                    const img = thumbnailPreview.querySelector('img') || document.createElement('img');
                    img.src = thumbnailInput.value;
                    img.className = 'img-fluid rounded';
                    img.style.maxHeight = '200px';
                    img.onerror = function() {
                        thumbnailPreview.innerHTML = `
                            <div class="preview-placeholder border-danger">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                                <p class="mt-2 mb-0 text-danger">Invalid image URL</p>
                            </div>
                        `;
                    };
                    img.onload = function() {
                        thumbnailPreview.innerHTML = '';
                        thumbnailPreview.appendChild(img);
                    };
                } else {
                    thumbnailPreview.innerHTML = `
                        <div class="preview-placeholder">
                            <i class="fas fa-image fa-2x"></i>
                            <p class="mt-2 mb-0">Thumbnail preview</p>
                        </div>
                    `;
                }
            }
            
            [titleInput, genreInput, descriptionInput, thumbnailInput].forEach(input => {
                input.addEventListener('input', updatePreview);
            });
            
            updatePreview();
        });
        
        function testTrailer() {
            const trailerCode = document.querySelector('textarea[name="trailer_embed_code"]').value;
            const trailerPreview = document.getElementById('trailerPreview');
            
            if(trailerCode) {
                trailerPreview.innerHTML = trailerCode;
                const trailerModal = new bootstrap.Modal(document.getElementById('trailerModal'));
                trailerModal.show();
            } else {
                alert('Please enter trailer embed code first.');
            }
        }
    </script>
</body>
</html>