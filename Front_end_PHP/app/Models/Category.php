<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';
    protected $primaryKey = 'category_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['category_name', 'category_description'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
    
    /**
     * Get all shops belonging to this category.
     */
    public function shops()
    {
        return $this->hasMany(Shop::class, 'category_id', 'category_id');
    }
}
