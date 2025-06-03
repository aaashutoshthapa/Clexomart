{{--
/**
 * Shopping Cart Page Template
 * 
 * This Blade template renders the complete shopping cart interface for ClexoMart,
 * providing customers with cart management, order summary, and checkout functionality.
 * 
 * Key Features:
 * - Responsive cart item display with product details
 * - Real-time quantity updates with AJAX
 * - Stock validation and availability checking
 * - Order summary with totals and quantity tracking
 * - Pickup slot selection interface
 * - PayPal payment integration
 * - Empty cart state handling
 * - Cart capacity management (20-item limit)
 * 
 * Layout Structure:
 * - Left Column: Cart items list with controls
 * - Right Column: Order summary and pickup slot selection
 * 
 * Interactive Elements:
 * - Quantity input with real-time updates
 * - Remove item buttons with confirmation
 * - Pickup slot selection with availability checking
 * - Checkout button with validation
 * 
 * Business Rules:
 * - Maximum 20 items per cart (total quantity)
 * - Stock validation for all quantities
 * - Pickup slots required for checkout
 * - Authentication required for purchase
 * 
 * Dependencies:
 * - cartproduct.css for styling
 * - JavaScript for AJAX cart operations
 * - PayPal integration for payments
 * - Pickup slot management system
 * 
 * @extends layouts.app
 * @section content
 * @author ClexoMart Development Team
 * @version 1.0
 */
--}}

@extends('layouts.app')

@section('title', 'Cart')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/cartproduct.css') }}">
@endpush

@section('content')
    {{-- 
        Cart Header Section
        Displays the main cart title with shopping cart icon
        Provides visual confirmation that user is on cart page
    --}}
    <div class="columns cart">
        <div class="column">
            <div class="box">
                <p class="title is-3 cart-title">Your Cart <i class="fas fa-shopping-cart"></i></p>
            </div>
        </div>
    </div>

    <div class="columns item">
        {{-- 
            Left Column: Cart Items Display
            
            This section displays all items currently in the user's cart.
            Each item is rendered as an individual card with:
            - Product image with fallback
            - Product name and pricing
            - Stock availability information
            - Quantity controls with validation
            - Remove button functionality
            - Total price calculation
            
            Uses @forelse directive to handle empty cart gracefully
        --}}
        <div class="column is-6">
            @forelse($items as $item)
                {{-- 
                    Individual Cart Item Card
                    
                    Each item in the cart is displayed as a structured card with:
                    - Horizontal layout for desktop viewing
                    - Responsive design for mobile devices
                    - Interactive controls for quantity and removal
                    - Real-time price updates
                --}}
                    <div class="column is-full">
                    <div class="box cart-item-card">
                        <div class="cart-item-layout">
                                {{-- 
                                    Product Image Section
                                    
                                    Displays product image with:
                                    - Dynamic image loading from product ID
                                    - Fallback to default image on error
                                    - Optimized sizing for cart display
                                    - Alt text for accessibility
                                --}}
                            <div class="cart-image-container">
                                <img src="{{ route('trader.product.image', $item['id']) }}" 
                                     alt="{{ $item['name'] }}" 
                                     class="cart-item-image"
                                     onerror="this.src='{{ asset('images/default.png') }}'">
                                </div>
                            
                                {{-- 
                                    Product Information Section
                                    
                                    Contains essential product details:
                                    - Product name for identification
                                    - Current price (including discounts)
                                    - Stock availability information
                                    - Formatted currency display
                                --}}
                            <div class="cart-details-container">
                                <h5 class="cart-product-name">{{ $item['name'] }}</h5>
                                <p class="cart-product-price">${{ number_format($item['price'], 2) }}</p>
                                <p class="cart-stock-info">Stock: {{ $item['stock'] }} available</p>
                                </div>
                            
                            {{-- 
                                Quantity Control Section
                                
                                Interactive quantity management:
                                - Number input with min/max validation
                                - Stock-based maximum limits
                                - Real-time AJAX updates
                                - Accessibility labels
                                - Data attributes for JavaScript handling
                            --}}
                            <div class="cart-quantity-container">
                                <label class="cart-quantity-label">Quantity</label>
                                    <input type="number" 
                                       class="cart-quantity-input" 
                                           value="{{ $item['quantity'] }}" 
                                           min="1" 
                                           max="{{ $item['stock'] }}" 
                                           data-product-id="{{ $item['id'] }}"
                                           aria-label="Quantity">
                                </div>
                            
                            {{-- 
                                Remove Item Section
                                
                                Item removal functionality:
                                - Single-click removal with AJAX
                                - Trash icon for clear action indication
                                - Product ID data for backend processing
                                - Accessible button with tooltip
                            --}}
                            <div class="cart-remove-container">
                                <button class="cart-remove-btn" 
                                        data-product-id="{{ $item['id'] }}"
                                        title="Remove item">
                                            <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            
                            {{-- 
                                Item Total Section
                                
                                Displays calculated total for this item:
                                - Price × quantity calculation
                                - Currency formatting
                                - Real-time updates on quantity change
                                - Clear labeling for user understanding
                            --}}
                            <div class="cart-total-container">
                                <span class="cart-total-label">Total</span>
                                <span class="cart-total-price">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- 
                    Empty Cart State
                    
                    Displayed when cart contains no items:
                    - Friendly empty state message
                    - Visual cart icon
                    - Call-to-action to start shopping
                    - Direct link to product browsing
                    - Encouraging tone to drive engagement
                --}}
                <div class="column is-full">
                    <div class="box has-text-centered">
                        <div class="content">
                            <span class="icon is-large has-text-grey-light">
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </span>
                            <h4 class="title is-4 has-text-grey">Your cart is empty</h4>
                            <p class="subtitle has-text-grey">Add some delicious items to get started!</p>
                            <a href="{{ route('home') }}" class="button is-primary">
                                <span class="icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </span>
                                <span>Start Shopping</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- 
            Right Column: Order Summary and Checkout
            
            This section contains:
            - Order summary with totals
            - Cart capacity tracking
            - Payment method display
            - Pickup slot selection interface
            - Checkout button with validation
            
            Only displayed when items exist in cart
        --}}
        <div class="column is-6">
            {{-- 
                Order Summary Box
                
                Comprehensive order summary including:
                - Cart capacity information (20-item limit)
                - Price breakdown (subtotal, discount, total)
                - Payment method options
                - Checkout button with authentication check
            --}}
            <div class="column is-full">
                <div class="box">
                    <p class="title is-6">Order Summary</p>
                    
                    {{-- 
                        Cart Capacity Tracking
                        
                        Displays current cart status:
                        - Total items vs. 20-item limit
                        - Remaining capacity information
                        - Color-coded warnings for near-full carts
                        - User guidance for cart management
                    --}}
                    @php
                        $totalQuantity = array_sum(array_column($items, 'quantity'));
                        $remainingItems = 20 - $totalQuantity;
                    @endphp
                    <div class="notification {{ $remainingItems <= 5 ? 'is-warning' : 'is-info' }} is-light mb-4 cart-quantity-notification">
                        <div class="has-text-centered">
                            <p class="cart-quantity-display"><strong>Cart Items: {{ $totalQuantity }}/20</strong></p>
                            <div class="cart-remaining-display">
                                @if($remainingItems > 0)
                                    <p class="is-size-7">{{ $remainingItems }} more item(s) can be added</p>
                                @else
                                    <p class="is-size-7 has-text-danger">Cart is full! Remove items to add more.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- 
                        Price Breakdown Section
                        
                        Detailed financial summary:
                        - Subtotal: Sum of all item totals
                        - Discount: Applied coupons/promotions (future feature)
                        - Final total: Amount to be charged
                        
                        Uses horizontal field layout for clear price display
                    --}}
                    
                    {{-- Subtotal Display --}}
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label">Subtotal:</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control subtotal-amount">${{ number_format($subtotal, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Discount Display (Future Feature) --}}
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label">Discount:</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">$0.00</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Final Total Display --}}
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label">Total:</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control has-text-weight-bold total-amount">${{ number_format($total, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- 
                        Payment Method Display
                        
                        Shows available payment options:
                        - PayPal integration (currently primary method)
                        - Visual payment method icons
                        - Future: Additional payment gateways
                    --}}
                    <p class="title is-6 mt-4">Payment Option</p>
                    <img src="{{ asset('images/cartproduct/PayPal.svg') }}" alt="PayPal" class="payment-icon">
                    
                    {{-- 
                        Checkout Button Section
                        
                        Conditional checkout button with multiple states:
                        1. Authenticated users with items: Show pickup slot requirement
                        2. Authenticated users with empty cart: Disabled state
                        3. Guest users: Redirect to login
                        
                        Includes pickup slot validation and form submission
                    --}}
                    <div class="field mt-4">
                    <div class="control">
                        @if($isAuthenticated)
                            @if(count($items))
                                {{-- 
                                    Pickup Slot Warning
                                    
                                    Shown when user hasn't selected pickup slot:
                                    - Hidden by default (display: none)
                                    - Shown via JavaScript when needed
                                    - Clear instructions for user action
                                    - Warning styling for attention
                                --}}
                                <div class="notification is-warning is-light" id="pickup-warning" style="display: none; margin-bottom: 1rem;">
                                    <p><strong>⚠️ Pickup Slot Required!</strong></p>
                                    <p>Please select a pickup date and time slot below before proceeding to checkout.</p>
                                </div>
                                
                                {{-- 
                                    Checkout Form
                                    
                                    PayPal payment form with hidden fields:
                                    - Total amount for payment
                                    - Pickup slot information
                                    - CSRF protection
                                    - Disabled until slot selected
                                --}}
                                <form action="{{ route('paypal.create') }}" method="POST" id="checkout-form">
                                    @csrf
                                    <input type="hidden" name="amount" value="{{ $total }}">
                                    <input type="hidden" name="pickup_date" id="checkout-pickup-date" value="">
                                    <input type="hidden" name="pickup_slot" id="checkout-pickup-slot" value="">
                                    <input type="hidden" name="slot_id" id="checkout-slot-id" value="">
                                    <button type="submit" class="button is-primary is-fullwidth" id="checkout-button" disabled>
                                        <span class="icon">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <span>Select Pickup Slot First</span>
                                    </button>
                                </form>
                            @else
                                {{-- Empty Cart State for Authenticated Users --}}
                                <button class="button is-primary is-fullwidth" disabled>
                                    Cart is empty
                                </button>
                            @endif
                        @else
                            {{-- Guest User State: Redirect to Login --}}
                            <a href="{{ route('signin') }}" class="button is-link is-fullwidth">
                                Login to Checkout
                            </a>
                        @endif
                    </div>
                </div>
                </div>
            </div>

            {{-- 
                Pickup Slot Selection Interface
                
                Comprehensive pickup scheduling system:
                - Date selection with business rules
                - Time slot availability checking
                - Real-time slot capacity updates
                - Validation and user feedback
                - Integration with checkout process
            --}}
            <div class="column is-full">
                <div class="box pickup-slot-box">
                    <p class="title is-6">Pickup Slot Selection</p>
                    {{-- 
                        Pickup Information Notice
                        
                        Important business rules and constraints:
                        - Available pickup days (Wed/Thu/Fri)
                        - 24-hour advance booking requirement
                        - Slot capacity limits
                        - User guidance for selection
                    --}}
                    <div class="notification is-info is-light">
                        <p><strong>Collection Information:</strong></p>
                        <ul style="margin-top: 0.5rem; margin-left: 1rem;">
                            <li>Available on Wednesday, Thursday, and Friday only</li>
                            <li>Three time slots: 10:00-13:00, 13:00-16:00, 16:00-19:00</li>
                            <li>Must be booked at least 24 hours in advance</li>
                            <li>Limited to 20 orders per slot</li>
                        </ul>
                    </div>
                    
                    {{-- Date Selection --}}
                    <div class="field">
                        <label class="label">Select Date</label>
                        <div class="control">
                            <select class="select is-fullwidth" id="pickup-date" name="pickup-date">
                                <option value="">Select a date</option>
                                <!-- Available dates will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    
                    {{-- Time Slot Selection --}}
                    <div class="field">
                        <label class="label">Select Time Slot</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select id="pickup-slot" name="pickup-slot" disabled>
                                    <option value="">Select a time slot</option>
                                    <option value="10-13">10:00 - 13:00</option>
                                    <option value="13-16">13:00 - 16:00</option>
                                    <option value="16-19">16:00 - 19:00</option>
                                </select>
                            </div>
                        </div>
                        <p class="help slot-availability"></p>
                    </div>
                    
                    {{-- Coupon Field --}}
                    <div class="field">
                        <div class="control">
                            <button class="button is-link is-fullwidth" id="apply-coupon">
                                Apply Coupon
                            </button>
                        </div>
                    </div>
                    
                    {{-- Hidden Field for Selected Slot ID --}}
                    <input type="hidden" id="selected-slot-id" name="selected-slot-id" value="">
                </div>
            </div>
        </div>
    </div>
    
    {{-- Confirmation Modal for Item Removal --}}
    <div class="modal" id="confirm-modal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Confirm Removal</p>
                <button class="delete" aria-label="close"></button>
            </header>
            <section class="modal-card-body">
                <p>Are you sure you want to remove this item from your cart?</p>
            </section>
            <footer class="modal-card-foot">
                <button class="button is-danger" id="confirm-remove">Remove Item</button>
                <button class="button" id="cancel-remove">Cancel</button>
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ===== Quantity Change Handling =====
            document.querySelectorAll('.cart-quantity-input, .quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.dataset.productId;
                    const quantity = this.value;
                    
                    // Disable input during update
                    this.disabled = true;
                    
                    // Send AJAX request to update cart
                    fetch('{{ route("cart.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart totals and display
                            updateCartDisplay(data.updated_totals);
                            showSuccessMessage('Cart updated successfully');
                        } else {
                            alert('Error updating cart: ' + data.message);
                            // Reload page on error to reset the input
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error updating cart:', error);
                        alert('Error updating cart. Please try again.');
                        window.location.reload();
                    })
                    .finally(() => {
                        // Re-enable input
                        this.disabled = false;
                    });
                });
            });

            // Function to update cart display with new totals
            function updateCartDisplay(totals) {
                // Add updating animation class
                const subtotalElement = document.querySelector('.subtotal-amount');
                const totalElement = document.querySelector('.total-amount');
                const cartQuantityElement = document.querySelector('.cart-quantity-display');
                
                // Add updating class for animation
                [subtotalElement, totalElement, cartQuantityElement].forEach(el => {
                    if (el) el.classList.add('updating');
                });
                
                // Update subtotal
                if (subtotalElement) {
                    subtotalElement.textContent = '$' + totals.formatted_subtotal;
                }
                
                // Update total
                if (totalElement) {
                    totalElement.textContent = '$' + totals.formatted_total;
                }
                
                // Update cart quantity info
                if (cartQuantityElement) {
                    cartQuantityElement.innerHTML = `<strong>Cart Items: ${totals.total_quantity}/20</strong>`;
                }
                
                const cartRemainingElement = document.querySelector('.cart-remaining-display');
                if (cartRemainingElement) {
                    if (totals.remaining_items > 0) {
                        cartRemainingElement.innerHTML = `<p class="is-size-7">${totals.remaining_items} more item(s) can be added</p>`;
                    } else {
                        cartRemainingElement.innerHTML = `<p class="is-size-7 has-text-danger">Cart is full! Remove items to add more.</p>`;
                    }
                }
                
                // Update cart quantity notification color
                const cartNotification = document.querySelector('.cart-quantity-notification');
                if (cartNotification) {
                    cartNotification.className = `notification ${totals.remaining_items <= 5 ? 'is-warning' : 'is-info'} is-light mb-4 cart-quantity-notification`;
                }
                
                // Update PayPal form amount
                const amountInput = document.querySelector('input[name="amount"]');
                if (amountInput) {
                    amountInput.value = totals.total;
                }
                
                // Remove updating class after animation
                setTimeout(() => {
                    [subtotalElement, totalElement, cartQuantityElement].forEach(el => {
                        if (el) el.classList.remove('updating');
                    });
                }, 300);
            }

            // Function to show success message
            function showSuccessMessage(message) {
                // Create and show a temporary success notification
                const notification = document.createElement('div');
                notification.className = 'notification is-success is-light';
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '300px';
                notification.innerHTML = `
                    <button class="delete"></button>
                    <strong>✓ ${message}</strong>
                `;
                
                document.body.appendChild(notification);
                
                // Add click to close
                notification.querySelector('.delete').addEventListener('click', () => {
                    notification.remove();
                });
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }

            // ===== Modal Elements =====
            const modal = document.getElementById('confirm-modal');
            const closeBtn = modal.querySelector('.delete');
            const cancelBtn = document.getElementById('cancel-remove');
            const confirmBtn = document.getElementById('confirm-remove');
            let productIdToRemove = null;

            // Modal control functions
            function openModal(productId) {
                productIdToRemove = productId;
                modal.classList.add('is-active');
            }

            function closeModal() {
                modal.classList.remove('is-active');
                productIdToRemove = null;
            }

            // Modal event listeners
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            
            // Clicking outside the modal closes it
            modal.querySelector('.modal-background').addEventListener('click', closeModal);

            // ===== Item Removal Handling =====
            document.querySelectorAll('.cart-remove-btn, .remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    openModal(productId);
                });
            });

            // Confirm removal action
            confirmBtn.addEventListener('click', function() {
                if (productIdToRemove) {
                    // Store reference to the element before AJAX call
                    const itemElement = document.querySelector(`[data-product-id="${productIdToRemove}"]`);
                    const itemRow = itemElement ? itemElement.closest('.column') : null;
                    
                    console.log('Starting removal for product:', productIdToRemove);
                    console.log('Found element:', itemElement);
                    console.log('Found row:', itemRow);
                    
                    // Send AJAX request to remove item
                    fetch('{{ route("cart.remove") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            product_id: productIdToRemove
                        })
                    })
                    .then(response => {
                        console.log('Remove response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Remove response data:', data);
                        if (data.success) {
                            // Remove the item row from display using stored reference
                            if (itemRow) {
                                itemRow.remove();
                                console.log('Item row removed from DOM');
                            } else {
                                console.warn('Could not find item row to remove, but removal was successful');
                                // If we can't find the specific row, try alternative approach
                                const allItems = document.querySelectorAll(`[data-product-id="${productIdToRemove}"]`);
                                console.log('Found alternative elements:', allItems.length);
                                allItems.forEach(el => {
                                    const row = el.closest('.column');
                                    if (row) row.remove();
                                });
                            }
                            
                            // Update cart totals and display
                            if (data.updated_totals) {
                                updateCartDisplay(data.updated_totals);
                            }
                            showSuccessMessage('Item removed from cart');
                            
                            // If cart is empty, reload to show empty cart message
                            if (data.updated_totals && data.updated_totals.total_quantity === 0) {
                                setTimeout(() => {
                            window.location.reload();
                                }, 1500);
                            }
                        } else {
                            console.error('Remove failed:', data.message);
                            alert('Error removing item: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error removing item:', error);
                        alert('Error removing item. Please try again. Details: ' + error.message);
                    });
                }
                closeModal();
            });

            // ===== Pickup Slot Functionality =====
            const pickupDateSelect = document.getElementById('pickup-date');
            const pickupSlotSelect = document.getElementById('pickup-slot');
            const slotAvailability = document.querySelector('.slot-availability');
            const selectedSlotIdInput = document.getElementById('selected-slot-id');
            
            // Checkout validation elements
            const checkoutButton = document.getElementById('checkout-button');
            const pickupWarning = document.getElementById('pickup-warning');
            const checkoutPickupDate = document.getElementById('checkout-pickup-date');
            const checkoutPickupSlot = document.getElementById('checkout-pickup-slot');
            const checkoutSlotId = document.getElementById('checkout-slot-id');
            const checkoutForm = document.getElementById('checkout-form');
            
            // Validate pickup slot selection and update checkout button
            function validatePickupSlot() {
                const date = pickupDateSelect.value;
                const slot = pickupSlotSelect.value;
                const slotId = selectedSlotIdInput.value;
                
                if (date && slot && slotId) {
                    // Valid pickup slot selected
                    checkoutButton.disabled = false;
                    checkoutButton.innerHTML = '<span class="icon"><i class="fab fa-paypal"></i></span><span>Checkout with PayPal</span>';
                    checkoutButton.classList.remove('is-light');
                    checkoutButton.classList.add('is-primary');
                    pickupWarning.style.display = 'none';
                    
                    // Update hidden form fields
                    checkoutPickupDate.value = date;
                    checkoutPickupSlot.value = slot;
                    checkoutSlotId.value = slotId;
                } else {
                    // No valid pickup slot selected
                    checkoutButton.disabled = true;
                    checkoutButton.innerHTML = '<span class="icon"><i class="fas fa-lock"></i></span><span>Select Pickup Slot First</span>';
                    checkoutButton.classList.remove('is-primary');
                    checkoutButton.classList.add('is-light');
                    pickupWarning.style.display = 'block';
                    
                    // Clear hidden form fields
                    checkoutPickupDate.value = '';
                    checkoutPickupSlot.value = '';
                    checkoutSlotId.value = '';
                }
            }
            
            // Add form submission validation
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    const date = checkoutPickupDate.value;
                    const slot = checkoutPickupSlot.value;
                    const slotId = checkoutSlotId.value;
                    
                    if (!date || !slot || !slotId) {
                        e.preventDefault();
                        alert('⚠️ Please select a pickup date and time slot before proceeding to checkout.');
                        pickupWarning.style.display = 'block';
                        return false;
                    }
                });
            }
            
            // Get available dates (Wed, Thu, Fri at least 24 hours in advance)
            function getAvailableDates() {
                const dates = [];
                const today = new Date();
                const minDate = new Date(today);
                minDate.setDate(today.getDate() + 1); // At least 24 hours in advance
                
                // Look ahead for the next 4 weeks
                for (let i = 0; i < 28; i++) {
                    const date = new Date(minDate);
                    date.setDate(minDate.getDate() + i);
                    
                    // Only include Wed (3), Thu (4), Fri (5)
                    const dayOfWeek = date.getDay();
                    if (dayOfWeek >= 3 && dayOfWeek <= 5) {
                        dates.push({
                            date: date,
                            formatted: date.toISOString().split('T')[0],
                            display: date.toLocaleDateString('en-US', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            })
                        });
                    }
                }
                
                return dates;
            }
            
            // Populate date select with available dates
            function populateDateSelect() {
                const dates = getAvailableDates();
                
                // Clear existing options except the placeholder
                while (pickupDateSelect.options.length > 1) {
                    pickupDateSelect.remove(1);
                }
                
                // Add available dates
                dates.forEach(date => {
                    const option = document.createElement('option');
                    option.value = date.formatted;
                    option.textContent = date.display;
                    pickupDateSelect.appendChild(option);
                });
            }
            
            // Check slot availability via AJAX
            function checkSlotAvailability(date, slot) {
                // Make an AJAX call to the server to check availability
                fetch('{{ route("cart.check-slot") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        date: date,
                        slot: slot
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateSlotAvailabilityUI(data);
                    } else {
                        slotAvailability.textContent = data.message || 'Error checking availability';
                        slotAvailability.classList.remove('has-text-success');
                        slotAvailability.classList.add('has-text-danger');
                        selectedSlotIdInput.value = '';
                        validatePickupSlot(); // Update checkout button
                    }
                })
                .catch(error => {
                    console.error('Error checking slot availability:', error);
                    slotAvailability.textContent = 'Error checking availability';
                    slotAvailability.classList.remove('has-text-success');
                    slotAvailability.classList.add('has-text-danger');
                    selectedSlotIdInput.value = '';
                    validatePickupSlot(); // Update checkout button
                });
            }
            
            // Update UI based on slot availability response
            function updateSlotAvailabilityUI(data) {
                if (data.available) {
                    slotAvailability.innerHTML = `
                        <span class="has-text-success">
                            <i class="fas fa-check-circle"></i> 
                            ${data.message || `${data.remaining} of ${data.total} slots available`}
                        </span>
                    `;
                    slotAvailability.classList.remove('has-text-danger');
                    slotAvailability.classList.add('has-text-success');
                    selectedSlotIdInput.value = data.slot_id;
                } else {
                    slotAvailability.innerHTML = `
                        <span class="has-text-danger">
                            <i class="fas fa-times-circle"></i> 
                            ${data.message || 'This slot is fully booked'}
                        </span>
                    `;
                    slotAvailability.classList.remove('has-text-success');
                    slotAvailability.classList.add('has-text-danger');
                    selectedSlotIdInput.value = '';
                }
                validatePickupSlot(); // Update checkout button
            }
            
            // Initialize pickup slot functionality
            populateDateSelect();
            validatePickupSlot(); // Initial validation
            
            // Update slot availability message
            function updateSlotAvailability() {
                const date = pickupDateSelect.value;
                const slot = pickupSlotSelect.value;
                
                if (date && slot) {
                    checkSlotAvailability(date, slot);
                } else {
                    slotAvailability.textContent = '';
                    selectedSlotIdInput.value = '';
                    validatePickupSlot(); // Update checkout button
                }
            }

            // Event listeners for pickup slot selection
            pickupDateSelect.addEventListener('change', function() {
                if (this.value) {
                    pickupSlotSelect.disabled = false;
                } else {
                    pickupSlotSelect.disabled = true;
                    pickupSlotSelect.value = '';
                }
                updateSlotAvailability();
            });
            
            pickupSlotSelect.addEventListener('change', updateSlotAvailability);

            
        });
    </script>
@endpush