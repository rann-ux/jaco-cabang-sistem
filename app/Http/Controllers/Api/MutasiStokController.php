<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\MutasiStok;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutasiStokController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer|exists:cabangs,id_cabang',
            'id_produk' => 'required|integer|exists:produks,id_produk',
            'id_karyawan' => 'required|integer|exists:karyawans,id_karyawan',
            'jenis' => 'required|in:masuk,keluar',
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'sometimes|date',
            'referensi_dokumen' => 'nullable|string|max:50',
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        $result = DB::transaction(function () use ($validated, $tanggal) {

            $karyawanSesuai = Karyawan::where('id_karyawan', $validated['id_karyawan'])
                ->where('id_cabang', $validated['id_cabang'])
                ->where('status', 'aktif')
                ->exists();

            if (!$karyawanSesuai) {
                throw ValidationException::withMessages([
                    'id_karyawan' => 'Karyawan tidak aktif atau tidak terdaftar pada cabang yang dipilih.',
                ]);
            }

            $stok = Stok::where('id_cabang', $validated['id_cabang'])
                ->where('id_produk', $validated['id_produk'])
                ->lockForUpdate()
                ->first();

            if (!$stok) {
                throw ValidationException::withMessages([
                    'id_produk' => 'Stok produk tidak ditemukan pada cabang ini.',
                ]);
            }

            if (
                $validated['jenis'] === 'keluar' &&
                $stok->jumlah < $validated['jumlah']
            ) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok->jumlah,
                ]);
            }

            if ($validated['jenis'] === 'masuk') {
                $stok->jumlah += $validated['jumlah'];
            } else {
                $stok->jumlah -= $validated['jumlah'];
            }

            $stok->tgl_update = $tanggal;
            $stok->save();

            $mutasi = MutasiStok::create([
                'id_stok' => $stok->id_stok,
                'id_karyawan' => $validated['id_karyawan'],
                'jenis' => $validated['jenis'],
                'jumlah' => $validated['jumlah'],
                'tanggal' => $tanggal,
                'referensi_dokumen' => $validated['referensi_dokumen'] ?? null,
            ]);

            return $mutasi->load([
                'stok.cabang',
                'stok.produk',
                'karyawan',
            ]);
        });

        return response()->json([
            'message' => 'Mutasi stok berhasil dicatat dan stok berhasil diperbarui.',
            'data' => $result,
        ], 201);
    }
}
