<?php
// Admin header - this is included by index.php before the content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jollibee Ordering System</title>
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
        .fade-in {
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .sidebar {
            transition: all 0.3s ease-in-out;
            z-index: 20;
        }
        .sidebar.collapsed {
            width: 0;
            min-width: 0;
            overflow: hidden;
        }
        .main-content {
            transition: margin 0.3s ease-in-out;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                height: calc(100vh - 4rem);
                top: 4rem;
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
        .toggle-icon {
            transition: transform 0.3s ease;
        }
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation Bar -->
        <nav class="bg-jollibee-red shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="text-white p-2 rounded-md hover:bg-jollibee-darkred focus:outline-none focus:ring-2 focus:ring-opacity-50 focus:ring-jollibee-yellow">
                            <span class="toggle-icon">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        </button>
                        <div class="flex-shrink-0 flex items-center ml-2">
                            <a href="<?php echo APP_URL; ?>?page=admin&view=dashboard" class="font-bold text-white text-xl">
                                <span class="text-jollibee-yellow">Jollibee</span> Admin
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" id="user-menu-button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jollibee-red" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-jollibee-yellow text-jollibee-red">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </button>
                            </div>
                            <div id="user-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Logged in as <span class="font-semibold">Administrator</span>
                                </div>
                                <a href="<?php echo APP_URL; ?>?page=admin&view=settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">Settings</a>
                                <a href="<?php echo APP_URL; ?>?page=admin&view=dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex flex-1 overflow-hidden">
            <!-- Sidebar Navigation -->
            <aside id="sidebar" class="sidebar bg-white shadow-md w-64 flex-shrink-0">
                <div class="py-6 px-3">
                    <div class="mb-6 px-3 flex items-center justify-center">
                        <span class="text-jollibee-red text-2xl font-bold">Jollibee<span class="text-jollibee-yellow">Admin</span></span>
                    </div>
                    
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=dashboard" class="<?php echo (!isset($_GET['view']) || $_GET['view'] == 'dashboard') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-tachometer-alt w-6 text-center"></i>
                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=orders" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'orders') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-shopping-cart w-6 text-center"></i>
                                <span class="ml-3">Orders</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=products" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'products') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-burger w-6 text-center"></i>
                                <span class="ml-3">Products</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=marketing" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'marketing') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-bullhorn w-6 text-center"></i>
                                <span class="ml-3">Marketing</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=categories" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'categories') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-tags w-6 text-center"></i>
                                <span class="ml-3">Categories</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=payments" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'payments') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-credit-card w-6 text-center"></i>
                                <span class="ml-3">Payments</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=settings" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'settings') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-cog w-6 text-center"></i>
                                <span class="ml-3">Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo APP_URL; ?>?page=admin&view=support" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'support') ? 'bg-jollibee-red bg-opacity-10 text-jollibee-red' : 'text-gray-700 hover:bg-jollibee-red hover:bg-opacity-10 hover:text-jollibee-red'; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-headset w-6 text-center"></i>
                                <span class="ml-3">Customer Support</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto main-content p-6">
                <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 fade-in">
                    <?php echo $_SESSION['success']; ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 fade-in">
                    <?php echo $_SESSION['error']; ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?> 