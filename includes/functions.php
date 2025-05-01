<?php
/**
 * Utility functions for the Jollibee Ordering System
 */

// Database connection - only establish if constants are defined
$conn = null;
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Generate a unique order number
 * 
 * @return string Order number in format JB + YYMMDD + 3 random digits
 */
function generateOrderNumber() {
    $prefix = 'JB';
    $date = date('ymd');
    $random = rand(100, 999);
    return $prefix . $date . $random;
}

/**
 * Calculate cart total
 * 
 * @param array $cart Cart items
 * @return float Total amount
 */
function calculateCartTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Get order details by order number
 * 
 * @param mysqli $conn Database connection
 * @param string $orderNumber Order number to find
 * @return array|null Order details or null if not found
 */
function getOrderByNumber($conn, $orderNumber) {
    $query = "SELECT * FROM orders WHERE order_number = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $orderNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Get order items for an order
 * 
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @return array Order items
 */
function getOrderItems($conn, $orderId) {
    $items = [];
    
    $query = "SELECT oi.*, p.name, p.image
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get all active categories
 * 
 * @param mysqli $conn Database connection
 * @return array Categories
 */
function getCategories($conn) {
    $categories = [];
    
    $query = "SELECT * FROM categories ORDER BY name";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Get products by category
 * 
 * @param mysqli $conn Database connection
 * @param int $categoryId Category ID
 * @return array Products
 */
function getProductsByCategory($conn, $categoryId) {
    $products = [];
    
    $query = "SELECT * FROM products WHERE category_id = ? ORDER BY name";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    return $products;
}

/**
 * Get featured products
 * 
 * @param mysqli $conn Database connection
 * @return array Featured products
 */
function getFeaturedProducts($conn) {
    $products = [];
    
    $query = "SELECT * FROM products WHERE is_featured = 1 ORDER BY name LIMIT 4";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    return $products;
}

/**
 * Get product by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @return array|null Product details or null if not found
 */
function getProductById($conn, $productId) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Fetch food image from TheMealDB API
 * 
 * @param string $query Food name to search for
 * @return string Image URL or default image if not found
 */
function getFoodImageFromAPI($query) {
    // Sanitize and format the query
    $searchTerm = urlencode(trim($query));
    
    // If search term is empty, return a default image
    if (empty($searchTerm)) {
        return APP_URL . '/assets/images/default.jpg';
    }
    
    // TheMealDB API URL - using the free API (no key required)
    $apiUrl = "https://www.themealdb.com/api/json/v1/1/search.php?s={$searchTerm}";
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Close cURL session
    curl_close($ch);
    
    // Process the response
    if ($response) {
        $data = json_decode($response, true);
        
        // Check if we got valid meals data
        if (isset($data['meals']) && is_array($data['meals']) && !empty($data['meals'])) {
            return $data['meals'][0]['strMealThumb'];
        }
    }
    
    // If API call fails or no image found, try Unsplash API as fallback
    $unsplashUrl = "https://source.unsplash.com/300x300/?food,{$searchTerm}";
    return $unsplashUrl;
}

/**
 * Get cached or fetch new food image
 * 
 * @param string $productName Product name to get image for
 * @param string $currentImage Current image path if exists
 * @return string Image URL
 */
function getFoodImage($productName, $currentImage = '') {
    // If we already have an image and it's not default.jpg, use it
    if (!empty($currentImage) && $currentImage !== 'default.jpg') {
        // Check if file exists
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/jollibee-ordering-system/assets/images/' . $currentImage;
        if (file_exists($imagePath)) {
            return APP_URL . '/assets/images/' . $currentImage;
        }
    }
    
    // Create placeholder image with first letter of product name
    $letter = strtoupper(substr($productName, 0, 1));
    $width = 300;
    $height = 300;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Colors (Jollibee Red)
    $red = imagecolorallocate($image, 228, 24, 55);
    $white = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefill($image, 0, 0, $red);
    
    // Add text
    $font = 5; // Built-in font (large)
    $fontWidth = imagefontwidth($font);
    $fontHeight = imagefontheight($font);
    $textWidth = $fontWidth * strlen($letter);
    $textHeight = $fontHeight;
    
    // Center the text
    $centerX = $width / 2 - $textWidth / 2;
    $centerY = $height / 2 - $textHeight / 2;
    
    // Draw text
    imagestring($image, $font, $centerX, $centerY, $letter, $white);
    
    // Create cache directory if it doesn't exist
    $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/jollibee-ordering-system/assets/images/placeholders/';
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Save image
    $placeholderFilename = md5($productName) . '.png';
    $placeholderPath = $cacheDir . $placeholderFilename;
    $placeholderUrl = APP_URL . '/assets/images/placeholders/' . $placeholderFilename;
    
    imagepng($image, $placeholderPath);
    imagedestroy($image);
    
    return $placeholderUrl;
}

/**
 * Get recent orders for a user or all recent orders if user ID is not provided
 * 
 * @param mysqli $conn Database connection
 * @param int|null $userId Optional user ID to filter orders
 * @param int $limit Number of orders to return
 * @return array Recent orders
 */
function getRecentOrders($conn, $userId = null, $limit = 10) {
    $orders = [];
    
    // Use a simpler query approach instead of prepared statements for flexibility
    if ($userId) {
        $query = "SELECT * FROM orders WHERE user_id = " . (int)$userId . " ORDER BY order_date DESC LIMIT " . (int)$limit;
    } else {
        $query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT " . (int)$limit;
    }
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
    }
    
    return $orders;
}
?> 