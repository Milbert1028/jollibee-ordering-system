<?php
// Start output buffering to allow header modifications throughout the application
ob_start();

session_start();
include_once 'config/config.php';
include_once 'includes/functions.php';

// Default page is the menu
$page = isset($_GET['page']) ? $_GET['page'] : 'menu';

// Special handling for admin pages
if ($page === 'admin') {
    // Admin pages have their own header/footer structure, so include only the admin page
    include_once 'pages/admin/index.php';
} else {
    // For non-admin pages, include the regular site header
    include_once 'includes/header.php';
    
    // Main content routing for regular pages
    switch ($page) {
        case 'menu':
            include_once 'pages/menu.php';
            break;
        case 'cart':
            include_once 'pages/cart.php';
            break;
        case 'checkout':
            include_once 'pages/checkout.php';
            break;
        case 'family-bundles':
            include_once 'pages/family-bundles.php';
            break;
        case 'deals':
            include_once 'pages/deals.php';
            break;
        case 'order-status':
            include_once 'pages/order-status.php';
            break;
        case 'support':
            include_once 'pages/support.php';
            break;
        default:
            include_once 'pages/menu.php';
            break;
    }
    
    // Include the regular site footer for non-admin pages
    include_once 'includes/footer.php';
}

// End output buffering and send the buffered content to the browser
ob_end_flush();
?> 