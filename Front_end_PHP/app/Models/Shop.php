<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    // Define the table name explicitly (optional if it's the default 'shops')
    protected $table = 'shop';

    // Primary key configuration (not auto-incrementing, using string)
    protected $primaryKey = 'shop_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Disable timestamps since they don't exist in the database
    public $timestamps = false;

    // Fillable properties (attributes that are mass-assignable)
    protected $fillable = [
        'shop_id', 
        'shop_name', 
        'shop_description', 
        'user_id', 
        'logo', 
        'shop_image_mimetype', 
        'shop_image_filename', 
        'shop_image_lastupd', 
        'category_id'  
    ];
    
    /**
     * Get the category that owns the shop.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    // Relationship with User model (Shop belongs to a User)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with Product model (Shop has many Products)
    public function products()
    {
        return $this->hasMany(Product::class, 'shop_id');
    }

    // You may also define accessor/mutator methods for logo handling if needed
}
