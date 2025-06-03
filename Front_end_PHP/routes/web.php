<?php

/**
 * ClexoMart Web Routes Configuration
 * 
 * This file defines all web routes for the ClexoMart e-commerce platform.
 * Routes are organized by functionality and user roles for easy maintenance.
 * 
 * Route Categories:
 * 1. Public Routes - Accessible to all users
 * 2. Authentication Routes - Login, registration, verification
 * 3. Customer Routes - Shopping cart, profile management
 * 4. Trader Routes - Shop management, product CRUD, analytics
 * 5. Payment Routes - PayPal integration, checkout flow
 * 6. Utility Routes - Debug, testing, development tools
 * 
 * Security Considerations:
 * - Authentication checks handled in controllers
 * - CSRF protection on all POST routes
 * - Role-based access control in middleware
 * - Input validation in form requests
 * 
 * Performance Features:
 * - Route caching in production
 * - Controller method optimization
 * - Database query optimization
 * - Image serving optimization
 * 
 * @author ClexoMart Development Team
 * @version 1.0
 */

use App\Http\Controllers\VendorController;
use App\Http\Controllers\User1Controller;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

// ============================================================================
// PUBLIC ROUTES
// These routes are accessible to all users without authentication
// Includes main pages, product browsing, and public information
// ============================================================================

/**
 * Main Website Pages
 * 
 * Core public pages that form the foundation of the shopping experience:
 * - Home page with featured products and categories
 * - Cart page for shopping cart management
 * - Contact page for customer support
 */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/contact', [ContactController::class, 'showContactForm'])->name('contact');

/**
 * Product and Category Browsing Routes
 * 
 * Public product discovery functionality:
 * - Individual product detail pages
 * - Category listing and browsing
 * - Product search and filtering
 */
Route::get('/products/{id}', [ProductController::class, 'show'])->name('product.detail');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// ============================================================================
// AUTHENTICATION ROUTES
// User registration, login, email verification, and session management
// Includes OTP verification system and password reset functionality
// ============================================================================

/**
 * User Registration Flow
 * 
 * Complete registration process with email verification:
 * 1. Show registration form
 * 2. Process registration data
 * 3. Send OTP verification email
 * 4. Verify OTP code
 * 5. Activate account
 */
Route::get('/signup', [AuthController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.submit');
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtpForm'])->name('verify.otp.form');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp');

/**
 * User Authentication Flow
 * 
 * Login and logout functionality with session management:
 * - Login form display and processing
 * - Session establishment with role-based routing
 * - Secure logout with session cleanup
 * - Profile access for authenticated users
 */
Route::get('/signin', [AuthController::class, 'showSigninForm'])->name('signin');
Route::post('/signin', [AuthController::class, 'signin'])->name('signin.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');

// ============================================================================
// SHOPPING CART & CHECKOUT ROUTES
// Cart management, item operations, and payment processing
// Includes AJAX endpoints for real-time cart updates
// ============================================================================

/**
 * Cart Management Operations
 * 
 * Complete shopping cart functionality:
 * - Add products to cart with quantity validation
 * - Update item quantities with stock checking
 * - Remove items from cart
 * - Pickup slot availability checking
 * - Cart capacity management (20-item limit)
 */
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'updateCart'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'removeFromCart'])->name('cart.remove');
Route::post('/cart/check-slot', [CartController::class, 'checkSlotAvailability'])->name('cart.check-slot');
Route::post('/cart/set-slot', [CartController::class, 'setSlotId']);

/**
 * Cart Testing and Debugging Routes
 * 
 * Development routes for testing cart functionality:
 * - Test AJAX update operations
 * - Test remove item operations
 * - Debug cart state and validation
 */
Route::post('/cart/test-update', [CartController::class, 'testUpdate'])->name('cart.test-update');
Route::post('/cart/test-remove', [CartController::class, 'testRemove'])->name('cart.test-remove');

/**
 * PayPal Payment Integration Routes
 * 
 * Complete payment processing workflow:
 * 1. Create PayPal transaction with order data
 * 2. Handle successful payment response
 * 3. Handle payment cancellation
 * 4. Display order success page
 */
Route::post('/paypal/transaction/create', [CheckoutController::class, 'createTransaction'])->name('paypal.create');
Route::get('/paypal/transaction/success', [CheckoutController::class, 'successTransaction'])->name('paypal.success');
Route::get('/paypal/transaction/cancel', [CheckoutController::class, 'cancelTransaction'])->name('paypal.cancel');
Route::get('/order/success/{order_id}', [CheckoutController::class, 'orderSuccess'])->name('order.success');

// ============================================================================
// USER PROFILE MANAGEMENT ROUTES
// Personal account management, settings, and profile updates
// ============================================================================

/**
 * Profile Management Operations
 * 
 * User account management functionality:
 * - View and edit profile information
 * - Change password with validation
 * - Profile image upload and display
 * - Account settings and preferences
 */
Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
Route::post('/profile/edit', [ProfileController::class, 'updateProfile'])->name('profile.update');
Route::get('/profile/edit', [ProfileController::class, 'editProfile'])->name('profile-edit');
Route::get('/profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.changepass');
Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
Route::get('/profile/image/{id}', [ProfileController::class, 'showProfileImage'])->name('profile.image');

// ============================================================================
// TRADER DASHBOARD ROUTES
// Trader-specific functionality for shop and inventory management
// Requires trader role authentication and admin approval
// ============================================================================

/**
 * Main Trader Dashboard Pages
 * 
 * Core trader interface for business management:
 * - Main dashboard with metrics and overview
 * - Order management and fulfillment
 * - Product catalog management
 * - Sales analytics and reporting
 */
Route::get('/trader', [VendorController::class, 'dashboard'])->name('trader');
Route::get('/trader_order', [VendorController::class, 'orders'])->name('Trader Order');
Route::get('/trader_product', [VendorController::class, 'products'])->name('Trader Product');
Route::get('/trader_analytics', [VendorController::class, 'analytics'])->name('Trader Analytics');
Route::post('/trader/update-shop', [VendorController::class, 'updateShop'])->name('trader.updateShop');

/**
 * Advanced Trader Analytics and Reporting
 * 
 * Business intelligence and performance tracking:
 * - Sales performance analytics
 * - Customer review management
 * - Time-period specific reports
 * - Revenue and profit tracking
 */
Route::get('/trader/sales', [VendorController::class, 'analytics'])->name('trader.sales');
Route::get('/trader/reviews', [VendorController::class, 'reviews'])->name('trader.reviews');
Route::get('/trader/analytics/period/{period}', [VendorController::class, 'analyticsByPeriod'])->name('trader.analytics.period');

/**
 * Product Management CRUD Operations
 * 
 * Complete product lifecycle management:
 * - Create new products with images and details
 * - Edit existing product information
 * - Update product pricing and inventory
 * - Delete products with relationship handling
 */
Route::post('/trader/products', [VendorController::class, 'storeProduct'])->name('trader.products.store');
Route::get('/trader/products/{id}/edit', [VendorController::class, 'editProduct'])->name('trader.products.edit');
Route::put('/trader/products/{id}', [VendorController::class, 'updateProduct'])->name('trader.products.update');
Route::delete('/trader/products/{id}', [VendorController::class, 'deleteProduct'])->name('trader.products.delete');

/**
 * Order Management and Fulfillment
 * 
 * Order processing and status management:
 * - Update order status (pending, processing, completed)
 * - Check order status and details
 * - Order fulfillment workflow
 * - Customer communication
 */
Route::post('/trader/orders/{orderId}/update-status', [VendorController::class, 'updateOrderStatus'])->name('trader.orders.update-status');
Route::get('/trader/orders/{orderId}/status-check', [VendorController::class, 'checkOrderStatus'])->name('trader.orders.status-check');

/**
 * Product Image Management System
 * 
 * Advanced image handling for product catalog:
 * - Dynamic image serving with optimization
 * - Image fix tool for corrupted uploads
 * - Product image loading and editing
 * - Default image application
 * - Image format conversion and validation
 */
Route::get('/trader/product-image/{id}', [VendorController::class, 'viewProductImage'])->name('trader.product.image');
Route::get('/trader/image-fix', [VendorController::class, 'showImageFixTool'])->name('trader.image.fix');
Route::post('/trader/image-fix/product', [VendorController::class, 'loadProductForImageFix'])->name('trader.image.fix.product');
Route::post('/trader/image-fix/upload', [VendorController::class, 'uploadImageFix'])->name('trader.image.fix.upload');
Route::post('/trader/image-fix/default', [VendorController::class, 'applyDefaultImageFix'])->name('trader.image.fix.default');

/**
 * RFID Inventory Management System
 * 
 * IoT integration for automated inventory tracking:
 * - Start RFID relay for tag scanning
 * - Stop RFID relay and process data
 * - Real-time inventory updates
 * - Product-tag association management
 */
Route::post('/trader/rfid/run-relay', [App\Http\Controllers\VendorController::class, 'runRfidRelay'])->name('trader.rfid.run-relay');
Route::post('/trader/rfid/stop-relay', [App\Http\Controllers\VendorController::class, 'stopRfidRelay'])->name('trader.rfid.stop-relay');

// ============================================================================
// DEVELOPMENT & DEBUGGING ROUTES
// Testing, debugging, and development utility routes
// Should be disabled or restricted in production environment
// ============================================================================

/**
 * Legacy and Alternative Registration Routes
 * 
 * Alternative user registration interfaces:
 * - Legacy signup form interface
 * - Alternative user creation endpoints
 * - Backward compatibility routes
 */
Route::get('/signup_form', [User1Controller::class, 'showForm']);
Route::post('/user-form', [User1Controller::class, 'store']);

/**
 * Debug and Diagnostic Routes
 * 
 * Development tools for troubleshooting and testing:
 * - Raw product data inspection
 * - Image corruption fix utilities
 * - Order data validation
 * - Product image debugging
 */
Route::get('/debug/raw-product', [VendorController::class, 'showRawProduct'])->name('debug.raw-product');
Route::post('/debug/fix-image', [VendorController::class, 'fixProductImage'])->name('debug.fix-image');
Route::get('/debug/fix-image', [VendorController::class, 'fixProductImage'])->name('debug.fix-image.get');
Route::get('/debug/order-data', [VendorController::class, 'checkOrderData'])->name('debug.order-data');
Route::get('/trader/debug-images', [VendorController::class, 'debugProductImages'])->name('trader.debug-images');

/**
 * System Testing Routes
 * 
 * Infrastructure and integration testing:
 * - Oracle database connectivity testing
 * - BLOB data handling verification
 * - Email system functionality testing
 * - Performance and load testing endpoints
 */
Route::get('/test-oracle', function () {
    $results = DB::select('SELECT * FROM dual');
    return response()->json($results);
});
Route::get('/test-oracle-blob', [VendorController::class, 'testOracleBlob']);
Route::get('/test-email', function () {
    \Mail::to('shahprabesh777@gmail.com')->send(new OtpMail('123456'));
    return 'Email sent!';
});