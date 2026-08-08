<?php

namespace App\Http\Controllers;

use App\Models\SuratTugas;
use App\Models\User;
use App\Helpers\ActivityHelper;
use App\Helpers\NomorDokumenHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratTugasController extends Controller
{
    public function index()
    {
        $role = strtolower(auth()->user()->role);

        if ($role === 'sekretaris') {
            $data = SuratTugas::with('user')->latest()->paginate(10);
        } elseif ($role === 'dirut') {
            $data = SuratTugas::with('user')->latest()->paginate(10);
        } else {
            // Pegawai biasa melihat data miliknya sendiri
            $data = SuratTugas::where('user_id', auth()->id())
                        ->latest()
                        ->paginate(10);
        }

        return view('surat_tugas.index', compact('data'));
    }

    public function create()
    {
        $role = strtolower(auth()->user()->role);
        $users = [];
        if (in_array($role, ['dirut', 'direktur1', 'direktur2', 'sekretaris'])) {
            $users = User::all();
        }
        return view('surat_tugas.create', compact('users'));
    }

    public function store(Request $request)
    {
        $rules = [
            'perihal_tugas'           => 'required|string',
            'tujuan'                  => 'required|string',
            'tanggal_mulai'           => 'required|date',
            'tanggal_selesai'         => 'required|date|after_or_equal:tanggal_mulai',
            'file'                    => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ];

        // Jika Dirut dll yang buat, mereka bisa pilih user_id. Jika staff, user_id otomatis miliknya
        $role = strtolower(auth()->user()->role);
        $userId = auth()->id();
        $ditugaskanOleh = null;

        if (in_array($role, ['dirut', 'direktur1', 'direktur2', 'sekretaris'])) {
            $rules['user_id'] = 'required|exists:users,id';
            $validated = $request->validate($rules);
            $userId = $request->user_id;
            $ditugaskanOleh = auth()->id();
        } else {
            $validated = $request->validate($rules);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('surat_tugas', 'public');
        }

        // Nomor urut diambil dari counter terkunci per tahun. Sebelumnya dipakai
        // max(id)+1 yang menghasilkan nomor ganda begitu ada data yang dihapus.
        $nomorUrut = NomorDokumenHelper::next('surat_tugas', (int) date('Y'));
        $nomor_surat_tugas = 'ST-' . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT) . '/' . date('m') . '/' . date('Y');

        $suratTugas = SuratTugas::create([
            'nomor_surat_tugas'       => $nomor_surat_tugas,
            'user_id'                 => $userId,
            'ditugaskan_oleh'         => $ditugaskanOleh,
            'perihal_tugas'           => $request->perihal_tugas,
            'tujuan'                  => $request->tujuan,
            'tanggal_mulai'           => $request->tanggal_mulai,
            'tanggal_selesai'         => $request->tanggal_selesai,
            'status'                  => 'draft',
            'file'                    => $filePath,
        ]);

        ActivityHelper::log('Tambah Surat Tugas', 'Membuat Surat Tugas nomor ' . $nomor_surat_tugas);

        return redirect()->route('surat-tugas.index')->with('success', 'Surat Tugas berhasil dibuat dan disimpan sebagai draft.');
    }

    public function show(SuratTugas $surat_tuga)
    {
        $surat_tugas = $surat_tuga;
        $role = strtolower(auth()->user()->role);

        // Security check
        if (!in_array($role, ['sekretaris', 'dirut']) && $surat_tugas->user_id !== auth()->id() && $surat_tugas->ditugaskan_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $surat_tugas->load(['user', 'penugasOleh', 'skpd']);
        
        return view('surat_tugas.show', compact('surat_tugas'));
    }

    public function edit(SuratTugas $surat_tuga)
    {
        $surat_tugas = $surat_tuga;
        $role = strtolower(auth()->user()->role);

        // Hanya bisa diedit jika draft
        if ($surat_tugas->status !== 'draft') {
            abort(403, 'Surat Tugas yang sudah diterbitkan tidak dapat diedit.');
        }

        // Security check
        if ($role !== 'sekretaris' && $surat_tugas->user_id !== auth()->id() && $surat_tugas->ditugaskan_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $users = [];
        if (in_array($role, ['dirut', 'direktur1', 'direktur2', 'sekretaris'])) {
            $users = User::all();
        }

        return view('surat_tugas.edit', compact('surat_tugas', 'users'));
    }

    public function update(Request $request, SuratTugas $surat_tuga)
    {
        $surat_tugas = $surat_tuga;
        $role = strtolower(auth()->user()->role);

        if ($surat_tugas->status !== 'draft') {
            abort(403, 'Surat Tugas yang sudah diterbitkan tidak dapat diedit.');
        }

        // Security check
        if ($role !== 'sekretaris' && $surat_tugas->user_id !== auth()->id() && $surat_tugas->ditugaskan_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'perihal_tugas'           => 'required|string',
            'tujuan'                  => 'required|string',
            'tanggal_mulai'           => 'required|date',
            'tanggal_selesai'         => 'required|date|after_or_equal:tanggal_mulai',
            'file'                    => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ];

        $userId = $surat_tugas->user_id;
        
        if (in_array($role, ['dirut', 'direktur1', 'direktur2', 'sekretaris']) && $surat_tugas->ditugaskan_oleh) {
            $rules['user_id'] = 'required|exists:users,id';
            $validated = $request->validate($rules);
            $userId = $request->user_id;
        } else {
            $validated = $request->validate($rules);
        }

        $filePath = $surat_tugas->file;
        if ($request->hasFile('file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file')->store('surat_tugas', 'public');
        }

        $surat_tugas->update([
            'user_id'                 => $userId,
            'perihal_tugas'           => $request->perihal_tugas,
            'tujuan'                  => $request->tujuan,
            'tanggal_mulai'           => $request->tanggal_mulai,
            'tanggal_selesai'         => $request->tanggal_selesai,
            'file'                    => $filePath,
        ]);

        ActivityHelper::log('Edit Surat Tugas', 'Memperbarui Surat Tugas nomor ' . $surat_tugas->nomor_surat_tugas);

        return redirect()->route('surat-tugas.index')->with('success', 'Surat Tugas berhasil diupdate');
    }

    public function approve(SuratTugas $surat_tugas)
    {
        if (strtolower(auth()->user()->role) !== 'dirut') {
            abort(403, 'Hanya Direktur Utama yang dapat menerbitkan Surat Tugas.');
        }

        $surat_tugas->update([
            'status' => 'diterbitkan'
        ]);

        ActivityHelper::log('Terbitkan Surat Tugas', 'Menerbitkan Surat Tugas nomor ' . $surat_tugas->nomor_surat_tugas);

        return redirect()->route('surat-tugas.show', $surat_tugas->id)->with('success', 'Surat Tugas berhasil diterbitkan.');
    }

    public function destroy(SuratTugas $surat_tuga)
    {
        $surat_tugas = $surat_tuga;
        $role = strtolower(auth()->user()->role ?? '');
        
        if ($role !== 'sekretaris' && $surat_tugas->user_id !== auth()->id() && $surat_tugas->ditugaskan_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus data Surat Tugas ini.');
        }

        if ($surat_tugas->status !== 'draft') {
            abort(403, 'Surat Tugas yang sudah diterbitkan tidak dapat dihapus.');
        }

        // Berkas fisik dipertahankan agar Surat Tugas masih dapat dipulihkan.
        ActivityHelper::log('Hapus Surat Tugas', 'Menghapus Surat Tugas nomor ' . $surat_tugas->nomor_surat_tugas);

        $surat_tugas->delete();

        return redirect()->route('surat-tugas.index')->with('success', 'Surat Tugas berhasil dihapus');
    }
}
