<?php
// Handle add to cart
if (isset($_POST['add_bundle_to_cart'])) {
    $bundleName = cleanInput($_POST['bundle_name']);
    $bundlePrice = (float)$_POST['bundle_price'];
    $bundleQuantity = (int)$_POST['bundle_quantity'];
    $bundleImage = isset($_POST['bundle_image']) ? $_POST['bundle_image'] : '';
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Create a unique ID for the bundle item
    $bundleItemId = 'bundle_' . md5($bundleName);
    
    // Check if bundle already in cart
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if (isset($item['bundle_id']) && $item['bundle_id'] == $bundleItemId) {
            $_SESSION['cart'][$key]['quantity'] += $bundleQuantity;
            $found = true;
            break;
        }
    }
    
    // If bundle not in cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => -100, // Use special ID to distinguish from regular products
            'bundle_id' => $bundleItemId,
            'name' => $bundleName,
            'price' => $bundlePrice,
            'image' => $bundleImage,
            'quantity' => $bundleQuantity,
            'type' => 'bundle'
        ];
    }
    
    $_SESSION['success'] = 'Family bundle added to cart successfully!';
    header('Location: ' . APP_URL . '?page=family-bundles');
    exit;
}

// Get all active family bundles from promotions table
$query = "SELECT * FROM promotions WHERE type = 'bundle' AND is_active = 1";
$result = mysqli_query($conn, $query);

$bundles = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bundles[] = $row;
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-jollibee-red mb-4">Family Bundles</h1>
        <p class="text-gray-600 max-w-3xl mx-auto">
            Perfect for sharing with your family and friends. These bundles are designed to bring joy to your group gatherings and family meals.
        </p>
    </div>

    <!-- Bundles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
        <?php if (empty($bundles)): ?>
            <div class="col-span-full text-center py-12">
                <div class="text-5xl text-gray-300 mb-4">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No bundles available</h3>
                <p class="text-gray-500">Check back later for our special family bundles!</p>
            </div>
        <?php else: ?>
            <?php foreach ($bundles as $bundle): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform duration-300 hover:transform hover:scale-105 flex flex-col">
                    <!-- Bundle Image -->
                    <div class="h-48 overflow-hidden relative">
                        <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $bundle['image']; ?>" alt="<?php echo $bundle['title']; ?>" class="w-full h-full object-cover">
                        
                        <?php if ($bundle['original_price'] && $bundle['original_price'] > $bundle['price']): ?>
                            <div class="absolute top-4 right-4 bg-jollibee-red text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?php 
                                    $discount = round(($bundle['original_price'] - $bundle['price']) / $bundle['original_price'] * 100);
                                    echo "SAVE {$discount}%"; 
                                ?>
                </div>
                        <?php endif; ?>
            </div>
            
                    <!-- Bundle Info -->
                    <div class="p-6 flex-grow flex flex-col">
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo $bundle['title']; ?></h3>
                        <p class="text-gray-600 mb-4 flex-grow"><?php echo $bundle['description']; ?></p>
                        
                        <!-- Price -->
                        <div class="flex items-center mb-4">
                            <span class="text-2xl font-bold text-jollibee-red">₱<?php echo number_format($bundle['price'], 2); ?></span>
                            <?php if ($bundle['original_price']): ?>
                                <span class="ml-2 text-gray-500 line-through text-sm">₱<?php echo number_format($bundle['original_price'], 2); ?></span>
                            <?php endif; ?>
                </div>
                
                        <!-- Add to Cart Button -->
                        <form method="post" action="<?php echo APP_URL; ?>?page=cart" class="mt-auto">
                            <input type="hidden" name="product_id" value="<?php echo $bundle['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo $bundle['title']; ?>">
                            <input type="hidden" name="product_price" value="<?php echo $bundle['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo $bundle['image']; ?>">
                            <input type="hidden" name="product_quantity" value="1">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="w-full bg-jollibee-red hover:bg-jollibee-darkred text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Call to Action -->
    <div class="bg-gradient-to-r from-jollibee-red to-jollibee-darkred text-white rounded-xl p-8 text-center shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Looking for regular menu items?</h2>
        <p class="mb-6">Check out our full menu with more delicious options to satisfy your cravings!</p>
        <a href="<?php echo APP_URL; ?>?page=menu" class="inline-block bg-white text-jollibee-red font-bold py-3 px-6 rounded-full hover:bg-jollibee-yellow hover:text-jollibee-red transition-colors">
            Browse Our Menu
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to bundle cards as they appear in viewport
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const bundleCards = document.querySelectorAll('.grid > div');
    bundleCards.forEach(card => {
        observer.observe(card);
    });
});
</script> 