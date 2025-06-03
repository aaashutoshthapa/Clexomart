<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Review;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UpdatedFruitShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */    public function run(): void
    {
        // Using existing trader with ID user0001
        $shopOwner = DB::table('USER1')->where('user_id', 'user0001')->first();
        if (!$shopOwner) {
            $this->command->error('Trader user0001 not found. Please ensure this user exists before running the seeder.');
            return;
        }
        
        $this->command->info('Using existing trader user with ID: user0001');
        
        // Use existing users from user0002 to user0016
        $userIds = [];
        for ($i = 2; $i <= 16; $i++) {
            $userId = 'user' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $user = DB::table('USER1')->where('user_id', $userId)->first();
            if ($user) {
                $userIds[] = $userId;
                $this->command->info("Found existing user with ID: {$userId}");
            }
        }
        
        if (empty($userIds)) {
            $this->command->error('No customer users found in the database. Please ensure users exist before running the seeder.');
            return;
        }
        
        // Use existing shop with ID shop0001
        $shopId = 'shop0001';
        $shop = DB::table('SHOP')->where('shop_id', $shopId)->first();
        
        if (!$shop) {
            $this->command->error('Shop with ID shop0001 not found. Please ensure this shop exists before running the seeder.');
            return;
        }
        
        $this->command->info('Using existing shop with ID: shop0001');
          // Set a default category ID for products
        $categoryId = DB::table('CATEGORY')->where('category_name', 'Fresh Fruits')->value('category_id');
        if (!$categoryId) {
            $categoryId = DB::table('CATEGORY')->first()->category_id;
        }
        
        // Create 10 fruit products
        $fruits = [
            [
                'name' => 'Fresh Apples',
                'description' => 'Crisp and juicy apples freshly picked',
                'price' => 1.99,
                'stock' => 100
            ],
            [
                'name' => 'Organic Bananas',
                'description' => 'Sweet and ripe organic bananas',
                'price' => 1.49,
                'stock' => 150
            ],
            [
                'name' => 'Juicy Oranges',
                'description' => 'Sweet and tangy oranges full of vitamin C',
                'price' => 2.49,
                'stock' => 80
            ],
            [
                'name' => 'Premium Strawberries',
                'description' => 'Sweet red strawberries, perfect for desserts',
                'price' => 3.99,
                'stock' => 50
            ],
            [
                'name' => 'Ripe Avocados',
                'description' => 'Perfectly ripe and ready to eat avocados',
                'price' => 2.99,
                'stock' => 60
            ],
            [
                'name' => 'Sweet Pineapples',
                'description' => 'Tropical sweet pineapples',
                'price' => 4.99,
                'stock' => 40
            ],
            [
                'name' => 'Fresh Grapes',
                'description' => 'Seedless green grapes',
                'price' => 3.49,
                'stock' => 70
            ],
            [
                'name' => 'Organic Lemons',
                'description' => 'Zesty organic lemons',
                'price' => 1.29,
                'stock' => 90
            ],
            [
                'name' => 'Ripe Mangoes',
                'description' => 'Sweet and juicy mangoes',
                'price' => 2.99,
                'stock' => 45
            ],
            [
                'name' => 'Fresh Blueberries',
                'description' => 'Antioxidant-rich blueberries',
                'price' => 4.49,
                'stock' => 35
            ]
        ];
          $productIds = [];
        foreach ($fruits as $fruit) {            if (!DB::table('PRODUCT')->where('product_name', $fruit['name'])->exists()) {
                // Let Oracle generate product_id via trigger
                DB::table('PRODUCT')->insert([
                    'product_id' => null, // Let Oracle trigger generate ID
                    'product_name' => $fruit['name'],
                    'description' => $fruit['description'],
                    'unit_price' => $fruit['price'],
                    'price_after_discount' => $fruit['price'],
                    'stock' => $fruit['stock'],
                    'shop_id' => $shopId,
                    'category_id' => $categoryId
                ]);
                
                // Get the generated product ID
                $newProduct = DB::table('PRODUCT')
                    ->where('product_name', $fruit['name'])
                    ->first();
                    
                if ($newProduct) {
                    $productIds[] = $newProduct->product_id;
                    $this->command->info("Created product: {$fruit['name']} with ID: {$newProduct->product_id}");
                } else {
                    $this->command->error("Failed to create product: {$fruit['name']}");
                }
            } else {
                // Get existing product ID
                $existingProduct = DB::table('PRODUCT')
                    ->where('product_name', $fruit['name'])
                    ->first();
                $productIds[] = $existingProduct->product_id;
                $this->command->info("Using existing product: {$fruit['name']} with ID: {$existingProduct->product_id}");
            }
        }        // Create a single cart for each customer
        $cartIds = [];
        foreach ($userIds as $userId) {
            // Check if user already has a cart
            $existingCart = DB::table('CART')->where('user_id', $userId)->first();
            
            if ($existingCart) {
                $this->command->info("Using existing cart for user {$userId}");
                $cartIds[$userId] = $existingCart->cart_id;
            } else {
                // Let Oracle assign cart_id via the trigger (trg_cartid)
                DB::table('CART')->insert([
                    'user_id' => $userId,
                    'creation_date' => now()
                ]);
                
                // Get the newly created cart ID
                $newCart = DB::table('CART')
                    ->where('user_id', $userId)
                    ->orderBy('creation_date', 'desc')
                    ->first();
                    
                if ($newCart) {
                    $cartIds[$userId] = $newCart->cart_id;
                    $this->command->info("Created new cart for user {$userId}: {$newCart->cart_id}");
                } else {
                    $this->command->error("Failed to create cart for user {$userId}");
                }
            }
        }
        
        // Create historical orders (from past months)
        $this->createHistoricalOrders($shopId, $productIds, $cartIds);
        
        // Create recent reviews
        $this->createProductReviews($productIds);
        
        // Add this to the changes log
        $this->updateChangesLog();
        
        $this->command->info('Fresh Fruits Shop data seeded successfully!');
    }
    
    /**
     * Create orders spread over the last 6 months to have good analytics data
     */    private function createHistoricalOrders($shopId, $productIds, $cartIds)
    {
        // Use existing users for orders (using the same array keys as $cartIds)
        $customerIds = array_keys($cartIds);
        $orderCount = 0;
          // Use existing collection slots
        $slotIds = DB::table('COLLECTION_SLOT')->pluck('slot_id')->toArray();
        
        // If no slots exist, create them
        if (empty($slotIds)) {
            $this->command->info("No collection slots found. Creating new ones...");
            for ($i = 0; $i < 5; $i++) {
                // Let Oracle assign slot_id via the trigger (trg_slotid)
                DB::table('COLLECTION_SLOT')->insert([
                    'day' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'][array_rand(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])],
                    'time' => now()->addHours(rand(9, 17)),
                    'no_order' => rand(0, 10)
                ]);
            }
            // Get the newly created slot IDs
            $slotIds = DB::table('COLLECTION_SLOT')->pluck('slot_id')->toArray();
        }
        
        $this->command->info("Using " . count($slotIds) . " collection slots: " . implode(", ", $slotIds));          // Create orders over the last 6 months
        // Get existing order IDs
        $existingOrderIds = DB::table('ORDER1')->pluck('order_id')->toArray();
        $existingOrderStatuses = DB::table('ORDER_STATUS')->pluck('order_id')->toArray();
        
        // Pre-defined order IDs for consistency with your data
        $predefinedOrderIds = ['ord0004', 'ord0005', 'ord0006', 'ord0007', 'ord0008', 'ord0009', 
                              'ord0010', 'ord0011', 'ord0012', 'ord0013', 'ord0014', 'ord0015'];
        $orderIdIndex = 0;
        
        for ($month = 5; $month >= 0; $month--) {
            // More orders in recent months, fewer in older months
            $numberOfOrders = $month == 0 ? 15 : ($month == 1 ? 10 : 8); // Create more orders for better analytics data
            
            for ($i = 0; $i < $numberOfOrders; $i++) {
                $orderDate = now()->subMonths($month)->subDays(rand(0, 29));
                $customerId = $customerIds[array_rand($customerIds)];
                $cartId = $cartIds[$customerId];
                $slotId = $slotIds[array_rand($slotIds)];
                
                // Use pre-defined order IDs if available, otherwise let Oracle generate one
                $orderId = null;
                if ($orderIdIndex < count($predefinedOrderIds) && !in_array($predefinedOrderIds[$orderIdIndex], $existingOrderIds)) {
                    $orderId = $predefinedOrderIds[$orderIdIndex];
                    $orderIdIndex++;
                }
                
                // Get 1-3 random products for this order
                $randomKeys = array_rand($productIds, min(rand(1, 3), count($productIds)));
                $orderProducts = is_array($randomKeys) ? array_map(function($key) use ($productIds) {
                    return $productIds[$key];
                }, $randomKeys) : [$productIds[$randomKeys]];
                
                $totalAmount = 0;
                  // Add products to order total first without adding to cart_product
                foreach ($orderProducts as $prodId) {
                    $product = DB::table('PRODUCT')->where('product_id', $prodId)->first();
                    if ($product) {
                        $quantity = rand(1, 3);
                        $price = $product->unit_price;
                        $itemTotal = $price * $quantity;
                        $totalAmount += $itemTotal;
                    }
                }                // Create the order
                // If we're using a predefined order ID
                if ($orderId) {
                    DB::table('ORDER1')->insert([
                        'order_id' => $orderId,
                        'user_id' => $customerId,
                        'order_date' => $orderDate,
                        'payment_amount' => $totalAmount,
                        'cart_id' => $cartId,
                        'slot_id' => $slotId
                    ]);
                } else {
                    // Let Oracle generate order ID
                    DB::table('ORDER1')->insert([
                        'user_id' => $customerId,
                        'order_date' => $orderDate,
                        'payment_amount' => $totalAmount,
                        'cart_id' => $cartId,
                        'slot_id' => $slotId
                    ]);
                    
                    // Get the generated order ID - we need to retrieve the latest order
                    $latestOrder = DB::table('ORDER1')
                        ->where('user_id', $customerId)
                        ->where('cart_id', $cartId)
                        ->orderBy('order_date', 'desc')
                        ->first();
                    
                    if (!$latestOrder) {
                        $this->command->error("Failed to retrieve the created order for user {$customerId}");
                        continue;
                    }
                    
                    $orderId = $latestOrder->order_id;
                }
                $this->command->info("Created order: {$orderId} for user {$customerId}");                  // Add order status only if it doesn't exist
                if (!in_array($orderId, $existingOrderStatuses)) {
                    try {
                        DB::table('ORDER_STATUS')->insert([
                            'order_id' => $orderId,
                            'status' => 'completed',
                            'created_at' => $orderDate,
                            'updated_at' => $orderDate->copy()->addDays(rand(1, 3))
                        ]);
                        // Add this order ID to our tracking array to avoid duplicate inserts
                        $existingOrderStatuses[] = $orderId;
                    } catch (\Exception $e) {
                        $this->command->error("Failed to insert order status for order {$orderId}: " . $e->getMessage());
                    }
                }// Add payment record
                DB::table('PAYMENT')->insert([
                    'payment_id' => null, // Let Oracle trigger generate ID
                    'payment_method' => ['Credit Card', 'Debit Card', 'PayPal'][array_rand(['Credit Card', 'Debit Card', 'PayPal'])],
                    'payment_date' => $orderDate,
                    'user_id' => $customerId,
                    'order_id' => $orderId,
                    'payment_amount' => $totalAmount
                ]);                // Predefined items for specific orders
                $predefinedItems = [
                    'ord0004' => [
                        ['id' => 'ITEM5m8gs', 'product' => 'pro0005', 'qty' => 2],
                        ['id' => 'ITEMGia9F', 'product' => 'pro0006', 'qty' => 2]
                    ],
                    'ord0005' => [
                        ['id' => 'ITEM3Vrtj', 'product' => 'pro0003', 'qty' => 2],
                        ['id' => 'ITEMyhYuT', 'product' => 'pro0009', 'qty' => 1]
                    ],
                    'ord0006' => [
                        ['id' => 'ITEMdgdOm', 'product' => 'pro0004', 'qty' => 3],
                        ['id' => 'ITEMvmTKX', 'product' => 'pro0007', 'qty' => 1],
                        ['id' => 'ITEM0ZAZS', 'product' => 'pro0011', 'qty' => 1]
                    ],
                    'ord0007' => [
                        ['id' => 'ITEMsAwct', 'product' => 'pro0006', 'qty' => 2],
                        ['id' => 'ITEM1joF0', 'product' => 'pro0007', 'qty' => 2]
                    ]
                ];
                
                // Add products to the order through product_order
                if (isset($predefinedItems[$orderId])) {
                    // Use predefined order items
                    foreach ($predefinedItems[$orderId] as $item) {
                        $prodId = $item['product'];
                        $quantity = $item['qty'];
                        $orderItemId = $item['id'];
                        
                        // Add to PRODUCT_ORDER
                        DB::table('PRODUCT_ORDER')->insert([
                            'product_id' => $prodId,
                            'order_id' => $orderId
                        ]);
                        
                        // Get product info
                        $product = DB::table('PRODUCT')->where('product_id', $prodId)->first();
                        if ($product) {
                            // Add to ORDER_ITEM with predefined ID
                            try {
                                DB::table('ORDER_ITEM')->insert([
                                    'order_item_id' => $orderItemId,
                                    'order_id' => $orderId,
                                    'product_id' => $prodId,
                                    'quantity' => $quantity,
                                    'unit_price' => $product->unit_price
                                ]);
                            } catch (\Exception $e) {
                                $this->command->error("Failed to insert order item {$orderItemId}: " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    // Use random products for other orders
                    foreach ($orderProducts as $prodId) {
                        DB::table('PRODUCT_ORDER')->insert([
                            'product_id' => $prodId,
                            'order_id' => $orderId
                        ]);
                        
                        // Also add to ORDER_ITEM with quantity and price
                        $product = DB::table('PRODUCT')->where('product_id', $prodId)->first();
                        $quantity = rand(1, 3);
                        
                        // Generate a unique item ID since there's no Oracle trigger for ORDER_ITEM
                        $orderItemId = 'ITEM' . Str::random(5);
                        
                        DB::table('ORDER_ITEM')->insert([
                            'order_item_id' => $orderItemId,
                            'order_id' => $orderId,
                            'product_id' => $prodId,
                            'quantity' => $quantity,
                            'unit_price' => $product->unit_price
                        ]);
                    }
                }
                
                $orderCount++;
            }
        }
        
        $this->command->info("Created {$orderCount} historical orders with varying dates");
    }
    
    /**
     * Create product reviews for products
     */    private function createProductReviews($productIds)
    {
        // Get first 3 users for reviews
        $customerIds = DB::table('USER1')
            ->where('user_type', 'customer')
            ->limit(3)
            ->pluck('user_id')
            ->toArray();
            
        if (empty($customerIds)) {
            $this->command->error('No customer users found for reviews.');
            return;
        }
        
        $reviewCount = 0;
        
        foreach ($productIds as $index => $productId) {
            // First 5 products get reviews
            if ($index >= 5) continue;
            
            $numberOfReviews = rand(1, 3); // 1-3 reviews per product
            
            for ($i = 0; $i < $numberOfReviews; $i++) {                // Let Oracle generate review_id via trigger
                $reviewId = null;
                $customerId = $customerIds[array_rand($customerIds)];
                $reviewDate = now()->subDays(rand(1, 60));
                
                $reviewTexts = [
                    'Great product, very fresh!',
                    'Excellent quality and taste.',
                    'Will buy again, highly recommend!',
                    'Arrived fresh and lasted well.',
                    'Good value for money.'
                ];
                
                $reviewText = $reviewTexts[array_rand($reviewTexts)];
                
                DB::table('REVIEW')->insert([
                    'review_id' => $reviewId,
                    'product_id' => $productId,
                    'user_id' => $customerId,
                    'review_description' => $reviewText,
                    'review_date' => $reviewDate
                ]);
                
                $reviewCount++;
            }
        }
        
        $this->command->info("Created {$reviewCount} product reviews");
    }
    
    /**
     * Update the changes log file
     */
    private function updateChangesLog()
    {
        $logFile = base_path('changes.txt');
        $content = file_get_contents($logFile);      $newContent = $content . "\n\n4. Created UpdatedFruitShopSeeder on May 21, 2025:\n"
                    . "   - Used existing shop (shop0001) and trader (user0001)\n"
                    . "   - Used existing customer accounts (user0002 through user0016)\n"
                    . "   - Created one cart for each user\n" 
                    . "   - Created 10 different fruit products with descriptions, prices, and stock\n"
                    . "   - Generated historical orders spread across the last 6 months\n"
                    . "   - Used Oracle triggers for generating IDs\n"
                    . "   - Created product reviews for the first 5 products\n"
                    . "   - This seed data populates the analytics dashboard with meaningful data";
        
        file_put_contents($logFile, $newContent);
    }
}
