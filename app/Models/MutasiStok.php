<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stoks';
    protected $primaryKey = 'id_mutasi';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_stok',
        'id_karyawan',
        'jenis',
        'jumlah',
        'tanggal',
        'referensi_dokumen',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'tanggal' => 'datetime',
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