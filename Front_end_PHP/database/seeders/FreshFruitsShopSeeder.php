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

class FreshFruitsShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {        // Create a test user for the shop owner if it doesn't exist
        $shopOwner = DB::table('USER1')->where('user_id', 'trader001')->first();
        if (!$shopOwner) {
            DB::table('USER1')->insert([
                'user_id' => 'trader001',
                'first_name' => 'John',
                'last_name' => 'Fruitman',
                'email' => 'john@freshfruits.com',
                'password' => bcrypt('password123'),
                'address' => '123 Fruit Street',
                'postcode' => 'FR123',
                'contact_no' => '7700900001',
                'user_type' => 'trader'
            ]);
        }
        
        // Create test customer users
        $customers = [
            [
                'user_id' => 'cust001',
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'email' => 'alice@example.com',
                'password' => bcrypt('password123'),
                'address' => '1 Customer Road',
                'postcode' => 'C1 2AB',
                'contact_no' => '7700900100',
                'user_type' => 'customer'
            ],            [
                'user_id' => 'cust002',
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'email' => 'bob@example.com',
                'password' => bcrypt('password123'),
                'address' => '2 Customer Avenue',
                'postcode' => 'C2 3CD',
                'contact_no' => '7700900200',
                'user_type' => 'customer'
            ],
            [
                'user_id' => 'cust003',
                'first_name' => 'Charlie',
                'last_name' => 'Wilson',
                'email' => 'charlie@example.com',
                'password' => bcrypt('password123'),
                'address' => '3 Customer Lane',
                'postcode' => 'C3 4EF',
                'contact_no' => '7700900300',
                'user_type' => 'customer'
            ]
        ];
        
        foreach ($customers as $customer) {
            if (!DB::table('USER1')->where('user_id', $customer['user_id'])->exists()) {
                DB::table('USER1')->insert($customer);
            }
        }
        
        // Find or create a fruits category
        $fruitCategory = DB::table('CATEGORY')->where('category_name', 'Fresh Fruits')->first();
        if (!$fruitCategory) {
            $categoryId = 'CAT' . Str::random(5);
            DB::table('CATEGORY')->insert([
                'category_id' => $categoryId,
                'category_name' => 'Fresh Fruits',
                'description' => 'Fresh and organic fruits'
            ]);
            $fruitCategory = DB::table('CATEGORY')->where('category_id', $categoryId)->first();
        }
        
        // Create a Fresh Fruits shop
        $shopId = 'SHOP' . Str::random(4);
        if (!Shop::where('shop_name', 'Fresh Fruits Market')->exists()) {
            Shop::create([
                'shop_id' => $shopId,
                'shop_name' => 'Fresh Fruits Market',
                'shop_description' => 'Selling the freshest organic fruits in town',
                'user_id' => 'trader001',
                'category_id' => $fruitCategory->category_id,
                'shop_image_mimetype' => 'image/jpeg',
                'shop_image_filename' => 'fruits-shop.jpg'
            ]);
        } else {
            $shop = Shop::where('shop_name', 'Fresh Fruits Market')->first();
            $shopId = $shop->shop_id;
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
        foreach ($fruits as $fruit) {
            $productId = 'PROD' . Str::random(4);
            $productIds[] = $productId;
            
            if (!Product::where('product_name', $fruit['name'])->exists()) {
                Product::create([
                    'product_id' => $productId,
                    'product_name' => $fruit['name'],
                    'description' => $fruit['description'],
                    'unit_price' => $fruit['price'],
                    'price_after_discount' => $fruit['price'],
                    'stock' => $fruit['stock'],
                    'shop_id' => $shopId,
                    'category_id' => $fruitCategory->category_id
                ]);
            }
        }
        
        // Create historical orders (from past months)
        $this->createHistoricalOrders($shopId, $productIds);
        
        // Create recent reviews
        $this->createProductReviews($productIds);
        
        // Add this to the changes log
        $this->updateChangesLog();
    }
    
    /**
     * Create orders spread over the last 6 months to have good analytics data
     */
    private function createHistoricalOrders($shopId, $productIds)
    {
        $customerIds = ['cust001', 'cust002', 'cust003'];
        $orderCount = 0;
        
        // Create orders over the last 6 months
        for ($month = 5; $month >= 0; $month--) {
            // More orders in recent months, fewer in older months
            $numberOfOrders = (6 - $month) * 5; // 5, 10, 15, 20, 25, 30 orders per month
            
            for ($i = 0; $i < $numberOfOrders; $i++) {
                $orderDate = now()->subMonths($month)->subDays(rand(0, 29));
                $customerId = $customerIds[array_rand($customerIds)];
                
                // Create a new order
                $orderId = 'ORD' . Str::random(5);
                
                // Get 1-3 random products for this order
                $orderProducts = array_rand(array_flip($productIds), rand(1, 3));
                if (!is_array($orderProducts)) {
                    $orderProducts = [$orderProducts];
                }
                
                $totalAmount = 0;
                $orderProductData = [];
                
                foreach ($orderProducts as $prodId) {
                    $product = Product::find($prodId);
                    if ($product) {
                        $quantity = rand(1, 5);
                        $price = $product->unit_price;
                        $totalAmount += $price * $quantity;
                        
                        $orderProductData[] = [
                            'order_id' => $orderId,
                            'product_id' => $prodId,
                            'quantity' => $quantity,
                            'price_at_purchase' => $price
                        ];
                    }
                }
                
                // Create the order
                DB::table('ORDER1')->insert([
                    'order_id' => $orderId,
                    'user_id' => $customerId,
                    'order_date' => $orderDate,
                    'payment_amount' => $totalAmount,
                    'order_status' => 'completed'
                ]);
                
                // Add products to the order
                foreach ($orderProductData as $productOrder) {
                    DB::table('PRODUCT_ORDER')->insert($productOrder);
                }
                
                $orderCount++;
            }
        }
        
        $this->command->info("Created {$orderCount} historical orders with varying dates");
    }
    
    /**
     * Create product reviews for products
     */
    private function createProductReviews($productIds)
    {
        $customerIds = ['cust001', 'cust002', 'cust003'];
        $reviewCount = 0;
        
        foreach ($productIds as $productId) {
            $numberOfReviews = rand(0, 5); // 0-5 reviews per product
            
            for ($i = 0; $i < $numberOfReviews; $i++) {
                $reviewId = 'REV' . Str::random(5);
                $customerId = $customerIds[array_rand($customerIds)];
                $rating = rand(3, 5); // Most reviews are positive (3-5 stars)
                $reviewDate = now()->subDays(rand(1, 60));
                
                $reviewTexts = [
                    'Great product, very fresh!',
                    'Excellent quality and taste.',
                    'Will buy again, highly recommend!',
                    'Arrived fresh and lasted well.',
                    'Good value for money.',
                    'My family loved these.',
                    'Not bad, but could be fresher.',
                    'Pretty good overall.',
                    'Decent quality for the price.',
                    'Very satisfied with my purchase!'
                ];
                
                $reviewText = $reviewTexts[array_rand($reviewTexts)];
                
                DB::table('REVIEW')->insert([
                    'review_id' => $reviewId,
                    'product_id' => $productId,
                    'user_id' => $customerId,
                    'review_description' => $reviewText,
                    'review_date' => $reviewDate,
                    'rating' => $rating
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
        $content = file_get_contents($logFile);
        
        $newContent = $content . "\n\n4. Created FreshFruitsShopSeeder on May 21, 2025:
   - Added a 'Fresh Fruits Market' shop with trader001 user
   - Created 10 different fruit products with descriptions, prices, and stock
   - Generated 105 historical orders spread across the last 6 months
   - Created random product reviews with ratings
   - This seed data populates the analytics dashboard with meaningful data";
        
        file_put_contents($logFile, $newContent);
    }
}
