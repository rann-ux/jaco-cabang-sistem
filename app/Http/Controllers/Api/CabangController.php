<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CabangController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Daftar cabang berhasil diambil.',
            'data' => Cabang::orderBy('id_cabang')->get(),
        ]);
    }

    public function show($id)
    {
        $cabang = Cabang::findOrFail($id);

        return response()->json([
            'message' => 'Detail cabang berhasil diambil.',
            'data' => $cabang,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:100',
            'jenis' => 'required|in:showroom,kantor,gudang',
            'alamat' => 'required|string',
        ]);

        $cabang = Cabang::create($validated);

        return response()->json([
            'message' => 'Cabang berhasil ditambahkan.',
            'data' => $cabang,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cabang = Cabang::findOrFail($id);

        $validated = $request->validate([
            'nama_cabang' => 'sometimes|required|string|max:100',
            'jenis' => 'sometimes|required|in:showroom,kantor,gudang',
            'alamat' => 'sometimes|required|string',
        ]);

        $cabang->update($validated);

        return response()->json([
            'message' => 'Cabang berhasil diperbarui.',
            'data' => $cabang->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $cabang = Cabang::findOrFail($id);

        $cabang->delete();

        return response()->json([
            'message' => 'Cabang berhasil dihapus.',
        ]);
    }
}
