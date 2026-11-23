<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayarans';
    protected $primaryKey = 'id_bayar';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_faktur',
        'metode',
        'jumlah',
        'tanggal',
        'status_verifikasi_sales',
        'status_cek_pusat',
        'tgl_cek_pusat',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'status_verifikasi_sales' => 'boolean',
        'status_cek_pusat' => 'boolean',
        'tanggal' => 'datetime',
        'tgl_cek_pusat' => 'datetime',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'id_faktur', 'id_faktur');
    }
}
