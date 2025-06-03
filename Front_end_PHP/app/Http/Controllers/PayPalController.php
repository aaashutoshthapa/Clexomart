<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PayPalController extends Controller
{
    protected $paypalService;

    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Initiate PayPal checkout
     */
    public function checkout(Request $request)
    {
        // Check if user is authenticated using session (this app uses session-based auth)
        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to proceed with checkout',
                'redirect_to_login' => true,
                'login_url' => route('signin')
            ]);
        }

        // Validate pickup slot selection
        $request->validate([
            'pickup_date' => 'required|date_format:Y-m-d',
            'pickup_slot' => 'required|in:10-13,13-16,16-19',
            'slot_id' => 'required|string'
        ]);

        // Additional validation for pickup date and slot
        $date = $request->pickup_date;
        $slot = $request->pickup_slot;
        
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

        // Check slot availability
        $existingSlot = DB::table('COLLECTION_SLOT')->where('slot_id', $request->slot_id)->first();
        if ($existingSlot && $existingSlot->no_order >= 20) {
            return response()->json([
                'success' => false,
                'message' => 'This pickup slot is fully booked. Please select another slot.'
            ]);
        }

        // Get cart items for this user
        $cart = Cart::with('products')->where('user_id', $userId)->first();
        
        if (!$cart || $cart->products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty'
            ]);
        }

        // Calculate total amount and validate stock
        $totalAmount = 0;
        $cartItems = [];
        
        foreach ($cart->products as $product) {
            // Check stock availability before proceeding
            if ($product->stock < $product->pivot->product_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for {$product->product_name}. Available: {$product->stock}"
                ]);
            }

            $price = $product->price_after_discount ?? $product->unit_price;
            $subtotal = $price * $product->pivot->product_quantity;
            $totalAmount += $subtotal;
            
            $cartItems[] = [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'quantity' => $product->pivot->product_quantity,
                'unit_price' => $price,
                'total' => $subtotal
            ];
        }

        // Store cart data and pickup info in session for later use
        Session::put('checkout_data', [
            'cart_id' => $cart->cart_id,
            'user_id' => $userId,
            'pickup_date' => $request->pickup_date,
            'pickup_slot' => $request->pickup_slot,
            'slot_id' => $request->slot_id,
            'cart_items' => $cartItems,
            'total_amount' => $totalAmount
        ]);

        // Create PayPal order
        $paypalResponse = $this->paypalService->createOrder(
            $totalAmount,
            'USD',
            'Clexomart Order - ' . count($cartItems) . ' items'
        );

        if ($paypalResponse['success']) {
            // Store PayPal order ID in session
            Session::put('paypal_order_id', $paypalResponse['order_id']);
            
            // Return approval URL for frontend to redirect
            return response()->json([
                'success' => true,
                'approval_url' => $paypalResponse['approval_url']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $paypalResponse['message'] ?? 'Payment initiation failed'
        ]);
    }

    /**
     * Handle successful PayPal payment
     */
    public function success(Request $request)
    {
        \Log::info('PayPal success method called', [
            'request_all' => $request->all(),
            'query_params' => $request->query(),
            'url' => $request->fullUrl()
        ]);
        
        // PayPal returns with query parameters like ?token=XXXXX&PayerID=YYYYY
        $paypalToken = $request->query('token');
        $payerId = $request->query('PayerID');
        
        \Log::info('PayPal return parameters', [
            'token' => $paypalToken,
            'payer_id' => $payerId
        ]);
        
        $paypalOrderId = Session::get('paypal_order_id');
        $checkoutData = Session::get('checkout_data');

        \Log::info('Session data retrieved', [
            'paypal_order_id' => $paypalOrderId,
            'checkout_data_exists' => !empty($checkoutData),
            'checkout_data' => $checkoutData
        ]);

        // If we have PayPal token but no session data, it might be a session timeout
        if (($paypalToken || $payerId) && (!$paypalOrderId || !$checkoutData)) {
            \Log::warning('PayPal returned with token but session data missing', [
                'token' => $paypalToken,
                'payer_id' => $payerId,
                'session_order_id' => $paypalOrderId,
                'has_checkout_data' => !empty($checkoutData)
            ]);
            return redirect()->route('cart')->with('error', 'Payment session expired. Please try again.');
        }

        if (!$paypalOrderId || !$checkoutData) {
            \Log::error('Missing session data for PayPal success');
            return redirect()->route('cart')->with('error', 'Invalid payment session');
        }

        // Capture the PayPal payment
        \Log::info('Attempting to capture PayPal payment', ['order_id' => $paypalOrderId]);
        $captureResponse = $this->paypalService->captureOrder($paypalOrderId);
        \Log::info('PayPal capture response', ['response' => $captureResponse]);

        if (!$captureResponse['success']) {
            \Log::error('PayPal capture failed', ['response' => $captureResponse]);
            return redirect()->route('cart')->with('error', 'Payment capture failed: ' . ($captureResponse['message'] ?? 'Unknown error'));
        }

        try {
            \Log::info('Starting database transaction');
            DB::beginTransaction();

            // Generate order ID
            $orderId = 'ORD' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            \Log::info('Generated order ID', ['order_id' => $orderId]);

            // 1. First ensure COLLECTION_SLOT exists and get/update it
            \Log::info('Processing collection slot', ['slot_id' => $checkoutData['slot_id']]);
            $slot = DB::table('COLLECTION_SLOT')->where('slot_id', $checkoutData['slot_id'])->first();
            
            if (!$slot) {
                \Log::info('Creating new collection slot');
                $slotTime = $this->getSlotTime($checkoutData['pickup_date'], $checkoutData['pickup_slot']);
                \Log::info('Slot time calculated', ['slot_time' => $slotTime]);
                
                $slotData = [
                    'slot_id' => $checkoutData['slot_id'],
                    'day' => $checkoutData['pickup_date'],
                    'time' => $slotTime,
                    'no_order' => 1 // Start with 1 order
                ];
                \Log::info('Inserting slot data', ['slot_data' => $slotData]);
                
                $slotInsert = DB::table('COLLECTION_SLOT')->insert($slotData);
                \Log::info('Slot insertion result', ['success' => $slotInsert]);
            } else {
                \Log::info('Incrementing existing slot order count', ['current_count' => $slot->no_order]);
                DB::table('COLLECTION_SLOT')
                    ->where('slot_id', $checkoutData['slot_id'])
                    ->increment('no_order');
                \Log::info('Slot order count incremented');
            }

            // 2. Create ORDER1 record (main order table)
            \Log::info('Creating ORDER1 record');
            $orderData = [
                'order_id' => $orderId,
                'order_date' => now()->format('Y-m-d'),
                'user_id' => $checkoutData['user_id'],
                'cart_id' => $checkoutData['cart_id'],
                'payment_amount' => $checkoutData['total_amount'],
                'slot_id' => $checkoutData['slot_id'],
                'coupon_id' => null
            ];
            \Log::info('ORDER1 data', ['order_data' => $orderData]);
            
            $orderInsert = DB::table('ORDER1')->insert($orderData);
            \Log::info('ORDER1 insertion result', ['success' => $orderInsert]);

            // 3. Create ORDER_STATUS record (depends on ORDER1)
            \Log::info('Creating ORDER_STATUS record');
            $statusData = [
                'order_id' => $orderId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];
            \Log::info('ORDER_STATUS data', ['status_data' => $statusData]);
            
            $statusInsert = DB::table('ORDER_STATUS')->insert($statusData);
            \Log::info('ORDER_STATUS insertion result', ['success' => $statusInsert]);

            // 4. Create ORDER_ITEM records (depends on ORDER1)
            \Log::info('Creating ORDER_ITEM records', ['item_count' => count($checkoutData['cart_items'])]);
            foreach ($checkoutData['cart_items'] as $index => $item) {
                $orderItemId = 'OI' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                
                $itemData = [
                    'order_item_id' => $orderItemId,
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ];
                \Log::info("ORDER_ITEM data for item {$index}", ['item_data' => $itemData]);
                
                $itemInsert = DB::table('ORDER_ITEM')->insert($itemData);
                \Log::info("ORDER_ITEM insertion result for item {$index}", ['success' => $itemInsert]);

                // Update product stock
                \Log::info("Updating stock for product {$item['product_id']}", ['decrement' => $item['quantity']]);
                $stockUpdate = DB::table('PRODUCT')
                    ->where('product_id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
                \Log::info("Stock update result for product {$item['product_id']}", ['affected_rows' => $stockUpdate]);
            }

            // 5. Create PAYMENT record (depends on ORDER1)
            \Log::info('Creating PAYMENT record');
            $paymentId = 'PAY' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $paymentData = [
                'payment_id' => $paymentId,
                'payment_method' => 'PayPal',
                'payment_date' => now()->format('Y-m-d'),
                'user_id' => $checkoutData['user_id'],
                'order_id' => $orderId,
                'payment_amount' => $checkoutData['total_amount']
            ];
            \Log::info('PAYMENT data', ['payment_data' => $paymentData]);
            
            $paymentInsert = DB::table('PAYMENT')->insert($paymentData);
            \Log::info('PAYMENT insertion result', ['success' => $paymentInsert]);

            // 6. Clear the cart after successful order creation
            \Log::info('Clearing cart', ['cart_id' => $checkoutData['cart_id']]);
            $cartClear = DB::table('CART_PRODUCT')->where('cart_id', $checkoutData['cart_id'])->delete();
            \Log::info('Cart clearing result', ['deleted_rows' => $cartClear]);

            \Log::info('Committing database transaction');
            DB::commit();

            // Clear session data
            Session::forget(['paypal_order_id', 'checkout_data']);
            \Log::info('Session data cleared, redirecting to success page');

            return redirect()->route('order.success', ['order_id' => $orderId])
                ->with('success', 'Payment successful! Your order has been placed.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('cart')->with('error', 'Order creation failed. Please contact support. Error: ' . $e->getMessage());
        }
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
     * Handle cancelled PayPal payment
     */
    public function cancel(Request $request)
    {
        // Clear session data
        Session::forget(['paypal_order_id', 'checkout_data']);
        
        return redirect()->route('cart')->with('warning', 'Payment was cancelled. You can try again.');
    }

    /**
     * Show order success page
     */
    public function orderSuccess($orderId)
    {
        $order = DB::table('ORDER1')
            ->join('ORDER_STATUS', 'ORDER1.order_id', '=', 'ORDER_STATUS.order_id')
            ->join('COLLECTION_SLOT', 'ORDER1.slot_id', '=', 'COLLECTION_SLOT.slot_id')
            ->where('ORDER1.order_id', $orderId)
            ->select(
                'ORDER1.*',
                'ORDER_STATUS.status',
                'COLLECTION_SLOT.day as pickup_date',
                'COLLECTION_SLOT.time as pickup_time'
            )
            ->first();

        if (!$order) {
            return redirect()->route('home')->with('error', 'Order not found');
        }

        $orderItems = DB::table('ORDER_ITEM')
            ->join('PRODUCT', 'ORDER_ITEM.product_id', '=', 'PRODUCT.product_id')
            ->where('ORDER_ITEM.order_id', $orderId)
            ->select(
                'ORDER_ITEM.*',
                'PRODUCT.product_name',
                'PRODUCT.product_image'
            )
            ->get();

        return view('order-success', compact('order', 'orderItems'));
    }

    /**
     * Debug method to test the entire PayPal flow
     */
    public function debug()
    {
        $response = ['debug_steps' => []];
        
        try {
            // Step 1: Test database connection
            $response['debug_steps'][] = 'Testing database connection...';
            $dbTest = DB::select('SELECT 1 FROM DUAL');
            $response['debug_steps'][] = 'Database connection: SUCCESS';
            
            // Step 2: Test session data
            $userId = session('user_id');
            $response['debug_steps'][] = "Session user_id: " . ($userId ?? 'NULL');
            
            if ($userId) {
                // Step 3: Test cart retrieval
                $cart = Cart::with('products')->where('user_id', $userId)->first();
                $response['debug_steps'][] = "Cart found: " . ($cart ? 'YES' : 'NO');
                
                if ($cart) {
                    $response['debug_steps'][] = "Cart ID: " . $cart->cart_id;
                    $response['debug_steps'][] = "Cart products count: " . $cart->products->count();
                    
                    // Step 4: Test collection slot creation
                    $testDate = date('Y-m-d', strtotime('+2 days'));
                    $testSlot = '10-13';
                    $slotId = 'test' . time();
                    
                    $slotInsert = DB::table('COLLECTION_SLOT')->insert([
                        'slot_id' => $slotId,
                        'day' => $testDate,
                        'time' => $this->getSlotTime($testDate, $testSlot),
                        'no_order' => 0
                    ]);
                    $response['debug_steps'][] = "Test slot creation: " . ($slotInsert ? 'SUCCESS' : 'FAILED');
                    
                    // Step 5: Test order creation
                    $testOrderId = 'TEST' . time();
                    $orderInsert = DB::table('ORDER1')->insert([
                        'order_id' => $testOrderId,
                        'order_date' => now()->format('Y-m-d'),
                        'user_id' => $userId,
                        'cart_id' => $cart->cart_id,
                        'payment_amount' => 10.00,
                        'slot_id' => $slotId,
                        'coupon_id' => null
                    ]);
                    $response['debug_steps'][] = "Test order creation: " . ($orderInsert ? 'SUCCESS' : 'FAILED');
                    
                    // Step 6: Test order status creation
                    $statusInsert = DB::table('ORDER_STATUS')->insert([
                        'order_id' => $testOrderId,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $response['debug_steps'][] = "Test order status creation: " . ($statusInsert ? 'SUCCESS' : 'FAILED');
                    
                    // Clean up test data
                    DB::table('ORDER_STATUS')->where('order_id', $testOrderId)->delete();
                    DB::table('ORDER1')->where('order_id', $testOrderId)->delete();
                    DB::table('COLLECTION_SLOT')->where('slot_id', $slotId)->delete();
                    $response['debug_steps'][] = "Test data cleanup: SUCCESS";
                }
            }
            
            // Step 7: Test PayPal service
            $paypalTest = $this->paypalService->createOrder(1.00, 'USD', 'Test Order');
            $response['debug_steps'][] = "PayPal service test: " . ($paypalTest['success'] ? 'SUCCESS' : 'FAILED - ' . $paypalTest['message']);
            
            $response['overall_status'] = 'DEBUG_COMPLETE';
            
        } catch (\Exception $e) {
            $response['debug_steps'][] = "ERROR: " . $e->getMessage();
            $response['debug_steps'][] = "TRACE: " . $e->getTraceAsString();
            $response['overall_status'] = 'DEBUG_FAILED';
        }
        
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Test method to simulate order creation without PayPal
     */
    public function testOrderCreation()
    {
        $response = ['test_steps' => []];
        
        try {
            $userId = session('user_id');
            if (!$userId) {
                return response()->json(['error' => 'Please login first'], 400);
            }
            
            // Get user's cart
            $cart = Cart::with('products')->where('user_id', $userId)->first();
            if (!$cart || $cart->products->isEmpty()) {
                return response()->json(['error' => 'Cart is empty'], 400);
            }
            
            // Simulate checkout data
            $cartItems = [];
            $totalAmount = 0;
            
            foreach ($cart->products as $product) {
                $price = $product->price_after_discount ?? $product->unit_price;
                $subtotal = $price * $product->pivot->product_quantity;
                $totalAmount += $subtotal;
                
                $cartItems[] = [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'quantity' => $product->pivot->product_quantity,
                    'unit_price' => $price,
                    'total' => $subtotal
                ];
            }
            
            $testDate = date('Y-m-d', strtotime('+2 days'));
            $testSlot = '10-13';
            $slotId = 'test' . time();
            
            $checkoutData = [
                'cart_id' => $cart->cart_id,
                'user_id' => $userId,
                'pickup_date' => $testDate,
                'pickup_slot' => $testSlot,
                'slot_id' => $slotId,
                'cart_items' => $cartItems,
                'total_amount' => $totalAmount
            ];
            
            $response['test_steps'][] = 'Test data prepared';
            $response['checkout_data'] = $checkoutData;
            
            // Test the order creation process
            DB::beginTransaction();
            
            $orderId = 'TEST' . time();
            $response['test_steps'][] = "Generated order ID: {$orderId}";
            
            // 1. Collection slot
            $slotTime = $this->getSlotTime($checkoutData['pickup_date'], $checkoutData['pickup_slot']);
            $slotData = [
                'slot_id' => $checkoutData['slot_id'],
                'day' => $checkoutData['pickup_date'],
                'time' => $slotTime,
                'no_order' => 1
            ];
            
            $slotInsert = DB::table('COLLECTION_SLOT')->insert($slotData);
            $response['test_steps'][] = 'COLLECTION_SLOT insert: ' . ($slotInsert ? 'SUCCESS' : 'FAILED');
            
            // 2. Order
            $orderData = [
                'order_id' => $orderId,
                'order_date' => now()->format('Y-m-d'),
                'user_id' => $checkoutData['user_id'],
                'cart_id' => $checkoutData['cart_id'],
                'payment_amount' => $checkoutData['total_amount'],
                'slot_id' => $checkoutData['slot_id'],
                'coupon_id' => null
            ];
            
            $orderInsert = DB::table('ORDER1')->insert($orderData);
            $response['test_steps'][] = 'ORDER1 insert: ' . ($orderInsert ? 'SUCCESS' : 'FAILED');
            
            // 3. Order status
            $statusData = [
                'order_id' => $orderId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $statusInsert = DB::table('ORDER_STATUS')->insert($statusData);
            $response['test_steps'][] = 'ORDER_STATUS insert: ' . ($statusInsert ? 'SUCCESS' : 'FAILED');
            
            // 4. Order items
            foreach ($checkoutData['cart_items'] as $index => $item) {
                $orderItemId = 'TEST_OI' . time() . $index;
                
                $itemData = [
                    'order_item_id' => $orderItemId,
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ];
                
                $itemInsert = DB::table('ORDER_ITEM')->insert($itemData);
                $response['test_steps'][] = "ORDER_ITEM {$index} insert: " . ($itemInsert ? 'SUCCESS' : 'FAILED');
            }
            
            // 5. Payment
            $paymentId = 'TEST_PAY' . time();
            $paymentData = [
                'payment_id' => $paymentId,
                'payment_method' => 'Test',
                'payment_date' => now()->format('Y-m-d'),
                'user_id' => $checkoutData['user_id'],
                'order_id' => $orderId,
                'payment_amount' => $checkoutData['total_amount']
            ];
            
            $paymentInsert = DB::table('PAYMENT')->insert($paymentData);
            $response['test_steps'][] = 'PAYMENT insert: ' . ($paymentInsert ? 'SUCCESS' : 'FAILED');
            
            // Check data exists
            $orderCheck = DB::table('ORDER1')->where('order_id', $orderId)->first();
            $statusCheck = DB::table('ORDER_STATUS')->where('order_id', $orderId)->first();
            $itemsCheck = DB::table('ORDER_ITEM')->where('order_id', $orderId)->count();
            $paymentCheck = DB::table('PAYMENT')->where('order_id', $orderId)->first();
            $slotCheck = DB::table('COLLECTION_SLOT')->where('slot_id', $slotId)->first();
            
            $response['verification'] = [
                'order_exists' => !empty($orderCheck),
                'status_exists' => !empty($statusCheck),
                'items_count' => $itemsCheck,
                'payment_exists' => !empty($paymentCheck),
                'slot_exists' => !empty($slotCheck),
                'slot_order_count' => $slotCheck->no_order ?? 0
            ];
            
            // Rollback to not affect real data
            DB::rollBack();
            $response['test_steps'][] = 'Transaction rolled back (test mode)';
            
            $response['overall_status'] = 'TEST_COMPLETE';
            
        } catch (\Exception $e) {
            DB::rollBack();
            $response['test_steps'][] = "ERROR: " . $e->getMessage();
            $response['error_details'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            $response['overall_status'] = 'TEST_FAILED';
        }
        
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }
} 