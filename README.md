# Jollibee Ordering System

A responsive web-based kiosk ordering system for Jollibee, built using PHP, MySQL, and Tailwind CSS.

## Features

### Customer Features
- Browse interactive menu with categories and product details
- Add items to cart with quantity control
- View and manage shopping cart (update quantities, remove items)
- Checkout with multiple payment options
- Order tracking and status updates
- Responsive design for all device sizes

### Admin Features
- Dashboard with sales statistics and popular products
- Order management (view, update status)
- Product management (add, edit, delete)
- Category management
- User-friendly interface

## Technology Stack

- **Frontend:** HTML, Tailwind CSS
- **Backend:** Pure PHP (no frameworks)
- **Database:** MySQL
- **Icons:** Font Awesome

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache, Nginx)

## Installation

1. Clone the repository to your web server directory:
   ```
   git clone https://github.com/yourusername/jollibee-ordering-system.git
   ```

2. Import the database schema:
   - Open your MySQL administration tool (phpMyAdmin)
   - Create a new database named "jollibee_ordering"
   - Import the SQL file from `config/database.sql`
   - Alternatively, visit the URL: `http://yourdomain.com/jollibee-ordering-system/config/init_db.php`

3. Configure the application:
   - Edit the `config/config.php` file to match your environment settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'jollibee_ordering');
   
   define('APP_URL', 'http://yourdomain.com/jollibee-ordering-system');
   ```

4. Set folder permissions:
   - Ensure the web server has write permissions for the `assets/images` directory

5. Access the application:
   - Customer interface: `http://yourdomain.com/jollibee-ordering-system/`
   - Admin interface: `http://yourdomain.com/jollibee-ordering-system/?page=admin`
   - Default admin credentials: Username: `admin` / Password: `admin123`

## Project Structure

```
jollibee-ordering-system/
├── assets/
│   └── images/        # Product and category images
├── config/
│   ├── config.php     # Application configuration
│   ├── database.sql   # Database schema
│   └── init_db.php    # Database initialization script
├── css/               # Custom CSS styles
├── includes/
│   ├── functions.php  # Utility functions
│   ├── header.php     # Site header template
│   └── footer.php     # Site footer template
├── js/                # JavaScript files
├── pages/
│   ├── admin/         # Admin pages
│   ├── menu.php       # Menu page
│   ├── cart.php       # Shopping cart page
│   ├── checkout.php   # Checkout page
│   └── order-status.php # Order tracking page
├── index.php          # Main entry point
└── README.md          # Project documentation
```

## User Interface

This application follows the 7 Habits of a Successful Interface Architecture:

1. **User-Centric Design:** Intuitive navigation and customer convenience
2. **Clarity and Simplicity:** Clean UI components with clear CTAs
3. **Consistency:** Uniform layout and behavior throughout the application
4. **Visual Hierarchy:** Logical grouping of content and highlighted important actions
5. **Feedback and Responsiveness:** Clear feedback on all user actions
6. **Accessibility:** Keyboard navigation and proper semantic HTML
7. **Iterative Design Process:** Built for continuous improvement

## License

This project is for educational purposes only.

## Credits

- Development: [Your Name]
- Design inspired by Jollibee branding
- Tailwind CSS for styling
- Font Awesome for icons

---

Note: This is a demo project and not affiliated with Jollibee Foods Corporation. 