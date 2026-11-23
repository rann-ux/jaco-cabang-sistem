<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Daftar pelanggan berhasil diambil.',
            'data' => Pelanggan::orderBy('id_pelanggan')->get(),
        ]);
    }

    public function show($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        return response()->json([
            'message' => 'Detail pelanggan berhasil diambil.',
            'data' => $pelanggan,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'alamat' => 'required|string',
            'no_hp' => 'required|string|max:20',
        ]);

        $pelanggan = Pelanggan::create($validated);

        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan.',
            'data' => $pelanggan,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:100',
            'alamat' => 'sometimes|required|string',
            'no_hp' => 'sometimes|required|string|max:20',
        ]);

        $pelanggan->update($validated);

        return response()->json([
            'message' => 'Pelanggan berhasil diperbarui.',
            'data' => $pelanggan->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $pelanggan->delete();

        return response()->json([
            'message' => 'Pelanggan berhasil dihapus.',
        ]);
    }
}
