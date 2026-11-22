<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasTransaksi extends Model
{
    protected $table = 'kas_transaksis';
    protected $primaryKey = 'id_kas';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_cabang',
        'id_karyawan',
        'id_faktur',
        'tanggal',
        'jenis',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'decimal:2',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'id_faktur', 'id_faktur');
    }
}
