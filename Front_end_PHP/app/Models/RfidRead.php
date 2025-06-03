<?php
// app/Models/RfidRead.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidRead extends Model
{
    /** ─────  Core Oracle mapping  ─────────────────────────────────── */
    protected $table      = 'RFID_READ';     // exact table name
    protected $primaryKey = 'rfid_id';       // varchar2 PK created by trigger

    public    $incrementing = false;         // Oracle trigger, not auto-inc
    protected $keyType      = 'string';      // PK is a VARCHAR2
    public    $timestamps   = false;         // no created_at / updated_at cols

    /** ─────  Mass-assignment  ─────────────────────────────────────── */
    // leave PK out so it can’t be overridden accidentally
    protected $fillable = ['rfid', 'time'];

    /** ─────  Attribute casting  ───────────────────────────────────── */
    // Optional: automatically return Carbon instances instead of strings
    protected $casts = [
        'read_at' => 'datetime',
    ];
}
