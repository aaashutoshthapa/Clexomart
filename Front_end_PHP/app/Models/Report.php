<?php
// app/Models/Report.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table      = 'REPORT';
    protected $primaryKey = 'report_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'report_date','report_title','report_body','user_id'
    ];

    public function user()   { return $this->belongsTo(User::class,'user_id'); }

    public function orders() { return $this->belongsToMany(
        Order::class,'ORDER_REPORT','report_id','order_id'); }
}
