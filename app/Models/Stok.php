<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stoks';
    protected $primaryKey = 'id_stok';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_cabang',
        'id_produk',
        'jumlah',
        'batas_minimum',
        'tgl_update',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'batas_minimum' => 'integer',
        'tgl_update' => 'datetime',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function mutasiStoks()
    {
        return $this->hasMany(MutasiStok::class, 'id_stok', 'id_stok');
    }
}
