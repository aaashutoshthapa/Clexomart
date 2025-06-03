<?php

/**
 * Product Model
 * 
 * This model represents products in the ClexoMart e-commerce system.
 * Each product belongs to a shop (owned by a trader) and is categorized
 * for easy browsing and organization.
 * 
 * Key Features:
 * - Maps to PRODUCT table in Oracle database
 * - Custom string-based product IDs
 * - Relationship with Shop (many-to-one)
 * - Relationship with Category (many-to-one)
 * - Price management with discount support
 * - BLOB image storage with metadata
 * - Stock inventory tracking
 * 
 * Business Logic:
 * - Products are owned by shops (traders)
 * - Categories help organize products
 * - Discounts can be applied via discount_id
 * - Stock levels are managed for inventory control
 * - Images are stored as BLOB with MIME type metadata
 * 
 * Database Table: PRODUCT
 * Primary Key: product_id (string, non-incrementing)
 * 
 * Relationships:
 * - belongsTo Shop (via shop_id)
 * - belongsTo Category (via category_id)
 * - belongsTo Discount (via discount_id, optional)
 * - belongsToMany Cart (via CART_PRODUCT pivot)
 * - hasMany Review
 * - hasMany OrderItem
 * 
 * @package App\Models
 * @author ClexoMart Development Team
 * @version 1.0
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Product Model Class
 * 
 * Manages product data including pricing, inventory, images, and relationships
 * with shops and categories in the ClexoMart marketplace
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * 
     * Points to the PRODUCT table in Oracle database which stores:
     * - Basic product information (name, description)
     * - Pricing data (unit_price, price_after_discount)
     * - Inventory management (stock levels)
     * - Shop and category relationships
     * - Image data with metadata
     * 
     * @var string
     */
    protected $table = 'PRODUCT';
    
    /**
     * The primary key for the model.
     * 
     * Uses product_id as primary key instead of default 'id'.
     * Format: Custom string identifier (e.g., 'PROD0001', 'P123ABC')
     * 
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * Set to false because we use custom string-based IDs
     * that are generated manually or by database triggers.
     * 
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The "type" of the auto-incrementing ID.
     * 
     * Set to string because product_id is VARCHAR2 in Oracle database.
     * 
     * @var string
     */
    public $keyType = 'string';
    
    /**
     * Indicates if the model should be timestamped.
     * 
     * Set to false because PRODUCT table doesn't have 
     * created_at/updated_at columns in our schema.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     * 
     * These fields can be safely assigned using create() or fill() methods.
     * Includes all product data fields for complete product management.
     * 
     * @var array
     */
    protected $fillable = [
        'product_id',                   // Unique product identifier
        'product_name',                 // Display name of the product
        'stock',                        // Current inventory count
        'shop_id',                      // Foreign key to owning shop
        'category_id',                  // Foreign key to product category
        'description',                  // Product description text
        'unit_price',                   // Base price before discounts
        'discount_id',                  // Foreign key to discount scheme (optional)
        'price_after_discount',         // Final price after discount applied
        'PRODUCT_image',                // Product image as BLOB data
        'PRODUCT_IMAGE_MIMETYPE',       // Image MIME type (image/jpeg, image/png, etc.)
        'PRODUCT_IMAGE_FILENAME',       // Original filename for download
        'PRODUCT_IMAGE_LASTUPD',        // Last image update timestamp
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * Automatic type conversion for better data handling:
     * - Price fields cast to float for mathematical operations
     * - Ensures consistent decimal handling across the application
     * 
     * @var array
     */
    protected $casts = [
        'unit_price' => 'float',                // Base price as decimal
        'price_after_discount' => 'float',      // Discounted price as decimal
    ];

    /**
     * Get the category that this product belongs to
     * 
     * Defines a many-to-one relationship where multiple products
     * can belong to the same category. Categories help organize
     * products for better browsing and filtering.
     * 
     * Usage: $product->category returns Category object
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
    
    /**
     * Get the shop that owns this product
     * 
     * Defines a many-to-one relationship where multiple products
     * belong to the same shop (trader). This establishes product
     * ownership and helps with shop-specific product management.
     * 
     * Usage: $product->shop returns Shop object
     * Access trader: $product->shop->user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
    
    /**
     * Get product image with proper BLOB handling
     * 
     * This accessor method provides safe access to product images
     * stored as BLOB data in the Oracle database. It handles null
     * values and empty data gracefully.
     * 
     * Image Data Flow:
     * 1. Check if image data exists
     * 2. Return raw binary data for image display
     * 3. Return null if no image available
     * 
     * Usage: $product->image returns binary image data or null
     * 
     * @param mixed $value Raw image data from database
     * @return mixed Binary image data or null
     */
    public function getImageAttribute($value)
    {
        // Image Validation: Check if image data exists and is not empty
        if (!is_null($value) && !empty($value)) {
            return $value;                              // Return raw binary data
        }
        
        // No Image Case: Return null for proper handling
        return null;
    }
    
    /**
     * Check if the product has an image
     * 
     * Utility method to determine if a product has an associated image.
     * Useful for conditional rendering in views and API responses.
     * 
     * Image Validation:
     * - Checks if PRODUCT_image field is not null
     * - Checks if PRODUCT_image field is not empty
     * - Returns boolean for easy conditional logic
     * 
     * Usage: 
     * if ($product->hasImage()) {
     *     // Show image
     * } else {
     *     // Show placeholder
     * }
     * 
     * @return bool True if product has image, false otherwise
     */
    public function hasImage()
    {
        return !is_null($this->PRODUCT_image) && !empty($this->PRODUCT_image);
    }
}
