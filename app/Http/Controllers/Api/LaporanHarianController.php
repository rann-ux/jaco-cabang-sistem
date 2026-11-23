<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanHarian;
use App\Models\Karyawan;
use App\Models\KasTransaksi;
use App\Models\Faktur;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\IntegrasiPusatService;

class LaporanHarianController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'sometimes|required|integer|exists:cabangs,id_cabang',
            'tanggal' => 'sometimes|required|date',
            'jenis_laporan' => 'sometimes|required|in:penjualan,stok,kas_masuk,kas_keluar',
        ]);

        $query = LaporanHarian::with(['cabang', 'karyawan'])
            ->orderByDesc('tanggal');

        if (isset($validated['id_cabang'])) {
            $query->where('id_cabang', $validated['id_cabang']);
        }

        if (isset($validated['tanggal'])) {
            $query->whereDate('tanggal', $validated['tanggal']);
        }

        if (isset($validated['jenis_laporan'])) {
            $query->where('jenis_laporan', $validated['jenis_laporan']);
        }

        return response()->json([
            'message' => 'Daftar laporan harian berhasil diambil.',
            'data' => $query->get(),
        ]);
    }

    public function show($id)
    {
        $laporan = LaporanHarian::with(['cabang', 'karyawan'])
            ->findOrFail($id);

        $ringkasan = [];

    if (in_array($laporan->jenis_laporan, ['kas_masuk', 'kas_keluar'])) {
        $jenisKas = $laporan->jenis_laporan === 'kas_masuk'
            ? 'masuk'
            : 'keluar';

        $transaksi = KasTransaksi::where('id_cabang', $laporan->id_cabang)
            ->whereDate('tanggal', $laporan->tanggal)
            ->where('jenis', $jenisKas);

        $ringkasan = [
            'jenis' => $jenisKas,
            'jumlah_transaksi' => (clone $transaksi)->count(),
            'total' => (clone $transaksi)->sum('jumlah'),
            'transaksi' => $transaksi->orderBy('tanggal')->get(),
        ];
    } elseif ($laporan->jenis_laporan === 'penjualan') {
        $faktur = Faktur::with(['pelanggan', 'karyawan'])
            ->where('id_cabang', $laporan->id_cabang)
            ->whereDate('tanggal', $laporan->tanggal);

        $ringkasan = [
            'jumlah_faktur' => (clone $faktur)->count(),
            'total_penjualan' => (clone $faktur)->sum('total'),
            'faktur' => $faktur->orderBy('tanggal')->get(),
        ];
    } elseif ($laporan->jenis_laporan === 'stok') {
        $stok = Stok::with('produk')
            ->where('id_cabang', $laporan->id_cabang);

        $ringkasan = [
            'jumlah_jenis_produk' => (clone $stok)->count(),
            'total_unit_stok' => (clone $stok)->sum('jumlah'),
            'jumlah_produk_mencapai_batas_minimum' => (clone $stok)
                ->whereColumn('jumlah', '<=', 'batas_minimum')
                ->count(),
            'stok' => $stok->orderBy('jumlah')->get(),
        ];
    }

        return response()->json([
            'message' => 'Detail laporan harian berhasil diambil.',
            'data' => $laporan,
            'ringkasan' => $ringkasan,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer|exists:cabangs,id_cabang',
            'id_karyawan' => 'required|integer|exists:karyawans,id_karyawan',
            'tanggal' => 'required|date',
            'jenis_laporan' => 'required|in:penjualan,stok,kas_masuk,kas_keluar',
        ]);

        $karyawan = Karyawan::where(
            'id_karyawan',
            $validated['id_karyawan']
        )
            ->where('id_cabang', $validated['id_cabang'])
            ->where('status', 'aktif')
            ->first();

        if (!$karyawan) {
            return response()->json([
                'message' => 'Karyawan tidak aktif atau bukan bagian dari cabang tersebut.',
            ], 422);
        }

        $laporan = LaporanHarian::firstOrCreate(
            [
                'id_cabang' => $validated['id_cabang'],
                'tanggal' => $validated['tanggal'],
                'jenis_laporan' => $validated['jenis_laporan'],
            ],
            [
                'id_karyawan' => $validated['id_karyawan'],
                'status_kirim' => 'belum_kirim',
                'tgl_kirim' => null,
            ]
        );

        if (!$laporan->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Laporan untuk cabang, tanggal, dan jenis tersebut sudah tersedia.',
                'data' => $laporan,
            ], 409);
        }

        return response()->json([
            'message' => 'Laporan harian berhasil dibuat.',
            'data' => $laporan,
        ], 201);
    }

    public function kirim($id, IntegrasiPusatService $integrasi)
    {
        $laporan = LaporanHarian::findOrFail($id);

        if ($laporan->status_kirim === 'terkirim') {
            return response()->json([
                'message' => 'Laporan ini sudah pernah dikirim.',
            ], 422);
        }

        try {
            $hasilIntegrasi = $integrasi->kirimLaporanHarian([
                'id_laporan' => $laporan->id_laporan,
                'id_cabang' => $laporan->id_cabang,
                'tanggal' => $laporan->tanggal,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Pengiriman laporan ke sistem pusat gagal.',
                'detail' => $e->getMessage(),
            ], 502);
        }

        $laporan->update([
            'status_kirim' => 'terkirim',
            'tgl_kirim' => now(),
        ]);

        return response()->json([
            'message' => 'Laporan berhasil dikirim ke sistem pusat.',
            'data' => $laporan->fresh(),
            'respons_pusat' => $hasilIntegrasi,
        ]);
    }
}
