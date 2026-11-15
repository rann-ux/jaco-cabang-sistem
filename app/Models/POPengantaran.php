<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class POPengantaran extends Model
{
    protected $table = 'po_pengantarans';
    protected $primaryKey = 'id_po';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_po',
        'id_faktur',
        'tanggal',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'id_faktur', 'id_faktur');
    }
}