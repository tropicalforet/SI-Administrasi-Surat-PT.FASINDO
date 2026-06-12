<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use Illuminate\Http\Request;

class SuratKeluarController extends Controller
{
    public function index()
    {
        $data = SuratKeluar::latest()->get();

        return view('surat_keluar.index', compact('data'));
    }

    public function create()
    {
        return view('surat_keluar.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_surat' => 'required',
            'tanggal_surat' => 'required|date',
            'tujuan' => 'required',
            'perihal' => 'required',
            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')
                ->store('surat_keluar', 'public');
        }

        SuratKeluar::create([
            'nomor_surat' => $request->nomor_surat,
            'tanggal_surat' => $request->tanggal_surat,
            'tujuan' => $request->tujuan,
            'perihal' => $request->perihal,
            'file' => $filePath,
            'status' => 'draft',
        ]);

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil ditambahkan');
    }

    public function show(SuratKeluar $surat_keluar)
    {
        //
    }

    public function edit(SuratKeluar $surat_keluar)
    {
        return view('surat_keluar.edit', compact('surat_keluar'));
    }

    public function update(Request $request, SuratKeluar $surat_keluar)
    {
        $request->validate([
            'nomor_surat' => 'required',
            'tanggal_surat' => 'required|date',
            'tujuan' => 'required',
            'perihal' => 'required',
            'status' => 'required',
            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $filePath = $surat_keluar->file;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')
                ->store('surat_keluar', 'public');
        }

        $surat_keluar->update([
            'nomor_surat' => $request->nomor_surat,
            'tanggal_surat' => $request->tanggal_surat,
            'tujuan' => $request->tujuan,
            'perihal' => $request->perihal,
            'status' => $request->status,
            'file' => $filePath,
        ]);

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil diupdate');
    }

    public function destroy(SuratKeluar $surat_keluar)
    {
        $surat_keluar->delete();

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dihapus');
    }
}
