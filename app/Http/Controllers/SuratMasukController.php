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

        // Filter Hak Akses
        $user = auth()->user();
        if (!in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            $query->where(function($q) use ($user) {
                $q->where('penerima_id', $user->id)
                  ->orWhereHas('disposisis', function($sq) use ($user) {
                      $sq->where('kepada_user_id', $user->id);
                  });
            });
        }

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

    /**
     * Aturan nomor surat yang membedakan bentrokan dengan surat aktif dan
     * dengan surat yang berada di arsip terhapus. Tanpa pembedaan ini
     * pengguna menerima pesan "sudah digunakan" untuk surat yang tidak
     * terlihat di daftar mana pun.
     *
     * @param  SuratMasuk|null  $kecuali  Surat yang sedang diedit.
     */
    private function aturanNomorSurat(?SuratMasuk $kecuali = null): array
    {
        return [
            'required',
            function ($attribute, $value, $fail) use ($kecuali) {
                $query = SuratMasuk::withTrashed()->where('nomor_surat', $value);

                if ($kecuali) {
                    $query->where('id', '!=', $kecuali->id);
                }

                $bentrok = $query->first();

                if (!$bentrok) {
                    return;
                }

                $fail($bentrok->trashed()
                    ? 'Nomor surat ini dipakai dokumen di arsip terhapus. Pulihkan dokumen tersebut atau gunakan nomor lain.'
                    : 'Nomor surat sudah digunakan.');
            },
        ];
    }

    public function create()
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');
        $users = \App\Models\User::orderBy('role')->orderBy('name')->get();
        return view('surat_masuk.create', compact('users'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        $validated = $request->validate([
            'nomor_surat'    => $this->aturanNomorSurat(),
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'pengirim'       => 'required|string',
            'penerima_id'    => 'required|exists:users,id',
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
            'penerima_id'    => $validated['penerima_id'],
            'penerima'       => \App\Models\User::find($validated['penerima_id'])->name, // Simpan nama penerima sebagai backup/kompatibilitas
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
        $users = \App\Models\User::orderBy('role')->orderBy('name')->get();
        return view('surat_masuk.edit', compact('surat_masuk', 'users'));
    }

    public function update(Request $request, SuratMasuk $surat_masuk)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        $validated = $request->validate([
            'nomor_surat'    => $this->aturanNomorSurat($surat_masuk),
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'pengirim'       => 'required|string',
            'penerima_id'    => 'required|exists:users,id',
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
            'penerima_id'    => $validated['penerima_id'],
            'penerima'       => \App\Models\User::find($validated['penerima_id'])->name, // Simpan nama penerima
            'perihal'        => $validated['perihal'],
            'file'           => $filePath,
        ]);

        ActivityHelper::log('Edit Surat Masuk', 'Mengubah surat ' . $surat_masuk->nomor_surat);

        return redirect()->route('surat-masuk.index')->with('success', 'Surat berhasil diupdate');
    }

    public function destroy(SuratMasuk $surat_masuk)
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus data.');

        // Berkas fisik sengaja dipertahankan karena data masih dapat dipulihkan
        // dari arsip terhapus.
        ActivityHelper::log('Hapus Surat Masuk', 'Menghapus surat ' . $surat_masuk->nomor_surat);
        
        $surat_masuk->delete();

        return redirect()->route('surat-masuk.index')->with('success', 'Surat berhasil dihapus');
    }

    public function clear()
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus semua data.');

        ActivityHelper::log('Hapus Semua Surat Masuk', 'Menghapus seluruh data surat masuk');

        // Penghapusan bersifat sementara: data pindah ke arsip terhapus dan
        // berkas fisiknya tetap tersimpan.
        SuratMasuk::query()->delete();

        return redirect()->route('surat-masuk.index')->with('success', 'Semua surat masuk dipindahkan ke arsip terhapus dan masih dapat dipulihkan.');
    }
}