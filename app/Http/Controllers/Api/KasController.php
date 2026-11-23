<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KasTransaksi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'sometimes|required|integer|exists:cabangs,id_cabang',
            'tanggal' => 'sometimes|required|date',
            'jenis' => 'sometimes|required|in:masuk,keluar,setoran',
        ]);

        $query = KasTransaksi::with(['cabang', 'karyawan', 'faktur'])
            ->orderByDesc('tanggal');

        if (isset($validated['id_cabang'])) {
            $query->where('id_cabang', $validated['id_cabang']);
        }

        if (isset($validated['tanggal'])) {
            $query->whereDate('tanggal', $validated['tanggal']);
        }

        if (isset($validated['jenis'])) {
            $query->where('jenis', $validated['jenis']);
        }

        return response()->json([
            'message' => 'Daftar transaksi kas berhasil diambil.',
            'data' => $query->get(),
        ]);
    }

    public function show($id)
    {
        $transaksi = KasTransaksi::with([
            'cabang',
            'karyawan',
            'faktur',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Detail transaksi kas berhasil diambil.',
            'data' => $transaksi,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer|exists:cabangs,id_cabang',
            'id_karyawan' => 'required|integer|exists:karyawans,id_karyawan',
            'jenis' => 'required|in:keluar,setoran',
            'jumlah' => 'required|numeric|gt:0',
            'tanggal' => 'sometimes|required|date',
            'keterangan' => 'nullable|string|max:255',
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

        $transaksi = DB::transaction(function () use ($validated) {
            return KasTransaksi::create([
                'id_cabang' => $validated['id_cabang'],
                'id_karyawan' => $validated['id_karyawan'],
                'id_faktur' => null,
                'tanggal' => $validated['tanggal'] ?? now(),
                'jenis' => $validated['jenis'],
                'jumlah' => $validated['jumlah'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Transaksi kas berhasil dicatat.',
            'data' => $transaksi->load(['cabang', 'karyawan']),
        ], 201);
    }
}
