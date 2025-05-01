    </main>
    </div>
    
    <!-- Footer Section -->
    <footer class="bg-jollibee-red text-white mt-4">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-3">About Jollibee</h3>
                    <div class="flex items-center mb-3">
                        <span class="font-bold text-white">Jollibee</span>
                    </div>
                    <p class="text-sm">Jollibee is the largest fast-food chain brand in the Philippines, operating a network of more than 1,400 stores. A dominant market leader in the Philippines, Jollibee enjoys the lion's share of the local market that is more than all the other multinational brands combined.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-3">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo APP_URL; ?>?page=menu" class="hover:text-jollibee-yellow">Menu</a></li>
                        <li><a href="<?php echo APP_URL; ?>?page=deals" class="hover:text-jollibee-yellow">Deals</a></li>
                        <li><a href="<?php echo APP_URL; ?>?page=family-bundles" class="hover:text-jollibee-yellow">Family Bundles</a></li>
                        <li><a href="<?php echo APP_URL; ?>?page=cart" class="hover:text-jollibee-yellow">Cart</a></li>
                        <li><a href="<?php echo APP_URL; ?>?page=order-status" class="hover:text-jollibee-yellow">Order Status</a></li>
                        <li><a href="<?php echo APP_URL; ?>?page=support" class="hover:text-jollibee-yellow">Customer Support</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-3">Contact Us</h3>
                    <p class="text-sm mb-2"><i class="fas fa-phone mr-2"></i> (02) 8-7000</p>
                    <p class="text-sm mb-2"><i class="fas fa-envelope mr-2"></i> customercare@jollibee.com.ph</p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-white hover:text-jollibee-yellow"><i class="fab fa-facebook-f text-xl"></i></a>
                        <a href="#" class="text-white hover:text-jollibee-yellow"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="#" class="text-white hover:text-jollibee-yellow"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="#" class="text-white hover:text-jollibee-yellow"><i class="fab fa-youtube text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/20 mt-8 pt-6 text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> Jollibee. All rights reserved. | This is a project for demonstration purposes only.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Toggle Mobile Menu
        const mobileMenuButton = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Alert dismiss
        document.querySelectorAll('[data-dismiss-alert]').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });

        // Item Quantity Control
        function updateQuantity(productId, action) {
            const quantityElement = document.getElementById(`quantity-${productId}`);
            let quantity = parseInt(quantityElement.textContent);
            
            if (action === 'decrease') {
                if (quantity > 1) {
                    quantity--;
                }
            } else {
                quantity++;
            }
            
            quantityElement.textContent = quantity;
            document.getElementById(`product-quantity-${productId}`).value = quantity;
        }
    </script>
</body>
</html> 