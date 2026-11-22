<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\DetailFaktur;
use App\Models\Faktur;
use App\Models\Karyawan;
use App\Models\MutasiStok;
use App\Models\POPengantaran;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FakturController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer|exists:cabangs,id_cabang',
            'id_pelanggan' => 'required|integer|exists:pelanggans,id_pelanggan',
            'id_karyawan' => 'required|integer|exists:karyawans,id_karyawan',
            'tanggal' => 'required|date',

            'skema_pembayaran' => 'sometimes|in:sekali_bayar,cicilan',

            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|integer|distinct|exists:produks,id_produk',
            'items.*.jumlah' => 'required|integer|min:1',
        ]);

        $skemaPembayaran = $validated['skema_pembayaran'] ?? 'sekali_bayar';

        $result = DB::transaction(function () use ($validated, $skemaPembayaran) {

            // Kunci data cabang untuk menjaga urutan nomor faktur
            $cabang = Cabang::where('id_cabang', $validated['id_cabang'])
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan karyawan berasal dari cabang yang sama
            $karyawanSesuai = Karyawan::where('id_karyawan', $validated['id_karyawan'])
                ->where('id_cabang', $cabang->id_cabang)
                ->where('status', 'aktif')
                ->exists();

            if (!$karyawanSesuai) {
                throw ValidationException::withMessages([
                    'id_karyawan' => 'Karyawan tidak aktif atau tidak terdaftar pada cabang yang dipilih.',
                ]);
            }

            // Nomor urut faktur berdasarkan cabang dan tanggal
            $nomorHarian = Faktur::where('id_cabang', $cabang->id_cabang)
                ->whereDate('tanggal', $validated['tanggal'])
                ->count() + 1;

            // Prefix menggunakan ID cabang
            $prefixCabang = 'CB' . str_pad(
                (string) $cabang->id_cabang,
                3,
                '0',
                STR_PAD_LEFT
            );

            $noFaktur = $prefixCabang . '-' .
                date('Ymd', strtotime($validated['tanggal'])) . '-' .
                str_pad((string) $nomorHarian, 3, '0', STR_PAD_LEFT);

            $detailItems = [];
            $total = 0;

            // Periksa dan kunci stok setiap produk
            foreach ($validated['items'] as $index => $item) {

                $produk = Produk::findOrFail($item['id_produk']);

                $stok = Stok::where('id_cabang', $cabang->id_cabang)
                    ->where('id_produk', $item['id_produk'])
                    ->lockForUpdate()
                    ->first();

                if (!$stok) {
                    throw ValidationException::withMessages([
                        "items.$index.id_produk" =>
                            'Stok produk tidak ditemukan pada cabang ini.',
                    ]);
                }

                if ($stok->jumlah < $item['jumlah']) {
                    throw ValidationException::withMessages([
                        "items.$index.jumlah" =>
                            'Stok tidak mencukupi. Stok tersedia: ' . $stok->jumlah,
                    ]);
                }

                $harga = (float) $produk->harga;
                $subtotal = $harga * $item['jumlah'];

                $total += $subtotal;

                $detailItems[] = [
                    'id_produk' => $produk->id_produk,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal,
                    'stok' => $stok,
                ];
            }

            // Buat faktur
            $faktur = Faktur::create([
                'no_faktur' => $noFaktur,
                'tanggal' => $validated['tanggal'],
                'total' => $total,
                'skema_pembayaran' => $skemaPembayaran,
                'status' => 'belum_dibayar',
                'id_cabang' => $cabang->id_cabang,
                'id_pelanggan' => $validated['id_pelanggan'],
                'id_karyawan' => $validated['id_karyawan'],
                'id_promo' => null,
            ]);

            // Simpan detail dan kurangi stok
            foreach ($detailItems as $item) {

                DetailFaktur::create([
                    'id_faktur' => $faktur->id_faktur,
                    'id_produk' => $item['id_produk'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);

                $stok = $item['stok'];
                $stok->jumlah -= $item['jumlah'];
                $stok->tgl_update = $validated['tanggal'];
                $stok->save();

                // Catat mutasi stok keluar
                MutasiStok::create([
                    'id_stok' => $stok->id_stok,
                    'id_karyawan' => $validated['id_karyawan'],
                    'jenis' => 'keluar',
                    'jumlah' => $item['jumlah'],
                    'tanggal' => $validated['tanggal'],
                    'referensi_dokumen' => $noFaktur,
                ]);
            }

            // Buat PO pengantaran
            POPengantaran::create([
                'id_faktur' => $faktur->id_faktur,
                'tanggal' => $validated['tanggal'],
                'status' => 'baru',
            ]);

            return $faktur->load([
                'detailFakturs.produk',
                'pelanggan',
                'cabang',
                'karyawan',
                'poPengantaran',
            ]);
        });

        return response()->json([
            'message' => 'Faktur berhasil dibuat.',
            'data' => $result,
        ], 201);
    }
}