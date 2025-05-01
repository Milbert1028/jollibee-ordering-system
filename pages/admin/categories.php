<?php
// Categories management page

// Process form submissions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $success = false;
    
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    } else {
        // Check what columns exist in the categories table
        $columns = array();
        $columns_result = $conn->query("SHOW COLUMNS FROM categories");
        if ($columns_result) {
            while ($col = $columns_result->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
        }
        
        // Check if specific columns exist
        $has_icon = in_array('icon', $columns);
        $has_description = in_array('description', $columns);
        
        if ($action == 'add') {
            // Add new category logic
            $name = isset($_POST['category_name']) ? $conn->real_escape_string($_POST['category_name']) : '';
            $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
            $icon = isset($_POST['icon']) ? $conn->real_escape_string($_POST['icon']) : '';
            
            // Build SQL based on available columns
            $fields = "name";
            $values = "'$name'";
            
            if ($has_description && !empty($description)) {
                $fields .= ", description";
                $values .= ", '$description'";
            }
            
            if ($has_icon && !empty($icon)) {
                $fields .= ", icon";
                $values .= ", '$icon'";
            }
            
            $sql = "INSERT INTO categories ($fields) VALUES ($values)";
            
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Category added successfully!";
                $success = true;
            } else {
                $_SESSION['error'] = "Error adding category: " . $conn->error;
            }
        } elseif ($action == 'edit') {
            // Edit category logic
            $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $name = isset($_POST['category_name']) ? $conn->real_escape_string($_POST['category_name']) : '';
            $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
            $icon = isset($_POST['icon']) ? $conn->real_escape_string($_POST['icon']) : '';
            
            // Build update SQL based on available columns
            $updates = "name='$name'";
            
            if ($has_description) {
                $updates .= ", description='$description'";
            }
            
            if ($has_icon) {
                $updates .= ", icon='$icon'";
            }
            
            $sql = "UPDATE categories SET $updates WHERE id=$id";
            
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Category updated successfully!";
                $success = true;
            } else {
                $_SESSION['error'] = "Error updating category: " . $conn->error . " (SQL: $sql)";
            }
        } elseif ($action == 'delete') {
            // Delete category logic
            $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            
            // First check if there are products in this category
            $checkSql = "SELECT COUNT(*) as count FROM products WHERE category_id = $id";
            $result = $conn->query($checkSql);
            $productCount = 0;
            
            if ($result && $row = $result->fetch_assoc()) {
                $productCount = $row['count'];
            }
            
            if ($productCount > 0) {
                $_SESSION['error'] = "Cannot delete category: There are $productCount products in this category. Please move or delete these products first.";
            } else {
                $sql = "DELETE FROM categories WHERE id=$id";
                
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['success'] = "Category deleted successfully!";
                    $success = true;
                } else {
                    $_SESSION['error'] = "Error deleting category: " . $conn->error;
                }
            }
        }
        
        $conn->close();
    }
    
    // Redirect to refresh the page
    header('Location: ' . APP_URL . '?page=admin&view=categories');
    exit;
}

// Fetch categories from database
$categories = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn->connect_error) {
    // First, check what columns are available in the categories table
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM categories");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    // Base SQL for categories with product counts
    $sql = "SELECT c.id, c.name";
    
    // Add optional columns if they exist
    if (in_array('description', $columns)) {
        $sql .= ", c.description";
    } else {
        $sql .= ", '' as description";
    }
    
    if (in_array('icon', $columns)) {
        $sql .= ", c.icon";
    } else {
        $sql .= ", 'fa-utensils' as icon";
    }
    
    // Complete the query with product count
    $sql .= ", COUNT(p.id) as product_count 
              FROM categories c 
              LEFT JOIN products p ON c.id = p.category_id 
              GROUP BY c.id 
              ORDER BY c.name";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
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
        <h1 class="text-2xl font-bold text-gray-800">Category Management</h1>
        <button id="addCategoryBtn" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
            <i class="fas fa-plus mr-2"></i> Add New Category
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($categories)): ?>
        <!-- Sample Categories (shown only when database is empty) -->
        <!-- Category Card 1 -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="h-32 bg-gray-100 flex items-center justify-center">
                <i class="fas fa-drumstick-bite text-4xl text-jollibee-red"></i>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">Chicken</h3>
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">10 Products</span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Our signature fried chicken meals and combos.</p>
                <div class="flex justify-end space-x-2">
                    <button class="text-blue-600 hover:text-blue-900 p-1 edit-category-btn" 
                            data-id="1" 
                            data-name="Chicken" 
                            data-desc="Our signature fried chicken meals and combos." 
                            data-icon="fa-drumstick-bite">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="text-red-600 hover:text-red-900 p-1 delete-category-btn" 
                             data-id="1" 
                             data-name="Chicken">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Category Card 2 -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="h-32 bg-gray-100 flex items-center justify-center">
                <i class="fas fa-hamburger text-4xl text-jollibee-red"></i>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">Burgers</h3>
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">5 Products</span>
                </div>
                <p class="text-sm text-gray-600 mb-4">Delicious burgers for every taste preference.</p>
                <div class="flex justify-end space-x-2">
                    <button class="text-blue-600 hover:text-blue-900 p-1 edit-category-btn" 
                            data-id="2" 
                            data-name="Burgers" 
                            data-desc="Delicious burgers for every taste preference." 
                            data-icon="fa-hamburger">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="text-red-600 hover:text-red-900 p-1 delete-category-btn" 
                             data-id="2" 
                             data-name="Burgers">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- More sample categories... -->
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="h-32 bg-gray-100 flex items-center justify-center">
                    <i class="fas <?php echo isset($category['icon']) ? $category['icon'] : 'fa-utensils'; ?> text-4xl text-jollibee-red"></i>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $category['name']; ?></h3>
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full"><?php echo isset($category['product_count']) ? $category['product_count'] : 0; ?> Products</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4"><?php echo isset($category['description']) ? $category['description'] : ''; ?></p>
                    <div class="flex justify-end space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 p-1 edit-category-btn" 
                                data-id="<?php echo $category['id']; ?>" 
                                data-name="<?php echo $category['name']; ?>" 
                                data-desc="<?php echo isset($category['description']) ? $category['description'] : ''; ?>" 
                                data-icon="<?php echo isset($category['icon']) ? $category['icon'] : ''; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-900 p-1 delete-category-btn" 
                                data-id="<?php echo $category['id']; ?>" 
                                data-name="<?php echo $category['name']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add New Category</h3>
            <form id="categoryForm" method="post" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="category_id" id="categoryId" value="">
                
                <div class="mb-4">
                    <label for="categoryName" class="block text-sm font-medium text-gray-700">Category Name</label>
                    <input type="text" name="category_name" id="categoryName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red" required>
                </div>
                
                <div class="mb-4">
                    <label for="categoryDescription" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="categoryDescription" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="categoryIcon" class="block text-sm font-medium text-gray-700">Icon</label>
                    <select id="categoryIcon" name="icon" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                        <option value="fa-drumstick-bite">Chicken</option>
                        <option value="fa-hamburger">Burger</option>
                        <option value="fa-utensils">Food/Pasta</option>
                        <option value="fa-glass-water">Beverage</option>
                        <option value="fa-ice-cream">Dessert</option>
                        <option value="fa-bowl-food">Rice Meal</option>
                        <option value="fa-pizza-slice">Pizza</option>
                        <option value="fa-cookie">Snack</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3 mt-5">
                    <button type="button" id="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-jollibee-red text-white rounded-md hover:bg-jollibee-darkred focus:outline-none">Save Category</button>
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
            <p class="text-sm text-gray-500 text-center mb-4">Are you sure you want to delete <span id="deleteCategoryName" class="font-medium"></span>? This will also delete all products in this category.</p>
            
            <form id="deleteForm" method="post" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="deleteCategoryId" value="">
                
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
    const categoryModal = document.getElementById('categoryModal');
    const deleteModal = document.getElementById('deleteModal');
    const modalTitle = document.getElementById('modalTitle');
    const categoryForm = document.getElementById('categoryForm');
    const formAction = document.getElementById('formAction');
    const categoryId = document.getElementById('categoryId');
    const categoryName = document.getElementById('categoryName');
    const categoryDescription = document.getElementById('categoryDescription');
    const categoryIcon = document.getElementById('categoryIcon');
    
    // Add Category Button
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New Category';
            formAction.value = 'add';
            categoryId.value = '';
            categoryForm.reset();
            categoryModal.classList.remove('hidden');
        });
    }
    
    // Edit Category Buttons
    const editButtons = document.querySelectorAll('.edit-category-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const desc = this.getAttribute('data-desc');
            const icon = this.getAttribute('data-icon');
            
            modalTitle.textContent = 'Edit Category';
            formAction.value = 'edit';
            categoryId.value = id;
            categoryName.value = name;
            categoryDescription.value = desc;
            categoryIcon.value = icon;
            
            categoryModal.classList.remove('hidden');
        });
    });
    
    // Delete Category Buttons
    const deleteButtons = document.querySelectorAll('.delete-category-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            
            deleteModal.classList.remove('hidden');
        });
    });
    
    // Close Modal Buttons
    document.getElementById('closeModal').addEventListener('click', function() {
        categoryModal.classList.add('hidden');
    });
    
    document.getElementById('cancelDelete').addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === categoryModal) {
            categoryModal.classList.add('hidden');
        }
        if (event.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
});
</script> 