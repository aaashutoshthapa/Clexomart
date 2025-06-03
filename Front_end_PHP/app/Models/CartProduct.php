<?php

/**
 * CartProduct Model
 * 
 * This model represents the pivot table relationship between Cart and Product entities
 * in the ClexoMart e-commerce system. It handles the many-to-many relationship
 * with additional pivot data like quantity and total amount.
 * 
 * Key Features:
 * - Maps to CART_PRODUCT pivot table in Oracle database
 * - Uses composite primary key (cart_id + product_id)
 * - Stores additional pivot data (quantity, total_amount)
 * - Custom key handling for composite primary key
 * 
 * Database Table: CART_PRODUCT
 * Composite Primary Key: cart_id + product_id
 * 
 * @package App\Models
 * @author ClexoMart Development Team
 * @version 1.0
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CartProduct Model Class
 * 
 * Handles the pivot table between Cart and Product with additional data
 * This is a pivot model that manages cart items with quantities and totals
 */
class CartProduct extends Model
{
    /**
     * The table associated with the model.
     * 
     * Points to the CART_PRODUCT pivot table in Oracle database which stores:
     * - cart_id: Foreign key reference to CART table
     * - product_id: Foreign key reference to PRODUCT table
     * - product_quantity: Number of items of this product in the cart
     * - total_amount: Calculated total price (unit_price × quantity)
     * 
     * @var string
     */
    protected $table = 'CART_PRODUCT';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * Set to false because this table uses a composite primary key
     * (cart_id + product_id) rather than an auto-incrementing integer
     * 
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The primary key for the model.
     * 
     * Note: This is set to 'cart_id' but we handle the composite key manually
     * in the setKeysForSaveQuery method. Laravel doesn't natively support
     * composite keys, so we work around this limitation.
     * 
     * @var string
     */
    protected $primaryKey = 'cart_id'; // We'll handle the composite key manually
    
    /**
     * The attributes that are mass assignable.
     * 
     * These fields can be safely assigned using create() or fill() methods:
     * - cart_id: Which cart this item belongs to
     * - product_id: Which product this represents
     * - product_quantity: How many of this product
     * - total_amount: Pre-calculated total (price × quantity)
     * 
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_quantity',
        'total_amount'
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * Set to false because CART_PRODUCT table doesn't have 
     * created_at/updated_at columns as it's a simple pivot table
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the primary key value.
     * 
     * Since Laravel expects a single primary key but we have a composite key,
     * we return just the cart_id. This is a workaround for Laravel's limitation
     * with composite primary keys.
     * 
     * @return mixed The cart_id value
     */
    public function getKey()
    {
        return $this->cart_id;
    }

    /**
     * Set the keys for a save update query.
     * 
     * This is the crucial method that handles our composite primary key.
     * When Laravel tries to update a record, it normally uses just the primary key.
     * Since we have a composite key (cart_id + product_id), we override this
     * method to use both keys in the WHERE clause.
     * 
     * This ensures that updates target the correct cart-product combination.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder Modified query with composite key conditions
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('cart_id', '=', $this->getAttribute('cart_id'))
              ->where('product_id', '=', $this->getAttribute('product_id'));
        
        return $query;
    }

    /**
     * Define relationship with Cart model
     * 
     * Each cart-product record belongs to exactly one cart.
     * This creates a many-to-one relationship where:
     * - Multiple cart items can belong to one cart
     * - Each cart item belongs to exactly one cart
     * 
     * Foreign Key: cart_id in CART_PRODUCT table
     * References: cart_id in CART table
     * 
     * Usage: $cartProduct->cart returns the Cart object
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'cart_id');
    }

    /**
     * Define relationship with Product model
     * 
     * Each cart-product record references exactly one product.
     * This creates a many-to-one relationship where:
     * - Multiple cart items can reference the same product (different carts)
     * - Each cart item references exactly one product
     * 
     * Foreign Key: product_id in CART_PRODUCT table
     * References: product_id in PRODUCT table
     * 
     * Usage: $cartProduct->product returns the Product object
     * This is useful for getting product details like name, price, stock
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
