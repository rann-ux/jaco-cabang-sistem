<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $table = 'promos';
    protected $primaryKey = 'id_promo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_promo',
        'kode_promo',
        'deskripsi',
        'jenis_potongan',
        'nilai',
        'tgl_mulai',
        'tgl_selesai',
        'status',
    ];

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_promo', 'id_promo');
    }
}