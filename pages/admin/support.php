<?php
// Customer Support Management - Admin Page

// Create support_tickets table if it doesn't exist
$checkTableQuery = "SHOW TABLES LIKE 'support_tickets'";
$tableExists = mysqli_query($conn, $checkTableQuery);

if (mysqli_num_rows($tableExists) == 0) {
    // Table doesn't exist, create it
    $createTableQuery = "CREATE TABLE support_tickets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('open', 'in progress', 'closed') DEFAULT 'open',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createTableQuery)) {
        // Insert sample data
        $sampleDataQuery = "INSERT INTO support_tickets (customer_name, customer_email, subject, message) VALUES
        ('John Doe', 'john@example.com', 'Order #JB230501123 Delivery Issue', 'My order has been marked as delivered but I have not received it yet. Please help.'),
        ('Maria Santos', 'maria@example.com', 'Wrong Items in Order #JB230502456', 'I received chicken instead of spaghetti in my order. Can you help me?'),
        ('Alex Cruz', 'alex@example.com', 'App Payment Issue', 'I am unable to complete payment through the app. It keeps showing an error message.')";
        
        mysqli_query($conn, $sampleDataQuery);
    }
}

// Handle response submission
if (isset($_POST['submit_response'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $response = cleanInput($_POST['response']);
    $status = cleanInput($_POST['status']);
    
    $updateQuery = "UPDATE support_tickets SET 
                     admin_response = ?, 
                     status = ? 
                     WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ssi", $response, $status, $ticketId);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Response submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting response: " . mysqli_error($conn);
    }
    
    // Refresh the page to show the update
    header("Location: " . APP_URL . "?page=admin&view=support");
    exit;
}

// Get support tickets with filtering
$statusFilter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

$queryConditions = [];
$params = [];
$types = "";

if (!empty($statusFilter)) {
    $queryConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $queryConditions[] = "(customer_name LIKE ? OR customer_email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $types .= "ssss";
}

$whereClause = empty($queryConditions) ? "" : "WHERE " . implode(" AND ", $queryConditions);

$query = "SELECT * FROM support_tickets $whereClause ORDER BY 
          CASE 
            WHEN status = 'open' THEN 1 
            WHEN status = 'in progress' THEN 2 
            WHEN status = 'closed' THEN 3 
          END, 
          created_at DESC";

$tickets = [];
$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = $row;
    }
}

// Get ticket counts
$statuses = ['open', 'in progress', 'closed'];
$ticketCounts = [];

foreach ($statuses as $status) {
    $countQuery = "SELECT COUNT(*) as count FROM support_tickets WHERE status = ?";
    $stmt = mysqli_prepare($conn, $countQuery);
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    $countResult = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($countResult)['count'];
    $ticketCounts[$status] = $count;
}

$totalTickets = array_sum($ticketCounts);

// View a specific ticket if ID is provided
$selectedTicket = null;
if (isset($_GET['id'])) {
    $ticketId = (int)$_GET['id'];
    $ticketQuery = "SELECT * FROM support_tickets WHERE id = ?";
    $stmt = mysqli_prepare($conn, $ticketQuery);
    mysqli_stmt_bind_param($stmt, "i", $ticketId);
    mysqli_stmt_execute($stmt);
    $ticketResult = mysqli_stmt_get_result($stmt);
    
    if ($ticketResult && mysqli_num_rows($ticketResult) > 0) {
        $selectedTicket = mysqli_fetch_assoc($ticketResult);
    }
}
?>

<div class="container mx-auto fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Customer Support</h1>
        <div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=dashboard" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded inline-flex items-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Support Tickets Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Tickets</p>
                    <p class="text-xl font-semibold"><?php echo $totalTickets; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Open Tickets</p>
                    <p class="text-xl font-semibold"><?php echo $ticketCounts['open']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">In Progress</p>
                    <p class="text-xl font-semibold"><?php echo $ticketCounts['in progress']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Closed Tickets</p>
                    <p class="text-xl font-semibold"><?php echo $ticketCounts['closed']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Support Tickets List -->
        <div class="md:w-1/2 bg-white rounded-lg shadow-md p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Support Tickets</h2>
                <div class="flex space-x-2">
                    <a href="<?php echo APP_URL; ?>?page=admin&view=support" class="<?php echo empty($statusFilter) ? 'bg-jollibee-red text-white' : 'bg-gray-200 text-gray-700'; ?> px-3 py-1 rounded text-sm">All</a>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=support&status=open" class="<?php echo $statusFilter === 'open' ? 'bg-jollibee-red text-white' : 'bg-gray-200 text-gray-700'; ?> px-3 py-1 rounded text-sm">Open</a>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=support&status=in+progress" class="<?php echo $statusFilter === 'in progress' ? 'bg-jollibee-red text-white' : 'bg-gray-200 text-gray-700'; ?> px-3 py-1 rounded text-sm">In Progress</a>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=support&status=closed" class="<?php echo $statusFilter === 'closed' ? 'bg-jollibee-red text-white' : 'bg-gray-200 text-gray-700'; ?> px-3 py-1 rounded text-sm">Closed</a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <form action="" method="get" class="mb-4">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="view" value="support">
                <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                <?php endif; ?>
                <div class="flex">
                    <input type="text" name="search" placeholder="Search tickets..." value="<?php echo $searchQuery; ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                    <button type="submit" class="bg-jollibee-red text-white px-4 py-2 rounded-r hover:bg-jollibee-darkred">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Tickets List -->
            <div class="overflow-y-auto max-h-[500px]">
                <?php if (empty($tickets)): ?>
                <div class="text-center py-8">
                    <div class="mb-4 text-gray-400">
                        <i class="fas fa-ticket-alt text-5xl"></i>
                    </div>
                    <p class="text-gray-500">No support tickets found.</p>
                    <?php if (!empty($searchQuery)): ?>
                    <p class="text-gray-500 mt-2">Try using different search terms.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($tickets as $ticket): ?>
                    <a href="<?php echo APP_URL; ?>?page=admin&view=support&id=<?php echo $ticket['id']; ?>" class="block py-3 px-2 hover:bg-gray-50 transition-colors <?php echo (isset($_GET['id']) && $_GET['id'] == $ticket['id']) ? 'bg-yellow-50' : ''; ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-800"><?php echo $ticket['subject']; ?></h3>
                                <p class="text-sm text-gray-500"><?php echo $ticket['customer_name']; ?> (<?php echo $ticket['customer_email']; ?>)</p>
                            </div>
                            <div>
                                <?php 
                                $statusClasses = [
                                    'open' => 'bg-red-100 text-red-800',
                                    'in progress' => 'bg-yellow-100 text-yellow-800',
                                    'closed' => 'bg-green-100 text-green-800'
                                ];
                                $statusClass = $statusClasses[$ticket['status']];
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <p class="text-xs text-gray-500"><?php echo date('M d, Y g:i A', strtotime($ticket['created_at'])); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ticket Details / Response Form -->
        <div class="md:w-1/2 bg-white rounded-lg shadow-md p-4">
            <?php if ($selectedTicket): ?>
            <!-- Ticket Details -->
            <div class="mb-4 pb-3 border-b">
                <div class="flex justify-between items-start">
                    <h2 class="text-lg font-semibold text-gray-800"><?php echo $selectedTicket['subject']; ?></h2>
                    <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClasses[$selectedTicket['status']]; ?>">
                        <?php echo ucfirst($selectedTicket['status']); ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    From: <?php echo $selectedTicket['customer_name']; ?> (<?php echo $selectedTicket['customer_email']; ?>)
                </p>
                <p class="text-xs text-gray-500">
                    Submitted: <?php echo date('M d, Y g:i A', strtotime($selectedTicket['created_at'])); ?>
                </p>
            </div>
            
            <!-- Message Content -->
            <div class="mb-6 p-3 bg-gray-50 rounded">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Customer Message:</h3>
                <p class="whitespace-pre-line text-gray-800"><?php echo $selectedTicket['message']; ?></p>
            </div>
            
            <?php if (!empty($selectedTicket['admin_response'])): ?>
            <!-- Previous Response -->
            <div class="mb-6 p-3 bg-blue-50 rounded border-l-4 border-blue-500">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Your Previous Response:</h3>
                <p class="whitespace-pre-line text-gray-800"><?php echo $selectedTicket['admin_response']; ?></p>
                <p class="text-xs text-gray-500 mt-2">
                    Last updated: <?php echo date('M d, Y g:i A', strtotime($selectedTicket['updated_at'])); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Response Form -->
            <form method="post" action="">
                <input type="hidden" name="ticket_id" value="<?php echo $selectedTicket['id']; ?>">
                
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                    <select name="status" id="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-jollibee-red focus:ring focus:ring-jollibee-red focus:ring-opacity-50">
                        <option value="open" <?php echo $selectedTicket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in progress" <?php echo $selectedTicket['status'] == 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="closed" <?php echo $selectedTicket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="response" class="block text-sm font-medium text-gray-700 mb-1">Your Response</label>
                    <textarea name="response" id="response" rows="6" class="w-full border-gray-300 rounded-md shadow-sm focus:border-jollibee-red focus:ring focus:ring-jollibee-red focus:ring-opacity-50" placeholder="Type your response here..."><?php echo $selectedTicket['admin_response'] ?? ''; ?></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="submit_response" class="bg-jollibee-red hover:bg-jollibee-darkred text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Submit Response
                    </button>
                </div>
            </form>
            <?php else: ?>
            <!-- No Ticket Selected -->
            <div class="text-center py-12">
                <div class="mb-4 text-gray-400">
                    <i class="fas fa-comments text-6xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Select a Ticket</h2>
                <p class="text-gray-500">Choose a support ticket from the list to view details and respond.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add your JavaScript here if needed
</script> 