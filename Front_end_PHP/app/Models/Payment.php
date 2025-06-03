<?php
// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table      = 'PAYMENT';
    protected $primaryKey = 'payment_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'payment_method','payment_date','user_id',
        'order_id','payment_amount'
    ];

    public function user() { return $this->belongsTo(User::class,'user_id'); }
    public function order(){ return $this->belongsTo(Order::class,'order_id'); }
}
