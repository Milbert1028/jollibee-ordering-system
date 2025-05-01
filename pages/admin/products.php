<?php
// Products management page

// Process form submissions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $success = false;
    
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    } else {
        if ($action == 'add') {
            // Add new product logic
            $name = isset($_POST['product_name']) ? $conn->real_escape_string($_POST['product_name']) : '';
            $category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';
            $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
            
            // Handle image upload
            $image_field = "";
            $image_value = "";
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = array('jpg', 'jpeg', 'png', 'webp', 'jfif');
                $filename = $_FILES['image']['name'];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if(in_array(strtolower($ext), $allowed)) {
                    // Create safe filename
                    $newname = strtolower(str_replace(' ', '-', $name)) . '.' . $ext;
                    $target = dirname(dirname(dirname(__FILE__))) . '/assets/images/' . $newname;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                        $image_field = ", image";
                        $image_value = ", '$newname'";
                    }
                }
            }
            
            // Check if category_id exists or use direct category name
            $category_id_field = ""; 
            $category_field = "";
            
            // Check table structure to see if category_id exists
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
            if ($result && $result->num_rows > 0) {
                // First verify this category exists and get its ID
                $categoryId = 0;
                if (!empty($category)) {
                    $cat_result = $conn->query("SELECT id FROM categories WHERE name = '$category' LIMIT 1");
                    if ($cat_result && $cat_result->num_rows > 0) {
                        $cat_row = $cat_result->fetch_assoc();
                        $categoryId = (int)$cat_row['id'];
                    } else {
                        // If category doesn't exist, create it
                        $conn->query("INSERT INTO categories (name) VALUES ('$category')");
                        $categoryId = $conn->insert_id;
                    }
                }
                
                if ($categoryId > 0) {
                    $category_id_field = ", category_id";
                    $category_field = ", $categoryId";
                }
            } else {
                // Check if category column exists
                $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
                if ($result && $result->num_rows > 0 && !empty($category)) {
                    $category_id_field = ", category";
                    $category_field = ", '$category'";
                }
            }
            
            $sql = "INSERT INTO products (name, price $category_id_field $image_field) VALUES ('$name', $price $category_field $image_value)";
            
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Product added successfully!";
                $success = true;
            } else {
                $_SESSION['error'] = "Error adding product: " . $conn->error;
            }
        } elseif ($action == 'edit') {
            // Edit product logic
            $id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $name = isset($_POST['product_name']) ? $conn->real_escape_string($_POST['product_name']) : '';
            $category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';
            $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
            
            // Handle image upload
            $image_update = "";
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = array('jpg', 'jpeg', 'png', 'webp', 'jfif');
                $filename = $_FILES['image']['name'];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if(in_array(strtolower($ext), $allowed)) {
                    // Create safe filename
                    $newname = strtolower(str_replace(' ', '-', $name)) . '.' . $ext;
                    $target = dirname(dirname(dirname(__FILE__))) . '/assets/images/' . $newname;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                        $image_update = ", image = '$newname'";
                    }
                }
            }
            
            // Check if category_id exists or use direct category field
            $category_update = "";
            
            // Check table structure to see if category_id exists
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
            if ($result && $result->num_rows > 0) {
                // First verify this category exists and get its ID
                $categoryId = 0;
                if (!empty($category)) {
                    $cat_result = $conn->query("SELECT id FROM categories WHERE name = '$category' LIMIT 1");
                    if ($cat_result && $cat_result->num_rows > 0) {
                        $cat_row = $cat_result->fetch_assoc();
                        $categoryId = (int)$cat_row['id'];
                    } else {
                        // If category doesn't exist, create it
                        $conn->query("INSERT INTO categories (name) VALUES ('$category')");
                        $categoryId = $conn->insert_id;
                    }
                }
                
                if ($categoryId > 0) {
                    $category_update = ", category_id = $categoryId";
                }
            } else {
                // Check if category column exists
                $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
                if ($result && $result->num_rows > 0 && !empty($category)) {
                    $category_update = ", category = '$category'";
                }
            }
            
            $sql = "UPDATE products SET name='$name', price=$price $category_update $image_update WHERE id=$id";
            
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Product updated successfully!";
                $success = true;
            } else {
                $_SESSION['error'] = "Error updating product: " . $conn->error . " (SQL: $sql)";
            }
        } elseif ($action == 'delete') {
            // Delete product logic
            $id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            
            if ($id > 0) {
                $sql = "DELETE FROM products WHERE id=$id";
                
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['success'] = "Product deleted successfully!";
                    $success = true;
                } else {
                    $_SESSION['error'] = "Error deleting product: " . $conn->error;
                }
            } else {
                $_SESSION['error'] = "Invalid product ID for deletion";
            }
        }
        
        $conn->close();
    }
    
    // Redirect to refresh the page
    header('Location: ' . APP_URL . '?page=admin&view=products');
    exit;
}

// Fetch products from database
$products = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn->connect_error) {
    // Check if there's a category_id column that requires a join with categories
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
    if ($result && $result->num_rows > 0) {
        // If category_id exists, join with categories to get the name
        $sql = "SELECT p.*, c.name as category 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.id DESC";
    } else {
        // Otherwise just get all products
        $sql = "SELECT * FROM products ORDER BY id DESC";
    }
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    $conn->close();
}
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg onclick="this.parentElement.parentElement.style.display='none'" class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg onclick="this.parentElement.parentElement.style.display='none'" class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Product Management</h1>
        <button id="addProductBtn" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
            <i class="fas fa-plus mr-2"></i> Add New Product
        </button>
    </div>

    <!-- Products List -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($products)): ?>
                <!-- Sample Data (shown only when database is empty) -->
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?php echo APP_URL . '/assets/images/2pc chickenjoy solo.webp'; ?>" alt="Chickenjoy" class="w-10 h-10 rounded-full object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Chickenjoy</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Chicken</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱120.00</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3 edit-product-btn" data-id="1" data-name="Chickenjoy" data-category="Chicken" data-price="120.00"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600 hover:text-red-900 delete-product-btn" data-id="1" data-name="Chickenjoy"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?php echo APP_URL . '/assets/images/Jolly Spaghetti solo.webp'; ?>" alt="Jolly Spaghetti" class="w-10 h-10 rounded-full object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Jolly Spaghetti</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Pasta</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱90.00</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3 edit-product-btn" data-id="2" data-name="Jolly Spaghetti" data-category="Pasta" data-price="90.00"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600 hover:text-red-900 delete-product-btn" data-id="2" data-name="Jolly Spaghetti"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?php echo APP_URL . '/assets/images/Yumburger solo.webp'; ?>" alt="Yumburger" class="w-10 h-10 rounded-full object-cover">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Yumburger</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Burger</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱75.00</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3 edit-product-btn" data-id="3" data-name="Yumburger" data-category="Burger" data-price="75.00"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600 hover:text-red-900 delete-product-btn" data-id="3" data-name="Yumburger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo APP_URL . '/assets/images/' . $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-10 h-10 rounded-full object-cover">
                            <?php else: ?>
                                <div style="display:inline-flex; width:40px; height:40px; border-radius:100%; background-color:#E4022F; color:white; align-items:center; justify-content:center; font-weight:bold; font-size:16px;">
                                    <?php echo strtoupper(substr($product['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $product['name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo isset($product['category']) ? $product['category'] : 'Uncategorized'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?php echo isset($product['status']) ? $product['status'] : 'Available'; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-blue-600 hover:text-blue-900 mr-3 edit-product-btn" 
                                    data-id="<?php echo $product['id']; ?>" 
                                    data-name="<?php echo $product['name']; ?>" 
                                    data-category="<?php echo isset($product['category']) ? $product['category'] : ''; ?>" 
                                    data-price="<?php echo $product['price']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-red-600 hover:text-red-900 delete-product-btn" 
                                     data-id="<?php echo $product['id']; ?>" 
                                     data-name="<?php echo $product['name']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($products)): ?>
    <div class="py-3 flex items-center justify-between mt-4">
        <div class="flex-1 flex justify-between sm:hidden">
            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($products); ?></span> of <span class="font-medium"><?php echo count($products); ?></span> results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="#" aria-current="page" class="z-10 bg-jollibee-red border-jollibee-red text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Product Modal -->
<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add New Product</h3>
            <form id="productForm" method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="productId" value="">
                
                <div class="mb-4">
                    <label for="productName" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="product_name" id="productName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                </div>
                
                <div class="mb-4">
                    <label for="productCategory" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="productCategory" name="category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                        <option value="All Items">All Items</option>
                        <option value="Burger Steak">Burger Steak</option>
                        <option value="Chicken">Chicken</option>
                        <option value="Chicken Joy">Chicken Joy</option>
                        <option value="Jolly Spaghetti">Jolly Spaghetti</option>
                        <option value="Palabok Fiesta">Palabok Fiesta</option>
                        <option value="Sides & Desserts">Sides & Desserts</option>
                        <option value="Yumburger">Yumburger</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="productPrice" class="block text-sm font-medium text-gray-700">Price (₱)</label>
                    <input type="number" name="price" id="productPrice" step="0.01" min="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                </div>
                
                <div class="mb-4">
                    <label for="productImage" class="block text-sm font-medium text-gray-700">Product Image</label>
                    <input type="file" name="image" id="productImage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                </div>
                
                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" id="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-jollibee-red text-white rounded-md hover:bg-jollibee-darkred focus:outline-none">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-2 text-center">Confirm Delete</h3>
            <p class="text-sm text-gray-500 text-center mb-4">Are you sure you want to delete <span id="deleteProductName" class="font-medium"></span>? This action cannot be undone.</p>
            
            <form id="deleteForm" method="post" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" id="deleteProductId" value="">
                
                <div class="flex justify-center space-x-3 mt-5">
                    <button type="button" id="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const productModal = document.getElementById('productModal');
    const deleteModal = document.getElementById('deleteModal');
    const modalTitle = document.getElementById('modalTitle');
    const productForm = document.getElementById('productForm');
    const formAction = document.getElementById('formAction');
    const productId = document.getElementById('productId');
    const productName = document.getElementById('productName');
    const productCategory = document.getElementById('productCategory');
    const productPrice = document.getElementById('productPrice');
    
    // Add Product Button
    const addProductBtn = document.getElementById('addProductBtn');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New Product';
            formAction.value = 'add';
            productId.value = '';
            productForm.reset();
            productModal.classList.remove('hidden');
        });
    }
    
    // Edit Product Buttons
    const editButtons = document.querySelectorAll('.edit-product-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const category = this.getAttribute('data-category');
            const price = this.getAttribute('data-price');
            
            modalTitle.textContent = 'Edit Product';
            formAction.value = 'edit';
            productId.value = id;
            productName.value = name;
            productCategory.value = category;
            productPrice.value = price;
            
            productModal.classList.remove('hidden');
        });
    });
    
    // Delete Product Buttons
    const deleteButtons = document.querySelectorAll('.delete-product-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductName').textContent = name;
            
            deleteModal.classList.remove('hidden');
        });
    });
    
    // Close Modal Buttons
    document.getElementById('closeModal').addEventListener('click', function() {
        productModal.classList.add('hidden');
    });
    
    document.getElementById('cancelDelete').addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === productModal) {
            productModal.classList.add('hidden');
        }
        if (event.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
});
</script> 