<?php
// Customer Support Ticket Submission Page

// Handle form submission
if (isset($_POST['submit_ticket'])) {
    // Validate inputs
    $name = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? cleanInput($_POST['subject']) : '';
    $message = isset($_POST['message']) ? cleanInput($_POST['message']) : '';
    $orderNumber = isset($_POST['order_number']) ? cleanInput($_POST['order_number']) : '';
    
    $errors = [];
    
    // Simple validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If there are no errors, insert into database
    if (empty($errors)) {
        // Add order number to subject if provided
        if (!empty($orderNumber)) {
            $subject = "Order #" . $orderNumber . " - " . $subject;
        }
        
        $insertQuery = "INSERT INTO support_tickets (customer_name, customer_email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Your support ticket has been submitted. We'll get back to you soon!";
            // Clear form data
            unset($name, $email, $subject, $message, $orderNumber);
        } else {
            $_SESSION['error'] = "Error submitting your ticket. Please try again.";
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . APP_URL . "?page=support");
        exit;
    } else {
        // Store errors in session
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Get user information if logged in
$userName = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : '';
$userEmail = isset($_SESSION['customer_email']) ? $_SESSION['customer_email'] : '';

// Get order number from URL if coming from order-status page
$orderNumber = isset($_GET['order']) ? cleanInput($_GET['order']) : '';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Customer Support</h1>
        <div>
            <a href="<?php echo APP_URL; ?>" class="text-jollibee-red hover:text-jollibee-darkred">
                <i class="fas fa-arrow-left mr-2"></i> Back to Home
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center mb-6">
            <div class="bg-jollibee-red p-3 rounded-full text-white mr-4">
                <i class="fas fa-headset text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Need help? We're here for you.</h2>
                <p class="text-gray-600">Submit a ticket and our team will respond as soon as possible.</p>
            </div>
        </div>
        
        <form method="post" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? $name : $userName; ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo isset($email) ? $email : $userEmail; ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="order_number" class="block text-sm font-medium text-gray-700 mb-1">Order Number (if applicable)</label>
                    <input type="text" id="order_number" name="order_number" value="<?php echo isset($orderNumber) ? $orderNumber : ''; ?>" 
                           placeholder="e.g. JB123456789" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                    <p class="text-xs text-gray-500 mt-1">If your inquiry is about a specific order, please include the order number</p>
                </div>
                
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" id="subject" name="subject" value="<?php echo isset($subject) ? $subject : ''; ?>" required 
                           placeholder="Brief description of your issue" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red">
                </div>
            </div>
            
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                <textarea id="message" name="message" rows="6" required 
                          placeholder="Please describe your issue in detail so we can better assist you..." 
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jollibee-red"><?php echo isset($message) ? $message : ''; ?></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" name="submit_ticket" 
                        class="px-6 py-3 bg-jollibee-red text-white font-medium rounded-md hover:bg-jollibee-darkred transition-colors focus:outline-none focus:ring-2 focus:ring-jollibee-red focus:ring-opacity-50">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Ticket
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-lightbulb text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Quick Tips</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>For order-related inquiries, please include your order number if available</li>
                        <li>Be as specific as possible about the issue you're experiencing</li>
                        <li>Our support team typically responds within 24-48 hours</li>
                        <li>For urgent matters, you can also call us at (02) 8-7000</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add any client-side validation or effects here
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus the first empty required field
    const firstEmptyField = document.querySelector('input[required]:not([value]), textarea[required]:empty');
    if (firstEmptyField) {
        firstEmptyField.focus();
    }
});
</script> 