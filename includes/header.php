<?php 
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jollibee Ordering System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom configuration for Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        jollibee: {
                            red: '#E31837',
                            yellow: '#FFC20E',
                            darkred: '#B8001F',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
        }
        .logo-container img {
            mix-blend-mode: multiply;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <nav class="bg-jollibee-red text-white shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a class="flex items-center" href="<?php echo APP_URL; ?>">
                    <span class="text-xl font-bold">Jollibee Ordering</span>
                </a>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" id="menu-toggle" class="text-white hover:text-jollibee-yellow focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="<?php echo APP_URL; ?>?page=menu" class="text-white hover:text-jollibee-yellow">Menu</a>
                    <a href="<?php echo APP_URL; ?>?page=deals" class="text-white hover:text-jollibee-yellow">Deals</a>
                    <a href="<?php echo APP_URL; ?>?page=family-bundles" class="text-white hover:text-jollibee-yellow">Family Bundles</a>
                    <a href="<?php echo APP_URL; ?>?page=cart" class="text-white hover:text-jollibee-yellow flex items-center">
                        <i class="fas fa-shopping-cart mr-1"></i> Cart
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="ml-1 bg-white text-jollibee-red rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">
                            <?php echo count($_SESSION['cart']); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo APP_URL; ?>?page=order-status" class="text-white hover:text-jollibee-yellow">Order Status</a>
                    <a href="<?php echo APP_URL; ?>?page=support" class="text-white hover:text-jollibee-yellow">
                        <i class="fas fa-headset mr-1"></i> Support
                    </a>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="md:hidden hidden mt-3 pb-3">
                <div class="flex flex-col space-y-2">
                    <a href="<?php echo APP_URL; ?>?page=menu" class="text-white hover:text-jollibee-yellow py-2">Menu</a>
                    <a href="<?php echo APP_URL; ?>?page=deals" class="text-white hover:text-jollibee-yellow py-2">Deals</a>
                    <a href="<?php echo APP_URL; ?>?page=family-bundles" class="text-white hover:text-jollibee-yellow py-2">Family Bundles</a>
                    <a href="<?php echo APP_URL; ?>?page=cart" class="text-white hover:text-jollibee-yellow py-2 flex items-center">
                        <i class="fas fa-shopping-cart mr-1"></i> Cart
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="ml-1 bg-white text-jollibee-red rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">
                            <?php echo count($_SESSION['cart']); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo APP_URL; ?>?page=order-status" class="text-white hover:text-jollibee-yellow py-2">Order Status</a>
                    <a href="<?php echo APP_URL; ?>?page=support" class="text-white hover:text-jollibee-yellow py-2">
                        <i class="fas fa-headset mr-1"></i> Support
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-2 flex-grow">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-dismiss-alert>
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-dismiss-alert>
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <main>
            <!-- Page content will be displayed here -->
        </main>
    </div>
</body>
</html> 