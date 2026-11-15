<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawans';
    protected $primaryKey = 'id_karyawan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_karyawan',
        'id_cabang',
        'nama',
        'peran',
        'status',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function mutasiStoks()
    {
        return $this->hasMany(MutasiStok::class, 'id_karyawan', 'id_karyawan');
    }

    public function penerimaanBarangs()
    {
        return $this->hasMany(PenerimaanBarang::class, 'id_karyawan', 'id_karyawan');
    }

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_karyawan', 'id_karyawan');
    }

    public function kasTransaksis()
    {
        return $this->hasMany(KasTransaksi::class, 'id_karyawan', 'id_karyawan');
    }

    public function laporanHarians()
    {
        return $this->hasMany(LaporanHarian::class, 'id_karyawan', 'id_karyawan');
    }
}