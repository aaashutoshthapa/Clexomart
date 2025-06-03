<?php
// Simple script to check product images

// Connect to Laravel app
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Use Eloquent model
use App\Models\Product;

// Set headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Get products
$products = Product::take(5)->get();

echo '<h1>Product Image Checker</h1>';

foreach ($products as $product) {
    echo '<div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">';
    echo '<h2>' . htmlspecialchars($product->product_name) . '</h2>';
    echo '<p>Product ID: ' . htmlspecialchars($product->product_id) . '</p>';
    
    if (isset($product->PRODUCT_image) && !empty($product->PRODUCT_image)) {
        $imageSize = strlen($product->PRODUCT_image);
        echo '<p style="color: green;">Has image data: YES (size: ' . $imageSize . ' bytes)</p>';
        
        $mime = $product->PRODUCT_IMAGE_MIMETYPE ?? 'image/jpeg';
        echo '<p>MIME Type: ' . htmlspecialchars($mime) . '</p>';
        
        // Show image preview
        echo '<p>Image Preview:</p>';
        echo '<img src="data:' . $mime . ';base64,' . base64_encode($product->PRODUCT_image) . '" 
               style="max-width: 300px; border: 2px solid blue; max-height: 300px;">';
        
        // Add a link to fix this product
        echo '<p><a href="/debug/fix-image?product_id=' . $product->product_id . '" style="color: blue;">Use sample image for this product</a></p>';
    } else {
        echo '<p style="color: red;">Has image data: NO</p>';
        
        // Add a link to fix this product
        echo '<p><a href="/debug/fix-image?product_id=' . $product->product_id . '" style="color: red;">Use sample image for this product</a></p>';
    }
    
    echo '</div>';
} 