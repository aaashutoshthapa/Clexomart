<?php

/**
 * CheckoutController
 * 
 * This controller handles the complete checkout process for the ClexoMart e-commerce platform,
 * including PayPal payment integration, order creation, and post-payment processing.
 * 
 * Key Responsibilities:
 * - PayPal payment gateway integration and processing
 * - Order creation and database transaction management
 * - Pickup slot validation and reservation
 * - Cart clearing after successful payment
 * - Order confirmation and email notifications
 * - Payment capture and verification
 * 
 * Payment Flow:
 * 1. createTransaction(): Validates data and initiates PayPal payment
 * 2. PayPal redirect: User completes payment on PayPal
 * 3. successTransaction(): Captures payment and creates order
 * 4. Database operations: Order, payment, and cart management
 * 5. Email confirmation and redirect to success page
 * 
 * Business Rules:
 * - Pickup slots must be 24+ hours in advance
 * - Only Wednesday, Thursday, Friday pickup days allowed
 * - Maximum 20 orders per pickup slot
 * - Cart is cleared only after successful payment
 * - All database operations use transactions for consistency
 * 
 * Error Handling:
 * - Comprehensive validation at each step
 * - Database transaction rollback on failures
 * - User-friendly error messages and redirects
 * - Detailed logging for debugging and monitoring
 * 
 * @package App\Http\Controllers
 * @author ClexoMart Development Team
 * @version 1.0
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderInvoiceMail;
use Illuminate\Support\Facades\Log ;
use App\Models\Cart;  

/**
 * CheckoutController Class
 * 
 * Manages the complete payment and order creation workflow
 * Integrates with PayPal for secure payment processing
 */
class CheckoutController extends Controller
{
    /**
     * Create PayPal payment transaction and initiate checkout process
     * 
     * This method handles the initial checkout request from the cart page.
     * It validates all input data, checks pickup slot availability, and creates
     * a PayPal payment order for user approval.
     * 
     * Validation Steps:
     * 1. Input validation (amount, dates, slot format)
     * 2. Pickup day validation (Wed/Thu/Fri only)
     * 3. 24-hour advance booking rule
     * 4. Slot availability check (max 20 orders)
     * 
     * PayPal Integration:
     * - Creates payment order with capture intent
     * - Sets up return and cancel URLs
     * - Redirects user to PayPal for approval
     * 
     * Session Management:
     * - Stores pickup slot data for later processing
     * - Maintains order context during PayPal redirect
     * 
     * @param Request $request Contains amount, pickup_date, pickup_slot, slot_id
     * @return \Illuminate\Http\RedirectResponse Redirect to PayPal or error page
     */
    public function createTransaction(Request $request)
    {
        // Input Validation: Ensure all required checkout data is present and valid
        $request->validate([
            'amount' => 'required|numeric|min:0.01',                    // Minimum payment amount
            'pickup_date' => 'required|date_format:Y-m-d',             // Valid date format
            'pickup_slot' => 'required|in:10-13,13-16,16-19',          // Valid time slots only
            'slot_id' => 'required|string'                              // Slot identifier from database
        ]);

        // Business Logic Validation: Pickup date and time constraints
        $pickupDate = strtotime($request->pickup_date);
        $dayOfWeek = date('w', $pickupDate);                           // 0=Sunday, 6=Saturday
        $minDate = strtotime('+24 hours');                             // 24-hour advance requirement
        
        // Pickup Day Validation: Only Wednesday (3), Thursday (4), Friday (5)
        if ($dayOfWeek < 3 || $dayOfWeek > 5) {
            return redirect()->route('cart')->with('error', 'Pickup is only available on Wednesday, Thursday, and Friday.');
        }
        
        // Advance Booking Validation: Must be at least 24 hours in future
        if ($pickupDate < $minDate) {
            return redirect()->route('cart')->with('error', 'Pickup must be scheduled at least 24 hours in advance.');
        }

        // Session Storage: Preserve pickup data during PayPal redirect flow
        session([
            'pickup_data' => [
                'pickup_date' => $request->pickup_date,
                'pickup_slot' => $request->pickup_slot,
                'slot_id' => $request->slot_id
            ]
        ]);

        // Slot Capacity Validation: Check current order count (max 20 per slot)
        $currentOrderCount = DB::table('COLLECTION_SLOT')
            ->where('slot_id', $request->slot_id)
            ->value('no_order') ?? 0;
            
        if ($currentOrderCount >= 20) {
            return redirect()->route('cart')->with('error', 'Sorry, this pickup slot is now fully booked. Please select a different time slot.');
        }

        // PayPal Client Initialization
        $paypal = new PayPalClient;
        $paypal->setApiCredentials(config('paypal'));                  // Load credentials from config
        $token = $paypal->getAccessToken();                            // Authenticate with PayPal
        $paypal->setAccessToken($token);

        // PayPal Order Creation: Create payment order for user approval
        $response = $paypal->createOrder([
            "intent" => "CAPTURE",                                      // Immediate payment capture
            "application_context" => [
                "return_url" => route('paypal.success'),               // Success callback URL
                "cancel_url" => route('paypal.cancel'),                // Cancellation callback URL
            ],
            "purchase_units" => [
                [
                    "reference_id" => Str::uuid(),                      // Unique transaction reference
                    "amount" => [
                        "currency_code" => env('PAYPAL_CURRENCY', 'USD'), // Payment currency
                        "value" => $request->amount                     // Payment amount
                    ]
                ]
            ]
        ]);

        // PayPal Response Processing: Redirect to approval URL if successful
        if (isset($response['id']) && $response['status'] === 'CREATED') {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);             // Redirect to PayPal
                }
            }
        }

        // Error Response: PayPal order creation failed
        return redirect()->route('cart')->with('error', 'Something went wrong.');
    }

    /**
     * Process successful PayPal payment and create order
     * 
     * This method is called when PayPal redirects back after successful payment.
     * It captures the payment, creates the order record, and manages all related
     * database operations in a transaction to ensure data consistency.
     * 
     * Payment Processing:
     * 1. Capture PayPal payment using the approval token
     * 2. Verify payment completion status
     * 3. Extract payment details (amount, transaction ID)
     * 
     * Order Creation:
     * 1. Generate unique order and payment IDs
     * 2. Create ORDER1 record with pickup slot assignment
     * 3. Create ORDER_ITEM records for each cart product
     * 4. Create PAYMENT record with PayPal details
     * 5. Set initial order status to 'pending'
     * 
     * Cart Management:
     * 1. Clear CART_PRODUCT table after successful order
     * 2. Clear session cart for consistency
     * 3. Update pickup slot order count
     * 
     * Email Notification:
     * - Send order confirmation email to customer
     * 
     * @param Request $request Contains PayPal approval token
     * @return \Illuminate\Http\RedirectResponse Redirect to success page or error
     */
    public function successTransaction(Request $request)
    {
        // PayPal Client Re-initialization for payment capture
        $paypal = new PayPalClient;
        $paypal->setApiCredentials(config('paypal'));
        $token = $paypal->getAccessToken();
        $paypal->setAccessToken($token);

        // Payment Capture: Complete the PayPal transaction
        $response = $paypal->capturePaymentOrder($request->token);

        // Payment Verification: Ensure payment was successfully completed
        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            $userId = session(key: 'user_id');

            // Payment Details Extraction
            $capture = $response['purchase_units'][0]['payments']['captures'][0];
            $amount = $capture['amount']['value'];

            // ID Generation: Create unique identifiers for order and payment
            $paymentId = strtoupper(Str::random(8));                   // Payment record ID
            $orderId = strtoupper(Str::random(8));                     // Order record ID

            // Cart Validation: Ensure user has a valid cart with items
            $cart = DB::table('CART')->where('USER_ID', $userId)->first();
            if (!$cart) {
                return redirect()->route('cart')->with('error', 'Cart not found.');
            }

            $cartProducts = DB::table('CART_PRODUCT')->where('CART_ID', $cart->cart_id)->get();
            if ($cartProducts->isEmpty()) {
                return redirect()->route('cart')->with('error', 'Cart is empty.');
            }

            // Pickup Data Retrieval: Get slot information from session
            $pickupData = session('pickup_data');
            if (!$pickupData) {
                return redirect()->route('cart')->with('error', 'Pickup slot information is missing. Please select a pickup slot and try again.');
            }

            $slotId = $pickupData['slot_id'];
            $couponId = null;                                           // Future: coupon support

            try {
                // Database Transaction: Ensure all operations succeed or fail together
                DB::beginTransaction();

                // Order Creation: Main order record
                DB::table('ORDER1')->insert([
                    'ORDER_ID' => $orderId,
                    'ORDER_DATE' => now(),
                    'COUPON_ID' => $couponId,                          // Future coupon integration
                    'CART_ID' => $cart->cart_id,
                    'PAYMENT_AMOUNT' => $amount,
                    'SLOT_ID' => $slotId,                              // Pickup slot assignment
                    'USER_ID' => $userId
                ]);

                // Order Verification: Ensure order was created successfully
                $order = DB::table('ORDER1')
                    ->where('USER_ID', $userId)
                    ->orderByDesc('ORDER_DATE')
                    ->first();

                if (!$order) {
                    DB::rollBack();
                    return redirect()->route('cart')->with('error', 'Order not found.');
                }

                $orderId = $order->order_id;
                
                // Order Items Creation: Convert cart items to order items
                foreach ($cartProducts as $item) {
                    // Product Price Retrieval: Use current pricing (discounted or regular)
                    $product = DB::table('PRODUCT')->where('PRODUCT_ID', $item->product_id)->first();

                    $unitPrice = $product->price_after_discount ?? $product->unit_price ?? 0;
                    $quantity = $item->product_quantity ?? 1;

                    // Order Item Record Creation
                    DB::table('ORDER_ITEM')->insert([
                        'ORDER_ITEM_ID' => strtoupper(Str::random(10)),
                        'ORDER_ID' => $orderId,
                        'PRODUCT_ID' => $item->product_id,
                        'QUANTITY' => $quantity,
                        'UNIT_PRICE' => $unitPrice,
                    ]);
                }

                // Order Status Initialization
                DB::table('ORDER_STATUS')->insert([
                    'ORDER_ID' => $orderId,
                    'STATUS' => 'pending',                             // Initial status
                    'CREATED_AT' => now(),
                    'UPDATED_AT' => now()
                ]);

                // Payment Record Creation: Store PayPal transaction details
                DB::table('PAYMENT')->insert([
                    'PAYMENT_ID' => $paymentId,
                    'PAYMENT_METHOD' => 'PayPal',
                    'PAYMENT_DATE' => now(),
                    'USER_ID' => $userId,
                    'ORDER_ID' => $orderId,
                    'PAYMENT_AMOUNT' => $amount
                ]);

                // Cart Cleanup: Clear cart after successful order creation
                $cartClearResult = DB::table('CART_PRODUCT')->where('CART_ID', $cart->cart_id)->delete();
                Log::info('Cart cleared after successful payment', [
                    'cart_id' => $cart->cart_id,
                    'deleted_rows' => $cartClearResult,
                    'order_id' => $orderId
                ]);

                // Session Cart Cleanup: Ensure consistency across storage methods
                if (session()->has('cart')) {
                    session()->forget('cart');
                    Log::info('Session cart cleared after successful payment', [
                        'order_id' => $orderId,
                        'user_id' => $userId
                    ]);
                }

                // Session Cleanup: Clear pickup slot data after successful order
                if (session()->has('pickup_data')) {
                    session()->forget('pickup_data');
                    Log::info('Pickup slot data cleared from session', [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'pickup_slot_id' => $slotId
                    ]);
                }

                // Email Notification: Send order confirmation invoice to customer
                try {
                    // Customer Data Retrieval: Get user email for invoice delivery
                    $user = DB::table('USER1')->where('user_id', $userId)->first();
                    if ($user && $user->email) {
                        // Order Items Data: Prepare product details for email
                        $emailOrderItems = DB::table('ORDER_ITEM')
                            ->join('PRODUCT', 'ORDER_ITEM.product_id', '=', 'PRODUCT.product_id')
                            ->where('ORDER_ITEM.order_id', $orderId)
                            ->select(
                                'ORDER_ITEM.*',
                                'PRODUCT.product_name',
                                'PRODUCT.unit_price'
                            )
                            ->get();
                        
                        // Order Details Data: Prepare order summary for email
                        $emailOrder = DB::table('ORDER1')
                            ->join('ORDER_STATUS', 'ORDER1.order_id', '=', 'ORDER_STATUS.order_id')
                            ->leftJoin('COLLECTION_SLOT', 'ORDER1.slot_id', '=', 'COLLECTION_SLOT.slot_id')
                            ->where('ORDER1.order_id', $orderId)
                            ->select(
                                'ORDER1.*',
                                'ORDER_STATUS.status',
                                'COLLECTION_SLOT.day as pickup_date',
                                'COLLECTION_SLOT.time as pickup_time'
                            )
                            ->first();

                        // Email Delivery: Send formatted invoice email
                        Mail::to($user->email)->send(new OrderInvoiceMail($emailOrder, $emailOrderItems, $user->email));
                        
                        Log::info('Invoice email sent successfully', [
                            'order_id' => $orderId,
                            'user_id' => $userId,
                            'email' => $user->email
                        ]);
                    } else {
                        Log::warning('Could not send invoice email - user email not found', [
                            'order_id' => $orderId,
                            'user_id' => $userId
                        ]);
                    }
                } catch (\Exception $emailException) {
                    Log::error('Failed to send invoice email', [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'error' => $emailException->getMessage()
                    ]);
                    // Email failure doesn't break the order process - graceful degradation
                }

                // Slot Management: Update pickup slot order count (atomic operation within transaction)
                DB::table('COLLECTION_SLOT')
                    ->where('slot_id', $slotId)
                    ->increment('no_order');
                
                Log::info('Collection slot order count incremented', [
                    'slot_id' => $slotId,
                    'order_id' => $orderId
                ]);

                // Transaction Completion: Commit all database changes atomically
                DB::commit();

                // Success Response: Redirect to order success page with confirmation
                return redirect()->route('order.success', ['order_id' => $orderId])
                    ->with('success', 'Payment successful! Your order has been placed. Your cart has been cleared.');

            } catch (\Exception $e) {
                // Error Handling: Rollback all database changes on any failure
                DB::rollBack();
                Log::error('Order creation failed during payment success', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'cart_id' => $cart->cart_id
                ]);
                return redirect()->route('cart')->with('error', 'Order processing failed. Please contact support.');
            }
        }

        // Payment Failure Response: PayPal payment was not completed successfully
        return redirect()->route('cart')->with('error', 'Payment failed.');
    }
    
    /**
     * Handle PayPal payment cancellation
     * 
     * This method is called when user cancels the payment on PayPal's website.
     * It provides a user-friendly response and redirects back to the cart.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirect to cart with cancellation message
     */
    public function cancelTransaction()
    {
        return redirect()->route('cart')->with('error', 'Payment was cancelled.');
    }

    /**
     * Display order success page with order details
     * 
     * This method handles the order success page display after successful payment.
     * It retrieves comprehensive order information including items, status, and
     * pickup details to show the customer a complete order summary.
     * 
     * Data Retrieved:
     * - Order details (ID, date, amount, status)
     * - Pickup slot information (date, time)
     * - Order items with product details
     * - Product images for visual confirmation
     * 
     * Error Handling:
     * - Validates order exists and belongs to valid session
     * - Redirects to home if order not found
     * 
     * @param string $orderId The order ID to display
     * @return \Illuminate\View\View Order success page view
     */
    public function orderSuccess($orderId)
    {
        // Order Data Retrieval: Get comprehensive order information
        $order = DB::table('ORDER1')
            ->join('ORDER_STATUS', 'ORDER1.order_id', '=', 'ORDER_STATUS.order_id')
            ->leftJoin('COLLECTION_SLOT', 'ORDER1.slot_id', '=', 'COLLECTION_SLOT.slot_id')
            ->where('ORDER1.order_id', $orderId)
            ->select(
                'ORDER1.*',                                             // Order basic details
                'ORDER_STATUS.status',                                  // Current order status
                'COLLECTION_SLOT.day as pickup_date',                   // Pickup date
                'COLLECTION_SLOT.time as pickup_time'                   // Pickup time slot
            )
            ->first();

        // Order Validation: Ensure order exists
        if (!$order) {
            return redirect()->route('home')->with('error', 'Order not found');
        }

        // Order Items Retrieval: Get all products in this order with details
        $orderItems = DB::table('ORDER_ITEM')
            ->join('PRODUCT', 'ORDER_ITEM.product_id', '=', 'PRODUCT.product_id')
            ->where('ORDER_ITEM.order_id', $orderId)
            ->select(
                'ORDER_ITEM.*',                                         // Order item details (quantity, price)
                'PRODUCT.product_name',                                 // Product name for display
                'PRODUCT.product_image'                                 // Product image for visual confirmation
            )
            ->get();

        // View Response: Display order success page with all order details
        return view('order-success', compact('order', 'orderItems'));
    }
}