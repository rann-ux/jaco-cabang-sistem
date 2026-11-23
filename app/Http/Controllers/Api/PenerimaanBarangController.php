<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Karyawan;
use App\Models\MutasiStok;
use App\Models\PenerimaanBarang;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Services\IntegrasiPusatService;
use Illuminate\Support\Facades\Log;

class PenerimaanBarangController extends Controller
{
    public function store(Request $request, IntegrasiPusatService $integrasi)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer|exists:cabangs,id_cabang',
            'id_produk' => 'required|integer|exists:produks,id_produk',
            'id_karyawan' => 'required|integer|exists:karyawans,id_karyawan',
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'sometimes|date',
            'no_dokumen_pusat' => 'required|string|max:30',
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        try {
            $result = DB::transaction(function () use ($validated, $tanggal) {

            $cabang = Cabang::where('id_cabang', $validated['id_cabang'])
                ->lockForUpdate()
                ->firstOrFail();

            $karyawanSesuai = Karyawan::where('id_karyawan', $validated['id_karyawan'])
                ->where('id_cabang', $cabang->id_cabang)
                ->where('status', 'aktif')
                ->exists();

            if (!$karyawanSesuai) {
                throw ValidationException::withMessages([
                    'id_karyawan' => 'Karyawan tidak aktif atau tidak terdaftar pada cabang yang dipilih.',
                ]);
            }

            $produk = Produk::findOrFail($validated['id_produk']);

            $duplikat = PenerimaanBarang::where('no_dokumen_pusat', $validated['no_dokumen_pusat'])
                ->where('id_cabang', $cabang->id_cabang)
                ->where('id_produk', $validated['id_produk'])
                ->exists();

            if ($duplikat) {
                throw ValidationException::withMessages([
                    'no_dokumen_pusat' => 'Dokumen penerimaan ini sudah pernah dicatat untuk cabang dan produk tersebut.',
                ]);
            }

            $stok = Stok::where('id_cabang', $cabang->id_cabang)
                ->where('id_produk', $produk->id_produk)
                ->lockForUpdate()
                ->first();

            if (!$stok) {
                $stok = Stok::create([
                    'id_cabang' => $cabang->id_cabang,
                    'id_produk' => $produk->id_produk,
                    'jumlah' => 0,
                    'batas_minimum' => 0,
                    'tgl_update' => $tanggal,
                ]);
            }

            $penerimaan = PenerimaanBarang::create([
                'id_cabang' => $cabang->id_cabang,
                'id_produk' => $produk->id_produk,
                'id_karyawan' => $validated['id_karyawan'],
                'jumlah' => $validated['jumlah'],
                'tanggal' => $tanggal,
                'no_dokumen_pusat' => $validated['no_dokumen_pusat'],
            ]);

            $stok->jumlah += $validated['jumlah'];
            $stok->tgl_update = $tanggal;
            $stok->save();

            MutasiStok::create([
                'id_stok' => $stok->id_stok,
                'id_karyawan' => $validated['id_karyawan'],
                'jenis' => 'masuk',
                'jumlah' => $validated['jumlah'],
                'tanggal' => $tanggal,
                'referensi_dokumen' => $validated['no_dokumen_pusat'],
            ]);

            return $penerimaan->load([
                'cabang',
                'produk',
                'karyawan',
            ]);
        });
    } catch (UniqueConstraintViolationException $e) {
        return response()->json([
            'message' => 'Dokumen penerimaan ini sudah pernah dicatat untuk cabang dan produk tersebut.',
            'errors' => [
                'no_dokumen_pusat' => [
                    'Dokumen penerimaan sudah terdaftar.',
                ],
            ],
        ], 422);
    }

        $hasilIntegrasi = null;
        $statusSinkronisasi = 'berhasil';

        try {
            $hasilIntegrasi = $integrasi->kirimPenerimaanBarang([
                'id_penerimaan' => $result->id_penerimaan,
                'id_cabang' => $result->id_cabang,
                'tanggal' => $result->tanggal,
            ]);
        } catch (\RuntimeException $e) {
            $statusSinkronisasi = 'gagal';

            Log::warning('Sinkronisasi penerimaan barang ke pusat gagal.', [
                'id_penerimaan' => $result->id_penerimaan,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Penerimaan barang berhasil dicatat dan stok berhasil diperbarui.',
            'data' => $result,
            'sinkronisasi_pusat' => $statusSinkronisasi,
            'respons_pusat' => $hasilIntegrasi,
        ], 201);
    }
}
