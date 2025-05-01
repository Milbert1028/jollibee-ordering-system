<?php
// Add explicit session start if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug information - enable for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config and functions if not already included
if (!function_exists('cleanInput')) {
    // Use absolute paths to ensure proper inclusion
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    include_once $docRoot . '/jollibee-ordering-system/config/config.php';
    include_once $docRoot . '/jollibee-ordering-system/includes/functions.php';
}

// Verify database connection
if (!isset($conn) || !$conn) {
    // Re-establish database connection if it's not available
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
}

// Always set admin session for direct access
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['admin_role'] = 'admin';

// If a view is specified, include it
if (isset($_GET['view'])) {
    $view = cleanInput($_GET['view']);
    $allowedViews = ['dashboard', 'orders', 'products', 'categories', 'settings', 'payments', 'support', 'marketing'];
    
    // Include admin header only once
    include_once 'pages/admin/admin_header.php';
    
    // Load the appropriate view
    if (in_array($view, $allowedViews)) {
        $viewPath = "pages/admin/{$view}.php";
        if (file_exists($viewPath)) {
            // Include the view file
            include_once $viewPath;
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">View file not found.</div>';
        }
    } else {
        // Default to dashboard
        include_once "pages/admin/dashboard.php";
    }
    
    // Include admin footer
    include_once 'pages/admin/admin_footer.php';
    exit;
} else {
    // Redirect to dashboard if no view specified
    header('Location: ' . APP_URL . '?page=admin&view=dashboard');
    exit;
} 