-- Create database
CREATE DATABASE IF NOT EXISTS `jollibee_ordering`;
USE `jollibee_ordering`;

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255),
  `availability` BOOLEAN DEFAULT 1,
  `is_featured` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(20) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100),
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
  `order_status` ENUM('received', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'received',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table (for admin access)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES
('admin', '$2y$10$q9UyrLM6gBxi9PjoZ/J1W.FT300FWBxOxEO7g4hsIlgOTF5/iPauW', 'Administrator', 'admin');

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`, `image`) VALUES
('Chicken Joy', 'Perfectly cooked fried chicken', 'chickenjoy.jpg'),
('Burger Steak', 'Juicy burger patties with mushroom gravy', 'burgersteak.jpg'),
('Jolly Spaghetti', 'Sweet style spaghetti with ham and hotdog', 'spaghetti.jpg'),
('Palabok Fiesta', 'Filipino noodle dish with savory sauce and toppings', 'palabok.jpg'),
('Yumburger', 'Delicious beef burgers', 'yumburger.jpg'),
('Sides & Desserts', 'Complete your meal with sides and desserts', 'sides.jpg');

-- Insert sample products
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `image`, `availability`, `is_featured`) VALUES
(1, '1pc Chickenjoy Solo', 'Crispylicious, Juicylicious, and Gravylicious 1pc Chickenjoy with rice', 99.00, '1pc chickenjoy solo.webp', 1, 1),
(1, '2pc Chickenjoy Solo', 'Crispylicious, Juicylicious, and Gravylicious 2pc Chickenjoy with rice', 175.00, '2pc chickenjoy solo.webp', 1, 1),
(1, 'Chickenjoy Bucket - 6pcs', 'Crispylicious, Juicylicious 6pc Chickenjoy bucket for sharing', 450.00, '6 pc. Chickenjoy Bucket with Jolly Spaghetti Family Pan.webp', 1, 0),
(2, '1pc Burger Steak Solo', 'Beefy-saucy goodness on a sizzling plate with rice', 85.00, 'burger steak solo.webp', 1, 1),
(2, '2pc Burger Steak Solo', 'Double the beefy-saucy goodness on a sizzling plate with rice', 140.00, '2pc burger steak solo.webp', 1, 0),
(3, 'Jolly Spaghetti Solo', 'Sweet-sarap spaghetti with ham, ground meat, and hotdog', 80.00, 'Jolly Spaghetti solo.webp', 1, 1),
(3, 'Jolly Spaghetti Family Pan', 'Sweet-sarap spaghetti for sharing', 195.00, 'Jolly Spaghetti Family Plan.webp', 1, 0),
(4, 'Palabok Fiesta Solo', 'Filipino-style noodle dish with special palabok sauce', 120.00, 'Palabok fiesta solo.webp', 1, 0),
(5, 'Yumburger Solo', 'Beefy langhap-sarap goodness', 45.00, 'Yumburger solo.webp', 1, 1),
(5, 'Cheesy Yumburger Solo', 'Cheesy langhap-sarap goodness', 60.00, 'Cheesy yumburger solo.webp', 1, 0),
(6, 'Peach Mango Pie', 'Sweet and fruity peach-mango filling in a crispy pie crust', 40.00, 'Peach Mango Pie.webp', 1, 1),
(6, 'Jolly Crispy Fries - Regular', 'Crispy and flavorful french fries', 65.00, 'Jolly Crispy Fries Regular.webp', 1, 0),
(6, 'Chocolate Sundae', 'Creamy vanilla soft-serve with chocolate syrup', 39.00, 'Chocolate Sundae.webp', 1, 0); 