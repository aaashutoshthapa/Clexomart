<?php
// app/Models/CollectionSlot.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionSlot extends Model
{
    protected $table      = 'COLLECTION_SLOT';
    protected $primaryKey = 'slot_id';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['day','time','no_order'];

    public function orders(){ return $this->hasMany(Order::class,'slot_id'); }
}
