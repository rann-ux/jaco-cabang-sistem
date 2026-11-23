<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SinkronisasiMasterDataController extends Controller
{
    public function syncProduk(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|array|min:1',
            'data.*.kode_produk' => 'required|string|max:30',
            'data.*.nama_produk' => 'required|string|max:150',
            'data.*.kategori' => 'required|string|max:50',
            'data.*.harga' => 'required|numeric|min:0',
            'data.*.masa_garansi' => 'required|integer|min:0',
        ]);

        $result = DB::transaction(function () use ($validated) {
            $created = 0;
            $updated = 0;

            foreach ($validated['data'] as $item) {
                $produk = Produk::where(
                    'kode_produk',
                    $item['kode_produk']
                )->first();

                if ($produk) {
                    $produk->update($item);
                    $updated++;
                } else {
                    Produk::create($item);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($validated['data']),
            ];
        });

        return response()->json([
            'message' => 'Sinkronisasi produk berhasil.',
            'data' => $result,
        ]);
    }

    public function syncPromo(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|array|min:1',
            'data.*.kode_promo' => 'required|string|max:30',
            'data.*.deskripsi' => 'required|string|max:255',
            'data.*.jenis_potongan' => 'required|in:persen,nominal',
            'data.*.nilai' => 'required|numeric|gt:0',
            'data.*.tgl_mulai' => 'required|date',
            'data.*.tgl_selesai' => 'required|date|after_or_equal:data.*.tgl_mulai',
            'data.*.status' => 'required|string|max:20',
        ]);

        foreach ($validated['data'] as $index => $item) {
            if (
                $item['jenis_potongan'] === 'persen'
                && $item['nilai'] > 100
            ) {
                return response()->json([
                    'message' => 'Validasi gagal.',
                    'errors' => [
                        "data.$index.nilai" => [
                            'Nilai diskon persentase tidak boleh lebih dari 100.'
                        ],
                    ],
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($validated) {
            $created = 0;
            $updated = 0;

            foreach ($validated['data'] as $item) {
                $promo = Promo::where(
                    'kode_promo',
                    $item['kode_promo']
                )->first();

                if ($promo) {
                    $promo->update($item);
                    $updated++;
                } else {
                    Promo::create($item);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => count($validated['data']),
            ];
        });

        return response()->json([
            'message' => 'Sinkronisasi promo berhasil.',
            'data' => $result,
        ]);
    }
}
