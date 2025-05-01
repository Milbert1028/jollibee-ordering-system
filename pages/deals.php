<?php
// Get all active deals from promotions table
$query = "SELECT * FROM promotions WHERE type = 'deal' AND is_active = 1";
$result = mysqli_query($conn, $query);

$deals = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $deals[] = $row;
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-jollibee-red mb-4">Special Deals</h1>
        <p class="text-gray-600 max-w-3xl mx-auto">
            Check out our limited-time offers and special meal deals that give you great value for your money!
        </p>
    </div>

    <!-- Featured Deal Banner (if available) -->
    <?php if (!empty($deals)): ?>
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl shadow-md overflow-hidden mb-12">
        <div class="flex flex-col md:flex-row">
            <div class="md:w-1/2">
                <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $deals[0]['image']; ?>" alt="<?php echo $deals[0]['title']; ?>" class="w-full h-64 md:h-full object-cover">
            </div>
            <div class="md:w-1/2 p-8 flex flex-col justify-center">
                <div class="bg-jollibee-red text-white text-xs font-bold px-3 py-1 rounded-full w-max mb-4">FEATURED DEAL</div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?php echo $deals[0]['title']; ?></h2>
                <p class="text-gray-600 mb-6"><?php echo $deals[0]['description']; ?></p>
                <div class="flex items-center mb-6">
                    <span class="text-3xl font-bold text-jollibee-red">₱<?php echo number_format($deals[0]['price'], 2); ?></span>
                    <?php if ($deals[0]['original_price']): ?>
                        <span class="ml-3 text-gray-500 line-through text-lg">₱<?php echo number_format($deals[0]['original_price'], 2); ?></span>
                        <span class="ml-3 bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">
                            <?php 
                                $discount = round(($deals[0]['original_price'] - $deals[0]['price']) / $deals[0]['original_price'] * 100);
                                echo "SAVE {$discount}%"; 
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?php echo APP_URL; ?>?page=cart">
                    <input type="hidden" name="product_id" value="<?php echo $deals[0]['id']; ?>">
                    <input type="hidden" name="product_name" value="<?php echo $deals[0]['title']; ?>">
                    <input type="hidden" name="product_price" value="<?php echo $deals[0]['price']; ?>">
                    <input type="hidden" name="product_image" value="<?php echo $deals[0]['image']; ?>">
                    <input type="hidden" name="product_quantity" value="1">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                        <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Deals Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
        <?php if (empty($deals)): ?>
            <div class="col-span-full text-center py-12">
                <div class="text-5xl text-gray-300 mb-4">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No deals available</h3>
                <p class="text-gray-500">Check back later for our special deals!</p>
            </div>
        <?php else: ?>
            <?php 
            // Skip the first deal if it's already featured
            $startIndex = count($deals) > 1 ? 1 : 0;
            for ($i = $startIndex; $i < count($deals); $i++): 
                $deal = $deals[$i];
            ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform duration-300 hover:transform hover:scale-105 flex flex-col">
                    <!-- Deal Image -->
                    <div class="h-48 overflow-hidden relative">
                        <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $deal['image']; ?>" alt="<?php echo $deal['title']; ?>" class="w-full h-full object-cover">
                        
                        <?php if ($deal['original_price'] && $deal['original_price'] > $deal['price']): ?>
                            <div class="absolute top-4 right-4 bg-jollibee-red text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?php 
                                    $discount = round(($deal['original_price'] - $deal['price']) / $deal['original_price'] * 100);
                                    echo "SAVE {$discount}%"; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Deal Info -->
                    <div class="p-6 flex-grow flex flex-col">
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo $deal['title']; ?></h3>
                        <p class="text-gray-600 mb-4 flex-grow"><?php echo $deal['description']; ?></p>
                        
                        <!-- Price -->
                        <div class="flex items-center mb-4">
                            <span class="text-2xl font-bold text-jollibee-red">₱<?php echo number_format($deal['price'], 2); ?></span>
                            <?php if ($deal['original_price']): ?>
                                <span class="ml-2 text-gray-500 line-through text-sm">₱<?php echo number_format($deal['original_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add to Cart Button -->
                        <form method="post" action="<?php echo APP_URL; ?>?page=cart" class="mt-auto">
                            <input type="hidden" name="product_id" value="<?php echo $deal['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo $deal['title']; ?>">
                            <input type="hidden" name="product_price" value="<?php echo $deal['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo $deal['image']; ?>">
                            <input type="hidden" name="product_quantity" value="1">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="w-full bg-jollibee-red hover:bg-jollibee-darkred text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
    
    <!-- Limited Time Banner -->
    <div class="bg-yellow-100 border-l-4 border-yellow-400 p-4 mb-8 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-clock text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Limited Time Offers</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>These special deals are only available for a limited time. Get them while they last!</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="bg-gradient-to-r from-jollibee-red to-jollibee-darkred text-white rounded-xl p-8 text-center shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Planning a family gathering?</h2>
        <p class="mb-6">Check out our family bundles for your perfect group meal!</p>
        <a href="<?php echo APP_URL; ?>?page=family-bundles" class="inline-block bg-white text-jollibee-red font-bold py-3 px-6 rounded-full hover:bg-jollibee-yellow hover:text-jollibee-red transition-colors">
            View Family Bundles
        </a>
    </div>
</div> 