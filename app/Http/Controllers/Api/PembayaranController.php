<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faktur;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'metode' => 'required|in:tunai,debit,transfer',
            'jumlah' => 'required|numeric|gt:0',
            'tanggal' => 'sometimes|date',
        ]);

        $pembayaran = DB::transaction(function () use ($validated, $id) {

            $faktur = Faktur::where('id_faktur', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($faktur->status === 'lunas') {
                throw ValidationException::withMessages([
                    'id_faktur' => 'Faktur sudah lunas dan tidak menerima pembayaran lagi.',
                ]);
            }

            $totalTerverifikasi = Pembayaran::where('id_faktur', $faktur->id_faktur)
                ->where('status_verifikasi_sales', true)
                ->sum('jumlah');

            $totalMenungguVerifikasi = Pembayaran::where('id_faktur', $faktur->id_faktur)
                ->where('status_verifikasi_sales', false)
                ->sum('jumlah');

            $sisaTagihan = (float) $faktur->total - (float) $totalTerverifikasi;

            if ((float) $validated['jumlah'] > $sisaTagihan - (float) $totalMenungguVerifikasi) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah pembayaran melebihi sisa tagihan yang tersedia.',
                ]);
            }

            if (
                $faktur->skema_pembayaran === 'sekali_bayar' &&
                (float) $validated['jumlah'] !== round($sisaTagihan - (float) $totalMenungguVerifikasi, 2)
            ) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Pembayaran dengan skema sekali bayar harus melunasi seluruh sisa tagihan.',
                ]);
            }

            return Pembayaran::create([
                'id_faktur' => $faktur->id_faktur,
                'metode' => $validated['metode'],
                'jumlah' => $validated['jumlah'],
                'tanggal' => $validated['tanggal'] ?? now(),
                'status_verifikasi_sales' => false,
                'status_cek_pusat' => false,
                'tgl_cek_pusat' => null,
            ]);
        });

        return response()->json([
            'message' => 'Pembayaran berhasil dicatat dan menunggu verifikasi sales.',
            'data' => $pembayaran->load('faktur'),
        ], 201);
    }

    public function verifySales($id)
    {
        $result = DB::transaction(function () use ($id) {

            $pembayaran = Pembayaran::where('id_bayar', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pembayaran->status_verifikasi_sales) {
                throw ValidationException::withMessages([
                    'id_bayar' => 'Pembayaran sudah diverifikasi oleh sales.',
                ]);
            }

            $faktur = Faktur::where('id_faktur', $pembayaran->id_faktur)
                ->lockForUpdate()
                ->firstOrFail();

            $totalTerverifikasi = (float) Pembayaran::where(
                'id_faktur',
                $faktur->id_faktur
            )
                ->where('status_verifikasi_sales', true)
                ->sum('jumlah');

            $totalSetelahVerifikasi = $totalTerverifikasi
                + (float) $pembayaran->jumlah;

            if ($totalSetelahVerifikasi > (float) $faktur->total) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Total pembayaran terverifikasi melebihi total faktur.',
                ]);
            }

            $pembayaran->update([
                'status_verifikasi_sales' => true,
            ]);

            // Perbarui status faktur
            if ($totalSetelahVerifikasi >= (float) $faktur->total) {
                $faktur->status = 'lunas';
            } elseif ($totalSetelahVerifikasi > 0) {
                $faktur->status = 'dibayar_sebagian';
            } else {
                $faktur->status = 'belum_dibayar';
            }

            $faktur->save();

            // Catat kas masuk otomatis
            \App\Models\KasTransaksi::create([
                'id_cabang' => $faktur->id_cabang,
                'id_karyawan' => $faktur->id_karyawan,
                'id_faktur' => $faktur->id_faktur,
                'tanggal' => $pembayaran->tanggal,
                'jenis' => 'masuk',
                'jumlah' => $pembayaran->jumlah,
                'keterangan' => 'Pembayaran faktur ' . $faktur->no_faktur,
            ]);

            return $pembayaran->load('faktur');
        });

        return response()->json([
            'message' => 'Pembayaran berhasil diverifikasi dan kas masuk telah dicatat.',
            'data' => $result,
        ]);
    }

    public function cekPusat($id)
    {
        $pembayaran = DB::transaction(function () use ($id) {

            $pembayaran = Pembayaran::where('id_bayar', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$pembayaran->status_verifikasi_sales) {
                throw ValidationException::withMessages([
                    'id_bayar' => 'Pembayaran belum diverifikasi oleh sales.',
                ]);
            }

            if ($pembayaran->status_cek_pusat) {
                throw ValidationException::withMessages([
                    'id_bayar' => 'Pembayaran sudah diperiksa oleh sistem pusat.',
                ]);
            }

            $pembayaran->update([
                'status_cek_pusat' => true,
                'tgl_cek_pusat' => now(),
            ]);

            return $pembayaran->load('faktur');
        });

        return response()->json([
            'message' => 'Pembayaran berhasil diperiksa oleh sistem pusat.',
            'data' => $pembayaran,
        ]);
    }

}
