<?php
// Settings management page
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-600">Configure your Jollibee Ordering System settings</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">General Settings</h2>
            </div>
            <div class="p-4">
                <form>
                    <div class="mb-4">
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="Jollibee Ordering System" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">Official online ordering system for Jollibee.</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="contact@jollibee.com" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="+63 (2) 8-7000" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Settings -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">Order Settings</h2>
            </div>
            <div class="p-4">
                <form>
                    <div class="mb-4">
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select id="currency" name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                            <option value="PHP">Philippine Peso (₱)</option>
                            <option value="USD">US Dollar ($)</option>
                            <option value="EUR">Euro (€)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" value="12" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="delivery_fee" class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee</label>
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">₱</span>
                            </div>
                            <input type="number" id="delivery_fee" name="delivery_fee" value="50" min="0" step="1" class="pl-7 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="min_order" class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">₱</span>
                            </div>
                            <input type="number" id="min_order" name="min_order" value="100" min="0" step="1" class="pl-7 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">Payment Settings</h2>
            </div>
            <div class="p-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Methods</label>
                    
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input id="payment_cash" name="payment_methods[]" type="checkbox" checked class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded">
                            <label for="payment_cash" class="ml-2 block text-sm text-gray-900">Pay with Cash</label>
                        </div>
                        <div class="flex items-center">
                            <input id="payment_card" name="payment_methods[]" type="checkbox" checked class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded">
                            <label for="payment_card" class="ml-2 block text-sm text-gray-900">Credit/Debit Card</label>
                        </div>
                        <div class="flex items-center">
                            <input id="payment_gcash" name="payment_methods[]" type="checkbox" checked class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded">
                            <label for="payment_gcash" class="ml-2 block text-sm text-gray-900">GCash</label>
                        </div>
                        <div class="flex items-center">
                            <input id="payment_paymaya" name="payment_methods[]" type="checkbox" class="h-4 w-4 text-jollibee-red focus:ring-jollibee-red border-gray-300 rounded">
                            <label for="payment_paymaya" class="ml-2 block text-sm text-gray-900">PayMaya</label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Admin Account -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">Admin Account</h2>
            </div>
            <div class="p-4">
                <form>
                    <div class="mb-4">
                        <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1">Admin Name</label>
                        <input type="text" id="admin_name" name="admin_name" value="Administrator" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" value="admin@jollibee.com" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-jollibee-red focus:border-jollibee-red">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-jollibee-red hover:bg-jollibee-darkred text-white px-4 py-2 rounded-md">
                            Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 