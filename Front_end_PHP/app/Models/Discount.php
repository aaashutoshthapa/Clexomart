<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;  // Use the factory trait to enable factory methods for this model

    protected $table = 'discount';  // Specify the table name for this model (default is 'discounts')
    protected $primaryKey = 'discount_id';  // Set the primary key column for the table
    public $incrementing = false;  // Since 'discount_id' is not an auto-incrementing integer
    protected $keyType = 'string';  // Define the key type as string (usually for UUIDs or other non-integer keys)

    protected $fillable = ['discount_amount', 'start_date', 'end_date'];  // Define which attributes are mass assignable

    // Define a relationship with the Product model
    public function products()
    {
        // 'discount_id' on the 'products' table is the foreign key referencing the Discount model
        return $this->hasMany(Product::class, 'discount_id');  
    }
}
