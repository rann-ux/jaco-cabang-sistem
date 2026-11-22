<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 'cabangs';
    protected $primaryKey = 'id_cabang';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nama_cabang',
        'jenis',
        'alamat',
    ];

    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'id_cabang', 'id_cabang');
    }

    public function stoks()
    {
        return $this->hasMany(Stok::class, 'id_cabang', 'id_cabang');
    }

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_cabang', 'id_cabang');
    }

    public function penerimaanBarangs()
    {
        return $this->hasMany(PenerimaanBarang::class, 'id_cabang', 'id_cabang');
    }

    public function kasTransaksis()
    {
        return $this->hasMany(KasTransaksi::class, 'id_cabang', 'id_cabang');
    }

    public function laporanHarians()
    {
        return $this->hasMany(LaporanHarian::class, 'id_cabang', 'id_cabang');
    }
}
