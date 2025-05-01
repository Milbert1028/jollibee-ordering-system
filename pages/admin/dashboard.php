<?php
// Admin authentication check removed to allow direct access

// Connect to database if not already connected
if (!isset($conn) || !$conn) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
}

// Set default values in case database tables don't exist
$totalOrders = 0;
$pendingOrders = 0;
$completedOrders = 0;
$totalRevenue = 0;
$recentOrders = [];
$popularProducts = [];

// Daily revenue data (demo data - would be pulled from database in production)
$dailyRevenue = [
    date('M d', strtotime('-6 days')) => 4200,
    date('M d', strtotime('-5 days')) => 5300,
    date('M d', strtotime('-4 days')) => 4800,
    date('M d', strtotime('-3 days')) => 6100,
    date('M d', strtotime('-2 days')) => 5700,
    date('M d', strtotime('-1 days')) => 7200,
    date('M d') => 6500
];

// Most ordered items (demo data - would be pulled from database in production)
$mostOrderedItems = [
    ['product' => 'Chickenjoy Solo', 'count' => 158, 'revenue' => 18960],
    ['product' => 'Jolly Spaghetti', 'count' => 145, 'revenue' => 13050],
    ['product' => '2pc Chickenjoy w/ Rice', 'count' => 132, 'revenue' => 21120],
    ['product' => 'Yumburger', 'count' => 120, 'revenue' => 9000],
    ['product' => 'Jolly Crispy Fries', 'count' => 112, 'revenue' => 8960]
];

// Check if orders table exists
$tableExists = false;
$orderStatusColumn = 'order_status';
$result = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if ($result && mysqli_num_rows($result) > 0) {
    $tableExists = true;
    
    // Get columns from orders table
    $columnsResult = mysqli_query($conn, "SHOW COLUMNS FROM orders");
    $columns = [];
    while ($row = mysqli_fetch_assoc($columnsResult)) {
        $columns[] = $row['Field'];
    }
    
    // Determine status column name
    if (in_array('status', $columns)) {
        $orderStatusColumn = 'status';
    } elseif (in_array('order_status', $columns)) {
        $orderStatusColumn = 'order_status';
    } elseif (in_array('state', $columns)) {
        $orderStatusColumn = 'state';
    }
    
    // Get actual data if table exists
    try {
        // Total orders
        $query = "SELECT COUNT(*) as total FROM orders";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $totalOrders = $row['total'];
        }
        
        // Pending & completed orders
        if ($orderStatusColumn) {
            $query = "SELECT COUNT(*) as pending FROM orders WHERE {$orderStatusColumn} = 'pending'";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $pendingOrders = $row['pending'];
            }
            
            $query = "SELECT COUNT(*) as completed FROM orders WHERE {$orderStatusColumn} = 'completed'";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $completedOrders = $row['completed'];
            }
        }
        
        // Total revenue
        $query = "SELECT SUM(total_amount) as revenue FROM orders";
        if ($orderStatusColumn) {
            $query .= " WHERE {$orderStatusColumn} = 'completed'";
        }
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $totalRevenue = $row['revenue'] ? $row['revenue'] : 0;
        }
        
        // Recent orders
        $query = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $recentOrders[] = $row;
            }
        }
    } catch (Exception $e) {
        // Silently handle database errors
    }
}

// Check if products table exists
try {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
    if ($result && mysqli_num_rows($result) > 0) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'order_details'");
        if ($result && mysqli_num_rows($result) > 0) {
            // Get popular products if both tables exist
            $query = "SELECT p.*, COUNT(od.product_id) as order_count 
                      FROM order_details od 
                      JOIN products p ON od.product_id = p.id 
                      GROUP BY od.product_id 
                      ORDER BY order_count DESC 
                      LIMIT 5";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $popularProducts[] = $row;
                }
            }
        }
    }
} catch (Exception $e) {
    // Silently handle database errors
}

// If no actual data, use demo data
if (empty($recentOrders)) {
    $recentOrders = [
        ['id' => 1, 'customer_name' => 'John Doe', 'total_amount' => 450.50, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        ['id' => 2, 'customer_name' => 'Jane Smith', 'total_amount' => 320.75, 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 day'))],
        ['id' => 3, 'customer_name' => 'Mark Johnson', 'total_amount' => 150.25, 'status' => 'processing', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 day'))]
    ];
}

if (empty($popularProducts)) {
    $popularProducts = [
        ['id' => 1, 'name' => 'Chickenjoy', 'price' => 120.00, 'image' => '', 'order_count' => 42],
        ['id' => 2, 'name' => 'Jolly Spaghetti', 'price' => 90.00, 'image' => '', 'order_count' => 36],
        ['id' => 3, 'name' => 'Yumburger', 'price' => 75.00, 'image' => '', 'order_count' => 28]
    ];
}

$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Administrator';
?>

<!-- Dashboard Content -->
<div class="bg-gray-50 p-6 rounded-lg">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <p class="text-gray-600 mt-1">Welcome back, <?php echo $adminName; ?>!</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <i class="fas fa-calendar"></i>
                </span>
                <input type="text" class="bg-white border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm" value="<?php echo date('F j, Y'); ?>" readonly>
            </div>
            <button class="bg-jollibee-red hover:bg-jollibee-darkred text-white rounded-lg px-4 py-2 transition-colors duration-200 flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-blue-50 text-blue-500">
                        <i class="fas fa-shopping-cart text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo $totalOrders; ?></span>
                            <span class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                                <i class="fas fa-arrow-up mr-1"></i>12%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50 px-6 py-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center justify-end">
                    View all orders <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-amber-50 text-amber-500">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Pending Orders</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo $pendingOrders; ?></span>
                            <span class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                                <i class="fas fa-exclamation-circle mr-1"></i>Needs Action
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-amber-50 px-6 py-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-xs text-amber-600 hover:text-amber-800 font-medium flex items-center justify-end">
                    Process orders <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-green-50 text-green-500">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Completed Orders</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo $completedOrders; ?></span>
                            <span class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 text-green-600">
                                <i class="fas fa-arrow-up mr-1"></i>8%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 px-6 py-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-xs text-green-600 hover:text-green-800 font-medium flex items-center justify-end">
                    View completed <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 rounded-lg bg-jollibee-red bg-opacity-10 text-jollibee-red">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-800">₱<?php echo number_format($totalRevenue, 2); ?></span>
                            <span class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-jollibee-red bg-opacity-10 text-jollibee-red">
                                <i class="fas fa-arrow-up mr-1"></i>15%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-jollibee-red bg-opacity-10 px-6 py-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=reports" class="text-xs text-jollibee-red hover:text-jollibee-darkred font-medium flex items-center justify-end">
                    View financial reports <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Analytics - Takes 2 columns on larger screens -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-800">Daily Revenue <span class="text-gray-500 text-sm font-normal">(Last 7 Days)</span></h3>
                    <p class="text-sm text-gray-500 mt-1">Performance overview by day</p>
                </div>
                <div class="flex space-x-2">
                    <button class="inline-flex items-center text-sm px-3 py-1.5 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-file-export mr-2 text-gray-500"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Most Ordered Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-800">Popular Items</h3>
                    <p class="text-sm text-gray-500 mt-1">Most ordered products</p>
                </div>
                <a href="<?php echo APP_URL; ?>?page=admin&view=reports" class="text-sm text-jollibee-red hover:text-jollibee-darkred transition-colors">View All</a>
            </div>
            <div class="p-6">
                <div class="space-y-5">
                    <?php foreach(array_slice($mostOrderedItems, 0, 5) as $index => $item): ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-medium text-sm">
                            <?php echo $index + 1; ?>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex justify-between">
                                <h4 class="text-sm font-medium text-gray-800"><?php echo $item['product']; ?></h4>
                                <span class="text-sm font-semibold text-jollibee-red">₱<?php echo number_format($item['revenue'], 0); ?></span>
                            </div>
                            <div class="flex items-center mt-1">
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-jollibee-red h-1.5 rounded-full" style="width: <?php echo min(100, ($item['count'] / 158) * 100); ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500 ml-2 min-w-[40px] text-right"><?php echo $item['count']; ?> sold</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders - Takes 2 columns -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-800">Recent Orders</h3>
                    <p class="text-sm text-gray-500 mt-1">Latest customer orders</p>
                </div>
                <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="text-sm text-jollibee-red hover:text-jollibee-darkred transition-colors">View All</a>
            </div>
            <div class="px-6 py-4">
                <?php if (count($recentOrders) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($recentOrders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800">#<?php echo $order['id']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $order['customer_name'] ?? 'Guest'; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800">₱<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php 
                                    $status = isset($order[$orderStatusColumn]) ? $order[$orderStatusColumn] : (isset($order['status']) ? $order['status'] : 'pending');
                                    
                                    if ($status == 'pending'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-amber-500"></span>
                                        Pending
                                    </span>
                                    <?php elseif ($status == 'processing'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-blue-500"></span>
                                        Processing
                                    </span>
                                    <?php elseif ($status == 'completed'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-500"></span>
                                        Completed
                                    </span>
                                    <?php elseif ($status == 'cancelled'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span>
                                        Cancelled
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-gray-500"></span>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=orders&id=<?php echo $order['id']; ?>" class="text-jollibee-red hover:text-jollibee-darkred transition-colors">
                                        View <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No recent orders found</p>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="mt-2 inline-block text-sm text-jollibee-red hover:text-jollibee-darkred transition-colors">View all orders</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Popular Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-semibold text-gray-800">Top Products</h3>
                    <p class="text-sm text-gray-500 mt-1">Best selling menu items</p>
                </div>
                <a href="<?php echo APP_URL; ?>?page=admin&view=products" class="text-sm text-jollibee-red hover:text-jollibee-darkred transition-colors">View All</a>
            </div>
            <div class="p-6">
                <?php if (count($popularProducts) > 0): ?>
                <div class="space-y-5">
                    <?php foreach($popularProducts as $product): ?>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                            <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="h-full w-full object-cover">
                            <?php else: ?>
                            <div class="h-full w-full flex items-center justify-center bg-gray-100 text-gray-400">
                                <i class="fas fa-hamburger"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex justify-between">
                                <h4 class="text-sm font-medium text-gray-800"><?php echo $product['name']; ?></h4>
                                <span class="text-xs font-medium text-gray-600">₱<?php echo number_format($product['price'], 0); ?></span>
                            </div>
                            <div class="flex items-center mt-1">
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-jollibee-red h-1.5 rounded-full" style="width: <?php echo min(100, ($product['order_count'] / 50) * 100); ?>%"></div>
                                </div>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-jollibee-yellow text-jollibee-red">
                                    <?php echo $product['order_count']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 text-center">
                    <a href="<?php echo APP_URL; ?>?page=admin&view=products" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Manage Products
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fas fa-burger text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No popular products found</p>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=products" class="mt-2 inline-block text-sm text-jollibee-red hover:text-jollibee-darkred transition-colors">Add products</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    var ctx = document.getElementById('revenueChart');
    if (ctx) {
        ctx = ctx.getContext('2d');
        var dates = [
            <?php 
            // Generate day labels
            $days = array_keys($dailyRevenue);
            foreach ($days as $day): 
                echo "'$day',"; 
            endforeach; 
            ?>
        ];
        var revenues = [
            <?php 
            // Generate revenue data
            foreach ($dailyRevenue as $revenue): 
                echo "$revenue,"; 
            endforeach; 
            ?>
        ];

        var revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Daily Revenue (₱)',
                    data: revenues,
                    backgroundColor: 'rgba(227, 24, 55, 0.1)',
                    borderColor: 'rgba(227, 24, 55, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(227, 24, 55, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        bodyFont: {
                            size: 12
                        },
                        displayColors: false,
                        padding: 10,
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        caretSize: 8,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₱' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Revenue chart initialized successfully');
    } else {
        console.error('Could not find revenue chart canvas element');
    }
});
</script>

<script>
    // JavaScript for user dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', function() {
                userDropdown.classList.toggle('hidden');
            });
            
            // Close the dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
    });
</script> 