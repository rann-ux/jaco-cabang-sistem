<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    protected $table = 'fakturs';
    protected $primaryKey = 'id_faktur';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_faktur',
        'no_faktur',
        'tanggal',
        'total',
        'status',
        'id_cabang',
        'id_pelanggan',
        'id_karyawan',
        'id_promo',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'id_promo', 'id_promo');
    }

    public function detailFakturs()
    {
        return $this->hasMany(DetailFaktur::class, 'id_faktur', 'id_faktur');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'id_faktur', 'id_faktur');
    }

    public function poPengantaran()
    {
        return $this->hasOne(POPengantaran::class, 'id_faktur', 'id_faktur');
    }

    public function kasTransaksis()
    {
        return $this->hasMany(KasTransaksi::class, 'id_faktur', 'id_faktur');
    }
}