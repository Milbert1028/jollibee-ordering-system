<?php
// Marketing Management Page - Manage Deals and Family Bundles

// Check if promotions table exists, if not create it
$checkPromotionsTable = "SHOW TABLES LIKE 'promotions'";
$tableExists = mysqli_query($conn, $checkPromotionsTable);

if (mysqli_num_rows($tableExists) == 0) {
    // Create the promotions table
    $createTableQuery = "CREATE TABLE promotions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        original_price DECIMAL(10,2) DEFAULT NULL,
        image VARCHAR(255) NOT NULL,
        type ENUM('deal', 'bundle') NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createTableQuery)) {
        // Insert sample data
        $sampleDataQuery = "INSERT INTO promotions (title, description, price, original_price, image, type) VALUES
        ('Family Bundle A', 'Enjoy 6pc Chickenjoy, 1 Jolly Spaghetti Family Pan, and 3 regular drinks.', 549.00, 650.00, 'family-bundle-a.jpg', 'bundle'),
        ('Burger Bundle', '3 Yumburgers, 3 regular fries, and 3 regular drinks.', 349.00, 415.00, 'burger-bundle.jpg', 'bundle'),
        ('Chicken Meal Deal', '2pc Chickenjoy with rice and drink', 179.00, 199.00, 'chicken-deal.jpg', 'deal'),
        ('Breakfast Deal', '1 Pancake, 1 Regular Coffee, and 1 Hashbrown', 99.00, 129.00, 'breakfast-deal.jpg', 'deal')";
        
        mysqli_query($conn, $sampleDataQuery);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new promotion
    if (isset($_POST['add_promotion'])) {
        $title = cleanInput($_POST['title']);
        $description = cleanInput($_POST['description']);
        $price = floatval($_POST['price']);
        $originalPrice = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : NULL;
        $type = cleanInput($_POST['type']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
        
        // Handle image upload
        $imageName = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = __DIR__ . '/../../assets/images/';
            $fileName = basename($_FILES['image']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'promotion_' . time() . '.' . $fileExt;
            
            // Only allow certain file types
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($fileExt), $allowedExtensions)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
                    $imageName = $newFileName;
                } else {
                    $_SESSION['error'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
            }
        } else {
            $_SESSION['error'] = "Please select an image.";
        }
        
        if (empty($_SESSION['error']) && !empty($imageName)) {
            $insertQuery = "INSERT INTO promotions (title, description, price, original_price, image, type, is_active, start_date, end_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($stmt, "ssddssiss", $title, $description, $price, $originalPrice, $imageName, $type, $isActive, $startDate, $endDate);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Promotion added successfully!";
                // Redirect to clear the form
                header('Location: ' . APP_URL . '?page=admin&view=marketing');
                exit;
            } else {
                $_SESSION['error'] = "Error adding promotion: " . mysqli_error($conn);
            }
        }
    }
    
    // Edit promotion
    if (isset($_POST['edit_promotion'])) {
        $id = (int)$_POST['promotion_id'];
        $title = cleanInput($_POST['title']);
        $description = cleanInput($_POST['description']);
        $price = floatval($_POST['price']);
        $originalPrice = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : NULL;
        $type = cleanInput($_POST['type']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
        
        // Get current image
        $getImageQuery = "SELECT image FROM promotions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $getImageQuery);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $currentImage = $row['image'];
        
        // Check if new image was uploaded
        $imageName = $currentImage;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = __DIR__ . '/../../assets/images/';
            $fileName = basename($_FILES['image']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'promotion_' . time() . '.' . $fileExt;
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($fileExt), $allowedExtensions)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
                    $imageName = $newFileName;
                    
                    // Delete old image if it exists
                    if (!empty($currentImage) && file_exists($uploadDir . $currentImage)) {
                        unlink($uploadDir . $currentImage);
                    }
                } else {
                    $_SESSION['error'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
            }
        }
        
        if (empty($_SESSION['error'])) {
            $updateQuery = "UPDATE promotions SET 
                            title = ?, 
                            description = ?, 
                            price = ?, 
                            original_price = ?, 
                            image = ?, 
                            type = ?, 
                            is_active = ?, 
                            start_date = ?, 
                            end_date = ? 
                            WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ssddssissi", $title, $description, $price, $originalPrice, $imageName, $type, $isActive, $startDate, $endDate, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Promotion updated successfully!";
                header('Location: ' . APP_URL . '?page=admin&view=marketing');
                exit;
            } else {
                $_SESSION['error'] = "Error updating promotion: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete promotion
    if (isset($_POST['delete_promotion'])) {
        $id = (int)$_POST['promotion_id'];
        
        // Get image filename before deleting
        $getImageQuery = "SELECT image FROM promotions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $getImageQuery);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $imageName = $row['image'];
            
            // Delete the record
            $deleteQuery = "DELETE FROM promotions WHERE id = ?";
            $stmt = mysqli_prepare($conn, $deleteQuery);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Delete the image file
                $imagePath = __DIR__ . '/../../assets/images/' . $imageName;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                $_SESSION['success'] = "Promotion deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting promotion: " . mysqli_error($conn);
            }
            
            header('Location: ' . APP_URL . '?page=admin&view=marketing');
            exit;
        }
    }
}

// Define filter values
$typeFilter = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$activeFilter = isset($_GET['active']) ? cleanInput($_GET['active']) : '';
$searchQuery = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Build the SQL query with filters
$queryConditions = [];
$params = [];
$types = "";

if (!empty($typeFilter)) {
    $queryConditions[] = "type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

if ($activeFilter !== '') {
    $queryConditions[] = "is_active = ?";
    $params[] = $activeFilter;
    $types .= "i";
}

if (!empty($searchQuery)) {
    $queryConditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $types .= "ss";
}

$whereClause = empty($queryConditions) ? "" : "WHERE " . implode(" AND ", $queryConditions);

// Get promotions from database
$query = "SELECT * FROM promotions $whereClause ORDER BY created_at DESC";
$promotions = [];

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $promotions[] = $row;
    }
}

// Get promotion to edit if ID is provided
$editPromotion = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editQuery = "SELECT * FROM promotions WHERE id = ?";
    $stmt = mysqli_prepare($conn, $editQuery);
    mysqli_stmt_bind_param($stmt, "i", $editId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $editPromotion = mysqli_fetch_assoc($result);
    }
}
?>

<div class="container mx-auto fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Marketing Management</h1>
        <div>
            <a href="<?php echo APP_URL; ?>?page=admin&view=dashboard" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded inline-flex items-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px">
            <li class="mr-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=marketing" class="inline-block py-2 px-4 text-sm font-medium text-center <?php echo empty($typeFilter) ? 'border-b-2 border-jollibee-red text-jollibee-red' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300'; ?>">
                    All Promotions
                </a>
            </li>
            <li class="mr-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=marketing&type=deal" class="inline-block py-2 px-4 text-sm font-medium text-center <?php echo $typeFilter === 'deal' ? 'border-b-2 border-jollibee-red text-jollibee-red' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300'; ?>">
                    Deals
                </a>
            </li>
            <li class="mr-2">
                <a href="<?php echo APP_URL; ?>?page=admin&view=marketing&type=bundle" class="inline-block py-2 px-4 text-sm font-medium text-center <?php echo $typeFilter === 'bundle' ? 'border-b-2 border-jollibee-red text-jollibee-red' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300'; ?>">
                    Family Bundles
                </a>
            </li>
        </ul>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Promotions List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <?php if ($typeFilter === 'deal'): ?>
                            Deals
                        <?php elseif ($typeFilter === 'bundle'): ?>
                            Family Bundles
                        <?php else: ?>
                            All Promotions
                        <?php endif; ?>
                    </h2>
                    
                    <!-- Search and Filter Controls -->
                    <div class="flex">
                        <form action="" method="get" class="flex items-center">
                            <input type="hidden" name="page" value="admin">
                            <input type="hidden" name="view" value="marketing">
                            <?php if (!empty($typeFilter)): ?>
                            <input type="hidden" name="type" value="<?php echo $typeFilter; ?>">
                            <?php endif; ?>
                            
                            <div class="mr-2">
                                <select name="active" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                                    <option value="" <?php echo $activeFilter === '' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="1" <?php echo $activeFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $activeFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="flex rounded-md overflow-hidden border border-gray-300">
                                <input type="text" name="search" placeholder="Search promotions..." value="<?php echo $searchQuery; ?>" 
                                       class="px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-jollibee-red border-none">
                                <button type="submit" class="px-3 py-2 bg-jollibee-red text-white">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (empty($promotions)): ?>
                <div class="text-center py-8">
                    <div class="mb-4 text-gray-400">
                        <i class="fas fa-bullhorn text-5xl"></i>
                    </div>
                    <p class="text-gray-500">No promotions found.</p>
                    <?php if (!empty($searchQuery)): ?>
                    <p class="text-gray-500 mt-2">Try using different search terms.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($promotions as $promotion): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-12 w-12 rounded overflow-hidden flex-shrink-0">
                                            <img class="h-full w-full object-cover" src="<?php echo APP_URL; ?>/assets/images/<?php echo $promotion['image']; ?>" alt="<?php echo $promotion['title']; ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $promotion['title']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo mb_substr($promotion['description'], 0, 50) . (mb_strlen($promotion['description']) > 50 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($promotion['type'] == 'deal'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Deal</span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Bundle</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">₱<?php echo number_format($promotion['price'], 2); ?></div>
                                    <?php if ($promotion['original_price']): ?>
                                    <div class="text-xs text-gray-500 line-through">₱<?php echo number_format($promotion['original_price'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($promotion['is_active']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?php echo APP_URL; ?>?page=admin&view=marketing&edit=<?php echo $promotion['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $promotion['id']; ?>, '<?php echo htmlspecialchars($promotion['title'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    
                                    <form id="delete-form-<?php echo $promotion['id']; ?>" method="post" action="" class="hidden">
                                        <input type="hidden" name="promotion_id" value="<?php echo $promotion['id']; ?>">
                                        <input type="hidden" name="delete_promotion" value="1">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add/Edit Promotion Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <?php echo $editPromotion ? 'Edit Promotion' : 'Add New Promotion'; ?>
                </h2>
                
                <form method="post" action="" enctype="multipart/form-data" class="space-y-4">
                    <?php if ($editPromotion): ?>
                    <input type="hidden" name="promotion_id" value="<?php echo $editPromotion['id']; ?>">
                    <input type="hidden" name="edit_promotion" value="1">
                    <?php else: ?>
                    <input type="hidden" name="add_promotion" value="1">
                    <?php endif; ?>
                    
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo $editPromotion ? $editPromotion['title'] : ''; ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                        <select id="type" name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                            <option value="deal" <?php echo $editPromotion && $editPromotion['type'] == 'deal' ? 'selected' : ''; ?>>Deal</option>
                            <option value="bundle" <?php echo $editPromotion && $editPromotion['type'] == 'bundle' ? 'selected' : ''; ?>>Family Bundle</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">₱</span>
                                </div>
                                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo $editPromotion ? $editPromotion['price'] : ''; ?>" required 
                                       class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                            </div>
                        </div>
                        
                        <div>
                            <label for="original_price" class="block text-sm font-medium text-gray-700 mb-1">Original Price</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">₱</span>
                                </div>
                                <input type="number" id="original_price" name="original_price" min="0" step="0.01" value="<?php echo $editPromotion && $editPromotion['original_price'] ? $editPromotion['original_price'] : ''; ?>" 
                                       class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red"><?php echo $editPromotion ? $editPromotion['description'] : ''; ?></textarea>
                    </div>
                    
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">
                            Image <?php echo $editPromotion ? '' : '<span class="text-red-500">*</span>'; ?>
                        </label>
                        <?php if ($editPromotion && $editPromotion['image']): ?>
                        <div class="mb-2 flex items-center">
                            <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $editPromotion['image']; ?>" alt="Current Image" class="h-16 w-16 object-cover rounded">
                            <span class="ml-2 text-sm text-gray-500">Current image</span>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" <?php echo $editPromotion ? '' : 'required'; ?> 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                        <p class="mt-1 text-xs text-gray-500">Recommended size: 800x600px. JPG, PNG, or WEBP only.</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $editPromotion && $editPromotion['start_date'] ? $editPromotion['start_date'] : ''; ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $editPromotion && $editPromotion['end_date'] ? $editPromotion['end_date'] : ''; ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo (!$editPromotion || $editPromotion['is_active']) ? 'checked' : ''; ?> 
                               class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                    </div>
                    
                    <div class="flex justify-end pt-4">
                        <?php if ($editPromotion): ?>
                        <a href="<?php echo APP_URL; ?>?page=admin&view=marketing" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 transition-colors">
                            Cancel
                        </a>
                        <?php endif; ?>
                        <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white py-2 px-4 rounded-md transition-colors">
                            <?php echo $editPromotion ? 'Update Promotion' : 'Add Promotion'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Confirm delete function
function confirmDelete(id, title) {
    if (confirm('Are you sure you want to delete the promotion "' + title + '"?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}

// Preview image before upload
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Check if there's already a preview
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview mt-2';
                        imageInput.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <div class="flex items-center">
                            <img src="${event.target.result}" alt="Preview" class="h-16 w-16 object-cover rounded">
                            <span class="ml-2 text-sm text-gray-500">Preview of new image</span>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script> 