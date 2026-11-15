<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produks';
    protected $primaryKey = 'id_produk';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_produk',
        'kode_produk',
        'nama_produk',
        'kategori',
        'harga',
        'masa_garansi',
    ];

    public function stoks()
    {
        return $this->hasMany(Stok::class, 'id_produk', 'id_produk');
    }

    public function detailFakturs()
    {
        return $this->hasMany(DetailFaktur::class, 'id_produk', 'id_produk');
    }

    public function penerimaanBarangs()
    {
        return $this->hasMany(PenerimaanBarang::class, 'id_produk', 'id_produk');
    }
}