<?php
// app/Models/Coupon.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table      = 'COUPON';
    protected $primaryKey = 'coupon_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['coupon_code','start_date','end_date','coupon_amount'];

    public function orders(){ return $this->hasMany(Order::class,'coupon_id'); }
}
