<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

// Check if OCI8 extension is loaded
if (!extension_loaded('oci8')) {
    trigger_error('OCI8 extension is required for Oracle database operations', E_USER_WARNING);
}

class VendorController extends Controller
{
    public function dashboard()
    {
        $userId = session('user_id');
        $shop = null;
        $categories = Category::all(); // Fetch all categories
        $shopCategory = null;
        $recentProducts = [];
        $recentOrders = [];

        try {
            \Log::info('Loading dashboard for trader user ID: ' . $userId);
            $shop = Shop::where('user_id', $userId)->first();

            if ($shop) {
                $shopId = $shop->shop_id;
                \Log::info('Shop found: ' . $shopId);
                
                // Get shop category
                if ($shop->category_id) {
                    $shopCategory = Category::find($shop->category_id);
                }
                
                // Get recent products using Oracle 11g-safe query
                $recentProductsSql = "
                    SELECT *
                    FROM (
                            SELECT  p.product_id,
                                    p.product_name,
                                    p.unit_price,
                                    p.stock,
                                    p.product_image_filename
                            FROM    PRODUCT p
                            WHERE   p.shop_id = :shop_id
                            ORDER BY p.product_id DESC
                    )
                    WHERE  ROWNUM <= 4
                ";
                
                \Log::info('Executing recent products query for shop: ' . $shopId);
                $recentProducts = \DB::select($recentProductsSql, ['shop_id' => $shopId]);
                \Log::info('Recent products count: ' . count($recentProducts));
                if (count($recentProducts) > 0) {
                    \Log::info('First product sample: ' . json_encode($recentProducts[0]));
                }
                
                // Get recent orders using Oracle 11g-safe query
                $recentOrdersSql = "
                    SELECT *
                    FROM (
                            SELECT  o.order_id,
                                    o.order_date,
                                    o.payment_amount,
                                    NVL(os.status, 'pending')          AS order_status,
                                    COUNT(oi.order_item_id)            AS item_count
                            FROM        ORDER1       o
                            JOIN        ORDER_ITEM   oi ON oi.order_id  = o.order_id
                            JOIN        PRODUCT      p  ON p.product_id = oi.product_id
                            LEFT JOIN   ORDER_STATUS os ON os.order_id  = o.order_id
                            WHERE       p.shop_id = :shop_id
                            GROUP BY    o.order_id,
                                        o.order_date,
                                        o.payment_amount,
                                        NVL(os.status,'pending')
                            ORDER BY    o.order_date DESC
                    )
                    WHERE  ROWNUM <= 4
                ";
                
                \Log::info('Executing recent orders query for shop: ' . $shopId);
                $recentOrders = \DB::select($recentOrdersSql, ['shop_id' => $shopId]);
                \Log::info('Recent orders count: ' . count($recentOrders));
                if (count($recentOrders) > 0) {
                    \Log::info('First order sample: ' . json_encode($recentOrders[0]));
                }
            }
        
            return view('trader/trader', compact(
                'shop', 
                'categories', 
                'shopCategory',
                'recentProducts',
                'recentOrders'
            ));
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return view('trader/trader', [
                'shop' => $shop,
                'categories' => $categories,
                'shopCategory' => $shopCategory,
                'recentProducts' => [],
                'recentOrders' => [],
                'error' => 'There was an error loading your dashboard. Please try again.'
            ]);
        }
    }

    public function updateShop(Request $request)
    {
        // Validate the request data
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_description' => 'required|string',
            'category_id' => 'required|string|exists:CATEGORY,category_id',
            'shop_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Get the current user ID from the session
        $userId = session('user_id');
        \Log::info('Updating shop for user: ' . $userId);
        
        try {
            // Find or create the shop for this user
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                \Log::info('Creating new shop for user: ' . $userId);
                // Create a new shop if one doesn't exist
                $seqVal = \DB::selectOne("SELECT seq_shopid.NEXTVAL val FROM dual")->val;
                $shopId = 'S' . str_pad($seqVal, 5, '0', STR_PAD_LEFT);
                
                $shop = new Shop();
                $shop->shop_id = $shopId;
                $shop->user_id = $userId;
            } else {
                \Log::info('Updating existing shop: ' . $shop->shop_id);
            }
            
            // Store the shop_id value to avoid issues with indirect modification
            $shopId = $shop->shop_id;
            
            // Update shop details
            $shop->shop_name = $request->shop_name;
            $shop->shop_description = $request->shop_description;
            $shop->category_id = $request->category_id;
            
            // Handle shop logo upload if provided
            if ($request->hasFile('shop_logo')) {
                $image = $request->file('shop_logo');
                \Log::info('Shop logo uploaded: ' . $image->getClientOriginalName() . ' (size: ' . $image->getSize() . ' bytes)');
                
                // Store image metadata
                $shop->shop_image_mimetype = $image->getMimeType();
                $shop->shop_image_filename = $image->getClientOriginalName();
                $shop->shop_image_lastupd = now();
                
                \Log::info('Updated image metadata: ' . $image->getMimeType() . ', ' . $image->getClientOriginalName());
            }
            
            // Save the shop details (excluding BLOB)
            $shop->save();
            \Log::info('Saved shop details successfully');
            
            // Handle the BLOB data separately if a new image was uploaded
            if ($request->hasFile('shop_logo')) {
                \Log::info('Processing BLOB data for image');
                try {
                    // Get a PDO connection for working with BLOBs
                    $pdo = \DB::connection()->getPdo();
                    
                    // Read file contents as a string (not as a resource)
                    $imageContents = file_get_contents($image->getPathname());
                    \Log::info('Read image contents, size: ' . strlen($imageContents) . ' bytes');
                    
                    // Prepare the update statement for the BLOB field
                    $stmt = $pdo->prepare("UPDATE shop SET logo = :b WHERE shop_id = :sid");
                    
                    // Bind parameters with string data (not resource)
                    $stmt->bindParam(':b', $imageContents, \PDO::PARAM_LOB);
                    $stmt->bindParam(':sid', $shopId);
                    
                    // Execute the update
                    $stmt->execute();
                    \Log::info('Successfully updated BLOB data in the database');
                } catch (\Exception $e) {
                    \Log::error('BLOB update error: ' . $e->getMessage());
                    throw $e; // Re-throw to be caught by the main catch block
                }
            }
            
            // Redirect back to the dashboard with a success message
            return redirect()->route('trader')->with('success', 'Shop details updated successfully!');
            
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Shop update error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Redirect back with error message and input data
            return redirect()->route('trader')
                ->with('error', 'Failed to update shop details. Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function products()
    {
        // Get the current user ID from the session
        $userId = session('user_id');
        
        // Get shop details for the current vendor/trader
        $shop = Shop::where('user_id', $userId)->first();
        
        // Get categories for reference but not for selection
        $categories = Category::all();
        
        // Load the shop with its category relationship for product listing
        if ($shop) {
            $shop->load('category');
        }
        
        // Get products for this shop using Eloquent but without loading BLOBs
        $products = [];
        if ($shop) {
            $shopId = $shop->getAttribute('shop_id');
            
            // First get basic product data using Eloquent
            $products = Product::where('shop_id', $shopId)
                ->orderBy('product_id', 'desc')
                ->get();
                
            // If we have products, use direct SQL to get the images
            if (count($products) > 0) {
                // Build an array of product IDs
                $productIds = $products->pluck('product_id')->toArray();
                
                // Use direct query to load image data for better Oracle BLOB handling
                try {
                    $pdo = \DB::connection()->getPdo();
                    
                    // Prepare placeholders for the IN clause
                    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                    
                    // Query to get image data
                    $sql = "SELECT product_id, PRODUCT_image, PRODUCT_IMAGE_MIMETYPE
                           FROM PRODUCT 
                           WHERE product_id IN ($placeholders)";
                    
                    $stmt = $pdo->prepare($sql);
                    
                    // Bind all product IDs as parameters
                    foreach ($productIds as $index => $id) {
                        $stmt->bindValue($index + 1, $id);
                    }
                    
                    $stmt->execute();
                    
                    // Create an image data lookup array
                    $imageData = [];
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        if (!empty($row['PRODUCT_IMAGE'])) {
                            $imageData[$row['PRODUCT_ID']] = [
                                'data' => $row['PRODUCT_IMAGE'],
                                'mime' => $row['PRODUCT_IMAGE_MIMETYPE'] ?: 'image/jpeg'
                            ];
                        }
                    }
                    
                    // Get RFID UIDs for all products
                    $rfidSql = "SELECT product_id, rfid FROM RFID_PRODUCT WHERE product_id IN ($placeholders)";
                    $rfidStmt = $pdo->prepare($rfidSql);
                    
                    // Bind all product IDs as parameters again
                    foreach ($productIds as $index => $id) {
                        $rfidStmt->bindValue($index + 1, $id);
                    }
                    
                    $rfidStmt->execute();
                    
                    // Create an RFID UID lookup array
                    $rfidData = [];
                    while ($row = $rfidStmt->fetch(\PDO::FETCH_ASSOC)) {
                        // Handle both uppercase and lowercase column names
                        $productId = $row['PRODUCT_ID'] ?? $row['product_id'] ?? null;
                        $rfid = $row['RFID'] ?? $row['rfid'] ?? null;
                        
                        if ($productId && $rfid) {
                            $rfidData[$productId] = $rfid;
                        }
                    }
                    
                    // Add image_data and rfid_uid attributes to each product
                    foreach ($products as $product) {
                        $productId = $product->product_id;
                        if (isset($imageData[$productId])) {
                            $product->setAttribute('image_data_base64', base64_encode($imageData[$productId]['data']));
                            $product->setAttribute('image_mime_type', $imageData[$productId]['mime']);
                        }
                        
                        if (isset($rfidData[$productId])) {
                            $product->setAttribute('rfid_uid', $rfidData[$productId]);
                        } else {
                            $product->setAttribute('rfid_uid', null);
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Error loading product images: ' . $e->getMessage());
                }
            }
        }
        
        return view('trader/trader_product', compact('products', 'categories', 'shop'));
    }

    public function orders()
    {
        // Get the current user ID from the session
        $userId = session('user_id');
        \Log::info('Loading orders for trader user ID: ' . $userId);
        
        // Get shop details for the current vendor/trader
        $shop = Shop::where('user_id', $userId)->first();
        
        if (!$shop) {
            return view('trader/trader_order', [
                'orders' => [],
                'message' => 'You need to setup your shop before viewing orders.'
            ]);
        }
        
        \Log::info('Shop found: ' . $shop->shop_id);
        
        // Get orders containing the trader's products
        $orders = [];
        
        try {
            // Direct query using Oracle PDO connection
            $pdo = \DB::connection()->getPdo();
            
            // Find all orders that contain products from this shop with detailed information
            $ordersSql = "
                SELECT  o.order_id,
                        o.order_date,
                        o.payment_amount,
                        u.user_id,
                        u.first_name || ' ' || u.last_name AS customer_name,
                        u.email,
                        os.status,
                        COUNT(oi.order_item_id) as total_items
                FROM      order1      o                              
                JOIN      user1       u  ON u.user_id  = o.user_id   
                JOIN      order_item  oi ON oi.order_id = o.order_id 
                JOIN      product     p ON p.product_id = oi.product_id
                LEFT JOIN ORDER_STATUS os ON o.order_id = os.order_id
                WHERE     p.shop_id = :shop_id                       
                GROUP BY  o.order_id, o.order_date, o.payment_amount, u.user_id, u.first_name, u.last_name, u.email, os.status
                ORDER BY  o.order_date DESC
            ";
            
            $ordersStmt = $pdo->prepare($ordersSql);
            $shopId = $shop->shop_id; // Get the shop_id first
            $ordersStmt->bindParam(':shop_id', $shopId, \PDO::PARAM_STR);
            $ordersStmt->execute();
            $ordersData = $ordersStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Debug the first row to see column names
            if (!empty($ordersData)) {
                \Log::info('Sample order data keys: ' . implode(', ', array_keys($ordersData[0])));
                \Log::info('First order data (sample): ' . json_encode($ordersData[0]));
            }
            
            \Log::info('Found ' . count($ordersData) . ' orders from query');
            
            if (empty($ordersData)) {
                return view('trader/trader_order', [
                    'orders' => [],
                    'message' => 'No orders found containing your products.'
                ]);
            }
            
            // Process each order
            foreach ($ordersData as $orderData) {
                // Oracle returns uppercase column names, but we need to make this case-insensitive
                $orderId = $orderData['order_id'] ?? $orderData['ORDER_ID'] ?? null;
                if (!$orderId) {
                    \Log::warning('Order ID not found in data: ' . json_encode($orderData));
                    continue;
                }
                
                \Log::info('Processing order: ' . $orderId);
                
                // Get items from this order that belong to this shop using parameterized query
                $orderItemsSql = "
                    SELECT 
                        oi.order_item_id, 
                        oi.product_id, 
                        oi.quantity, 
                        oi.unit_price,
                        oi.quantity * oi.unit_price as item_total, 
                        p.product_name
                    FROM ORDER_ITEM oi
                    JOIN PRODUCT p ON oi.product_id = p.product_id
                    WHERE oi.order_id = :order_id AND p.shop_id = :shop_id
                ";
                
                $orderItemsStmt = $pdo->prepare($orderItemsSql);
                $orderItemsStmt->bindParam(':order_id', $orderId, \PDO::PARAM_STR);
                $shopId = $shop->shop_id; // Get the shop_id first to avoid overloaded property issue
                $orderItemsStmt->bindParam(':shop_id', $shopId, \PDO::PARAM_STR);
                $orderItemsStmt->execute();
                $orderItems = $orderItemsStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // Debug the first item to see column names
                if (!empty($orderItems)) {
                    \Log::info('Sample order item data keys: ' . implode(', ', array_keys($orderItems[0])));
                    \Log::info('First order item (sample): ' . json_encode($orderItems[0]));
                }
                
                \Log::info('Found ' . count($orderItems) . ' items for order: ' . $orderId);
                
                // If no items found for this shop in this order, skip
                if (empty($orderItems)) {
                    \Log::warning('No items found for order ' . $orderId . ' in shop ' . $shop->shop_id);
                    continue;
                }
                
                // Format order items for display
                $orderProducts = [];
                $traderTotal = 0;
                
                foreach ($orderItems as $item) {
                    // Make case-insensitive by checking both lowercase and uppercase keys
                    $quantity = $item['quantity'] ?? $item['QUANTITY'] ?? 1;
                    $unitPrice = $item['unit_price'] ?? $item['UNIT_PRICE'] ?? 0;
                    $itemTotal = $item['item_total'] ?? $item['ITEM_TOTAL'] ?? ($quantity * $unitPrice);
                    $traderTotal += $itemTotal;
                    
                    $orderProducts[] = [
                        'PRODUCT_ID' => $item['product_id'] ?? $item['PRODUCT_ID'] ?? '',
                        'PRODUCT_NAME' => $item['product_name'] ?? $item['PRODUCT_NAME'] ?? '',
                        'UNIT_PRICE' => $unitPrice,
                        'QUANTITY' => $quantity,
                        'ITEM_TOTAL' => $itemTotal
                    ];
                }
                
                // Get order status: pending, processing, or completed
                $status = 'pending'; // Default status
                if (isset($orderData['status']) || isset($orderData['STATUS'])) {
                    $originalStatus = strtolower($orderData['status'] ?? $orderData['STATUS']);
                    if ($originalStatus == 'completed') {
                        $status = 'completed';
                    } else if ($originalStatus == 'processing') {
                        $status = 'processing';
                    } else {
                        $status = 'pending';
                    }
                }
                
                // Get customer name from the query or compose it from first and last name
                $customerName = $orderData['customer_name'] ?? $orderData['CUSTOMER_NAME'] ?? 
                    ((isset($orderData['first_name']) || isset($orderData['FIRST_NAME'])) ? 
                        ($orderData['first_name'] ?? $orderData['FIRST_NAME']) : '') . ' ' . 
                    ((isset($orderData['last_name']) || isset($orderData['LAST_NAME'])) ? 
                        ($orderData['last_name'] ?? $orderData['LAST_NAME']) : '');
                
                // Add to orders array in a case-insensitive manner
                $orders[] = [
                    'id' => $orderId,
                    'date' => date('M d, Y', strtotime($orderData['order_date'] ?? $orderData['ORDER_DATE'])),
                    'timestamp' => $orderData['order_date'] ?? $orderData['ORDER_DATE'],
                    'customer' => $customerName,
                    'email' => $orderData['email'] ?? $orderData['EMAIL'],
                    'total_amount' => $orderData['payment_amount'] ?? $orderData['PAYMENT_AMOUNT'],
                    'trader_amount' => $traderTotal,
                    'items' => $orderProducts,
                    'status' => $status
                ];
            }
            
            \Log::info('Processed and returning ' . count($orders) . ' orders for shop ' . $shop->shop_id);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching trader orders: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            
            return view('trader/trader_order', [
                'orders' => [],
                'error' => 'Error fetching orders: ' . $e->getMessage()
            ]);
        }
        
        // Dump order data to logs for debugging
        $this->dumpOrderData($orders);
        
        return view('trader/trader_order', [
            'orders' => $orders,
            'shop' => $shop
        ]);
    }

    public function analytics()
    {
        $userId = session('user_id');
        $shop = null;
        $analyticsData = [
            'today_sales' => 0,
            'today_orders' => 0,
            'yesterday_sales' => 0,
            'yesterday_orders' => 0,
            'this_month_sales' => 0,
            'this_month_orders' => 0,
            'last_month_sales' => 0,
            'last_month_orders' => 0,
            'total_sales_all_time' => 0,
            'total_orders_all_time' => 0,
            'average_order_value' => 0,
            'monthly_sales_trend' => [],
            'product_sales_distribution' => [],
            'top_selling_products' => [],
            'sales_by_day_of_week' => [],
            'order_status_distribution' => [],
            'average_items_per_order' => 0,
            'customer_retention_rate' => 0, // Placeholder
            'total_customers' => 0,
            'new_vs_returning_customers' => ['new' => 0, 'returning' => 0], // Placeholder
            'total_items_sold' => 0,
            'inventory_value' => 0,
            'debug_info' => [] // Adding debug info to pass to the view
        ];

        try {
            $pdo = \DB::connection()->getPdo();
            $shop = Shop::where('user_id', $userId)->first();

            if ($shop) {
                $shopId = $shop->shop_id;
                $analyticsData['debug_info']['shop_id'] = $shopId;
                
                // Check if tables exist and have data
                $tableChecks = [
                    'order_item' => "SELECT COUNT(*) AS COUNT_VALUE FROM order_item",
                    'order1' => "SELECT COUNT(*) AS COUNT_VALUE FROM order1",
                    'product' => "SELECT COUNT(*) AS COUNT_VALUE FROM product WHERE shop_id = :shop_id",
                    'order_status' => "SELECT COUNT(*) AS COUNT_VALUE FROM order_status",
                    'user1' => "SELECT COUNT(*) AS COUNT_VALUE FROM user1"
                ];
                
                $tableData = [];
                foreach ($tableChecks as $table => $sql) {
                    try {
                        $stmt = $pdo->prepare($sql);
                        if (strpos($sql, ':shop_id') !== false) {
                            $stmt->bindParam(':shop_id', $shopId);
                        }
                        $stmt->execute();
                        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                        // Oracle returns column names in uppercase
                        $tableData[$table] = $result['COUNT_VALUE'] ?? $result['count_value'] ?? 0;
                    } catch (\Exception $e) {
                        $tableData[$table] = "Error: " . $e->getMessage();
                    }
                }
                $analyticsData['debug_info']['table_counts'] = $tableData;
                
                // Check sample data from each key table
                try {
                    // Check if shop has any products
                    $productSql = "SELECT * FROM (SELECT product_id, product_name, unit_price, stock FROM product WHERE shop_id = :shop_id) WHERE ROWNUM <= 5";
                    $stmt = $pdo->prepare($productSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $productSamples = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['product_samples'] = $productSamples;
                    
                    // See if any orders contain products from this shop
                    $orderSql = "
                        SELECT * FROM (
                            SELECT DISTINCT o.order_id, o.order_date
                            FROM order1 o
                            JOIN order_item oi ON o.order_id = oi.order_id
                            JOIN product p ON oi.product_id = p.product_id
                            WHERE p.shop_id = :shop_id
                        ) WHERE ROWNUM <= 5
                    ";
                    $stmt = $pdo->prepare($orderSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $orderSamples = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['order_samples'] = $orderSamples;
                    
                    // Check for order items related to this shop's products
                    $orderItemSql = "
                        SELECT * FROM (
                            SELECT oi.order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price
                            FROM order_item oi
                            JOIN product p ON oi.product_id = p.product_id
                            WHERE p.shop_id = :shop_id
                        ) WHERE ROWNUM <= 5
                    ";
                    $stmt = $pdo->prepare($orderItemSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $orderItemSamples = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['order_item_samples'] = $orderItemSamples;
                    
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['sample_error'] = $e->getMessage();
                }
                
                // --- Oracle 11g Compatible SQL Queries ---
                
                // 1. Total Sales - All Time Revenue
                try {
                    $totalSalesSql = "
                        SELECT NVL(SUM(oi.quantity*oi.unit_price),0) AS total_sales_all 
                        FROM order_item oi 
                        JOIN product p ON p.product_id = oi.product_id 
                        WHERE p.shop_id = :shop_id
                    ";
                    $stmt = $pdo->prepare($totalSalesSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['total_sales_result'] = $result;
                    $analyticsData['total_sales_all_time'] = $result['TOTAL_SALES_ALL'] ?? ($result['total_sales_all'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['total_sales_error'] = $e->getMessage();
                }
                
                // 2. Total Orders - Orders Completed
                try {
                    $totalOrdersSql = "
                        SELECT COUNT(DISTINCT o.order_id) AS total_orders_all 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id = o.order_id 
                        JOIN product p ON p.product_id = oi.product_id 
                        WHERE p.shop_id = :shop_id
                    ";
                    $stmt = $pdo->prepare($totalOrdersSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['total_orders_result'] = $result;
                    $analyticsData['total_orders_all_time'] = $result['TOTAL_ORDERS_ALL'] ?? ($result['total_orders_all'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['total_orders_error'] = $e->getMessage();
                }
                
                // 3. Average Order - Average Order Value
                try {
                    $avgOrderValueSql = "
                        SELECT ROUND(
                            NVL(SUM(oi.quantity*oi.unit_price),0) / 
                            NULLIF(COUNT(DISTINCT o.order_id),0), 
                            2
                        ) AS avg_order_value 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id
                    ";
                    $stmt = $pdo->prepare($avgOrderValueSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['avg_order_value_result'] = $result;
                    $analyticsData['average_order_value'] = $result['AVG_ORDER_VALUE'] ?? ($result['avg_order_value'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['avg_order_value_error'] = $e->getMessage();
                }
                
                // 4. Items Sold - Total Units
                try {
                    $itemsSoldSql = "
                        SELECT NVL(SUM(oi.quantity),0) AS total_items_sold 
                        FROM order_item oi 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id
                    ";
                    $stmt = $pdo->prepare($itemsSoldSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['total_items_sold_result'] = $result;
                    $analyticsData['total_items_sold'] = $result['TOTAL_ITEMS_SOLD'] ?? ($result['total_items_sold'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['total_items_sold_error'] = $e->getMessage();
                }
                
                // 5. Customer Base - Unique Customers
                try {
                    $uniqueCustomersSql = "
                        SELECT COUNT(DISTINCT o.user_id) AS total_customers 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id
                    ";
                    $stmt = $pdo->prepare($uniqueCustomersSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['total_customers_result'] = $result;
                    $analyticsData['total_customers'] = $result['TOTAL_CUSTOMERS'] ?? ($result['total_customers'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['total_customers_error'] = $e->getMessage();
                }
                
                // 6. Order Composition - Items Per Order
                try {
                    $avgItemsPerOrderSql = "
                        SELECT ROUND(
                            NVL(SUM(oi.quantity),0) / 
                            NULLIF(COUNT(DISTINCT o.order_id),0), 
                            2
                        ) AS avg_items_per_order 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id
                    ";
                    $stmt = $pdo->prepare($avgItemsPerOrderSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['avg_items_per_order_result'] = $result;
                    $analyticsData['average_items_per_order'] = $result['AVG_ITEMS_PER_ORDER'] ?? ($result['avg_items_per_order'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['avg_items_per_order_error'] = $e->getMessage();
                }
                
                // 7. Inventory Value - Current Stock Value
                try {
                    $inventoryValueSql = "
                        SELECT NVL(SUM(p.stock * p.unit_price),0) AS inventory_value 
                        FROM product p 
                        WHERE p.shop_id=:shop_id
                    ";
                    $stmt = $pdo->prepare($inventoryValueSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['inventory_value_result'] = $result;
                    $analyticsData['inventory_value'] = $result['INVENTORY_VALUE'] ?? ($result['inventory_value'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['inventory_value_error'] = $e->getMessage();
                }
                
                // 8. Monthly Performance - This Month
                try {
                    $thisMonthSql = "
                        SELECT NVL(SUM(oi.quantity*oi.unit_price),0) AS this_month_sales 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id 
                        AND o.order_date >= TRUNC(SYSDATE,'MM')
                    ";
                    $stmt = $pdo->prepare($thisMonthSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['this_month_sales_result'] = $result;
                    $analyticsData['this_month_sales'] = $result['THIS_MONTH_SALES'] ?? ($result['this_month_sales'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['this_month_sales_error'] = $e->getMessage();
                }
                
                // 9. Monthly Performance - Last Month
                try {
                    $lastMonthSql = "
                        SELECT NVL(SUM(oi.quantity*oi.unit_price),0) AS last_month_sales 
                        FROM order1 o 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id 
                        AND o.order_date >= ADD_MONTHS(TRUNC(SYSDATE,'MM'),-1) 
                        AND o.order_date < TRUNC(SYSDATE,'MM')
                    ";
                    $stmt = $pdo->prepare($lastMonthSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $analyticsData['debug_info']['last_month_sales_result'] = $result;
                    $analyticsData['last_month_sales'] = $result['LAST_MONTH_SALES'] ?? ($result['last_month_sales'] ?? 0);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['last_month_sales_error'] = $e->getMessage();
                }
                
                // Order Status Distribution
                try {
                    $orderStatusSql = "
                        SELECT COALESCE(os.status,'Unknown') AS status,
                        COUNT(DISTINCT o.order_id) AS status_count 
                        FROM order1 o 
                        LEFT JOIN order_status os ON os.order_id=o.order_id 
                        JOIN order_item oi ON oi.order_id=o.order_id 
                        JOIN product p ON p.product_id=oi.product_id 
                        WHERE p.shop_id=:shop_id 
                        GROUP BY COALESCE(os.status,'Unknown')
                    ";
                    $stmt = $pdo->prepare($orderStatusSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $analyticsData['order_status_distribution'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['order_status_error'] = $e->getMessage();
                }
                
                // Monthly Sales Trend for Chart
                try {
                    $monthlyTrendSql = "
                        SELECT  TO_CHAR(cal.mth, 'MON YYYY', 'NLS_DATE_LANGUAGE=ENGLISH')  AS month_label,
                                NVL(SUM(oi.quantity * oi.unit_price), 0)                   AS monthly_sales
                        FROM   (                        /* ← calendar CTE, 6 months incl. current  */
                                 SELECT ADD_MONTHS(TRUNC(SYSDATE,'MM'), -LEVEL + 1) AS mth
                                 FROM   dual
                                 CONNECT BY LEVEL <= 6
                               ) cal
                        LEFT   JOIN order1      o  ON TRUNC(o.order_date,'MM') = cal.mth
                        LEFT   JOIN order_item  oi ON oi.order_id  = o.order_id
                        LEFT   JOIN product     p  ON p.product_id = oi.product_id
                        WHERE        p.shop_id  = :shop_id
                        GROUP  BY    cal.mth
                        ORDER  BY    cal.mth
                    ";
                    $stmt = $pdo->prepare($monthlyTrendSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $analyticsData['monthly_sales_trend'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['monthly_trend_error'] = $e->getMessage();
                }
                
                // Product Sales Distribution for pie chart
                try {
                    $productSalesSql = "
                        SELECT 
                            pr.product_name AS \"PRODUCT_NAME\",
                            SUM(oi.quantity * oi.unit_price) AS \"PRODUCT_SALES\"
                        FROM PRODUCT pr
                        JOIN ORDER_ITEM oi ON pr.product_id = oi.product_id
                        WHERE pr.shop_id = :shop_id
                        GROUP BY pr.product_name
                        ORDER BY SUM(oi.quantity * oi.unit_price) DESC
                    ";
                    $stmt = $pdo->prepare($productSalesSql);
                    $stmt->bindParam(':shop_id', $shopId);
                    $stmt->execute();
                    $analyticsData['product_sales_distribution'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    $analyticsData['debug_info']['product_sales_error'] = $e->getMessage();
                }
            } else {
                $analyticsData['debug_info']['shop_error'] = 'No shop found for user ID: ' . $userId;
            }
        } catch (\Exception $e) {
            $analyticsData['debug_info']['main_error'] = $e->getMessage();
        }

        return view('trader/trader_analytics', compact('analyticsData', 'shop'));
    }


    public function sales()
    {
        // Redirect to analytics as we're merging these pages
        return redirect()->route('Trader Analytics');
    }

    public function reviews()
    {
        try {
            // Get the current user's shop
            $userId = session('user_id');
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return view('trader.trader-review', [
                    'reviews' => [],
                    'shop' => null,
                    'error' => 'Please set up your shop first to view customer reviews.'
                ]);
            }
            
            // Get the shop ID
            $shopId = $shop->shop_id;
            
            // Get reviews with the provided SQL query
            $reviewsSql = "
                SELECT
                    r.review_id,
                    p.product_id,
                    p.product_name,
                    r.review_description,
                    -- optional: r.rating_value,
                    TO_CHAR(r.review_date, 'YYYY-MM-DD') AS review_date,
                    u.first_name || ' ' || u.last_name   AS reviewer
                FROM   review  r
                JOIN   product p ON p.product_id = r.product_id
                JOIN   user1   u ON u.user_id    = r.user_id
                WHERE  p.shop_id = :shop_id
                ORDER  BY r.review_date DESC
            ";
            
            $pdo = \DB::connection()->getPdo();
            $stmt = $pdo->prepare($reviewsSql);
            $stmt->bindParam(':shop_id', $shopId);
            $stmt->execute();
            $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Log some debugging information
            \Log::info('Fetched ' . count($reviews) . ' reviews for shop: ' . $shopId);
            if (count($reviews) > 0) {
                \Log::info('First review sample: ' . json_encode($reviews[0]));
            }
            
            // Return the view with the reviews
            return view('trader.trader-review', [
                'reviews' => $reviews,
                'shop' => $shop
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching reviews: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return view('trader.trader-review', [
                'reviews' => [],
                'error' => 'Error fetching reviews: ' . $e->getMessage()
            ]);
        }
    }

    public function settings()
    {
        // This method can be removed since we're removing the settings page
        return redirect()->route('trader');
    }
    
    public function getAnalyticsByPeriod($period)
    {
        $userId = session('user_id');
        $responseData = [
            'sales' => 0,
            'orders' => 0,
            'avgOrderValue' => 0,
            'salesChange' => 0,
            'ordersChange' => 0,
            'avgOrderChange' => 0,
            'productSalesDistribution' => [],
            'period' => $period
        ];

        try {
            $pdo = \DB::connection()->getPdo();
            $shop = Shop::where('user_id', $userId)->first();

            if ($shop) {
                $shopId = $shop->shop_id;
                
                // Define date ranges based on the period
                $today = date('Y-m-d');
                $startDate = null;
                $compareStartDate = null;
                $compareEndDate = null;
                
                switch ($period) {
                    case 'daily':
                        // Today's data
                        $startDate = $today . ' 00:00:00';
                        // Yesterday for comparison
                        $compareStartDate = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
                        $compareEndDate = $startDate;
                        break;
                    
                    case 'weekly':
                        // Current week (starting from Sunday or Monday based on locale)
                        $startDate = date('Y-m-d', strtotime('this week')) . ' 00:00:00';
                        // Last week for comparison
                        $compareStartDate = date('Y-m-d', strtotime('last week')) . ' 00:00:00';
                        $compareEndDate = $startDate;
                        break;
                    
                    case 'monthly':
                        // Current month
                        $startDate = date('Y-m-01') . ' 00:00:00';
                        // Last month for comparison
                        $compareStartDate = date('Y-m-01', strtotime('first day of last month')) . ' 00:00:00';
                        $compareEndDate = $startDate;
                        break;
                    
                    default:
                        // Invalid period, use today as default
                        $startDate = $today . ' 00:00:00';
                        $compareStartDate = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
                        $compareEndDate = $startDate;
                }
                
                // Get current period data
                $currentSql = "
                    SELECT 
                        SUM(oi.item_total) AS TOTAL_SALES,
                        COUNT(DISTINCT o.order_id) AS TOTAL_ORDERS
                    FROM ORDER1 o
                    JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
                    JOIN PRODUCT p ON oi.product_id = p.product_id
                    WHERE p.shop_id = :shop_id AND o.order_date >= TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')
                ";
                
                $currentStmt = $pdo->prepare($currentSql);
                $currentStmt->bindParam(':shop_id', $shopId);
                $currentStmt->bindParam(':start_date', $startDate);
                
                try {
                    $currentStmt->execute();
                    $currentData = $currentStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    $responseData['sales'] = $currentData['TOTAL_SALES'] ?? 0;
                    $responseData['orders'] = $currentData['TOTAL_ORDERS'] ?? 0;
                    
                    // Calculate average order value
                    if ($responseData['orders'] > 0) {
                        $responseData['avgOrderValue'] = $responseData['sales'] / $responseData['orders'];
                    }
                    
                    // Get comparison period data
                    $compareSql = "
                        SELECT 
                            SUM(oi.item_total) AS TOTAL_SALES,
                            COUNT(DISTINCT o.order_id) AS TOTAL_ORDERS
                        FROM ORDER1 o
                        JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
                        JOIN PRODUCT p ON oi.product_id = p.product_id
                        WHERE p.shop_id = :shop_id 
                            AND o.order_date >= TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')
                    ";
                    
                    if ($compareEndDate) {
                        $compareSql .= " AND o.order_date < TO_DATE(:end_date, 'YYYY-MM-DD HH24:MI:SS')";
                    }
                    
                    $compareStmt = $pdo->prepare($compareSql);
                    $compareStmt->bindParam(':shop_id', $shopId);
                    $compareStmt->bindParam(':start_date', $compareStartDate);
                    if ($compareEndDate) {
                        $compareStmt->bindParam(':end_date', $compareEndDate);
                    }
                    
                    $compareStmt->execute();
                    $compareData = $compareStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    // Calculate percentage changes
                    $compareSales = $compareData['TOTAL_SALES'] ?? 0;
                    $compareOrders = $compareData['TOTAL_ORDERS'] ?? 0;
                    
                    // Avoid division by zero
                    if ($compareSales > 0) {
                        $responseData['salesChange'] = round((($responseData['sales'] - $compareSales) / $compareSales) * 100, 1);
                    } else {
                        $responseData['salesChange'] = $responseData['sales'] > 0 ? 100 : 0;
                    }
                    
                    if ($compareOrders > 0) {
                        $responseData['ordersChange'] = round((($responseData['orders'] - $compareOrders) / $compareOrders) * 100, 1);
                    } else {
                        $responseData['ordersChange'] = $responseData['orders'] > 0 ? 100 : 0;
                    }
                    
                    // Calculate average order values
                    $currentAvgOrder = $responseData['orders'] > 0 ? ($responseData['sales'] / $responseData['orders']) : 0;
                    $compareAvgOrder = $compareOrders > 0 ? ($compareSales / $compareOrders) : 0;
                    
                    // Update average order value in response data
                    $responseData['avgOrderValue'] = $currentAvgOrder;
                    
                    // Calculate average order change
                    if ($compareAvgOrder > 0) {
                        $responseData['avgOrderChange'] = round((($currentAvgOrder - $compareAvgOrder) / $compareAvgOrder) * 100, 1);
                    } else {
                        $responseData['avgOrderChange'] = $currentAvgOrder > 0 ? 100 : 0;
                    }
                    
                    // Also get trending products for the period
                    $trendingProductsSql = "
                        SELECT 
                            p.product_name AS \"PRODUCT_NAME\",
                            NVL(SUM(oi.quantity * oi.unit_price), 0) AS \"PRODUCT_SALES\"
                        FROM ORDER_ITEM oi 
                        JOIN PRODUCT p ON p.product_id = oi.product_id
                        JOIN ORDER1 o ON o.order_id = oi.order_id
                        WHERE p.shop_id = :shop_id
                          AND o.order_date >= TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')
                        GROUP BY p.product_name
                        ORDER BY SUM(oi.quantity * oi.unit_price) DESC
                    ";
                    
                    $trendingStmt = $pdo->prepare($trendingProductsSql);
                    $trendingStmt->bindParam(':shop_id', $shopId);
                    $trendingStmt->bindParam(':start_date', $startDate);
                    $trendingStmt->execute();
                    
                    // Add top selling products by quantity
                    $topSellingProductsSql = "
                        SELECT * FROM (
                            SELECT 
                                p.product_name AS \"product_name\",
                                SUM(oi.quantity) AS \"total_quantity_sold\",
                                p.product_image_filename AS \"product_image_filename\",
                                p.stock AS \"current_stock\"
                            FROM PRODUCT p
                            JOIN ORDER_ITEM oi ON oi.product_id = p.product_id
                            JOIN ORDER1 o ON o.order_id = oi.order_id
                            WHERE p.shop_id = :shop_id
                              AND o.order_date >= TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')
                            GROUP BY p.product_name, p.product_image_filename, p.stock
                            ORDER BY SUM(oi.quantity) DESC
                        ) WHERE ROWNUM <= 5
                    ";
                    
                    $topSellingStmt = $pdo->prepare($topSellingProductsSql);
                    $topSellingStmt->bindParam(':shop_id', $shopId);
                    $topSellingStmt->bindParam(':start_date', $startDate);
                    $topSellingStmt->execute();
                    
                    // Get trending products and top selling products
                    $trendingProducts = $trendingStmt->fetchAll(\PDO::FETCH_ASSOC);
                    $topSellingProducts = $topSellingStmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    // Handle case sensitivity for product data
                    $formattedTrendingProducts = [];
                    foreach ($trendingProducts as $product) {
                        $formattedTrendingProducts[] = [
                            'PRODUCT_NAME' => $product['PRODUCT_NAME'] ?? $product['product_name'] ?? 'Unknown Product',
                            'PRODUCT_SALES' => $product['PRODUCT_SALES'] ?? $product['product_sales'] ?? 0
                        ];
                    }
                    
                    $formattedTopSellingProducts = [];
                    foreach ($topSellingProducts as $product) {
                        $formattedTopSellingProducts[] = [
                            'product_name' => $product['product_name'] ?? $product['PRODUCT_NAME'] ?? 'Unknown Product',
                            'total_quantity_sold' => $product['total_quantity_sold'] ?? $product['TOTAL_QUANTITY_SOLD'] ?? 0,
                            'current_stock' => $product['current_stock'] ?? $product['CURRENT_STOCK'] ?? 0
                        ];
                    }
                    
                    // Add product sales distribution to response for the pie chart
                    $responseData['productSalesDistribution'] = $formattedTrendingProducts;
                    
                    // Format top 5 products by quantity
                    $responseData['topSellingProducts'] = $formattedTopSellingProducts;
                    $responseData['success'] = true;
                    
                    // Add sales trend data for the selected period
                    $salesTrendSql = "";
                    
                    switch ($period) {
                        case 'daily':
                            // Hourly trend for today
                            $salesTrendSql = "
                                WITH hour_data AS (
                                    SELECT LEVEL-1 as hour_num,
                                           TO_CHAR(TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS') + (LEVEL-1)/24, 'HH24:MI') as hour_label
                                    FROM dual
                                    CONNECT BY LEVEL <= 24
                                )
                                SELECT 
                                    hd.hour_label AS \"TIME_LABEL\",
                                    NVL(SUM(oi.quantity * oi.unit_price), 0) AS \"SALES_AMOUNT\"
                                FROM hour_data hd
                                LEFT JOIN ORDER1 o ON TO_CHAR(o.order_date, 'HH24') = TO_CHAR(TO_DATE(hd.hour_label, 'HH24:MI'), 'HH24')
                                    AND TRUNC(o.order_date) = TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS'))
                                LEFT JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
                                LEFT JOIN PRODUCT p ON oi.product_id = p.product_id AND p.shop_id = :shop_id
                                GROUP BY hd.hour_label, hd.hour_num
                                ORDER BY hd.hour_num ASC
                            ";
                            break;
                            
                        case 'weekly':
                            // Daily trend for current week
                            $salesTrendSql = "
                                WITH day_data AS (
                                    SELECT 
                                        TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')) + LEVEL - 1 AS day_date,
                                        TO_CHAR(TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD HH24:MI:SS')) + LEVEL - 1, 'DY, DD-MON') AS day_label
                                    FROM dual
                                    CONNECT BY LEVEL <= 7
                                )
                                SELECT 
                                    dd.day_label AS \"TIME_LABEL\",
                                    NVL(SUM(oi.quantity * oi.unit_price), 0) AS \"SALES_AMOUNT\"
                                FROM day_data dd
                                LEFT JOIN ORDER1 o ON TRUNC(o.order_date) = dd.day_date
                                LEFT JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
                                LEFT JOIN PRODUCT p ON oi.product_id = p.product_id AND p.shop_id = :shop_id
                                GROUP BY dd.day_label, dd.day_date
                                ORDER BY dd.day_date ASC
                            ";
                            break;
                            
                        case 'monthly':
                        default:
                            // Daily trend for current month
                            $daysInMonth = date('t', strtotime($startDate));
                            $salesTrendSql = "
                                WITH day_data AS (
                                    SELECT 
                                        TO_DATE(:month_start, 'YYYY-MM-DD HH24:MI:SS') + LEVEL - 1 AS day_date,
                                        TO_CHAR(TO_DATE(:month_start, 'YYYY-MM-DD HH24:MI:SS') + LEVEL - 1, 'DD-MON') AS day_label
                                    FROM dual
                                    CONNECT BY LEVEL <= :days_in_month
                                )
                                SELECT 
                                    dd.day_label AS \"TIME_LABEL\",
                                    NVL(SUM(oi.quantity * oi.unit_price), 0) AS \"SALES_AMOUNT\"
                                FROM day_data dd
                                LEFT JOIN ORDER1 o ON TRUNC(o.order_date) = dd.day_date
                                LEFT JOIN ORDER_ITEM oi ON o.order_id = oi.order_id
                                LEFT JOIN PRODUCT p ON oi.product_id = p.product_id AND p.shop_id = :shop_id
                                GROUP BY dd.day_label, dd.day_date
                                ORDER BY dd.day_date ASC
                            ";
                            break;
                    }
                    
                    // Execute the trend query
                    $trendStmt = $pdo->prepare($salesTrendSql);
                    $trendStmt->bindParam(':shop_id', $shopId);
                    $trendStmt->bindParam(':start_date', $startDate);
                    
                    // Add additional parameters for monthly query
                    if ($period === 'monthly') {
                        $monthStart = date('Y-m-01') . ' 00:00:00'; // First day of current month
                        $trendStmt->bindParam(':month_start', $monthStart);
                        $trendStmt->bindParam(':days_in_month', $daysInMonth, \PDO::PARAM_INT);
                    }
                    
                    $trendStmt->execute();
                    $trendData = $trendStmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    // Format trend data for chart
                    $salesTrend = [
                        'labels' => [],
                        'values' => []
                    ];
                    
                    foreach ($trendData as $point) {
                        $salesTrend['labels'][] = $point['TIME_LABEL'] ?? $point['time_label'] ?? '';
                        $salesTrend['values'][] = floatval($point['SALES_AMOUNT'] ?? $point['sales_amount'] ?? 0);
                    }
                    
                    $responseData['salesTrend'] = $salesTrend;
            
        } catch (\Exception $e) {
                    \Log::error('Period analytics fetch error: ' . $e->getMessage());
                    $responseData['error'] = 'Error fetching data for the selected period.';
                }
            } else {
                $responseData['error'] = 'Shop not found for this trader.';
            }
            } catch (\Exception $e) {
            \Log::error('Period analytics error: ' . $e->getMessage());
            $responseData['error'] = 'System error while retrieving analytics data.';
        }
        
        return response()->json($responseData);
    }
    
    /**
     * Helper function to dump order data to logs for debugging
     */
    private function dumpOrderData($orders) 
    {
        \Log::info('======= DEBUG ORDER DATA =======');
        \Log::info('Total orders being passed to view: ' . count($orders));
        
        if (count($orders) > 0) {
            \Log::info('First order keys: ' . implode(', ', array_keys($orders[0])));
            \Log::info('First order data: ' . json_encode($orders[0]));
            
            if (!empty($orders[0]['items'])) {
                \Log::info('First order item count: ' . count($orders[0]['items']));
                if (count($orders[0]['items']) > 0) {
                    \Log::info('First item data: ' . json_encode($orders[0]['items'][0]));
                }
                
                // Calculate total order value for first order
                $totalValue = 0;
                foreach ($orders[0]['items'] as $item) {
                    $totalValue += $item['QUANTITY'] * $item['UNIT_PRICE'];
                }
                \Log::info('First order calculated total: ' . $totalValue . ' vs stored total: ' . $orders[0]['trader_amount']);
            } else {
                \Log::info('No items in first order');
            }
            
            // Log distribution of order statuses
            $statusCounts = ['pending' => 0, 'completed' => 0, 'other' => 0];
            foreach ($orders as $order) {
                $status = $order['status'] ?? 'other';
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                } else {
                    $statusCounts['other']++;
                }
            }
            \Log::info('Order status distribution: ' . json_encode($statusCounts));
        }
        \Log::info('======= END DEBUG ORDER DATA =======');
    }

    /**
     * Update the status of an order
     *
     * @param Request $request
     * @param string $orderId
     * @return \Illuminate\Http\Response
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|in:pending,processing,completed'
        ]);

        $newStatus = $request->input('status');
        $userId = session('user_id');
        
        try {
            // Get shop details for the current vendor/trader
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return redirect()->back()->with('error', 'Shop not found for this trader.');
            }
            
            $shopId = $shop->shop_id;
            
            // Check if this order contains products from this shop
            $pdo = \DB::connection()->getPdo();
            $checkOrderSql = "
                SELECT COUNT(*) AS order_count
                FROM order1 o
                JOIN order_item oi ON o.order_id = oi.order_id
                JOIN product p ON oi.product_id = p.product_id
                WHERE o.order_id = :order_id
                AND p.shop_id = :shop_id
            ";
            
            $checkOrderStmt = $pdo->prepare($checkOrderSql);
            $checkOrderStmt->bindParam(':order_id', $orderId);
            $checkOrderStmt->bindParam(':shop_id', $shopId);
            $checkOrderStmt->execute();
            $result = $checkOrderStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Fix: Handle case insensitivity for Oracle's uppercase column names
            $orderCount = $result['order_count'] ?? $result['ORDER_COUNT'] ?? 0;
            
            if (!$result || $orderCount < 1) {
                return redirect()->back()->with('error', 'Order not found or does not contain your products.');
            }
            
            // Update the order status
            $updateStatusSql = "
                MERGE INTO order_status 
                USING dual 
                ON (order_id = :order_id)
                WHEN MATCHED THEN
                    UPDATE SET status = :status, updated_at = SYSDATE
                WHEN NOT MATCHED THEN
                    INSERT (order_id, status, created_at, updated_at)
                    VALUES (:order_id, :status, SYSDATE, SYSDATE)
            ";
            
            $updateStmt = $pdo->prepare($updateStatusSql);
            $updateStmt->bindParam(':order_id', $orderId);
            $updateStmt->bindParam(':status', $newStatus);
            $updateStmt->execute();
            
            // If request is AJAX, return JSON response
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order status updated successfully',
                    'status' => $newStatus
                ]);
            }
            
            // Otherwise, return with success message
            return redirect()->back()->with('success', 'Order status updated successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Error updating order status: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating order status: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error updating order status: ' . $e->getMessage());
        }
    }

    /**
     * Check the current status of an order
     *
     * @param string $orderId
     * @return \Illuminate\Http\Response
     */
    public function checkOrderStatus($orderId)
    {
        $userId = session('user_id');
        
        try {
            // Get shop details for the current vendor/trader
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return response()->json(['error' => 'Shop not found'], 404);
            }
            
            $shopId = $shop->shop_id;
            
            // Check the order status
            $pdo = \DB::connection()->getPdo();
            $statusSql = "
                SELECT os.status
                FROM order_status os
                JOIN order1 o ON os.order_id = o.order_id
                JOIN order_item oi ON o.order_id = oi.order_id
                JOIN product p ON oi.product_id = p.product_id
                WHERE o.order_id = :order_id
                AND p.shop_id = :shop_id
            ";
            
            $statusStmt = $pdo->prepare($statusSql);
            $statusStmt->bindParam(':order_id', $orderId);
            $statusStmt->bindParam(':shop_id', $shopId);
            $statusStmt->execute();
            $result = $statusStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result) {
                return response()->json(['status' => 'pending'], 200); // Default to pending if no status found
            }
            
            // Fix: Handle case insensitivity for Oracle's uppercase column names
            $status = $result['status'] ?? $result['STATUS'] ?? 'pending';
            
            return response()->json(['status' => strtolower($status)], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error checking order status: ' . $e->getMessage());
            return response()->json(['error' => 'Error checking order status'], 500);
        }
    }

    /**
     * Display a product image
     *
     * @param string $id The product ID
     * @return \Illuminate\Http\Response
     */
    public function viewProductImage($id)
    {
        try {
            // Fetch product from database
            $sql = "
                SELECT 
                    product_image AS image_blob,
                    product_name,
                    product_image_mimetype
                FROM 
                    product
                WHERE 
                    product_id = :product_id
            ";
            
            $pdo = \DB::connection()->getPdo();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':product_id', $id);
            $stmt->execute();
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Normalize array keys to lowercase for consistency
            if ($product) {
                $product = array_change_key_case($product, CASE_LOWER);
            }
            
            // Default image path with more careful path handling
            $defaultImagePath = public_path('images/default-product.jpg');
            
            // Create default image directory if it doesn't exist
            $imagesDir = public_path('images');
            if (!file_exists($imagesDir)) {
                mkdir($imagesDir, 0755, true);
            }
            
            // Create a default image if it doesn't exist
            if (!file_exists($defaultImagePath)) {
                // Check if GD extension is available
                if (extension_loaded('gd')) {
                    // Create a simple default image
                    $width = 200;
                    $height = 200;
                    $image = \imagecreatetruecolor($width, $height);
                    
                    // Set a light gray background
                    $bgColor = \imagecolorallocate($image, 240, 240, 240);
                    \imagefill($image, 0, 0, $bgColor);
                    
                    // Add a border
                    $borderColor = \imagecolorallocate($image, 200, 200, 200);
                    \imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
                    
                    // Add text
                    $textColor = \imagecolorallocate($image, 100, 100, 100);
                    $text = "No Image";
                    $font = 5; // Built-in font
                    $textWidth = \imagefontwidth($font) * strlen($text);
                    $textHeight = \imagefontheight($font);
                    $x = ($width - $textWidth) / 2;
                    $y = ($height - $textHeight) / 2;
                    \imagestring($image, $font, $x, $y, $text, $textColor);
                    
                    // Save the image
                    \imagejpeg($image, $defaultImagePath, 90);
                    \imagedestroy($image);
                } else {
                    // If GD is not available, create a simple text file as placeholder
                    $defaultPlaceholder = public_path('images/default.png');
                    if (file_exists($defaultPlaceholder)) {
                        file_put_contents($defaultImagePath, file_get_contents($defaultPlaceholder));
                    } else {
                        // Create an empty image file as last resort
                        file_put_contents($defaultImagePath, '');
                    }
                }
            }
            
            if (!$product || empty($product['image_blob'])) {
                return response()->file($defaultImagePath);
            }
            
            $mimeType = $product['product_image_mimetype'] ?? 'image/jpeg';
            return response($product['image_blob'])->header('Content-Type', $mimeType);
        } catch (\Exception $e) {
            // Provide a basic fallback response when everything fails
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                    <rect width="200" height="200" fill="#f0f0f0" />
                    <text x="50%" y="50%" font-family="Arial" font-size="16" text-anchor="middle" fill="#999">No Image</text>
                    </svg>';
                    
            return response($svg)->header('Content-Type', 'image/svg+xml');
        }
    }
    
    /**
     * Display edit form for a product
     *
     * @param string $id The product ID
     * @return \Illuminate\Http\Response
     */
    public function editProduct($id)
    {
        try {
            // Get the current user ID from the session
            $userId = session('user_id');
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Please set up your shop first');
            }
            
            // Fetch product from database with safety checks
            $shopId = $shop->shop_id;
            
            // Get detailed product information
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.description            AS product_description,   -- ← fixed from p.product_description
                    p.unit_price,
                    p.stock,
                    p.product_image_filename,
                    p.product_image_mimetype,
                    p.shop_id,
                    p.category_id,
                    c.category_name
                FROM 
                    product p
                LEFT JOIN 
                    category c ON p.category_id = c.category_id
                WHERE 
                    p.product_id = :product_id 
                    AND p.shop_id = :shop_id
            ";
            
            $pdo = \DB::connection()->getPdo();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':product_id', $id);
            $stmt->bindParam(':shop_id', $shopId);
            $stmt->execute();
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$product) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Product not found or you do not have permission to edit it');
            }
            
            // Format the product data for the view
            $formattedProduct = [
                'product_id' => $product['PRODUCT_ID'] ?? $product['product_id'],
                'product_name' => $product['PRODUCT_NAME'] ?? $product['product_name'],
                'product_description' => $product['PRODUCT_DESCRIPTION'] ?? $product['product_description'],
                'unit_price' => $product['UNIT_PRICE'] ?? $product['unit_price'],
                'stock' => $product['STOCK'] ?? $product['stock'],
                'product_image_filename' => $product['PRODUCT_IMAGE_FILENAME'] ?? $product['product_image_filename'],
                'category_id' => $product['CATEGORY_ID'] ?? $product['category_id'],
                'category_name' => $product['CATEGORY_NAME'] ?? $product['category_name'],
            ];
            
            // Get all categories for the dropdown
            $categories = Category::all();
            
            // Get product image data
            $imageSql = "
                SELECT 
                    product_image AS image_blob,
                    product_image_mimetype
                FROM 
                    product
                WHERE 
                    product_id = :product_id
            ";
            
            $imageStmt = $pdo->prepare($imageSql);
            $imageStmt->bindParam(':product_id', $id);
            $imageStmt->execute();
            $imageData = $imageStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Add image data to the product if available
            if ($imageData && !empty($imageData['IMAGE_BLOB'] ?? $imageData['image_blob'])) {
                $imageBlob = $imageData['IMAGE_BLOB'] ?? $imageData['image_blob'];
                $mimetype = $imageData['PRODUCT_IMAGE_MIMETYPE'] ?? $imageData['product_image_mimetype'] ?? 'image/jpeg';
                $formattedProduct['image_base64'] = 'data:' . $mimetype . ';base64,' . base64_encode($imageBlob);
            }
            
            // Get RFID UID for this product
            $rfidSql = "SELECT rfid FROM RFID_PRODUCT WHERE product_id = :product_id";
            $rfidStmt = $pdo->prepare($rfidSql);
            $rfidStmt->bindParam(':product_id', $id);
            $rfidStmt->execute();
            $rfidData = $rfidStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($rfidData) {
                $formattedProduct['rfid_uid'] = $rfidData['RFID'] ?? $rfidData['rfid'] ?? '';
            } else {
                $formattedProduct['rfid_uid'] = '';
            }
            
            // Return the edit product view with the product data and categories
            return view('trader.trader_product_edit', [
                'product' => $formattedProduct,
                'categories' => $categories,
                'shop' => $shop
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading product edit form: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('Trader Product')
                ->with('error', 'An error occurred while loading the product edit form: ' . $e->getMessage());
        }
    }
    
    /**
     * Store a newly created product
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|string|exists:CATEGORY,category_id',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rfid_uid' => 'nullable|string|max:32',
        ]);
        
        try {
            // Get the current user's shop
            $userId = session('user_id');
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return redirect()->route('Trader Product')
                    ->with('error', 'You need to set up your shop before adding products.');
            }
            
            // Generate a new product ID
            $seqVal = \DB::selectOne("SELECT seq_productid.NEXTVAL val FROM dual")->val;
            $productId = 'pro' . str_pad($seqVal, 5, '0', STR_PAD_LEFT);
            
            // Get the shop ID
            $shopId = $shop->shop_id;
            
            // Store request values in local variables to avoid indirect modification errors
            $productName = $request->input('product_name');
            $description = $request->input('description');
            $unitPrice = $request->input('unit_price');
            $stock = $request->input('stock');
            $categoryId = $request->input('category_id');
            $rfidUid = $request->input('rfid_uid');
            
            // Prepare the product data
            $pdo = \DB::connection()->getPdo();
            
            // Handle image file if provided
            $imageMimeType = null;
            $imageFileName = null;
            
            if ($request->hasFile('product_image')) {
                $image = $request->file('product_image');
                $imageMimeType = $image->getMimeType();
                $imageFileName = $image->getClientOriginalName();
            }
            
            // Insert product record (without BLOB data)
            $insertSql = "
                INSERT INTO product (
                    product_id, 
                    product_name, 
                    description,
                    unit_price, 
                    stock, 
                    shop_id, 
                    category_id,
                    product_image_mimetype,
                    product_image_filename,
                    product_image_lastupd
                ) VALUES (
                    :product_id,
                    :product_name,
                    :description,
                    :unit_price,
                    :stock,
                    :shop_id,
                    :category_id,
                    :mimetype,
                    :filename,
                    CASE WHEN :has_image = 1 THEN SYSDATE ELSE NULL END
                )
            ";
            
            $stmt = $pdo->prepare($insertSql);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':product_name', $productName);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':unit_price', $unitPrice);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':shop_id', $shopId);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->bindParam(':mimetype', $imageMimeType);
            $stmt->bindParam(':filename', $imageFileName);
            
            $hasImage = $request->hasFile('product_image') ? 1 : 0;
            $stmt->bindParam(':has_image', $hasImage);
            
            $stmt->execute();
            
            // Update the product_image BLOB data separately if an image was uploaded
            if ($request->hasFile('product_image')) {
                // Read file contents as a string (not as a resource)
                $imageContents = file_get_contents($image->getPathname());
                
                // Prepare the update statement for the BLOB field
                $blobSql = "UPDATE product SET product_image = :image_blob WHERE product_id = :product_id";
                $blobStmt = $pdo->prepare($blobSql);
                
                // Bind parameters
                $blobStmt->bindParam(':image_blob', $imageContents, \PDO::PARAM_LOB);
                $blobStmt->bindParam(':product_id', $productId);
                
                // Execute the BLOB update
                $blobStmt->execute();
            }
            
            // Store RFID UID if provided
            if (!empty($rfidUid)) {
                // First check if the RFID UID already exists
                $checkRfidSql = "SELECT COUNT(*) AS rfid_count FROM RFID_PRODUCT WHERE rfid = :rfid";
                $checkRfidStmt = $pdo->prepare($checkRfidSql);
                $checkRfidStmt->bindParam(':rfid', $rfidUid);
                $checkRfidStmt->execute();
                $rfidResult = $checkRfidStmt->fetch(\PDO::FETCH_ASSOC);
                
                $rfidCount = $rfidResult['RFID_COUNT'] ?? $rfidResult['rfid_count'] ?? 0;
                
                if ($rfidCount > 0) {
                    // RFID already exists, inform the user
                    return redirect()->route('Trader Product')
                        ->with('warning', 'Product created, but RFID UID is already assigned to another product.')
                        ->withInput();
                }
                
                // Insert into RFID_PRODUCT table
                $rfidSql = "INSERT INTO RFID_PRODUCT (rfid, product_id) VALUES (:rfid, :product_id)";
                $rfidStmt = $pdo->prepare($rfidSql);
                $rfidStmt->bindParam(':rfid', $rfidUid);
                $rfidStmt->bindParam(':product_id', $productId);
                $rfidStmt->execute();
            }
            
            return redirect()->route('Trader Product')
                ->with('success', 'Product created successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Error creating product: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('Trader Product')
                ->with('error', 'Error creating product: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Update an existing product
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|string|exists:CATEGORY,category_id',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rfid_uid' => 'nullable|string|max:32',
        ]);
        
        try {
            // Get the current user's shop
            $userId = session('user_id');
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Shop not found.');
            }
            
            // Get the shop ID
            $shopId = $shop->shop_id;
            
            // Store request values in local variables to avoid indirect modification errors
            $productName = $request->input('product_name');
            $description = $request->input('description');
            $unitPrice = $request->input('unit_price');
            $stock = $request->input('stock');
            $categoryId = $request->input('category_id');
            $rfidUid = $request->input('rfid_uid');
            
            // Verify the product exists and belongs to this shop
            $checkSql = "
                SELECT COUNT(*) AS product_count
                FROM product
                WHERE product_id = :product_id AND shop_id = :shop_id
            ";
            
            $pdo = \DB::connection()->getPdo();
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':product_id', $id);
            $checkStmt->bindParam(':shop_id', $shopId);
            $checkStmt->execute();
            $result = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            $productCount = $result['PRODUCT_COUNT'] ?? $result['product_count'] ?? 0;
            
            if ($productCount < 1) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Product not found or does not belong to your shop.');
            }
            
            // Handle image file if provided
            $imageMimeType = null;
            $imageFileName = null;
            $hasNewImage = false;
            
            if ($request->hasFile('product_image')) {
                $image = $request->file('product_image');
                $imageMimeType = $image->getMimeType();
                $imageFileName = $image->getClientOriginalName();
                $hasNewImage = true;
            }
            
            // Update the product record (without BLOB data)
            $updateSql = "
                UPDATE product
                SET product_name = :product_name,
                    description = :description,
                    unit_price = :unit_price,
                    stock = :stock,
                    category_id = :category_id
            ";
            
            // Add image metadata fields only if a new image was uploaded
            if ($hasNewImage) {
                $updateSql .= ",
                    product_image_mimetype = :mimetype,
                    product_image_filename = :filename,
                    product_image_lastupd = SYSDATE
                ";
            }
            
            $updateSql .= " WHERE product_id = :product_id";
            
            $stmt = $pdo->prepare($updateSql);
            $stmt->bindParam(':product_name', $productName);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':unit_price', $unitPrice);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->bindParam(':product_id', $id);
            
            if ($hasNewImage) {
                $stmt->bindParam(':mimetype', $imageMimeType);
                $stmt->bindParam(':filename', $imageFileName);
            }
            
            $stmt->execute();
            
            // Update the product_image BLOB data separately if a new image was uploaded
            if ($hasNewImage) {
                // Read file contents as a string (not as a resource)
                $imageContents = file_get_contents($image->getPathname());
                
                // Prepare the update statement for the BLOB field
                $blobSql = "UPDATE product SET product_image = :image_blob WHERE product_id = :product_id";
                $blobStmt = $pdo->prepare($blobSql);
                
                // Bind parameters
                $blobStmt->bindParam(':image_blob', $imageContents, \PDO::PARAM_LOB);
                $blobStmt->bindParam(':product_id', $id);
                
                // Execute the BLOB update
                $blobStmt->execute();
            }
            
            // Handle RFID UID update
            if (!empty($rfidUid)) {
                // Check if this RFID UID is already used by another product
                $checkRfidSql = "SELECT COUNT(*) AS rfid_count FROM RFID_PRODUCT WHERE rfid = :rfid AND product_id != :product_id";
                $checkRfidStmt = $pdo->prepare($checkRfidSql);
                $checkRfidStmt->bindParam(':rfid', $rfidUid);
                $checkRfidStmt->bindParam(':product_id', $id);
                $checkRfidStmt->execute();
                $rfidResult = $checkRfidStmt->fetch(\PDO::FETCH_ASSOC);
                
                $rfidCount = $rfidResult['RFID_COUNT'] ?? $rfidResult['rfid_count'] ?? 0;
                
                if ($rfidCount > 0) {
                    // RFID UID is already used by another product
                    return redirect()->route('Trader Product')
                        ->with('warning', 'Product updated, but RFID UID is already assigned to another product.');
                }
                
                // Check if the product already has an RFID UID
                $existingRfidSql = "SELECT rfid FROM RFID_PRODUCT WHERE product_id = :product_id";
                $existingRfidStmt = $pdo->prepare($existingRfidSql);
                $existingRfidStmt->bindParam(':product_id', $id);
                $existingRfidStmt->execute();
                $existingRfid = $existingRfidStmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($existingRfid) {
                    // Update existing RFID UID
                    $updateRfidSql = "UPDATE RFID_PRODUCT SET rfid = :rfid WHERE product_id = :product_id";
                    $updateRfidStmt = $pdo->prepare($updateRfidSql);
                    $updateRfidStmt->bindParam(':rfid', $rfidUid);
                    $updateRfidStmt->bindParam(':product_id', $id);
                    $updateRfidStmt->execute();
                } else {
                    // Insert new RFID UID for this product
                    $insertRfidSql = "INSERT INTO RFID_PRODUCT (rfid, product_id) VALUES (:rfid, :product_id)";
                    $insertRfidStmt = $pdo->prepare($insertRfidSql);
                    $insertRfidStmt->bindParam(':rfid', $rfidUid);
                    $insertRfidStmt->bindParam(':product_id', $id);
                    $insertRfidStmt->execute();
                }
            } else {
                // If RFID field is empty, remove any existing RFID UIDs for this product
                $deleteRfidSql = "DELETE FROM RFID_PRODUCT WHERE product_id = :product_id";
                $deleteRfidStmt = $pdo->prepare($deleteRfidSql);
                $deleteRfidStmt->bindParam(':product_id', $id);
                $deleteRfidStmt->execute();
            }
            
            return redirect()->route('Trader Product')
                ->with('success', 'Product updated successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Error updating product: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('trader.products.edit', $id)
                ->with('error', 'Error updating product: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Delete a product
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function deleteProduct($id)
    {
        try {
            // Get the current user's shop
            $userId = session('user_id');
            $shop = Shop::where('user_id', $userId)->first();
            
            if (!$shop) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Shop not found.');
            }
            
            // Get the shop ID
            $shopId = $shop->shop_id;
            
            // Verify the product exists and belongs to this shop
            $checkSql = "
                SELECT COUNT(*) AS product_count
                FROM product
                WHERE product_id = :product_id AND shop_id = :shop_id
            ";
            
            $pdo = \DB::connection()->getPdo();
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':product_id', $id);
            $checkStmt->bindParam(':shop_id', $shopId);
            $checkStmt->execute();
            $result = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            $productCount = $result['PRODUCT_COUNT'] ?? $result['product_count'] ?? 0;
            
            if ($productCount < 1) {
                return redirect()->route('Trader Product')
                    ->with('error', 'Product not found or does not belong to your shop.');
            }
            
            // Check if the product has existing orders
            $checkOrderSql = "
                SELECT COUNT(*) AS order_count
                FROM order_item
                WHERE product_id = :product_id
            ";
            
            $checkOrderStmt = $pdo->prepare($checkOrderSql);
            $checkOrderStmt->bindParam(':product_id', $id);
            $checkOrderStmt->execute();
            $orderResult = $checkOrderStmt->fetch(\PDO::FETCH_ASSOC);
            
            $orderCount = $orderResult['ORDER_COUNT'] ?? $orderResult['order_count'] ?? 0;
            
            if ($orderCount > 0) {
                // Product has existing orders, just mark it as out of stock
                $updateSql = "UPDATE product SET stock = 0 WHERE product_id = :product_id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindParam(':product_id', $id);
                $updateStmt->execute();
                
                // Even if we're keeping the product, remove any RFID UID associations
                $deleteRfidSql = "DELETE FROM RFID_PRODUCT WHERE product_id = :product_id";
                $deleteRfidStmt = $pdo->prepare($deleteRfidSql);
                $deleteRfidStmt->bindParam(':product_id', $id);
                $deleteRfidStmt->execute();
                
                return redirect()->route('Trader Product')
                    ->with('success', 'Product has existing orders. It has been marked as out of stock.');
            }
            
            // No orders exist, safe to delete
            // First delete any RFID UID associations
            $deleteRfidSql = "DELETE FROM RFID_PRODUCT WHERE product_id = :product_id";
            $deleteRfidStmt = $pdo->prepare($deleteRfidSql);
            $deleteRfidStmt->bindParam(':product_id', $id);
            $deleteRfidStmt->execute();
            
            // Then delete the product
            $deleteSql = "DELETE FROM product WHERE product_id = :product_id AND shop_id = :shop_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->bindParam(':product_id', $id);
            $deleteStmt->bindParam(':shop_id', $shopId);
            $deleteStmt->execute();
            
            return redirect()->route('Trader Product')
                ->with('success', 'Product deleted successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Error deleting product: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('Trader Product')
                ->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }

    /**
     * Get analytics data for a specific period (A=All Time, D=day, W=week, M=month)
     * 
     * @param string $period The period code (A=all time, D=day, W=week, M=month)
     * @return \Illuminate\Http\Response
     */
    public function analyticsByPeriod($period)
    {
        $userId = session('user_id');
        $shop = Shop::where('user_id', $userId)->first();
        
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }
        
        $shopId = $shop->shop_id;
        $pdo = \DB::connection()->getPdo();

        try {
            // Period labels for response
            $periodLabels = [
                'A' => 'All Time',
                'D' => 'Today',
                'W' => 'This Week',
                'M' => 'This Month'
            ];
            
            // For All Time, get statistics from the analytics method instead of period-specific stats
            if ($period === 'A') {
                // Get the overall stats
                // Use existing queries from analytics method
                
                // 1. Total Sales - All Time Revenue
                $totalSalesSql = "
                    SELECT NVL(SUM(oi.quantity*oi.unit_price),0) AS sales 
                    FROM order_item oi 
                    JOIN product p ON p.product_id = oi.product_id 
                    WHERE p.shop_id = :shop_id
                ";
                $stmt = $pdo->prepare($totalSalesSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $salesResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $sales = $salesResult['SALES'] ?? $salesResult['sales'] ?? 0;
                
                // 2. Total Orders - Orders Completed
                $totalOrdersSql = "
                    SELECT COUNT(DISTINCT o.order_id) AS orders 
                    FROM order1 o 
                    JOIN order_item oi ON oi.order_id = o.order_id 
                    JOIN product p ON p.product_id = oi.product_id 
                    WHERE p.shop_id = :shop_id
                ";
                $stmt = $pdo->prepare($totalOrdersSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $ordersResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $orders = $ordersResult['ORDERS'] ?? $ordersResult['orders'] ?? 0;
                
                // 3. Average Order - Average Order Value
                $avgOrderValueSql = "
                    SELECT ROUND(
                        NVL(SUM(oi.quantity*oi.unit_price),0) / 
                        NULLIF(COUNT(DISTINCT o.order_id),0), 
                        2
                    ) AS avg_order_value 
                    FROM order1 o 
                    JOIN order_item oi ON oi.order_id=o.order_id 
                    JOIN product p ON p.product_id=oi.product_id 
                    WHERE p.shop_id=:shop_id
                ";
                $stmt = $pdo->prepare($avgOrderValueSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $avgResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $avgOrderValue = $avgResult['AVG_ORDER_VALUE'] ?? $avgResult['avg_order_value'] ?? 0;
                
                // 4. Items Sold - Total Units
                $itemsSoldSql = "
                    SELECT NVL(SUM(oi.quantity),0) AS items_sold 
                    FROM order_item oi 
                    JOIN product p ON p.product_id=oi.product_id 
                    WHERE p.shop_id=:shop_id
                ";
                $stmt = $pdo->prepare($itemsSoldSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $itemsResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $itemsSold = $itemsResult['ITEMS_SOLD'] ?? $itemsResult['items_sold'] ?? 0;
                
                // 5. Customer Base - Unique Customers
                $uniqueCustomersSql = "
                    SELECT COUNT(DISTINCT o.user_id) AS unique_customers 
                    FROM order1 o 
                    JOIN order_item oi ON oi.order_id=o.order_id 
                    JOIN product p ON p.product_id=oi.product_id 
                    WHERE p.shop_id=:shop_id
                ";
                $stmt = $pdo->prepare($uniqueCustomersSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $customersResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $uniqueCustomers = $customersResult['UNIQUE_CUSTOMERS'] ?? $customersResult['unique_customers'] ?? 0;
                
                // 6. Order Composition - Items Per Order
                $avgItemsPerOrderSql = "
                    SELECT ROUND(
                        NVL(SUM(oi.quantity),0) / 
                        NULLIF(COUNT(DISTINCT o.order_id),0), 
                        1
                    ) AS items_per_order 
                    FROM order1 o 
                    JOIN order_item oi ON oi.order_id=o.order_id 
                    JOIN product p ON p.product_id=oi.product_id 
                    WHERE p.shop_id=:shop_id
                ";
                $stmt = $pdo->prepare($avgItemsPerOrderSql);
                $stmt->bindParam(':shop_id', $shopId);
                $stmt->execute();
                $itemsPerOrderResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $itemsPerOrder = $itemsPerOrderResult['ITEMS_PER_ORDER'] ?? $itemsPerOrderResult['items_per_order'] ?? 0;
                
                // Return all-time stats
                return response()->json([
                    'period' => $period,
                    'periodLabel' => $periodLabels[$period],
                    'sales' => (float)$sales,
                    'orders' => (int)$orders,
                    'avgOrderValue' => (float)$avgOrderValue,
                    'itemsSold' => (int)$itemsSold,
                    'uniqueCustomers' => (int)$uniqueCustomers,
                    'itemsPerOrder' => (float)$itemsPerOrder,
                    // No percentage changes for all time view
                    'salesChange' => 0,
                    'ordersChange' => 0,
                    'avgOrderChange' => 0
                ]);
            }
            
            // For other periods (D, W, M), use the period-specific SQL queries
            // Current period
            $sqlCurrent = file_get_contents(base_path('sql/period_stats.sql'));
            $stmtC = $pdo->prepare($sqlCurrent);
            $stmtC->execute(['shop_id' => $shopId, 'period' => $period]);
            $now = $stmtC->fetch(\PDO::FETCH_ASSOC);

            // Previous period
            $sqlPrev = file_get_contents(base_path('sql/period_stats_prev.sql'));
            $stmtP = $pdo->prepare($sqlPrev);
            $stmtP->execute(['shop_id' => $shopId, 'period' => $period]);
            $prev = $stmtP->fetch(\PDO::FETCH_ASSOC);

            // Oracle returns uppercase column names, so we handle both cases
            $sales = $now['SALES'] ?? $now['sales'] ?? 0;
            $orders = $now['ORDERS'] ?? $now['orders'] ?? 0;
            $avgOrderValue = $now['AVG_ORDER_VALUE'] ?? $now['avg_order_value'] ?? 0;
            $itemsSold = $now['ITEMS_SOLD'] ?? $now['items_sold'] ?? 0;
            $uniqueCustomers = $now['UNIQUE_CUST'] ?? $now['unique_cust'] ?? 0;
            $itemsPerOrder = $now['ITEMS_PER_ORDER'] ?? $now['items_per_order'] ?? 0;

            $prevSales = $prev['SALES'] ?? $prev['sales'] ?? 0;
            $prevOrders = $prev['ORDERS'] ?? $prev['orders'] ?? 0;
            $prevAvgOrder = $prev['AVG_ORDER_VALUE'] ?? $prev['avg_order_value'] ?? 0;

            // Calculate percentage changes
            $salesChange = $prevSales > 0 ? round(100 * ($sales - $prevSales) / $prevSales, 1) : 0;
            $ordersChange = $prevOrders > 0 ? round(100 * ($orders - $prevOrders) / $prevOrders, 1) : 0;
            $avgOrderChange = $prevAvgOrder > 0 ? round(100 * ($avgOrderValue - $prevAvgOrder) / $prevAvgOrder, 1) : 0;

            return response()->json([
                'period' => $period,
                'periodLabel' => $periodLabels[$period] ?? 'Custom Period',
                'sales' => (float)$sales,
                'orders' => (int)$orders,
                'avgOrderValue' => (float)$avgOrderValue,
                'itemsSold' => (int)$itemsSold,
                'uniqueCustomers' => (int)$uniqueCustomers,
                'itemsPerOrder' => (float)$itemsPerOrder,
                'salesChange' => $salesChange,
                'ordersChange' => $ordersChange,
                'avgOrderChange' => $avgOrderChange
            ]);
        } catch (\Exception $e) {
            \Log::error('Period analytics error: ' . $e->getMessage());
            return response()->json(['error' => 'Error processing analytics data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Run the RFID relay script
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function runRfidRelay()
    {
        try {
            // Get the path to the relay.py script
            $relayScriptPath = base_path('relay.py');
            
            // Check if the script exists
            if (!file_exists($relayScriptPath)) {
                \Log::error('RFID relay script not found at ' . $relayScriptPath);
                return response()->json([
                    'success' => false,
                    'message' => 'RFID relay script not found.'
                ], 404);
            }
            
            // Execute the script in the background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen('start /B python ' . $relayScriptPath, 'r'));
            } else {
                // Linux/Unix/Mac
                exec('python3 ' . $relayScriptPath . ' > /dev/null 2>&1 &');
            }
            
            \Log::info('RFID relay script started successfully');
            
            return response()->json([
                'success' => true,
                'message' => 'RFID relay started successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error running RFID relay script: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error running RFID relay script: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop the RFID relay script
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopRfidRelay()
    {
        try {
            // Find and kill the Python process
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - find and kill the Python process running relay.py
                // First try with taskkill by window title
                exec('taskkill /F /IM python.exe /FI "WINDOWTITLE eq relay.py"');
                
                // Also try finding by command line
                exec('wmic process where "commandline like \'%relay.py%\'" call terminate');
                
                // As a last resort, try to kill all python processes (may be too aggressive)
                // exec('taskkill /F /IM python.exe');
            } else {
                // Linux/Unix/Mac - find and kill the Python process running relay.py
                exec("pkill -f 'python.*relay.py'");
                exec("pkill -f 'python3.*relay.py'");
            }
            
            // Create a marker file to signal the script to exit gracefully if it's checking for it
            $stopSignalFile = base_path('relay_stop.signal');
            file_put_contents($stopSignalFile, date('Y-m-d H:i:s'));
            
            \Log::info('RFID relay script stop signal sent');
            
            return response()->json([
                'success' => true,
                'message' => 'RFID relay stop signal sent'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error stopping RFID relay script: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error stopping RFID relay script: ' . $e->getMessage()
            ], 500);
        }
    }
}

