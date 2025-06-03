<?php
// app/Models/Wishlist.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table      = 'WISHLIST';
    protected $primaryKey = 'wishlist_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['user_id','creation_date'];

    public function user()    { return $this->belongsTo(User::class,'user_id'); }

    public function products(){ return $this->belongsToMany(
        Product::class,'WISHLIST_PRODUCT','wishlist_id','product_id')
        ->withPivot('product_quantity'); }
}
