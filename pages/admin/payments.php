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

// Handle payment status update
if (isset($_POST['action']) && $_POST['action'] == 'update_payment') {
    $orderId = (int)$_POST['order_id'];
    $paymentStatus = cleanInput($_POST['payment_status']);
    
    // Validate status
    $allowedStatuses = ['pending', 'paid', 'failed'];
    if (in_array($paymentStatus, $allowedStatuses)) {
        $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $paymentStatus, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Payment status updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating payment status: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Invalid payment status.";
    }
    
    // Redirect to refresh
    header('Location: ' . APP_URL . '?page=admin&view=payments');
    exit;
}

// Handle adding payment method
if (isset($_POST['action']) && $_POST['action'] == 'add_method') {
    $methodName = cleanInput($_POST['method_name']);
    $methodCode = cleanInput($_POST['method_code']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if payment_methods table exists
    $tableExistsQuery = "SHOW TABLES LIKE 'payment_methods'";
    $tableExists = mysqli_query($conn, $tableExistsQuery);
    
    if (mysqli_num_rows($tableExists) == 0) {
        // Create payment_methods table if it doesn't exist
        $createTableQuery = "CREATE TABLE payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($conn, $createTableQuery)) {
            $_SESSION['error'] = "Error creating payment methods table: " . mysqli_error($conn);
            header('Location: ' . APP_URL . '?page=admin&view=payments');
            exit;
        }
    }
    
    // Insert new payment method
    $query = "INSERT INTO payment_methods (name, code, is_active) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $methodName, $methodCode, $isActive);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Payment method added successfully.";
    } else {
        $_SESSION['error'] = "Error adding payment method: " . mysqli_error($conn);
    }
    
    // Redirect to refresh
    header('Location: ' . APP_URL . '?page=admin&view=payments');
    exit;
}

// Handle updating payment method
if (isset($_POST['action']) && $_POST['action'] == 'edit_method') {
    $methodId = (int)$_POST['method_id'];
    $methodName = cleanInput($_POST['method_name']);
    $methodCode = cleanInput($_POST['method_code']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $query = "UPDATE payment_methods SET name = ?, code = ?, is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssii", $methodName, $methodCode, $isActive, $methodId);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Payment method updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating payment method: " . mysqli_error($conn);
    }
    
    // Redirect to refresh
    header('Location: ' . APP_URL . '?page=admin&view=payments');
    exit;
}

// Handle deleting payment method
if (isset($_POST['action']) && $_POST['action'] == 'delete_method') {
    $methodId = (int)$_POST['method_id'];
    
    $query = "DELETE FROM payment_methods WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $methodId);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Payment method deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting payment method: " . mysqli_error($conn);
    }
    
    // Redirect to refresh
    header('Location: ' . APP_URL . '?page=admin&view=payments');
    exit;
}

// Get payment methods
$paymentMethods = [];
$tableExistsQuery = "SHOW TABLES LIKE 'payment_methods'";
$tableExists = mysqli_query($conn, $tableExistsQuery);

if (mysqli_num_rows($tableExists) > 0) {
    $query = "SELECT * FROM payment_methods ORDER BY name";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $paymentMethods[] = $row;
        }
    }
}

// Get payment transactions with pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by payment status if provided
$statusFilter = isset($_GET['filter']) ? cleanInput($_GET['filter']) : '';
$whereClause = $statusFilter ? "WHERE payment_status = '$statusFilter'" : "";

// Count total transactions
$countQuery = "SELECT COUNT(*) as total FROM orders $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalTransactions = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalTransactions / $limit);

// Get transactions for current page
$query = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $offset, $limit);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$transactions = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
}
?>

<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Payment Management</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Methods Section -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-jollibee-red text-white p-4">
                    <h2 class="text-lg font-semibold">Payment Methods</h2>
                </div>
                <div class="p-4">
                    <button id="addMethodBtn" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md mb-4 flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Method
                    </button>
                    
                    <?php if (empty($paymentMethods)): ?>
                        <div class="text-gray-500 text-center py-4">
                            <p>No payment methods defined yet.</p>
                            <p class="text-sm">Click the button above to add payment methods.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($paymentMethods as $method): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $method['name']; ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $method['code']; ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $method['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3 edit-method-btn" 
                                                    data-id="<?php echo $method['id']; ?>" 
                                                    data-name="<?php echo $method['name']; ?>" 
                                                    data-code="<?php echo $method['code']; ?>" 
                                                    data-active="<?php echo $method['is_active']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-900 delete-method-btn" 
                                                    data-id="<?php echo $method['id']; ?>" 
                                                    data-name="<?php echo $method['name']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Transactions Section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-jollibee-red text-white p-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Payment Transactions</h2>
                    <div class="flex">
                        <select id="paymentStatusFilter" onchange="location = '?page=admin&view=payments&filter=' + this.value" class="bg-white text-gray-800 px-3 py-1 rounded text-sm">
                            <option value="" <?php echo $statusFilter == '' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $statusFilter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $statusFilter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No transactions found.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $transaction['order_number']; ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $transaction['customer_name']; ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo ucfirst($transaction['payment_method']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                switch ($transaction['payment_status']) {
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
                                                <?php echo ucfirst($transaction['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3 update-payment-btn" 
                                                    data-id="<?php echo $transaction['id']; ?>" 
                                                    data-order="<?php echo $transaction['order_number']; ?>"
                                                    data-status="<?php echo $transaction['payment_status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="<?php echo APP_URL; ?>?page=admin&view=orders&action=view&id=<?php echo $transaction['id']; ?>" 
                                               class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="py-3 flex items-center justify-between mt-4">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=payments&p=<?php echo $page-1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=payments&p=<?php echo $page+1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                                    <span class="font-medium"><?php echo min($offset + $limit, $totalTransactions); ?></span> of 
                                    <span class="font-medium"><?php echo $totalTransactions; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=payments&p=<?php echo $page-1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Page numbers -->
                                    <?php 
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($startPage + 4, $totalPages);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): 
                                    ?>
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=payments&p=<?php echo $i; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                                       class="<?php echo $i == $page ? 'z-10 bg-jollibee-red border-jollibee-red text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=payments&p=<?php echo $page+1; ?><?php echo $statusFilter ? '&filter=' . $statusFilter : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Payment Method Modal -->
<div id="paymentMethodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add Payment Method</h3>
            <form id="paymentMethodForm" method="post" action="">
                <input type="hidden" name="action" id="formAction" value="add_method">
                <input type="hidden" name="method_id" id="methodId" value="">
                
                <div class="mb-4">
                    <label for="methodName" class="block text-sm font-medium text-gray-700">Method Name</label>
                    <input type="text" name="method_name" id="methodName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                </div>
                
                <div class="mb-4">
                    <label for="methodCode" class="block text-sm font-medium text-gray-700">Method Code</label>
                    <input type="text" name="method_code" id="methodCode" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                    <p class="text-xs text-gray-500 mt-1">Unique code for this payment method (e.g., "cash", "credit_card")</p>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="methodActive" class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded" checked>
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" id="closeMethodModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-jollibee-red text-white rounded-md hover:bg-jollibee-darkred focus:outline-none">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Payment Status Modal -->
<div id="updatePaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Payment Status</h3>
            <form id="updatePaymentForm" method="post" action="">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="order_id" id="updateOrderId" value="">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Updating payment status for order: <span id="updateOrderNumber" class="font-semibold"></span></p>
                </div>
                
                <div class="mb-4">
                    <label for="paymentStatus" class="block text-sm font-medium text-gray-700">Payment Status</label>
                    <select name="payment_status" id="paymentStatus" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" id="closeUpdateModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-jollibee-red text-white rounded-md hover:bg-jollibee-darkred focus:outline-none">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Payment Method Modal -->
<div id="deleteMethodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-2 text-center">Confirm Delete</h3>
            <p class="text-sm text-gray-500 text-center mb-4">Are you sure you want to delete the payment method <span id="deleteMethodName" class="font-medium"></span>? This action cannot be undone.</p>
            
            <form id="deleteMethodForm" method="post" action="">
                <input type="hidden" name="action" value="delete_method">
                <input type="hidden" name="method_id" id="deleteMethodId" value="">
                
                <div class="flex justify-center space-x-3 mt-5">
                    <button type="button" id="cancelMethodDelete" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment Method Modal Elements
    const paymentMethodModal = document.getElementById('paymentMethodModal');
    const modalTitle = document.getElementById('modalTitle');
    const methodForm = document.getElementById('paymentMethodForm');
    const formAction = document.getElementById('formAction');
    const methodId = document.getElementById('methodId');
    const methodName = document.getElementById('methodName');
    const methodCode = document.getElementById('methodCode');
    const methodActive = document.getElementById('methodActive');
    
    // Add Payment Method Button
    const addMethodBtn = document.getElementById('addMethodBtn');
    if (addMethodBtn) {
        addMethodBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add Payment Method';
            formAction.value = 'add_method';
            methodId.value = '';
            methodForm.reset();
            methodActive.checked = true;
            paymentMethodModal.classList.remove('hidden');
        });
    }
    
    // Edit Payment Method Buttons
    const editMethodBtns = document.querySelectorAll('.edit-method-btn');
    editMethodBtns.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');
            const active = this.getAttribute('data-active') === '1';
            
            modalTitle.textContent = 'Edit Payment Method';
            formAction.value = 'edit_method';
            methodId.value = id;
            methodName.value = name;
            methodCode.value = code;
            methodActive.checked = active;
            
            paymentMethodModal.classList.remove('hidden');
        });
    });
    
    // Delete Payment Method Buttons
    const deleteMethodBtns = document.querySelectorAll('.delete-method-btn');
    const deleteMethodModal = document.getElementById('deleteMethodModal');
    deleteMethodBtns.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('deleteMethodId').value = id;
            document.getElementById('deleteMethodName').textContent = name;
            
            deleteMethodModal.classList.remove('hidden');
        });
    });
    
    // Update Payment Status Buttons
    const updatePaymentBtns = document.querySelectorAll('.update-payment-btn');
    const updatePaymentModal = document.getElementById('updatePaymentModal');
    updatePaymentBtns.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const orderNumber = this.getAttribute('data-order');
            const status = this.getAttribute('data-status');
            
            document.getElementById('updateOrderId').value = id;
            document.getElementById('updateOrderNumber').textContent = orderNumber;
            document.getElementById('paymentStatus').value = status;
            
            updatePaymentModal.classList.remove('hidden');
        });
    });
    
    // Close Modal Buttons
    document.getElementById('closeMethodModal').addEventListener('click', function() {
        paymentMethodModal.classList.add('hidden');
    });
    
    document.getElementById('closeUpdateModal').addEventListener('click', function() {
        updatePaymentModal.classList.add('hidden');
    });
    
    document.getElementById('cancelMethodDelete').addEventListener('click', function() {
        deleteMethodModal.classList.add('hidden');
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === paymentMethodModal) {
            paymentMethodModal.classList.add('hidden');
        }
        if (event.target === updatePaymentModal) {
            updatePaymentModal.classList.add('hidden');
        }
        if (event.target === deleteMethodModal) {
            deleteMethodModal.classList.add('hidden');
        }
    });
});
</script> 