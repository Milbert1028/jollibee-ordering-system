<?php
// Initialize cart if not exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Update item quantity
    if ($action === 'update') {
        $productId = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$key]['quantity'] = $quantity;
                } else {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
                }
                break;
            }
        }
        
        $_SESSION['success'] = 'Cart updated successfully!';
        header('Location: ' . APP_URL . '?page=cart');
        exit;
    }
    
    // Remove item from cart
    if ($action === 'remove') {
        $productId = $_POST['product_id'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
                break;
            }
        }
        
        $_SESSION['success'] = 'Item removed from cart!';
        header('Location: ' . APP_URL . '?page=cart');
        exit;
    }
    
    // Clear cart
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = 'Cart cleared successfully!';
        header('Location: ' . APP_URL . '?page=cart');
        exit;
    }
}

// Calculate cart total
$cartTotal = calculateCartTotal($_SESSION['cart']);
?>

<!-- CSS Animations -->
<style>
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

.slide-in {
    animation: slideIn 0.5s ease-out;
}

.bounce {
    animation: bounce 0.5s;
}

.scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0.8; }
    to { transform: scale(1); opacity: 1; }
}

.hover-scale {
    transition: transform 0.3s ease;
}

.hover-scale:hover {
    transform: scale(1.02);
}

.jollibee-card {
    border-left: 4px solid #E31837;
    transition: all 0.3s ease;
}

.jollibee-card:hover {
    box-shadow: 0 8px 15px rgba(227, 24, 55, 0.15);
}

.btn-jollibee {
    background-color: #E31837;
    color: white;
    transition: all 0.3s ease;
    transform-origin: center;
}

.btn-jollibee:hover {
    background-color: #B8001F;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(227, 24, 55, 0.3);
}

.btn-jollibee-secondary {
    background-color: #FFC20E;
    color: #333;
    transition: all 0.3s ease;
}

.btn-jollibee-secondary:hover {
    background-color: #EDAA00;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 194, 14, 0.3);
}

.quantity-control {
    border: 2px solid #E8E8E8;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.quantity-control:hover {
    border-color: #FFC20E;
}

.quantity-btn {
    background-color: #F5F5F5;
    color: #333;
    transition: all 0.2s ease;
}

.quantity-btn:hover {
    background-color: #FFC20E;
    color: #333;
}

.card-hover-effect {
    transition: all 0.3s ease;
}

.card-hover-effect:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
}

.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: #FFF9E6;
}

.progress-bar {
    height: 8px;
    background-color: #E8E8E8;
    border-radius: 4px;
    margin: 15px 0;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #E31837;
    width: 33.3%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

</style>

<!-- Cart Page Content -->
<div class="mb-8 fade-in">
    <!-- Cart Progress -->
    <div class="mb-8 slide-in">
        <div class="flex justify-between mb-1 text-sm font-medium">
            <span class="text-jollibee-red">Cart</span>
            <span class="text-gray-400">Checkout</span>
            <span class="text-gray-400">Order Complete</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-jollibee-red flex items-center">
            <i class="fas fa-shopping-cart mr-3 text-jollibee-yellow"></i>Your Cart
        </h1>
        <?php if (!empty($_SESSION['cart'])): ?>
        <form method="post" action="" class="inline-block">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition duration-300 flex items-center">
                <i class="fas fa-trash-alt mr-2 text-jollibee-red"></i> Clear Cart
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <?php if (empty($_SESSION['cart'])): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center card-hover-effect">
        <img src="<?php echo APP_URL; ?>/assets/images/jollibee-logo.png" alt="Jollibee Logo" class="h-24 mx-auto mb-6 bounce" style="max-height:96px; object-fit:contain;">
        <p class="text-xl text-gray-600 mb-6">Your cart is empty</p>
        <p class="text-gray-500 mb-8">Looks like you haven't added any items to your cart yet.</p>
        <a href="<?php echo APP_URL; ?>?page=menu" class="btn-jollibee px-6 py-3 rounded-full font-medium inline-flex items-center">
            <i class="fas fa-utensils mr-2"></i> Browse Our Menu
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 card-hover-effect">
                <div class="bg-gradient-to-r from-jollibee-red to-jollibee-darkred px-6 py-3 text-white">
                    <h2 class="font-bold flex items-center">
                        <i class="fas fa-hamburger mr-2"></i> Order Items
                    </h2>
                </div>
                <div class="p-4">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-100 cart-item scale-in mb-2 jollibee-card">
                        <div class="flex items-center w-full md:w-1/2 mb-4 md:mb-0">
                            <div class="flex-shrink-0 h-20 w-20 relative overflow-hidden rounded-lg hover-scale">
                                <?php 
                                // Determine the image path based on whether this is a regular product or featured item
                                $imageSrc = !empty($item['image']) ? APP_URL . '/assets/images/' . $item['image'] : APP_URL . '/assets/images/default.jpg';
                                ?>
                                <img class="h-full w-full object-cover" 
                                     src="<?php echo $imageSrc; ?>" 
                                     alt="<?php echo $item['name']; ?>">
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all duration-300"></div>
                            </div>
                            <div class="ml-4">
                                <div class="text-lg font-bold text-gray-800"><?php echo $item['name']; ?></div>
                                <div class="text-sm text-jollibee-red font-medium"><?php echo formatPrice($item['price']); ?></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between w-full md:w-1/2">
                            <form method="post" action="" class="flex items-center">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <div class="flex items-center quantity-control">
                                    <button type="button" 
                                            onclick="this.parentNode.querySelector('input[type=number]').stepDown(); this.closest('form').submit();" 
                                            class="quantity-btn w-10 h-10 flex items-center justify-center">
                                            <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" min="0" value="<?php echo $item['quantity']; ?>" 
                                           class="w-12 h-10 text-center border-none focus:ring-0 font-medium" 
                                           onchange="this.closest('form').submit();">
                                    <button type="button" 
                                            onclick="this.parentNode.querySelector('input[type=number]').stepUp(); this.closest('form').submit();" 
                                            class="quantity-btn w-10 h-10 flex items-center justify-center">
                                            <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="flex items-center">
                                <div class="text-lg font-bold text-gray-800 mr-4">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                                <form method="post" action="" class="inline-block">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-gray-400 hover:text-jollibee-red transition-colors duration-300 w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-50">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover-effect sticky top-4">
                <div class="bg-gradient-to-r from-jollibee-yellow to-yellow-500 px-6 py-3 text-gray-800">
                    <h2 class="font-bold flex items-center">
                        <i class="fas fa-receipt mr-2"></i> Order Summary
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex justify-between mb-4 py-2 border-b border-dashed border-gray-200">
                        <span class="text-gray-600 flex items-center"><i class="fas fa-utensils mr-2 text-gray-400"></i>Subtotal:</span>
                        <span class="text-gray-800 font-medium"><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="flex justify-between mb-4 py-2 border-b border-dashed border-gray-200">
                        <span class="text-gray-600 flex items-center"><i class="fas fa-percentage mr-2 text-gray-400"></i>Tax (0%):</span>
                        <span class="text-gray-800 font-medium"><?php echo formatPrice(0); ?></span>
                    </div>
                    <div class="flex justify-between mb-6 py-3 border-b-2 border-jollibee-red">
                        <span class="text-gray-800 font-bold text-lg flex items-center"><i class="fas fa-money-bill-wave mr-2 text-jollibee-red"></i>Total:</span>
                        <span class="text-jollibee-red font-bold text-xl"><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="space-y-4">
                        <a href="<?php echo APP_URL; ?>?page=menu" class="w-full py-3 rounded-lg btn-jollibee-secondary block text-center font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                        </a>
                        <a href="<?php echo APP_URL; ?>?page=checkout" class="w-full py-3 rounded-lg btn-jollibee block text-center font-medium">
                            Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center text-sm text-gray-500 mb-1">
                            <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                            <span>Secure Checkout</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Add some animation to quantity changes
document.addEventListener('DOMContentLoaded', function() {
    const quantityBtns = document.querySelectorAll('.quantity-btn');
    
    quantityBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const formElement = this.closest('form');
            const quantityInput = formElement.querySelector('input[type=number]');
            
            // Add bounce animation
            quantityInput.classList.add('bounce');
            
            // Remove the animation class after it completes
            setTimeout(() => {
                quantityInput.classList.remove('bounce');
            }, 500);
        });
    });
});
</script> 