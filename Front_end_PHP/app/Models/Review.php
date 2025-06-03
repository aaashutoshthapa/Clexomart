<?php
// app/Models/Review.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table      = 'REVIEW';
    protected $primaryKey = 'review_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'product_id','user_id','review_description','review_date'
    ];

    public function product(){ return $this->belongsTo(Product::class,'product_id'); }
    public function user()   { return $this->belongsTo(User::class,'user_id'); }
}
