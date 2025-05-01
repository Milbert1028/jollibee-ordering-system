<?php
// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Your cart is empty. Please add items to your cart before checkout.';
    header('Location: ' . APP_URL . '?page=menu');
    exit;
}

// Handle checkout submission
if (isset($_POST['place_order'])) {
    // Validate customer name
    $customerName = cleanInput($_POST['customer_name']);
    if (empty($customerName)) {
        $_SESSION['error'] = 'Please enter your name.';
        header('Location: ' . APP_URL . '?page=checkout');
        exit;
    }
    
    // Validate payment method
    $paymentMethod = cleanInput($_POST['payment_method']);
    if (empty($paymentMethod)) {
        $_SESSION['error'] = 'Please select a payment method.';
        header('Location: ' . APP_URL . '?page=checkout');
        exit;
    }
    
    // Calculate total amount
    $totalAmount = calculateCartTotal($_SESSION['cart']);
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Insert order into database
    $query = "INSERT INTO orders (order_number, customer_name, total_amount, payment_method) 
              VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssds", $orderNumber, $customerName, $totalAmount, $paymentMethod);
    
    if (mysqli_stmt_execute($stmt)) {
        $orderId = mysqli_insert_id($conn);
        
        // Get a valid product ID to use for featured items (use the first product in database)
        $fallbackProductId = 1; // Default to 1
        $fetchProductQuery = "SELECT id FROM products LIMIT 1";
        $productResult = mysqli_query($conn, $fetchProductQuery);
        if ($productResult && mysqli_num_rows($productResult) > 0) {
            $productRow = mysqli_fetch_assoc($productResult);
            $fallbackProductId = $productRow['id'];
        }
        
        // Insert order items
        $insertItemsSuccess = true;
        foreach ($_SESSION['cart'] as $item) {
            // Get the product ID, handling both regular products and featured items
            if (is_numeric($item['id'])) {
                $productId = (int)$item['id'];
                // Check if this is a featured item (negative ID)
                if ($productId < 0) {
                    $productId = $fallbackProductId;
                }
            } else {
                // Handle non-numeric IDs (like 'featured_hash')
                $productId = $fallbackProductId;
            }
            
            $quantity = $item['quantity'];
            $unitPrice = $item['price'];
            $subtotal = $unitPrice * $quantity;
            
            $query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiids", $orderId, $productId, $quantity, $unitPrice, $subtotal);
            
            if (!mysqli_stmt_execute($stmt)) {
                $insertItemsSuccess = false;
                break;
            }
        }
        
        if ($insertItemsSuccess) {
            // Store order number in session for tracking
            $_SESSION['order_number'] = $orderNumber;
            
            // Clear cart after successful order
            $_SESSION['cart'] = [];
            
            // Redirect to order status page
            $_SESSION['success'] = 'Your order has been placed successfully!';
            header('Location: ' . APP_URL . '?page=order-status&order=' . $orderNumber);
            exit;
        } else {
            $_SESSION['error'] = 'Error placing order. Please try again.';
        }
    } else {
        $_SESSION['error'] = 'Error placing order. Please try again.';
    }
}

// Calculate cart total
$cartTotal = calculateCartTotal($_SESSION['cart']);
?>

<!-- Checkout Page Content -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Checkout</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left Column - Order Details -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Details</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php 
                                            // Determine the image path based on whether this is a regular product or featured item
                                            $imageSrc = !empty($item['image']) ? APP_URL . '/assets/images/' . $item['image'] : APP_URL . '/assets/images/default.jpg';
                                            ?>
                                            <img class="h-10 w-10 object-cover rounded" 
                                                 src="<?php echo $imageSrc; ?>" 
                                                 alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $item['name']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatPrice($item['price']); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="text-gray-800 font-medium"><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Tax (0%):</span>
                        <span class="text-gray-800 font-medium"><?php echo formatPrice(0); ?></span>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <span class="text-gray-800">Total:</span>
                        <span class="text-jollibee-red"><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Checkout Form -->
        <div>
            <div class="bg-white rounded-lg shadow-md overflow-hidden p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Customer Information</h2>
                
                <form method="post" action="">
                    <div class="mb-4">
                        <label for="customer_name" class="block text-gray-700 font-medium mb-2">Your Name</label>
                        <input type="text" id="customer_name" name="customer_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red focus:border-transparent"
                               placeholder="Enter your name">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Payment Method</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="cash" checked
                                       class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300">
                                <span class="ml-2 text-gray-700">Pay With Cash</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="credit_card"
                                       class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300">
                                <span class="ml-2 text-gray-700">Credit/Debit Card</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="mobile_wallet"
                                       class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300">
                                <span class="ml-2 text-gray-700">Mobile Wallet</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="<?php echo APP_URL; ?>?page=cart" class="btn-secondary text-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Cart
                        </a>
                        <button type="submit" name="place_order" class="btn-primary">
                            Place Order <i class="fas fa-check ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 