<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggans';
    protected $primaryKey = 'id_pelanggan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_pelanggan',
        'nama',
        'alamat',
        'no_hp',
    ];

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_pelanggan', 'id_pelanggan');
    }
}