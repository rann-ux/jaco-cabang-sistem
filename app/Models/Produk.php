<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produks';
    protected $primaryKey = 'id_produk';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'kategori',
        'harga',
        'masa_garansi',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'masa_garansi' => 'integer',
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
