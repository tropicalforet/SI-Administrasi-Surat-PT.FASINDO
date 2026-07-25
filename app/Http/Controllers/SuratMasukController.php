<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Helpers\ActivityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Tambahkan ini untuk manajemen file

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $query = SuratMasuk::query();

        // Pencarian Dinamis
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('pengirim', 'like', "%{$search}%")
                  ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_awal')) {
            $query->whereDate('tanggal_surat', '>=', $request->tanggal_awal);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal_surat', '<=', $request->tanggal_akhir);
        }

        // Gunakan paginate() alih-alih get() agar memori server aman
        $data = $query->latest()->paginate(10)->withQueryString();

        return view('surat_masuk.index', compact('data'));
    }

    public function create()
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');
        return view('surat_masuk.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        $validated = $request->validate([
            'nomor_surat'    => 'required|unique:surat_masuks,nomor_surat', // Pastikan nomor surat tidak ganda
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'pengirim'       => 'required|string',
            'penerima'       => 'required|string',
            'perihal'        => 'required|string',
            'file'           => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $kategori = $validated['kategori_surat'] === 'Lainnya' ? $validated['kategori_surat_lainnya'] : $validated['kategori_surat'];

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('surat_masuk', 'public');
        }

        $suratMasuk = SuratMasuk::create([
            'nomor_surat'    => $validated['nomor_surat'],
            'kategori_surat' => $kategori,
            'tanggal_surat'  => $validated['tanggal_surat'],
            'pengirim'       => $validated['pengirim'],
            'penerima'       => $validated['penerima'],
            'perihal'        => $validated['perihal'],
            'file'           => $filePath,
            'status'         => 'baru',
        ]);

        ActivityHelper::log('Tambah Surat Masuk', 'Menambahkan surat ' . $suratMasuk->nomor_surat);

        return redirect()->route('surat-masuk.index')->with('success', 'Surat berhasil ditambahkan');
    }

    public function edit(SuratMasuk $surat_masuk)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');
        return view('surat_masuk.edit', compact('surat_masuk'));
    }

    public function update(Request $request, SuratMasuk $surat_masuk)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        $validated = $request->validate([
            'nomor_surat'    => 'required|unique:surat_masuks,nomor_surat,' . $surat_masuk->id, // Abaikan validasi unique untuk surat ini sendiri
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'pengirim'       => 'required|string',
            'penerima'       => 'required|string',
            'perihal'        => 'required|string',
            'file'           => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $kategori = $validated['kategori_surat'] === 'Lainnya' ? $validated['kategori_surat_lainnya'] : $validated['kategori_surat'];

        $filePath = $surat_masuk->file;

        // Jika ada file baru yang diunggah
        if ($request->hasFile('file')) {
            // Hapus file lama dari storage jika ada
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            // Simpan file baru
            $filePath = $request->file('file')->store('surat_masuk', 'public');
        }

        $surat_masuk->update([
            'nomor_surat'    => $validated['nomor_surat'],
            'kategori_surat' => $kategori,
            'tanggal_surat'  => $validated['tanggal_surat'],
            'pengirim'       => $validated['pengirim'],
            'penerima'       => $validated['penerima'],
            'perihal'        => $validated['perihal'],
            'file'           => $filePath,
        ]);

        ActivityHelper::log('Edit Surat Masuk', 'Mengubah surat ' . $surat_masuk->nomor_surat);

        return redirect()->route('surat-masuk.index')->with('success', 'Surat berhasil diupdate');
    }

    public function destroy(SuratMasuk $surat_masuk)
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus data.');

        // Hapus file fisik dari server sebelum data dihapus dari database
        if ($surat_masuk->file && Storage::disk('public')->exists($surat_masuk->file)) {
            Storage::disk('public')->delete($surat_masuk->file);
        }

        ActivityHelper::log('Hapus Surat Masuk', 'Menghapus surat ' . $surat_masuk->nomor_surat);
        
        $surat_masuk->delete();

        return redirect()->route('surat-masuk.index')->with('success', 'Surat berhasil dihapus');
    }

    public function clear()
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus semua data.');

        // Hapus semua file di folder surat_masuk
        Storage::disk('public')->deleteDirectory('surat_masuk');
        Storage::disk('public')->makeDirectory('surat_masuk');

        ActivityHelper::log('Hapus Semua Surat Masuk', 'Menghapus seluruh data surat masuk');
        
        // Hapus menggunakan Eloquent agar event dan foreign key cascade (jika ada) terpicu
        SuratMasuk::query()->delete();

        return redirect()->route('surat-masuk.index')->with('success', 'Semua riwayat surat masuk berhasil dihapus secara permanen.');
    }
}