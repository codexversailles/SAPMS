<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - StudyHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>My Classes</h2>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Join a Class</h5>
                        <form id="joinClassForm">
                            <div id="notification" class="alert" style="display: none;"></div>
                            <div class="mb-3">
                                <label for="classCode" class="form-label">Class Code</label>
                                <input type="text" class="form-control" id="classCode" required 
                                       placeholder="Enter class code">
                            </div>
                            <button type="submit" class="btn btn-primary" id="joinClassBtn">
                                Join Class
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="classesContainer">
            <!-- Classes will be loaded here dynamically -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/join-class.js"></script>
    <script src="js/student-classes.js"></script>
</body>
</html> 