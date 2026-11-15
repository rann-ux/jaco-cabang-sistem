<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stoks';
    protected $primaryKey = 'id_mutasi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_mutasi',
        'id_stok',
        'id_karyawan',
        'jenis',
        'jumlah',
        'tanggal',
        'referensi_dokumen',
    ];

    public function stok()
    {
        return $this->belongsTo(Stok::class, 'id_stok', 'id_stok');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }
}