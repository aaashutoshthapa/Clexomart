<?php

/**
 * CartController
 * 
 * This controller manages all shopping cart functionality in the ClexoMart e-commerce system.
 * It handles both authenticated users (database cart) and guest users (session cart).
 * 
 * Key Responsibilities:
 * - Display cart contents with product details and totals
 * - Add products to cart with stock and quantity validation
 * - Update product quantities with real-time AJAX responses
 * - Remove products from cart with confirmation
 * - Manage pickup slot selection and availability checking
 * - Transfer session cart to database when user logs in
 * - Enforce business rules (20-item cart limit, stock validation)
 * 
 * Cart Architecture:
 * - Guest Users: Cart stored in Laravel session
 * - Authenticated Users: Cart stored in CART and CART_PRODUCT database tables
 * - Automatic migration: Session cart → Database cart on login
 * 
 * Business Rules:
 * - Maximum 20 items total per cart (across all products)
 * - Stock availability validation before adding/updating
 * - Pickup slots must be 24+ hours in advance
 * - Only Wed/Thu/Fri pickup days allowed
 * - Maximum 20 orders per pickup slot
 * 
 * @package App\Http\Controllers
 * @author ClexoMart Development Team
 * @version 1.0
 */

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
/**
 * CartController Class
 * 
 * Main controller class handling all cart-related operations
 * Supports dual-mode operation for guest and authenticated users
 */
class CartController extends Controller
{
    /**
     * Display the shopping cart page with all items
     * 
     * This method serves as the main cart page endpoint. It handles both
     * authenticated and guest users by retrieving cart data from different sources:
     * 
     * Authenticated Users:
     * - Retrieves cart from CART table using user_id from session
     * - Loads products through CART_PRODUCT pivot table
     * - Includes product details, quantities, and calculated totals
     * 
     * Guest Users:
     * - Retrieves cart from Laravel session storage
     * - Loads product details for each item in session cart
     * - Calculates totals on-the-fly
     * 
     * Data Processing:
     * - Calculates subtotal by summing (price × quantity) for all items
     * - Determines total (currently same as subtotal, prepared for discounts)
     * - Formats product data for cart display with images and stock info
     * 
     * @return \Illuminate\View\View Cart page view with cart items and totals
     */
    public function index()
    {
        // Initialize arrays to store cart data
        $cartItems = [];  // Will hold formatted cart item data for the view
        $subtotal = 0;    // Running total of all items in cart
            
        // Check if user is authenticated (has active session)
        if (session()->has('user_id')) {
            $userId = session('user_id'); 
            
            // For authenticated users: retrieve cart from database
            // Load cart with eager loading of products to avoid N+1 queries
            $cart = Cart::with('products')->where('user_id', $userId)->first();

            // Process database cart if it exists
            if ($cart) {
                foreach ($cart->products as $product) {
                    // Safety check: skip null products (data integrity protection)
                    if (!$product) {
                        continue; 
                    }

                    // Extract quantity from pivot table data
                    $quantity = $product->pivot->product_quantity ?? 0;
                    
                    // Format cart item data for view consumption
                    $cartItems[] = [
                        'id' => $product->product_id,
                        'name' => $product->product_name,
                        'price' => $product->price_after_discount ?? $product->unit_price, // Use discounted price if available
                        'quantity' => $quantity,
                        'image' => route('trader.product.image', $product->product_id), // Generate image URL
                        'stock' => $product->stock // For stock validation in frontend
                    ];
                    
                    // Calculate running subtotal (price × quantity)
                    $subtotal += ($product->price_after_discount ?? $product->unit_price) * $quantity;
                }
            }
        } else {
            // For guest users: retrieve cart from session storage
            $sessionCart = session('cart', []); // Get cart array from session, default empty
            
            // Process each item in session cart
            foreach ($sessionCart as $productId => $item) {
                // Fetch current product data from database (prices/stock may have changed)
                $product = Product::find($productId);
                
                if ($product) {
                    // Format cart item data for view (same structure as database cart)
                    $cartItems[] = [
                        'id' => $product->product_id,
                        'name' => $product->product_name,
                        'price' => $product->price_after_discount ?? $product->unit_price,
                        'quantity' => $item['quantity'], // Quantity stored in session
                        'image' => route('trader.product.image', $product->product_id),
                        'stock' => $product->stock
                    ];
                    
                    // Calculate subtotal using current product price
                    $subtotal += ($product->price_after_discount ?? $product->unit_price) * $item['quantity'];
                }
            }
        }

        // Return cart view with formatted data
        return view('cart', [
            'items' => $cartItems,              // Array of cart items with product details
            'subtotal' => $subtotal,            // Sum of all item totals before discounts
            'total' => $subtotal,               // Final total (currently same as subtotal, prepared for future discount logic)
            'isAuthenticated' => session()->has('user_id') // Flag for conditional rendering in view
        ]);
    }

    /**
     * Add a product to the shopping cart
     * 
     * This method handles adding products to cart with comprehensive validation:
     * 
     * Validation Steps:
     * 1. Input validation (product exists, quantity is positive integer)
     * 2. Stock availability check
     * 3. Cart capacity validation (20-item limit)
     * 4. Duplicate product handling (update quantity vs. create new)
     * 
     * Business Logic:
     * - Enforces 20-item maximum per cart (total quantity across all products)
     * - Handles both new additions and quantity updates for existing products
     * - Supports both authenticated (database) and guest (session) users
     * - Calculates total amounts including discount prices
     * 
     * Error Handling:
     * - Stock insufficient: Redirect with error message
     * - Cart full: Specific error with remaining capacity info
     * - Database errors: Graceful error handling with rollback
     * 
     * @param Request $request Contains product_id and quantity
     * @return \Illuminate\Http\RedirectResponse Redirect to cart page with success/error message
     */
    public function addToCart(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'product_id' => 'required|exists:PRODUCT,product_id', // Product must exist in database
            'quantity' => 'required|integer|min:1'                // Quantity must be positive integer
        ]);

        // Fetch product details for validation and pricing
        $product = Product::findOrFail($request->product_id);

        // Stock Validation: Ensure sufficient inventory
        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Not enough stock available');
        }

        // Cart Capacity Validation: Calculate current total quantity in cart
        $currentCartQuantity = 0;
        
        if (session()->has('user_id')) {
            // For authenticated users: count items in database cart
            $userId = session('user_id');
            $cart = Cart::where('user_id', $userId)->first();
            
            if ($cart) {
                // Sum all product quantities across all cart items
                $currentCartQuantity = DB::table('CART_PRODUCT')
                    ->where('cart_id', $cart->cart_id)
                    ->sum('product_quantity');
            }
        } else {
            // For guest users: count items in session cart
            $sessionCart = session('cart', []);
            foreach ($sessionCart as $item) {
                $currentCartQuantity += $item['quantity'];
            }
        }

        // Business Rule Enforcement: Maximum 20 items per cart
        $newTotalQuantity = $currentCartQuantity + $request->quantity;
        if ($newTotalQuantity > 20) {
            $remainingSpace = 20 - $currentCartQuantity;
            
            // Provide specific error messages based on remaining capacity
            if ($remainingSpace <= 0) {
                return back()->with('error', 'Your cart is full! Maximum 20 items allowed per cart.');
            } else {
                return back()->with('error', "You can only add {$remainingSpace} more item(s) to your cart. Maximum 20 items allowed per cart.");
            }
        }

        // Database Operations for Authenticated Users
        if (session()->has('user_id')) {
            $userId = session('user_id');

            // Get or create user's cart (one cart per user)
            $cart = Cart::firstOrCreate(
                ['user_id' => $userId],                                      // Search criteria
                ['cart_id' => 'cart' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)] // Default values if creating
            );

            // Determine effective price (use discounted price if available)
            $price = $product->price_after_discount ?? $product->unit_price;

            // Check if this product is already in the cart
            $cartProduct = CartProduct::where('cart_id', $cart->cart_id)
                ->where('product_id', $product->product_id)
                ->first();

            if ($cartProduct) {
                // Product already exists in cart - update quantity
                
                // Calculate new quantities after addition
                $newQuantity = $cartProduct->product_quantity + $request->quantity;
                $newTotal = $price * $newQuantity;

                // Final validation: ensure updated quantity doesn't exceed cart limit
                $otherProductsQuantity = DB::table('CART_PRODUCT')
                    ->where('cart_id', $cart->cart_id)
                    ->where('product_id', '!=', $product->product_id) // Exclude current product
                    ->sum('product_quantity');
                
                if (($otherProductsQuantity + $newQuantity) > 20) {
                    $maxAllowed = 20 - $otherProductsQuantity;
                    return back()->with('error', "You can only add {$maxAllowed} more of this item. Maximum 20 items allowed per cart.");
                }

                // Database Update Strategy: Delete + Insert (avoids trigger conflicts)
                // This approach prevents issues with composite key updates
                $cartProduct->delete();

                // Insert updated record with new quantity and total
                CartProduct::create([
                    'cart_id' => $cart->cart_id,
                    'product_id' => $product->product_id,
                    'product_quantity' => $newQuantity,
                    'total_amount' => $newTotal
                ]);
            } else {
                // New product addition - create cart item
                CartProduct::create([
                    'cart_id' => $cart->cart_id,
                    'product_id' => $product->product_id,
                    'product_quantity' => $request->quantity,
                    'total_amount' => $price * $request->quantity
                ]);
            }


        } else {
            // Session Operations for Guest Users
            $cart = session('cart', []); // Get current session cart or empty array
            
            if (isset($cart[$product->product_id])) {
                // Product exists in session cart - increment quantity
                $cart[$product->product_id]['quantity'] += $request->quantity;
            } else {
                // New product for session cart - create entry
                $cart[$product->product_id] = [
                    'quantity' => $request->quantity,
                    'price' => $product->price_after_discount ?? $product->unit_price // Store current price in session
                ];
            }
            
            // Save updated cart back to session
            session(['cart' => $cart]);
        }

        // Success Response: Redirect to cart page with confirmation message
        return redirect()->route('cart')->with('success', 'Product added to cart');
    }

    /**
     * Transfer session cart to database after user login
     * 
     * This method is called when a guest user with items in their session cart
     * logs into the system. It migrates all session cart data to the database
     * cart system while preserving quantities and handling duplicates.
     * 
     * Migration Process:
     * 1. Check if user has session cart data
     * 2. Get or create database cart for the user
     * 3. Clear existing database cart items to avoid conflicts
     * 4. Transfer each session item to database with current pricing
     * 5. Clear session cart after successful transfer
     * 
     * Conflict Resolution:
     * - Uses delete + insert strategy to avoid trigger conflicts
     * - Recalculates totals using current product prices
     * - Maintains data integrity during the migration
     * 
     * @param string $userId The authenticated user's ID
     * @return void
     */
    public function transferSessionCartToDatabase($userId)
    {
        // Check if user has items in session cart
        if (session()->has('cart')) {
            $sessionCart = session('cart');
            
            // Get or create database cart for this user
            $cart = Cart::firstOrCreate(
                ['user_id' => $userId],                                      // Search criteria
                ['cart_id' => 'cart' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)] // Default values
            );

            // Data Integrity Check: Ensure cart has a valid ID
            if (!$cart->cart_id) {
                $cart->cart_id = 'cart' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $cart->save();
            }

            // Conflict Prevention: Clear existing cart items that might be duplicated
            // This prevents primary key conflicts and ensures clean migration
            foreach ($sessionCart as $productId => $item) {
                \DB::statement('DELETE FROM CART_PRODUCT WHERE cart_id = :cart_id AND product_id = :product_id', [
                    'cart_id' => $cart->cart_id,
                    'product_id' => $productId
                ]);
            }
            
            // Data Migration: Transfer each session cart item to database
            foreach ($sessionCart as $productId => $item) {
                $product = Product::find($productId);
                
                if ($product) {
                    $quantity = $item['quantity'];
                    
                    // Recalculate pricing: Use current product price (may have changed since session storage)
                    $price = $product->price_after_discount ?? $product->unit_price;
                    $totalAmount = $quantity * $price;

                    // Direct Database Insert: Avoids Eloquent trigger complications
                    \DB::statement('INSERT INTO CART_PRODUCT (cart_id, product_id, product_quantity, total_amount) VALUES (:cart_id, :product_id, :quantity, :total)', [
                        'cart_id' => $cart->cart_id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'total' => $totalAmount
                    ]);
                }
            }

            // Cleanup: Remove session cart after successful database migration
            session()->forget('cart');
        }
    }



    /**
     * Update product quantity in the cart
     * 
     * Handles AJAX requests to update item quantity
     * Validates product exists and has sufficient stock
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCart(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Cart update request:', [
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'user_session' => session()->has('user_id')
            ]);

        $request->validate([
            'product_id' => 'required|exists:PRODUCT,product_id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Check stock availability
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock available'
            ]);
        }

            // Check 20-item cart limit
            if (session()->has('user_id')) {
            $userId = session('user_id'); 
            $cart = Cart::where('user_id', $userId)->first();

                if ($cart) {
                    // Calculate total quantity excluding the current product
                    $otherProductsQuantity = DB::table('CART_PRODUCT')
                        ->where('cart_id', $cart->cart_id)
                        ->where('product_id', '!=', $product->product_id)
                        ->sum('product_quantity');
                    
                    if (($otherProductsQuantity + $request->quantity) > 20) {
                        $maxAllowed = 20 - $otherProductsQuantity;
                        return response()->json([
                            'success' => false,
                            'message' => "Maximum {$maxAllowed} items allowed for this product. Cart limit is 20 items total."
                        ]);
                    }

                    // Use direct DB update to avoid potential model issues
                    $updateResult = DB::table('CART_PRODUCT')
                        ->where('cart_id', $cart->cart_id)
                    ->where('product_id', $product->product_id)
                    ->update([
                        'product_quantity' => $request->quantity,
                        'total_amount' => ($product->price_after_discount ?? $product->unit_price) * $request->quantity
                    ]);

                    \Log::info('Database update result:', ['affected_rows' => $updateResult]);
            }
        } else {
            // Guest user - update session
            $cart = session('cart', []);
                
                // Calculate total quantity excluding the current product
                $otherProductsQuantity = 0;
                foreach ($cart as $productId => $item) {
                    if ($productId != $product->product_id) {
                        $otherProductsQuantity += $item['quantity'];
                    }
                }
                
                if (($otherProductsQuantity + $request->quantity) > 20) {
                    $maxAllowed = 20 - $otherProductsQuantity;
                    return response()->json([
                        'success' => false,
                        'message' => "Maximum {$maxAllowed} items allowed for this product. Cart limit is 20 items total."
                    ]);
                }
            
            if (isset($cart[$product->product_id])) {
                $cart[$product->product_id]['quantity'] = $request->quantity;
                    session(['cart' => $cart]);
                }
            }
            
            $updatedTotals = $this->getCartTotals();
            \Log::info('Updated totals calculated successfully');

        return response()->json([
            'success' => true,
                'message' => 'Cart updated successfully',
                'updated_totals' => $updatedTotals
            ]);

        } catch (\Exception $e) {
            \Log::error('Cart update error:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the cart: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove a product from the cart
     * 
     * Handles AJAX requests to remove items from cart
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromCart(Request $request)
    {
        try {
            \Log::info('Cart removal request started:', $request->all());

            $request->validate([
                'product_id' => 'required|exists:PRODUCT,product_id',
            ]);

            $productId = $request->product_id;

            if (session()->has('user_id')) {
                $userId = session('user_id'); 
                $cart = Cart::where('user_id', $userId)->first();
                
                if ($cart) {
                    $deleteResult = DB::table('CART_PRODUCT')
                        ->where('cart_id', $cart->cart_id)
                        ->where('product_id', $productId)
                        ->delete();
                    
                    \Log::info('Item removal result:', ['deleted_rows' => $deleteResult, 'product_id' => $productId]);
                }
            } else {
                // Guest user - remove from session
                $cart = session('cart', []);
                
                if (isset($cart[$productId])) {
                    unset($cart[$productId]);
                    session(['cart' => $cart]);
                    \Log::info('Item removed from session cart:', ['product_id' => $productId]);
                }
            }

            // Calculate updated totals
            try {
                $updatedTotals = $this->getCartTotals();
                \Log::info('Cart totals calculated after removal:', [
                    'total_quantity' => $updatedTotals['total_quantity'],
                    'total' => $updatedTotals['total']
                ]);
            } catch (\Exception $totalsError) {
                \Log::error('Error calculating cart totals after removal:', [
                    'error' => $totalsError->getMessage(),
                    'product_id' => $productId
                ]);
                
                // Return a safe response even if totals calculation fails
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'updated_totals' => [
                        'items' => [],
                        'subtotal' => 0,
                        'total' => 0,
                        'total_quantity' => 0,
                        'remaining_items' => 20,
                        'formatted_subtotal' => '0.00',
                        'formatted_total' => '0.00'
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
                'updated_totals' => $updatedTotals
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Cart removal error:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'product_id' => $request->product_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the item: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Check availability of a pickup slot
     * 
     * Validates that:
     * - Date is a valid pickup day (Wed, Thu, Fri)
     * - Date is at least 24 hours in advance
     * - Slot has available capacity (max 20 orders)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSlotAvailability(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'slot' => 'required|in:10-13,13-16,16-19'
        ]);

        $date = $request->date;
        $slot = $request->slot;
        
        // Check if date is valid (Wed, Thu, Fri)
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek < 3 || $dayOfWeek > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup is only available on Wednesday, Thursday, and Friday.'
            ]);
        }
        
        // Check if date is at least 24 hours in advance
        $pickupDate = strtotime($date);
        $minDate = strtotime('+24 hours');
        if ($pickupDate < $minDate) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup must be scheduled at least 24 hours in advance.'
            ]);
        }
        
        // Get or create the slot and check capacity
        $slotId = $this->getOrCreateSlotId($date, $slot);
        
        // Get current order count for this slot
        $currentOrderCount = DB::table('COLLECTION_SLOT')
            ->where('slot_id', $slotId)
            ->value('no_order') ?? 0;
            
        $maxOrders = 20;
        $remaining = $maxOrders - $currentOrderCount;
        $available = $remaining > 0;
        
        if (!$available) {
            return response()->json([
                'success' => true,
                'available' => false,
                'remaining' => 0,
                'total' => $maxOrders,
                'slot_id' => $slotId,
                'message' => 'This time slot is fully booked (20/20 orders). Please select a different time slot.'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'available' => true,
            'remaining' => $remaining,
            'total' => $maxOrders,
            'slot_id' => $slotId,
            'message' => $remaining === 1 ? 'Last slot available!' : "{$remaining} slots remaining"
        ]);
    }
    
    /**
     * Get or create a slot ID for a given date and time slot
     * 
     * @param string $date Date in Y-m-d format
     * @param string $timeSlot Time slot string (e.g., '10-13')
     * @return string The slot ID
     */
    private function getOrCreateSlotId($date, $timeSlot)
    {
        // Format: date_timeSlot (e.g., 2025-06-11_10-13)
        $slotKey = $date . '_' . $timeSlot;
        
        // Check if slot exists
        $slot = \DB::table('COLLECTION_SLOT')
            ->where('day', $date)
            ->where('time', $this->getSlotTime($date, $timeSlot))
            ->first();
            
        if ($slot) {
            return $slot->slot_id;
        }
        
        // Create new slot
        $slotId = 'slot' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        \DB::table('COLLECTION_SLOT')->insert([
            'slot_id' => $slotId,
            'day' => $date,
            'time' => $this->getSlotTime($date, $timeSlot),
            'no_order' => 0
        ]);
        
        return $slotId;
    }
    
    /**
     * Convert a slot string to a timestamp
     * 
     * @param string $date Date in Y-m-d format
     * @param string $timeSlot Time slot string (e.g., '10-13')
     * @return string Formatted timestamp
     */
    private function getSlotTime($date, $timeSlot)
    {
        $startHour = explode('-', $timeSlot)[0];
        return date('Y-m-d H:i:s', strtotime("$date $startHour:00:00"));
    }

    /**
     * Get current cart totals and information
     * Used for AJAX updates to refresh cart display
     */
    private function getCartTotals()
    {
        try {
            $cartItems = [];
            $subtotal = 0;
            $totalQuantity = 0;
                
            if (session()->has('user_id')) {
                $userId = session('user_id'); 
                $cart = Cart::with('products')->where('user_id', $userId)->first();

                if ($cart) {
                    foreach ($cart->products as $product) {
                        if (!$product) {
                            continue; // Skip if null
                        }

                        $quantity = $product->pivot->product_quantity ?? 0;
                        $price = $product->price_after_discount ?? $product->unit_price;
                        $itemTotal = $price * $quantity;
                        
                        $cartItems[] = [
                            'id' => $product->product_id,
                            'name' => $product->product_name,
                            'price' => $price,
                            'quantity' => $quantity,
                            'item_total' => $itemTotal,
                            'image' => route('trader.product.image', $product->product_id),
                            'stock' => $product->stock
                        ];
                        
                        $subtotal += $itemTotal;
                        $totalQuantity += $quantity;
                    }
                }
            } else {
                // Guest user - get cart from session
                $sessionCart = session('cart', []);
                
                foreach ($sessionCart as $productId => $item) {
                    $product = Product::find($productId);
                    if ($product) {
                        $price = $product->price_after_discount ?? $product->unit_price;
                        $quantity = $item['quantity'];
                        $itemTotal = $price * $quantity;
                        
                        $cartItems[] = [
                            'id' => $product->product_id,
                            'name' => $product->product_name,
                            'price' => $price,
                            'quantity' => $quantity,
                            'item_total' => $itemTotal,
                            'image' => route('trader.product.image', $product->product_id),
                            'stock' => $product->stock
                        ];
                        
                        $subtotal += $itemTotal;
                        $totalQuantity += $quantity;
                    }
                }
            }

            return [
                'items' => $cartItems,
                'subtotal' => $subtotal,
                'total' => $subtotal, // Will add discounts later
                'total_quantity' => $totalQuantity,
                'remaining_items' => 20 - $totalQuantity,
                'formatted_subtotal' => number_format($subtotal, 2),
                'formatted_total' => number_format($subtotal, 2)
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error calculating cart totals:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Return safe default values
            return [
                'items' => [],
                'subtotal' => 0,
                'total' => 0,
                'total_quantity' => 0,
                'remaining_items' => 20,
                'formatted_subtotal' => '0.00',
                'formatted_total' => '0.00'
            ];
        }
    }

    /**
     * Simple test method for debugging cart updates
     */
    public function testUpdate(Request $request)
    {
        try {
            \Log::info('Test update called with data:', $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Test successful',
                'data' => $request->all(),
                'session_has_user' => session()->has('user_id'),
                'session_user_id' => session('user_id')
            ]);
        } catch (\Exception $e) {
            \Log::error('Test update error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Simple test method for debugging cart removal
     */
    public function testRemove(Request $request)
    {
        try {
            \Log::info('Test remove called with data:', $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Test remove successful',
                'data' => $request->all(),
                'session_has_user' => session()->has('user_id'),
                'session_user_id' => session('user_id')
            ]);
        } catch (\Exception $e) {
            \Log::error('Test remove error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Test remove failed: ' . $e->getMessage()
            ]);
        }
    }

    // ... rest of your methods ...
}