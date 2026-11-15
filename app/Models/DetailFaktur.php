<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailFaktur extends Model
{
    protected $table = 'detail_fakturs';
    protected $primaryKey = 'id_detail';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_detail',
        'id_faktur',
        'id_produk',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'id_faktur', 'id_faktur');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}