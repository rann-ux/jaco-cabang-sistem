<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $table = 'promos';
    protected $primaryKey = 'id_promo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'kode_promo',
        'deskripsi',
        'jenis_potongan',
        'nilai',
        'tgl_mulai',
        'tgl_selesai',
        'status',
    ];

    protected $casts = [
        'nilai' => 'decimal:2',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_promo', 'id_promo');
    }
}
