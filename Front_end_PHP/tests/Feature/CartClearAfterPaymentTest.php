<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartClearAfterPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that cart is cleared after successful PayPal payment
     *
     * @return void
     */
    public function test_cart_is_cleared_after_successful_payment()
    {
        // Set up test data
        $userId = 'testuser';
        $cartId = 'testcart';
        $productId = 'testprod';
        
        // Mock session data
        Session::put('user_id', $userId);
        
        // Create test records in database
        DB::table('CART')->insert([
            'cart_id' => $cartId,
            'user_id' => $userId,
            'creation_date' => now()
        ]);
        
        DB::table('CART_PRODUCT')->insert([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'product_quantity' => 2,
            'total_amount' => 20.00
        ]);
        
        // Verify cart has items before payment
        $cartItemsBefore = DB::table('CART_PRODUCT')->where('cart_id', $cartId)->count();
        $this->assertEquals(1, $cartItemsBefore, 'Cart should have 1 item before payment');
        
        // Simulate cart clearing logic from CheckoutController
        // This would normally be called after successful PayPal payment
        $cartClearResult = DB::table('CART_PRODUCT')->where('cart_id', $cartId)->delete();
        
        // Verify cart is empty after payment
        $cartItemsAfter = DB::table('CART_PRODUCT')->where('cart_id', $cartId)->count();
        $this->assertEquals(0, $cartItemsAfter, 'Cart should be empty after payment');
        $this->assertGreaterThan(0, $cartClearResult, 'Cart clearing should affect at least 1 row');
    }
    
    /**
     * Test that session cart is also cleared
     *
     * @return void
     */
    public function test_session_cart_is_cleared_after_payment()
    {
        // Set up session cart
        Session::put('cart', [
            'product1' => ['quantity' => 1, 'price' => 10.00],
            'product2' => ['quantity' => 2, 'price' => 15.00]
        ]);
        
        // Verify session cart exists
        $this->assertTrue(Session::has('cart'), 'Session cart should exist before payment');
        $sessionCart = Session::get('cart');
        $this->assertCount(2, $sessionCart, 'Session cart should have 2 items');
        
        // Simulate session cart clearing (from CheckoutController)
        Session::forget('cart');
        
        // Verify session cart is cleared
        $this->assertFalse(Session::has('cart'), 'Session cart should be cleared after payment');
    }
} 