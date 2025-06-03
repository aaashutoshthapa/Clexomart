<?php
// app/Models/Order.php  (maps ORDER1)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table      = 'ORDER1';
    protected $primaryKey = 'order_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'order_date','coupon_id','cart_id','payment_amount',
        'slot_id','user_id'
    ];

    public function user()      { return $this->belongsTo(User::class,'user_id'); }
    public function coupon()    { return $this->belongsTo(Coupon::class,'coupon_id'); }
    public function cart()      { return $this->belongsTo(Cart::class,'cart_id'); }
    public function slot()      { return $this->belongsTo(CollectionSlot::class,'slot_id'); }

    public function products()  { return $this->belongsToMany(
        Product::class,'PRODUCT_ORDER','order_id','product_id'); }

    public function reports()   { return $this->belongsToMany(
        Report::class,'ORDER_REPORT','order_id','report_id'); }

    public function payment()   { return $this->hasOne(Payment::class,'order_id'); }
}
