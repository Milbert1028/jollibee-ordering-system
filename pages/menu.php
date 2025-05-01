<?php
// Check if we're being included by index.php or accessed directly
if (!isset($conn)) {
    // If accessed directly, include necessary files
    require_once '../config/config.php';
    require_once '../includes/functions.php';
    
    // Connection should now be available from functions.php
    if (!isset($conn)) {
        die("Database connection not established. Please access this page through the main website.");
    }
}

// No need to establish database connection here
// It's already defined in functions.php which is included by index.php

// Get all categories
$categories = [];
$query = "SELECT * FROM categories ORDER BY name";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Get active category
$activeCategoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get products based on category
$whereClause = $activeCategoryId > 0 ? "WHERE category_id = $activeCategoryId" : "";
$products = [];
$query = "SELECT * FROM products $whereClause ORDER BY name";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Get product details
    $query = "SELECT * FROM products WHERE id = $productId";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // If product not in cart, add it
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        $_SESSION['success'] = 'Product added to cart successfully!';
        header('Location: ' . APP_URL . '?page=menu' . ($activeCategoryId ? '&category=' . $activeCategoryId : ''));
        exit;
    }
}

// Handle adding featured item to cart
if (isset($_POST['add_featured_to_cart'])) {
    $itemName = cleanInput($_POST['item_name']);
    $itemPrice = (float)$_POST['item_price'];
    $itemQuantity = (int)$_POST['item_quantity'];
    $itemImage = isset($_POST['item_image']) ? $_POST['item_image'] : '';
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Create a unique ID for the featured item
    $featuredItemId = 'featured_' . md5($itemName);
    
    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if (isset($item['featured_id']) && $item['featured_id'] == $featuredItemId) {
            $_SESSION['cart'][$key]['quantity'] += $itemQuantity;
            $found = true;
            break;
        }
    }
    
    // If product not in cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => -1, // Use negative ID to distinguish from database products
            'featured_id' => $featuredItemId,
            'name' => $itemName,
            'price' => $itemPrice,
            'image' => $itemImage,
            'quantity' => $itemQuantity
        ];
    }
    
    $_SESSION['success'] = 'Featured item added to cart successfully!';
    header('Location: ' . APP_URL . '?page=menu');
    exit;
}
?>
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Our Menu</h1>
    
    <!-- Category Navigation -->
    <div class="flex overflow-x-auto pb-2 mb-4 space-x-2">
        <a href="<?php echo APP_URL; ?>?page=menu" 
           class="flex-shrink-0 px-4 py-2 rounded-full <?php echo $activeCategoryId === 0 ? 'bg-jollibee-red text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-800'; ?>">
            All Items
        </a>
        <?php foreach ($categories as $category): ?>
        <a href="<?php echo APP_URL; ?>?page=menu&category=<?php echo $category['id']; ?>" 
           class="flex-shrink-0 px-4 py-2 rounded-full <?php echo $activeCategoryId === $category['id'] ? 'bg-jollibee-red text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-800'; ?>">
            <?php echo $category['name']; ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Featured Items with API images - Only show on main menu page -->
    <?php if (!isset($_GET['category']) || $_GET['category'] == 0): ?>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-star text-jollibee-yellow mr-2"></i> Featured Items
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php
            // Get featured meals from TheMealDB API
            $featuredMeals = [
                'Chicken Burger' => ['Burger', 120, 'Chicken Burger.webp'],
                'Fried Chicken' => ['Fried Chicken', 180, 'Fried Chicken.webp'],
                'Spaghetti' => ['Spaghetti', 99, 'Spaghetti.webp'],
                'Beef Tapa' => ['Beef Tapa', 145, 'Beef tapa.webp']
            ];
            
            foreach ($featuredMeals as $mealName => $mealData):
                $searchTerm = $mealData[0];
                $price = $mealData[1];
                $imageName = $mealData[2];
                $imageUrl = APP_URL . '/assets/images/' . $imageName;
            ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 overflow-hidden relative group">
                    <img src="<?php echo $imageUrl; ?>" 
                         alt="<?php echo $mealName; ?>" 
                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                        <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity text-sm bg-jollibee-red px-2 py-1 rounded">Featured</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-semibold text-gray-800"><?php echo $mealName; ?></h3>
                        <span class="bg-jollibee-yellow text-xs text-gray-800 px-2 py-1 rounded-full">Featured</span>
                    </div>
                    <p class="text-jollibee-red font-bold text-xl mb-3"><?php echo formatPrice($price); ?></p>
                    <form method="post" action="" class="featured-form">
                        <input type="hidden" name="featured_item" value="1">
                        <input type="hidden" name="item_name" value="<?php echo $mealName; ?>">
                        <input type="hidden" name="item_price" value="<?php echo $price; ?>">
                        <input type="hidden" name="item_image" value="<?php echo $imageName; ?>">
                        <input type="hidden" name="item_quantity" value="1" class="featured-quantity">
                        <div class="flex items-center space-x-2 mb-3">
                            <button type="button" class="decrement-btn px-3 py-1 bg-gray-100 rounded-md text-gray-600 hover:bg-gray-200">−</button>
                            <span class="quantity-display flex-1 text-center font-medium">1</span>
                            <button type="button" class="increment-btn px-3 py-1 bg-gray-100 rounded-md text-gray-600 hover:bg-gray-200">+</button>
                        </div>
                        <button type="submit" name="add_featured_to_cart" class="w-full bg-jollibee-red hover:bg-jollibee-darkred text-white font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-shopping-cart mr-1"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Regular Product Listing -->
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
        <?php if ($activeCategoryId > 0): ?>
            <?php 
                $categoryName = '';
                foreach ($categories as $category) {
                    if ($category['id'] == $activeCategoryId) {
                        $categoryName = $category['name'];
                        break;
                    }
                }
                echo $categoryName;
            ?>
        <?php else: ?>
            <i class="fas fa-utensils text-jollibee-red mr-2"></i> All Products
        <?php endif; ?>
    </h2>
    
    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php if (empty($products)): ?>
        <div class="col-span-full text-center p-8 bg-gray-100 rounded-lg">
            <p class="text-gray-500">No products found in this category.</p>
        </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 overflow-hidden relative group">
                    <?php 
                        // Get food image using our API function
                        $imageUrl = getFoodImage($product['name'], $product['image']); 
                    ?>
                    <img src="<?php echo $imageUrl; ?>" 
                         alt="<?php echo $product['name']; ?>" 
                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform">
                         
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                        <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity text-sm bg-jollibee-red px-2 py-1 rounded">View Details</span>
                    </div>
                </div>
                <div class="p-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-1"><?php echo $product['name']; ?></h2>
                    <p class="text-jollibee-red font-bold text-xl mb-2"><?php echo formatPrice($product['price']); ?></p>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo $product['description']; ?></p>
                    
                    <form method="post" action="" class="flex items-center space-x-2">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="flex-1 flex items-center border rounded-md">
                            <button type="button" onclick="decrementQuantity('quantity_<?php echo $product['id']; ?>')" class="px-2 py-1 text-gray-600 hover:bg-gray-100">−</button>
                            <input type="number" id="quantity_<?php echo $product['id']; ?>" name="quantity" min="1" value="1" class="w-14 text-center border-none focus:ring-0">
                            <button type="button" onclick="incrementQuantity('quantity_<?php echo $product['id']; ?>')" class="px-2 py-1 text-gray-600 hover:bg-gray-100">+</button>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn-primary px-3 py-2 flex-shrink-0">
                            <i class="fas fa-shopping-cart mr-1"></i> Add
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function incrementQuantity(id) {
    const input = document.getElementById(id);
    input.value = parseInt(input.value) + 1;
}

function decrementQuantity(id) {
    const input = document.getElementById(id);
    const value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
    }
}

// Add this script for featured items quantity
document.addEventListener('DOMContentLoaded', function() {
    // Handle featured item quantity changes
    const featuredForms = document.querySelectorAll('.featured-form');
    
    featuredForms.forEach(form => {
        const quantityInput = form.querySelector('.featured-quantity');
        const quantityDisplay = form.querySelector('.quantity-display');
        const incrementBtn = form.querySelector('.increment-btn');
        const decrementBtn = form.querySelector('.decrement-btn');
        
        incrementBtn.addEventListener('click', function() {
            let quantity = parseInt(quantityInput.value) + 1;
            quantityInput.value = quantity;
            quantityDisplay.textContent = quantity;
        });
        
        decrementBtn.addEventListener('click', function() {
            let quantity = parseInt(quantityInput.value);
            if (quantity > 1) {
                quantity--;
                quantityInput.value = quantity;
                quantityDisplay.textContent = quantity;
            }
        });
    });
});
</script> 