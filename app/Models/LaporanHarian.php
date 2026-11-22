<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    protected $table = 'laporan_harians';
    protected $primaryKey = 'id_laporan';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_cabang',
        'id_karyawan',
        'tanggal',
        'jenis_laporan',
        'status_kirim',
        'tgl_kirim',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tgl_kirim' => 'datetime',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }
}
