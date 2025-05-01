<?php
// Include the required configuration files
if (!defined('APP_URL')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/jollibee-ordering-system/config/config.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/jollibee-ordering-system/includes/functions.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Connect to database if not already connected
    if (!isset($conn)) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }
    }
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . APP_URL . '?page=admin');
    exit;
}

// Include admin header
include_once 'pages/admin/admin_header.php';

// Handle order status update
if (isset($_GET['action']) && $_GET['action'] == 'update-status' && isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = (int)$_GET['id'];
    $status = cleanInput($_GET['status']);
    
    // Validate status value
    $allowedStatuses = ['received', 'preparing', 'ready', 'completed', 'cancelled'];
    
    if (in_array($status, $allowedStatuses)) {
        $query = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = 'Order status updated successfully.';
        } else {
            $_SESSION['error'] = 'Error updating order status.';
        }
    } else {
        $_SESSION['error'] = 'Invalid status value.';
    }
    
    // Check if we should return to the detail view
    if (isset($_GET['return']) && $_GET['return'] == 'view') {
        header('Location: ' . APP_URL . '?page=admin&view=orders&action=view&id=' . $orderId);
    } else {
        header('Location: ' . APP_URL . '?page=admin&view=orders');
    }
    exit;
}

// Handle payment status update
if (isset($_GET['action']) && $_GET['action'] == 'update-payment' && isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = (int)$_GET['id'];
    $status = cleanInput($_GET['status']);
    
    // Validate payment status value
    $allowedStatuses = ['pending', 'paid', 'failed'];
    
    if (in_array($status, $allowedStatuses)) {
        $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = 'Payment status updated successfully.';
        } else {
            $_SESSION['error'] = 'Error updating payment status.';
        }
    } else {
        $_SESSION['error'] = 'Invalid payment status value.';
    }
    
    // Check if we should return to the detail view
    if (isset($_GET['return']) && $_GET['return'] == 'view') {
        header('Location: ' . APP_URL . '?page=admin&view=orders&action=view&id=' . $orderId);
    } else {
        header('Location: ' . APP_URL . '?page=admin&view=orders');
    }
    exit;
}

// Get order details for viewing
$viewOrder = null;
$orderItems = [];

if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    
    // Get order details
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $viewOrder = mysqli_fetch_assoc($result);
        
        // Get order items
        $orderItems = getOrderItems($conn, $orderId);
    } else {
        $_SESSION['error'] = 'Order not found.';
        header('Location: ' . APP_URL . '?page=admin&view=orders');
        exit;
    }
}

// Get all orders with pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by status if provided
$statusFilter = isset($_GET['filter']) ? cleanInput($_GET['filter']) : '';
$whereClause = $statusFilter ? "WHERE order_status = '$statusFilter'" : "";

// Count total orders
$countQuery = "SELECT COUNT(*) as total FROM orders $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalOrders = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalOrders / $limit);

// Get orders for current page
$query = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $offset, $limit);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$orders = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}
?>

<!-- Orders Page Content -->
<div class="container mx-auto px-4 py-6">
    <?php if ($viewOrder): ?>
    <!-- View Single Order -->
    <div class="mb-4">
        <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-jollibee-red hover:underline flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Orders
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-jollibee-red text-white p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold">Order #<?php echo $viewOrder['order_number']; ?></h2>
                <p class="text-sm opacity-90">Placed on <?php echo date('F j, Y, g:i a', strtotime($viewOrder['created_at'])); ?></p>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <select onchange="updateOrderStatus(<?php echo $viewOrder['id']; ?>, this.value)" 
                        class="bg-white text-gray-800 px-3 py-2 rounded text-sm focus:outline-none w-full sm:w-auto">
                    <option value="" disabled selected>Update Status</option>
                    <option value="received" <?php echo $viewOrder['order_status'] == 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="preparing" <?php echo $viewOrder['order_status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo $viewOrder['order_status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="completed" <?php echo $viewOrder['order_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $viewOrder['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <select onchange="updateOrderStatus(<?php echo $viewOrder['id']; ?>, this.value, 'payment')" 
                        class="bg-white text-gray-800 px-3 py-2 rounded text-sm focus:outline-none w-full sm:w-auto">
                    <option value="" disabled selected>Payment Status</option>
                    <option value="pending" <?php echo $viewOrder['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $viewOrder['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="failed" <?php echo $viewOrder['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Order Progress -->
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Order Progress</h3>
                
                <div class="relative order-progress-container">
                    <!-- Progress bar background -->
                    <div class="absolute left-0 right-0 top-1/2 transform -translate-y-1/2 h-2 bg-gray-200 rounded-full z-0"></div>
                    
                    <!-- Active progress bar -->
                    <?php 
                    $statuses = ['received', 'preparing', 'ready', 'completed'];
                    $currentStatusIndex = array_search($viewOrder['order_status'], $statuses);
                    if ($currentStatusIndex === false && $viewOrder['order_status'] != 'cancelled') $currentStatusIndex = -1;
                    
                    $progressPercentage = ($currentStatusIndex + 1) * 100 / count($statuses);
                    if ($viewOrder['order_status'] == 'cancelled') $progressPercentage = 0;
                    ?>
                    
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-2 bg-jollibee-red rounded-full transition-all duration-500" 
                         style="width: <?php echo $progressPercentage; ?>%; z-index: 1;"></div>
                    
                    <!-- Status buttons -->
                    <div class="relative flex justify-between">
                        <?php foreach ($statuses as $index => $status): 
                            $isPast = $currentStatusIndex !== false && $index <= $currentStatusIndex;
                            $isCurrent = $currentStatusIndex !== false && $index == $currentStatusIndex;
                            $isFuture = $currentStatusIndex !== false && $index > $currentStatusIndex;
                            
                            // Define button styles based on status
                            if ($isCurrent) {
                                $buttonClass = 'bg-jollibee-red text-white border-4 border-white shadow-md scale-110 pulse-animation';
                                $textClass = 'text-jollibee-red font-bold';
                                $icon = $status == 'completed' ? 'fa-flag-checkered' : 'fa-spinner fa-spin';
                            } elseif ($isPast) {
                                $buttonClass = 'bg-green-500 text-white cursor-pointer hover:scale-105';
                                $textClass = 'text-green-600';
                                $icon = 'fa-check';
                            } else {
                                $buttonClass = 'bg-gray-200 text-gray-500 cursor-pointer hover:bg-gray-300 hover:scale-105';
                                $textClass = 'text-gray-500';
                                $icon = ($index === $currentStatusIndex + 1) ? 'fa-arrow-right' : 'fa-circle';
                            }
                            
                            // If cancelled, make all buttons neutral
                            if ($viewOrder['order_status'] == 'cancelled') {
                                $buttonClass = 'bg-gray-200 text-gray-400';
                                $textClass = 'text-gray-400';
                                $icon = 'fa-circle';
                            }
                            
                            // Status icons that represent each state
                            $statusIcons = [
                                'received' => 'fa-inbox',
                                'preparing' => 'fa-utensils',
                                'ready' => 'fa-check-circle',
                                'completed' => 'fa-flag-checkered'
                            ];
                        ?>
                        <div class="text-center z-10">
                            <?php if ($viewOrder['order_status'] != 'cancelled'): ?>
                            <button onclick="updateOrderStatusWithConfirm(<?php echo $viewOrder['id']; ?>, '<?php echo $status; ?>')" 
                                    class="status-step-button flex h-12 w-12 items-center justify-center rounded-full <?php echo $buttonClass; ?> transition-all duration-300"
                                    title="<?php echo $isPast ? 'Already ' . ucfirst($status) : ($isCurrent ? 'Current Status: ' . ucfirst($status) : 'Update to ' . ucfirst($status)); ?>">
                                <i class="fas <?php echo $icon; ?> text-sm"></i>
                            </button>
                            <?php else: ?>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full <?php echo $buttonClass; ?>">
                                <i class="fas <?php echo $icon; ?> text-sm"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex flex-col items-center mt-2">
                                <span class="text-xs font-medium <?php echo $textClass; ?> status-label">
                                    <?php echo ucfirst($status); ?>
                                </span>
                                <span class="text-2xl text-<?php echo $isPast || $isCurrent ? 'jollibee-red' : 'gray-300'; ?> mt-1">
                                    <i class="fas <?php echo $statusIcons[$status]; ?>"></i>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($viewOrder['order_status'] != 'cancelled' && $currentStatusIndex < 3): ?>
                    <!-- Quick advance button -->
                    <div class="mt-8 text-center">
                        <button onclick="advanceToNextStatus(<?php echo $viewOrder['id']; ?>, '<?php echo $statuses[$currentStatusIndex + 1]; ?>')" 
                                class="inline-flex items-center px-4 py-2 bg-jollibee-red text-white rounded-md hover:bg-jollibee-darkred transition-colors shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all duration-200">
                            <i class="fas fa-forward mr-2"></i> Advance to <?php echo ucfirst($statuses[$currentStatusIndex + 1]); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($viewOrder['order_status'] == 'cancelled'): ?>
                    <div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md text-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-red-700">This order has been cancelled and cannot be processed further.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Status History -->
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history mr-2 text-gray-500"></i> Status History
                </h3>
                
                <div class="bg-gray-50 rounded-md p-4">
                    <div class="flex items-center mb-3">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-inbox text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">Order Received</p>
                            <p class="text-xs text-gray-500"><?php echo date('F j, Y, g:i a', strtotime($viewOrder['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($currentStatusIndex >= 1 || $viewOrder['order_status'] == 'cancelled'): ?>
                    <div class="ml-4 border-l-2 border-gray-200 pl-4 mb-3">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="fas fa-utensils text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">Preparing Order</p>
                                <p class="text-xs text-gray-500"><?php echo date('F j, Y, g:i a', strtotime('+5 minutes', strtotime($viewOrder['created_at']))); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($currentStatusIndex >= 2 || $viewOrder['order_status'] == 'cancelled'): ?>
                    <div class="ml-4 border-l-2 border-gray-200 pl-4 mb-3">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-check-circle text-purple-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">Order Ready</p>
                                <p class="text-xs text-gray-500"><?php echo date('F j, Y, g:i a', strtotime('+15 minutes', strtotime($viewOrder['created_at']))); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($currentStatusIndex >= 3): ?>
                    <div class="ml-4 border-l-2 border-gray-200 pl-4">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-flag-checkered text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">Order Completed</p>
                                <p class="text-xs text-gray-500"><?php echo date('F j, Y, g:i a', strtotime('+25 minutes', strtotime($viewOrder['created_at']))); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($viewOrder['order_status'] == 'cancelled'): ?>
                    <div class="ml-4 border-l-2 border-gray-200 pl-4">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="fas fa-times text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">Order Cancelled</p>
                                <p class="text-xs text-gray-500"><?php echo date('F j, Y, g:i a', strtotime($viewOrder['updated_at'] ?? $viewOrder['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Customer Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Customer Information</h3>
                    <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($viewOrder['customer_name']); ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($viewOrder['customer_email'] ?? 'N/A'); ?></p>
                    <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($viewOrder['customer_phone'] ?? 'N/A'); ?></p>
                </div>
                
                <!-- Payment Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Payment Information</h3>
                    <p class="mb-2">
                        <strong>Method:</strong>
                    <?php
                    switch ($viewOrder['payment_method']) {
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
                                echo ucfirst($viewOrder['payment_method'] ?? 'N/A');
                    }
                    ?>
                </p>
                    <p class="mb-2">
                        <strong>Status:</strong> 
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold 
                            <?php 
                            switch ($viewOrder['payment_status']) {
                                case 'paid':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'failed':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($viewOrder['payment_status']); ?>
                        </span>
                    </p>
                </div>
                
                <!-- Order Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Order Information</h3>
                    <p class="mb-2"><strong>Order #:</strong> <?php echo $viewOrder['order_number']; ?></p>
                    <p class="mb-2"><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($viewOrder['created_at'])); ?></p>
                    <p class="mb-2">
                        <strong>Status:</strong>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold 
                            <?php 
                            switch ($viewOrder['order_status']) {
                                case 'received':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'preparing':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'ready':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'completed':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'cancelled':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                        ?>">
                            <?php echo ucfirst($viewOrder['order_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Order Items -->
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Items</h3>
            <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg">
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
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <img class="h-12 w-12 object-cover rounded shadow-sm" 
                                             src="<?php echo APP_URL; ?>/assets/images/<?php echo $item['image'] ?: 'default.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo isset($item['category']) ? htmlspecialchars($item['category']) : ''; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatPrice($item['unit_price']); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 py-1 bg-gray-100 rounded-full"><?php echo $item['quantity']; ?></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                <?php echo formatPrice($item['subtotal']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-900">Total:</td>
                            <td class="px-4 py-3 whitespace-nowrap text-lg font-bold text-jollibee-red">
                                <?php echo formatPrice($viewOrder['total_amount']); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Actions Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <?php if ($viewOrder['order_status'] != 'cancelled'): ?>
                <a href="#" onclick="if(confirm('Are you sure you want to cancel this order?')) { window.location.href='<?php echo APP_URL; ?>?page=admin&view=orders&action=update-status&id=<?php echo $viewOrder['id']; ?>&status=cancelled'; } return false;" 
                   class="px-4 py-2 bg-red-50 text-red-700 rounded-md hover:bg-red-100 transition-colors">
                    <i class="fas fa-times mr-1"></i> Cancel Order
                </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to All Orders
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors">
                    <i class="fas fa-print mr-1"></i> Print Order
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Orders List -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Orders Management</h1>
        
        <!-- Status Filter & Search bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full md:w-auto">
            <div class="relative w-full sm:w-64">
                <input type="text" id="orderSearchInput" placeholder="Search by order #, name..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-jollibee-red focus:border-transparent">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <select onchange="window.location.href='<?php echo APP_URL; ?>?page=admin&view=orders&filter=' + this.value" 
                    class="border border-gray-300 rounded-md text-gray-600 px-2 py-2 text-sm w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                <option value="" <?php echo $statusFilter == '' ? 'selected' : ''; ?>>All Orders</option>
                <option value="received" <?php echo $statusFilter == 'received' ? 'selected' : ''; ?>>Received</option>
                <option value="preparing" <?php echo $statusFilter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="ready" <?php echo $statusFilter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
    </div>
    
    <!-- Status Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- All Orders -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-500 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">All Orders</p>
                    <p class="text-2xl font-bold text-gray-700"><?php 
                        $countAllOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count']; 
                        echo $countAllOrders;
                    ?></p>
                </div>
                <div class="rounded-full bg-gray-100 p-2">
                    <i class="fas fa-shopping-cart text-gray-500"></i>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-xs text-gray-500 hover:text-jollibee-red mt-2 inline-block">View all</a>
        </div>
        
        <!-- Received Orders -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Received</p>
                    <p class="text-2xl font-bold text-gray-700"><?php 
                        $countReceived = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'received'"))['count']; 
                        echo $countReceived;
                    ?></p>
                </div>
                <div class="rounded-full bg-blue-100 p-2">
                    <i class="fas fa-inbox text-blue-500"></i>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=orders&filter=received" class="text-xs text-gray-500 hover:text-jollibee-red mt-2 inline-block">View received</a>
        </div>
        
        <!-- Preparing Orders -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Preparing</p>
                    <p class="text-2xl font-bold text-gray-700"><?php 
                        $countPreparing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'preparing'"))['count']; 
                        echo $countPreparing;
                    ?></p>
                </div>
                <div class="rounded-full bg-yellow-100 p-2">
                    <i class="fas fa-utensils text-yellow-500"></i>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=orders&filter=preparing" class="text-xs text-gray-500 hover:text-jollibee-red mt-2 inline-block">View preparing</a>
        </div>
        
        <!-- Ready Orders -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Ready</p>
                    <p class="text-2xl font-bold text-gray-700"><?php 
                        $countReady = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'ready'"))['count']; 
                        echo $countReady;
                    ?></p>
                </div>
                <div class="rounded-full bg-purple-100 p-2">
                    <i class="fas fa-check-circle text-purple-500"></i>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=orders&filter=ready" class="text-xs text-gray-500 hover:text-jollibee-red mt-2 inline-block">View ready</a>
        </div>
        
        <!-- Completed Orders -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Completed</p>
                    <p class="text-2xl font-bold text-gray-700"><?php 
                        $countCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status = 'completed'"))['count']; 
                        echo $countCompleted;
                    ?></p>
                </div>
                <div class="rounded-full bg-green-100 p-2">
                    <i class="fas fa-check-double text-green-500"></i>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=orders&filter=completed" class="text-xs text-gray-500 hover:text-jollibee-red mt-2 inline-block">View completed</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="ordersTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                            Order # <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                            Customer <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                            Amount <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(5)">
                            Date <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">No orders found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $order['order_number']; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $order['customer_name']; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                <?php echo formatPrice($order['total_amount']); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center <?php 
                                    switch ($order['order_status']) {
                                        case 'received':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'preparing':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'ready':
                                            echo 'bg-purple-100 text-purple-800';
                                            break;
                                        case 'completed':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'cancelled':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                ?> px-2.5 py-1 text-xs font-semibold rounded-full">
                                    <?php if ($order['order_status'] == 'received'): ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5 animate-pulse"></span>
                                    <?php elseif ($order['order_status'] == 'preparing'): ?>
                                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1.5 animate-pulse"></span>
                                    <?php elseif ($order['order_status'] == 'ready'): ?>
                                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-1.5 animate-pulse"></span>
                                    <?php elseif ($order['order_status'] == 'completed'): ?>
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                    <?php elseif ($order['order_status'] == 'cancelled'): ?>
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                    <?php endif; ?>
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="<?php 
                                    switch ($order['payment_status']) {
                                        case 'paid':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'failed':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                ?> px-2.5 py-1 text-xs font-semibold rounded-full">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=orders&action=view&id=<?php echo $order['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-1.5 rounded transition-colors"
                                       title="View Order Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Quick Status Update Dropdown -->
                                    <div class="relative inline-block text-left" x-data="{ open: false }">
                                        <button @click="open = !open" type="button" class="text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 p-1.5 rounded transition-colors" title="Quick Update">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" 
                                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95">
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                                <div class="text-xs font-semibold text-gray-500 uppercase px-4 py-2 border-b">Update Status</div>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'received'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-inbox text-blue-500 mr-2"></i> Received
                                                </a>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'preparing'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-utensils text-yellow-500 mr-2"></i> Preparing
                                                </a>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'ready'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-check-circle text-purple-500 mr-2"></i> Ready
                                                </a>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-check-double text-green-500 mr-2"></i> Completed
                                                </a>
                                                <div class="border-t my-1"></div>
                                                <div class="text-xs font-semibold text-gray-500 uppercase px-4 py-2 border-b">Payment Status</div>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending', 'payment'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-clock text-yellow-500 mr-2"></i> Pending
                                                </a>
                                                <a href="javascript:void(0)" 
                                                   onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'paid', 'payment'); return false;"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                                   <i class="fas fa-check text-green-500 mr-2"></i> Paid
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($page > 1): ?>
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders&p=<?php echo $page - 1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders&p=<?php echo $page + 1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                   class="relative inline-flex items-center px-4 py-2 ml-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo min(($page - 1) * $limit + 1, $totalOrders); ?></span> to 
                        <span class="font-medium"><?php echo min($page * $limit, $totalOrders); ?></span> of 
                        <span class="font-medium"><?php echo $totalOrders; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                        <a href="<?php echo APP_URL; ?>?page=admin&view=orders&p=<?php echo $page - 1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left h-5 w-5"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="<?php echo APP_URL; ?>?page=admin&view=orders&p=<?php echo $i; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium 
                                  <?php echo $i == $page ? 'bg-jollibee-red text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="<?php echo APP_URL; ?>?page=admin&view=orders&p=<?php echo $page + 1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <i class="fas fa-chevron-right h-5 w-5"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Alpine.js for dropdowns -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js" defer></script>

<script>
    // Function to update order status
    function updateOrderStatus(orderId, status, type = 'status') {
        let message = 'Are you sure you want to update this order';
        let action = 'update-status';
        
        if (type === 'payment') {
            message += ' payment status to ' + status + '?';
            action = 'update-payment';
        } else {
            message += ' status to ' + status + '?';
        }
        
        if (confirm(message)) {
            // Check if we're on the detail view page
            const urlParams = new URLSearchParams(window.location.search);
            const viewAction = urlParams.get('action');
            let returnParam = '';
            
            if (viewAction === 'view') {
                returnParam = '&return=view';
            }
            
            window.location.href = '<?php echo APP_URL; ?>?page=admin&view=orders&action=' + action + '&id=' + orderId + '&status=' + status + returnParam;
        }
    }
    
    // Function to update order status with a more user-friendly confirmation
    function updateOrderStatusWithConfirm(orderId, status) {
        // Get current status from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentId = urlParams.get('id');
        const viewAction = urlParams.get('action');
        let returnParam = '';
        
        if (viewAction === 'view') {
            returnParam = '&return=view';
        }
        
        // Only show confirmation if moving backward or skipping steps
        const statuses = ['received', 'preparing', 'ready', 'completed'];
        const currentStatus = document.querySelector('.status-step-button.pulse-animation')?.parentElement.querySelector('.status-label').textContent.toLowerCase();
        const currentIndex = statuses.indexOf(currentStatus);
        const newIndex = statuses.indexOf(status);
        
        let message = '';
        
        if (newIndex < currentIndex) {
            message = `Are you sure you want to move the order backward to "${status}" status?`;
        } else if (newIndex > currentIndex + 1) {
            message = `Are you sure you want to skip steps and change the order status to "${status}"?`;
        } else if (newIndex === currentIndex) {
            // No need to update if the status is the same
            return;
        } else {
            // Moving forward one step, just do it
            window.location.href = '<?php echo APP_URL; ?>?page=admin&view=orders&action=update-status&id=' + orderId + '&status=' + status + returnParam;
            return;
        }
        
        if (confirm(message)) {
            window.location.href = '<?php echo APP_URL; ?>?page=admin&view=orders&action=update-status&id=' + orderId + '&status=' + status + returnParam;
        }
    }
    
    // Function to advance to the next logical status
    function advanceToNextStatus(orderId, nextStatus) {
        const urlParams = new URLSearchParams(window.location.search);
        const viewAction = urlParams.get('action');
        let returnParam = '';
        
        if (viewAction === 'view') {
            returnParam = '&return=view';
        }
        
        window.location.href = '<?php echo APP_URL; ?>?page=admin&view=orders&action=update-status&id=' + orderId + '&status=' + nextStatus + returnParam;
    }
    
    // Table search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('orderSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const table = document.getElementById('ordersTable');
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const orderNumber = rows[i].getElementsByTagName('td')[0];
                    const customerName = rows[i].getElementsByTagName('td')[1];
                    
                    if (orderNumber && customerName) {
                        const orderNumberText = orderNumber.textContent || orderNumber.innerText;
                        const customerNameText = customerName.textContent || customerName.innerText;
                        
                        if (orderNumberText.toLowerCase().indexOf(searchTerm) > -1 || 
                            customerNameText.toLowerCase().indexOf(searchTerm) > -1) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
            });
        }
    });
    
    // Table sorting functionality
    let sortDirection = 1; // 1: ascending, -1: descending
    let lastSortedColumn = -1;
    
    function sortTable(columnIndex) {
        const table = document.getElementById('ordersTable');
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = Array.from(tbody.getElementsByTagName('tr'));
        
        // Toggle sort direction if clicking the same column
        if (lastSortedColumn === columnIndex) {
            sortDirection *= -1;
        } else {
            sortDirection = 1;
        }
        
        lastSortedColumn = columnIndex;
        
        // Sort rows
        rows.sort((a, b) => {
            let aValue = a.getElementsByTagName('td')[columnIndex].textContent.trim();
            let bValue = b.getElementsByTagName('td')[columnIndex].textContent.trim();
            
            // Special handling for currency
            if (columnIndex === 2) { // Amount column
                aValue = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                bValue = parseFloat(bValue.replace(/[^\d.-]/g, ''));
            }
            
            // Special handling for dates
            if (columnIndex === 5) { // Date column
                aValue = new Date(aValue).getTime();
                bValue = new Date(bValue).getTime();
            }
            
            if (aValue < bValue) return -1 * sortDirection;
            if (aValue > bValue) return 1 * sortDirection;
            return 0;
        });
        
        // Reinsert rows in new order
        rows.forEach(row => tbody.appendChild(row));
    }
</script>

<?php
// Include admin footer
include_once 'pages/admin/admin_footer.php';
?>

<style>
/* Order progress styles */
.order-progress-container {
    padding: 20px 0;
}

.status-step-button {
    box-shadow: 0 0 0 4px white;
    transition: all 0.3s ease;
}

.status-step-button:hover {
    transform: scale(1.1);
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(227, 24, 55, 0.4), 0 0 0 4px white;
    }
    70% {
        box-shadow: 0 0 0 10px rgba(227, 24, 55, 0), 0 0 0 4px white;
    }
    100% {
        box-shadow: 0 0 0 0 rgba(227, 24, 55, 0), 0 0 0 4px white;
    }
}

.status-label {
    white-space: nowrap;
}
</style> 