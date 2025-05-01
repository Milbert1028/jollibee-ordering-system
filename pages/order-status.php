<?php
// Order tracking functionality
$orderDetails = null;
$orderItems = [];
$orderFound = false;

// Get the most recent order by default if no specific order is requested
if (!isset($_GET['order']) || empty($_GET['order'])) {
    // Get the most recent order directly using a more efficient query
    $query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $orderDetails = mysqli_fetch_assoc($result);
        $orderNumber = $orderDetails['order_number'];
        $_GET['order'] = $orderNumber; // Set it as the current order to view
        $orderFound = true;
        $orderItems = getOrderItems($conn, $orderDetails['id']);
    }
} else {
// Check if order number is in URL
    $orderNumber = cleanInput($_GET['order']);
    
    // Get order details
    $orderDetails = getOrderByNumber($conn, $orderNumber);
    
    if ($orderDetails) {
        $orderFound = true;
        $orderItems = getOrderItems($conn, $orderDetails['id']);
    } else {
        $_SESSION['error'] = 'Order not found. Please check your order number.';
    }
}

// Handle order tracking form submission
if (isset($_POST['track_order'])) {
    $orderNumber = cleanInput($_POST['order_number']);
    
    if (empty($orderNumber)) {
        $_SESSION['error'] = 'Please enter an order number.';
    } else {
        // Redirect to same page with order number in URL
        header('Location: ' . APP_URL . '?page=order-status&order=' . $orderNumber);
        exit;
    }
}

// Debug statement - you can remove this later
if ($orderFound) {
    // For debugging purposes only
    // echo "<pre style='display:none'>Found order: " . $orderDetails['order_number'] . "</pre>";
}
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

.pulse {
    animation: pulse 2s infinite;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

.pulse-success {
    animation: pulseSuccess 2s infinite;
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

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes pulseSuccess {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
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

.status-timeline-line {
    background: linear-gradient(to bottom, #E31837 0%, #E31837 var(--progress, 25%), #E8E8E8 var(--progress, 25%), #E8E8E8 100%);
    transition: background 0.3s ease;
}

.card-hover-effect {
    transition: all 0.3s ease;
}

.card-hover-effect:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
}

.progress-bar {
    height: 8px;
    background-color: #E8E8E8;
    border-radius: 4px;
    margin: 15px 0;
    overflow: hidden;
}

.progress-fill-order {
    height: 100%;
    background-color: #E31837;
    width: 66.6%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.status-icon {
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.status-icon.active {
    transform: scale(1.2);
    box-shadow: 0 0 0 4px rgba(227, 24, 55, 0.2);
}

.status-tracker {
    position: relative;
}

.status-item {
    transition: all 0.3s ease;
}

.status-item:hover {
    transform: translateX(5px);
}

.status-item.active {
    transform: translateX(5px);
}

.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: #FFF9E6;
}

.success-banner {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.celebrate {
    animation: celebrate 1.5s ease-in-out;
}

@keyframes celebrate {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>

<!-- Order Status Page Content -->
<div class="mb-8 fade-in">
    
    <!-- Page progress indicator -->
    <div class="mb-8 slide-in">
        <div class="flex justify-between mb-1 text-sm font-medium">
            <span class="text-gray-400">Cart</span>
            <span class="text-gray-400">Checkout</span>
            <span class="text-jollibee-red">Order Status</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill-order"></div>
        </div>
    </div>
    
    <h1 class="text-3xl font-bold text-jollibee-red flex items-center mb-6">
        <i class="fas fa-clipboard-list text-jollibee-yellow mr-3"></i>Your Orders
    </h1>

    <?php if ($orderFound && !isset($_SESSION['success'])): ?>
    <!-- Auto-display notification -->
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 fade-in" role="alert">
        <span class="block sm:inline">
            <strong>Order found!</strong> Showing details for order #<?php echo $orderDetails['order_number']; ?>
        </span>
    </div>
    <?php endif; ?>
    
    <?php if (!$orderFound): ?>
        <!-- No Order Found Message -->
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <img src="<?php echo APP_URL; ?>/assets/images/jollibee-logo.png" alt="Jollibee Logo" class="h-24 mx-auto mb-6 bounce" style="max-height:96px; object-fit:contain;">
            <p class="text-xl text-gray-600 mb-4">No order history found</p>
            <p class="text-gray-500 mb-6">You haven't placed any orders yet. Place your first order to see it here!</p>
            <a href="<?php echo APP_URL; ?>?page=menu" class="btn-jollibee px-6 py-3 rounded-full font-medium inline-flex items-center">
                <i class="fas fa-utensils mr-2"></i> Start Ordering Now
            </a>
            
            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-gray-700 font-medium mb-4">Already have an order number?</p>
            <form method="post" action="" class="max-w-md mx-auto">
                    <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-receipt text-jollibee-red"></i>
                    </div>
                    <input type="text" name="order_number" required placeholder="Enter order number (e.g., JB220426789)" 
                           class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jollibee-red focus:border-transparent transition-all duration-300">
                </div>
                    <button type="submit" name="track_order" class="btn-jollibee w-full py-2 px-4 rounded-lg font-medium flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i> Track Order
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Order Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Order Status -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover-effect sticky top-4">
                <div class="bg-gradient-to-r from-jollibee-red to-jollibee-darkred px-6 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">Order #<?php echo $orderDetails['order_number']; ?></h2>
                        <?php 
                        $statusClass = '';
                        $liveStatus = false;
                        
                        $currentStatus = isset($orderDetails['status']) ? $orderDetails['status'] : (isset($orderDetails['order_status']) ? $orderDetails['order_status'] : 'received');
                        
                        if ($currentStatus === 'received' || $currentStatus === 'preparing') {
                            $liveStatus = true;
                        }
                        
                        if ($liveStatus): 
                        ?>
                        <span class="animate-pulse flex items-center">
                            <i class="fas fa-circle text-xs text-white mr-1"></i> Live
                        </span>
                        <?php elseif ($currentStatus === 'completed'): ?>
                        <span class="flex items-center bg-green-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow-sm">
                            <i class="fas fa-check-circle mr-1"></i> Completed
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm opacity-90">
                        <?php 
                            $dateField = isset($orderDetails['order_date']) ? 'order_date' : (isset($orderDetails['created_at']) ? 'created_at' : '');
                            echo $dateField ? date('F j, Y, g:i a', strtotime($orderDetails[$dateField])) : '';
                        ?>
                    </p>
                </div>
                
                <div class="p-6">
                    <!-- Customer Info -->
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-jollibee-red bg-opacity-10 flex items-center justify-center mr-3">
                                <i class="fas fa-user text-jollibee-red"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Customer</p>
                                <p class="font-medium text-gray-800"><?php echo $orderDetails['customer_name']; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-jollibee-red bg-opacity-10 flex items-center justify-center mr-3">
                                <i class="fas fa-credit-card text-jollibee-red"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Payment Method</p>
                                <p class="font-medium text-gray-800">
                                    <?php
                                    switch ($orderDetails['payment_method']) {
                                        case 'cash':
                                            echo 'Pay with Cash';
                                            break;
                                        case 'credit_card':
                                            echo 'Credit/Debit Card';
                                            break;
                                        case 'mobile_wallet':
                                            echo 'Mobile Wallet';
                                            break;
                                        default:
                                            echo ucfirst($orderDetails['payment_method']);
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Status Timeline -->
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock text-jollibee-yellow mr-2"></i>Order Status
                    </h3>
                    
                    <div class="relative">
                        <!-- Status Timeline -->
                        <?php
                        // Define all possible statuses
                        $allStatuses = ['received', 'preparing', 'ready', 'completed'];
                        
                        // Get current order status
                        $currentStatus = isset($orderDetails['status']) ? $orderDetails['status'] : (isset($orderDetails['order_status']) ? $orderDetails['order_status'] : 'received');
                        $currentStatusIndex = array_search($currentStatus, $allStatuses);
                        if ($currentStatusIndex === false) $currentStatusIndex = 0;
                        
                        // Calculate progress percentage
                        $progress = (($currentStatusIndex + 1) / count($allStatuses)) * 100;
                        ?>
                        
                        <div class="absolute left-4 top-4 bottom-0 w-0.5 status-timeline-line" style="--progress: <?php echo $progress; ?>%"></div>
                        
                        <!-- Display each status -->
                        <?php foreach ($allStatuses as $index => $status):
                            $isComplete = $index <= $currentStatusIndex;
                            $isCurrent = $index === $currentStatusIndex;
                            
                            if ($isCurrent) {
                                $statusClass = 'bg-jollibee-red border-white text-white';
                                $itemClass = 'text-jollibee-red font-medium active';
                                $iconClass = 'fas fa-spinner fa-spin text-white';
                            } elseif ($isComplete) {
                                $statusClass = 'bg-green-500 border-white text-white';
                                $itemClass = 'text-green-600';
                                $iconClass = 'fas fa-check text-white';
                            } else {
                                $statusClass = 'bg-gray-200 border-white text-gray-400';
                                $itemClass = 'text-gray-400';
                                $iconClass = 'fas fa-circle text-gray-400';
                            }
                            
                            // Special handling for completed status
                            if ($status === 'completed' && $currentStatus === 'completed') {
                                $statusClass = 'bg-green-500 border-white text-white pulse-success';
                                $itemClass = 'text-green-600 font-bold';
                                $iconClass = 'fas fa-check-double text-white';
                            }
                        ?>
                        <div class="flex mb-8 relative status-item <?php echo $isCurrent ? 'active' : ''; ?>">
                            <div class="status-icon z-10 flex items-center justify-center w-8 h-8 rounded-full border-2 <?php echo $statusClass; ?> <?php echo $isCurrent ? 'active pulse' : ''; ?> mr-4">
                                <i class="<?php echo $iconClass; ?> text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 flex items-center">
                                    <?php 
                                    $statusIcons = [
                                        'received' => 'fa-inbox',
                                        'preparing' => 'fa-utensils',
                                        'ready' => 'fa-check-circle',
                                        'completed' => 'fa-flag-checkered'
                                    ];
                                    
                                    echo '<i class="fas ' . $statusIcons[$status] . ' mr-2 ' . $itemClass . '"></i> ';
                                    echo ucfirst($status);
                                    
                                    if ($isCurrent): 
                                    ?>
                                    <span class="ml-2 text-xs bg-jollibee-red text-white px-2 py-0.5 rounded-full">Current</span>
                                    <?php endif; ?>
                                </h4>
                                <p class="text-sm <?php echo $isComplete ? 'text-gray-700' : 'text-gray-500'; ?>">
                                    <?php
                                    switch ($status) {
                                        case 'received':
                                            echo 'Your order has been received by the restaurant.';
                                            break;
                                        case 'preparing':
                                            echo 'The kitchen is preparing your delicious food.';
                                            break;
                                        case 'ready':
                                            echo 'Your food is ready for pickup/delivery.';
                                            break;
                                        case 'completed':
                                            echo 'Your order has been completed. Enjoy!';
                                            break;
                                    }
                                    ?>
                                </p>
                                <?php if ($isComplete): ?>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php 
                                    $dateField = isset($orderDetails['order_date']) ? 'order_date' : (isset($orderDetails['created_at']) ? 'created_at' : '');
                                    if ($dateField) {
                                        echo date('g:i a', strtotime('-' . (4 - $index) . ' minutes', strtotime($orderDetails[$dateField])));
                                    }
                                    ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="#" onclick="window.print()" class="btn-jollibee-secondary w-full py-2 px-4 rounded-lg flex items-center justify-center font-medium text-center">
                            <i class="fas fa-print mr-2"></i> Print Receipt
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Order Items -->
        <div class="lg:col-span-2">
            <?php if ($currentStatus === 'completed'): ?>
            <!-- Success Banner for Completed Orders -->
            <div class="success-banner rounded-lg p-4 mb-4 shadow-md">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-25 rounded-full p-2 mr-3">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Order Completed Successfully!</h3>
                        <p>Thank you for choosing Jollibee. We hope you enjoyed your meal!</p>
                    </div>
                    <div class="ml-auto hidden md:block">
                        <i class="fas fa-utensils text-3xl celebrate"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover-effect mb-6">
                <div class="bg-gradient-to-r from-jollibee-yellow to-yellow-500 px-6 py-3 text-gray-800">
                    <h2 class="font-bold flex items-center">
                        <i class="fas fa-receipt mr-2"></i> Order Summary
                    </h2>
                </div>
                
                <div class="p-6">
                    <!-- Order Items -->
                    <div class="mb-6">
                        <?php foreach ($orderItems as $item): ?>
                        <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-100 cart-item scale-in mb-2 jollibee-card">
                            <div class="flex items-center w-full md:w-1/2 mb-4 md:mb-0">
                                <div class="flex-shrink-0 h-16 w-16 relative overflow-hidden rounded-lg hover-scale">
                                    <?php 
                                    // Determine the image path
                                    $imageSrc = !empty($item['image']) ? APP_URL . '/assets/images/' . $item['image'] : APP_URL . '/assets/images/default.jpg';
                                    ?>
                                    <img class="h-full w-full object-cover" 
                                         src="<?php echo $imageSrc; ?>" 
                                         alt="<?php echo $item['name']; ?>">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all duration-300"></div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-lg font-bold text-gray-800">
                                        <?php echo $item['quantity']; ?>pc <?php echo $item['name']; ?>
                                    </div>
                                    <div class="text-sm text-jollibee-red font-medium">
                                        <?php echo formatPrice($item['unit_price']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end w-full md:w-1/2">
                                <div class="flex items-center mr-8">
                                    <span class="text-gray-500 mr-2">Qty:</span>
                                    <span class="font-medium"><?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="text-lg font-bold text-gray-800">
                                    <?php echo formatPrice($item['subtotal']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Totals -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between mb-2 py-2">
                            <span class="text-gray-600 flex items-center"><i class="fas fa-utensils mr-2 text-gray-400"></i>Subtotal:</span>
                            <span class="text-gray-800 font-medium"><?php echo formatPrice($orderDetails['total_amount']); ?></span>
                        </div>
                        <div class="flex justify-between mb-2 py-2">
                            <span class="text-gray-600 flex items-center"><i class="fas fa-percentage mr-2 text-gray-400"></i>Tax (0%):</span>
                            <span class="text-gray-800 font-medium"><?php echo formatPrice(0); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-t border-gray-200 mt-2">
                            <span class="text-gray-800 font-bold text-lg flex items-center"><i class="fas fa-money-bill-wave mr-2 text-jollibee-red"></i>Total:</span>
                            <span class="text-jollibee-red font-bold text-xl"><?php echo formatPrice($orderDetails['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-6">
                        <a href="<?php echo APP_URL; ?>?page=menu" class="btn-jollibee-secondary px-6 py-2 rounded-lg font-medium flex items-center">
                            <i class="fas fa-utensils mr-2"></i> Back to Menu
                        </a>
                        <a href="#" onclick="document.getElementById('track-form').style.display='block'; return false;" class="btn-jollibee px-6 py-2 rounded-lg font-medium flex items-center">
                            <i class="fas fa-search mr-2"></i> Track Another Order
                        </a>
                    </div>
                    
                    <!-- Hidden Track Order Form -->
                    <div id="track-form" class="hidden mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Track Another Order</h3>
                        <form method="post" action="" class="max-w-full">
                            <div class="flex">
                                <input type="text" name="order_number" required placeholder="Enter order number" 
                                       class="flex-grow border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-jollibee-red focus:border-transparent">
                                <button type="submit" name="track_order" class="bg-jollibee-red text-white px-4 py-2 rounded-r-lg hover:bg-jollibee-darkred transition-colors">
                                    <i class="fas fa-search mr-2"></i> Track
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Additional Help Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden p-6 card-hover-effect">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full bg-jollibee-red bg-opacity-10 flex items-center justify-center mr-3">
                        <i class="fas fa-headset text-jollibee-red"></i>
                    </div>
                    <h3 class="font-bold text-gray-800">Need help with your order?</h3>
                </div>
                <p class="text-gray-600 mb-4">If you have any questions or concerns about your order, please contact us.</p>
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-2 md:space-y-0">
                    <a href="tel:+63287000" class="btn-jollibee-secondary py-2 px-4 rounded-lg flex items-center justify-center font-medium text-center">
                        <i class="fas fa-phone-alt mr-2"></i> Call Customer Service
                    </a>
                    <a href="mailto:customercare@jollibee.com.ph" class="btn-jollibee-secondary py-2 px-4 rounded-lg flex items-center justify-center font-medium text-center">
                        <i class="fas fa-envelope mr-2"></i> Email Support
                    </a>
                    <a href="<?php echo APP_URL; ?>?page=support<?php echo isset($orderDetails['order_number']) ? '&order=' . $orderDetails['order_number'] : ''; ?>" class="btn-jollibee py-2 px-4 rounded-lg flex items-center justify-center font-medium text-center">
                        <i class="fas fa-ticket-alt mr-2"></i> Submit Support Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Add some animation when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Animate status icons sequentially
    const statusItems = document.querySelectorAll('.status-item');
    if (statusItems.length > 0) {
        statusItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('fade-in');
            }, 300 * index);
        });
    }
});
</script> 