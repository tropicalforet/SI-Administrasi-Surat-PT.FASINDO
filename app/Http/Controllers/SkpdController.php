<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Helpers\ActivityHelper;
use App\Helpers\NomorDokumenHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SkpdController extends Controller
{
    public function index()
    {
        $role = strtolower(auth()->user()->role);

        if ($role === 'sekretaris') {
            // Sekretaris melihat semua data SKPD kecuali milik Dirut
            $data = Skpd::whereHas('user', function($q) {
                $q->where('role', '!=', 'dirut');
            })->latest()->paginate(10);
        } elseif ($role === 'dirut') {
            // Dirut melihat data yang sudah berstatus 'diperiksa' (diajukan ke dirut), 'disetujui', atau 'ditolak'
            $data = Skpd::whereIn('status', ['diperiksa', 'disetujui', 'ditolak'])
                        ->orWhere('user_id', auth()->id())
                        ->latest()
                        ->paginate(10);
        } else {
            // Pegawai biasa melihat data miliknya sendiri
            $data = Skpd::where('user_id', auth()->id())
                        ->latest()
                        ->paginate(10);
        }

        return view('skpd.index', compact('data'));
    }

    public function create(Request $request)
    {
        $surat_tugas_id = $request->query('surat_tugas_id');
        if (!$surat_tugas_id) {
            abort(403, 'Akses ditolak. SKPD harus dibuat berdasarkan Surat Tugas yang sudah diterbitkan.');
        }

        $suratTugas = \App\Models\SuratTugas::find($surat_tugas_id);
        if (!$suratTugas || $suratTugas->status !== 'diterbitkan') {
            abort(403, 'Akses ditolak. Surat Tugas tidak ditemukan atau belum diterbitkan.');
        }

        // Cek jika Surat Tugas sudah punya SKPD
        if ($suratTugas->skpd()->exists()) {
            abort(403, 'Akses ditolak. SKPD untuk Surat Tugas ini sudah pernah dibuat.');
        }

        return view('skpd.create', compact('suratTugas'));
    }

    public function store(Request $request)
    {
        $rules = [
            'surat_tugas_id'          => 'required|exists:surat_tugas,id',
            'tujuan_dinas'            => 'required|string',
            'keperluan'               => 'required|string',
            'tanggal_berangkat'       => 'required|date',
            'tanggal_kembali'         => 'required|date|after_or_equal:tanggal_berangkat',
            'file'                    => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        $validated = $request->validate($rules);

        $suratTugas = \App\Models\SuratTugas::findOrFail($request->surat_tugas_id);
        if ($suratTugas->status !== 'diterbitkan') {
            abort(403, 'Surat Tugas belum diterbitkan.');
        }
        if ($suratTugas->skpd()->exists()) {
            abort(403, 'SKPD untuk Surat Tugas ini sudah pernah dibuat.');
        }

        $tanggalBerangkat = Carbon::parse($request->tanggal_berangkat);
        $tanggalKembali = Carbon::parse($request->tanggal_kembali);
        $durasi = $tanggalBerangkat->diffInDays($tanggalKembali) + 1;

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        // Nomor urut diambil dari counter terkunci per tahun. Sebelumnya dipakai
        // max(id)+1 yang menghasilkan nomor ganda begitu ada data yang dihapus.
        $nomorUrut = NomorDokumenHelper::next('skpd', (int) date('Y'));
        $nomor_skpd = 'SKPD-' . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT) . '/' . date('m') . '/' . date('Y');

        // Semua pengajuan baru langsung diperiksa oleh Dirut
        $status = 'diperiksa';

        $skpd = Skpd::create([
            'user_id'                 => auth()->id(),
            'surat_tugas_id'          => $suratTugas->id,
            'nomor_skpd'              => $nomor_skpd,
            'nama_pegawai'            => auth()->user()->name,
            'tujuan_dinas'            => $request->tujuan_dinas,
            'keperluan'               => $request->keperluan,
            'tanggal_berangkat'       => $request->tanggal_berangkat,
            'tanggal_kembali'         => $request->tanggal_kembali,
            'durasi_hari'             => $durasi,
            'file'                    => $filePath,
            'status'                  => $status,
        ]);

        ActivityHelper::log('Tambah SKPD', 'Membuat pengajuan SKPD nomor ' . $nomor_skpd . ' untuk ' . auth()->user()->name);

        return redirect()->route('skpd.index')->with('success', 'SKPD berhasil diajukan');
    }

    public function show(Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);

        // Security check: Hanya Sekretaris, Dirut, atau Pembuat pengajuan yang bisa melihat detail
        if (!in_array($role, ['sekretaris', 'dirut']) && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        return view('skpd.show', compact('skpd'));
    }

    public function edit(Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);

        // Security check: Hanya Sekretaris atau Pembuat pengajuan yang bisa edit
        if ($role !== 'sekretaris' && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        // Pegawai biasa hanya bisa mengedit jika statusnya 'pengajuan' atau 'ditolak'
        if ($role !== 'sekretaris' && !in_array($skpd->status, ['pengajuan', 'ditolak'])) {
            abort(403, 'SKPD sedang dalam proses persetujuan dan tidak dapat diedit.');
        }

        return view('skpd.edit', compact('skpd'));
    }

    public function update(Request $request, Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);

        // Security check
        if ($role !== 'sekretaris' && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        if ($role !== 'sekretaris' && !in_array($skpd->status, ['pengajuan', 'ditolak'])) {
            abort(403, 'SKPD sedang dalam proses persetujuan dan tidak dapat diupdate.');
        }

        $rules = [
            'tujuan_dinas'            => 'required|string',
            'keperluan'               => 'required|string',
            'tanggal_berangkat'       => 'required|date',
            'tanggal_kembali'         => 'required|date|after_or_equal:tanggal_berangkat',
            'file'                    => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        $validated = $request->validate($rules);

        $tanggalBerangkat = Carbon::parse($request->tanggal_berangkat);
        $tanggalKembali = Carbon::parse($request->tanggal_kembali);
        $durasi = $tanggalBerangkat->diffInDays($tanggalKembali) + 1;

        $filePath = $skpd->file;
        if ($request->hasFile('file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        $status = $skpd->status;
        if ($status === 'ditolak') {
            // Pengajuan ulang setelah direvisi kembali masuk antrean pemeriksaan Dirut
            $status = 'diperiksa';
        }

        $skpd->update([
            'tujuan_dinas'            => $request->tujuan_dinas,
            'keperluan'               => $request->keperluan,
            'tanggal_berangkat'       => $request->tanggal_berangkat,
            'tanggal_kembali'         => $request->tanggal_kembali,
            'durasi_hari'             => $durasi,
            'file'                    => $filePath,
            'status'                  => $status,
        ]);

        ActivityHelper::log('Edit SKPD', 'Memperbarui data SKPD nomor ' . $skpd->nomor_skpd);

        return redirect()->route('skpd.index')->with('success', 'SKPD berhasil diupdate');
    }

    public function downloadPdf(Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);

        // Verifikasi hak unduh berkas
        if ($skpd->status !== 'disetujui' && !in_array($role, ['sekretaris', 'dirut'])) {
            abort(403, 'Akses ditolak. SKPD belum disetujui oleh Direktur Utama.');
        }

        if (!in_array($role, ['sekretaris', 'dirut']) && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $qrCodeBase64 = $this->getQrCodeBase64($skpd);
        $pdf = Pdf::loadView('skpd.pdf', compact('skpd', 'qrCodeBase64'));

        ActivityHelper::log('Download SKPD', 'Mengunduh file PDF SKPD nomor ' . $skpd->nomor_skpd);

        // Bersihkan keperluan dan nama pegawai agar aman sebagai nama file
        $safeKeperluan = preg_replace('/[^A-Za-z0-9_\-]/', '_', $skpd->keperluan);
        $safePegawai = preg_replace('/[^A-Za-z0-9_\-]/', '_', $skpd->nama_pegawai);
        
        $safeKeperluan = substr($safeKeperluan, 0, 50);
        $safePegawai = substr($safePegawai, 0, 50);
        
        $downloadName = 'SKPD_' . $safeKeperluan . '_' . $safePegawai . '.pdf';

        return $pdf->download($downloadName);
    }

    public function previewPdf(Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);

        if ($skpd->status !== 'disetujui' && $skpd->status !== 'diperiksa' && !in_array($role, ['sekretaris', 'dirut', 'direktur1'])) {
            abort(403, 'Akses ditolak.');
        }

        if (!in_array($role, ['sekretaris', 'dirut']) && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $qrCodeBase64 = $this->getQrCodeBase64($skpd);
        $pdf = Pdf::loadView('skpd.pdf', compact('skpd', 'qrCodeBase64'));
        return $pdf->stream('SKPD_Preview.pdf');
    }

    public function approve(Skpd $skpd)
    {
        if (strtolower(auth()->user()->role) !== 'dirut') {
            abort(403, 'Hanya Direktur Utama yang dapat menyetujui SKPD.');
        }

        $skpd->update([
            'status'         => 'disetujui',
            'catatan_revisi' => null
        ]);

        ActivityHelper::log('Approve SKPD', 'Menyetujui SKPD nomor ' . $skpd->nomor_skpd);

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD berhasil disetujui.');
    }

    public function reject(Request $request, Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role);
        if ($role !== 'dirut') {
            abort(403, 'Hanya Direktur Utama yang dapat menolak/merevisi SKPD.');
        }

        $request->validate([
            'catatan_revisi' => 'required|string'
        ]);

        $skpd->update([
            'status'         => 'ditolak',
            'catatan_revisi' => $request->catatan_revisi
        ]);

        ActivityHelper::log('Reject SKPD', 'Menolak SKPD nomor ' . $skpd->nomor_skpd . ' dengan alasan: ' . $request->catatan_revisi);

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD berhasil ditolak dengan catatan revisi.');
    }

    public function destroy(Skpd $skpd)
    {
        $role = strtolower(auth()->user()->role ?? '');
        if ($role !== 'sekretaris' && $skpd->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus data SKPD ini.');
        }

        // SKPD yang sudah disetujui Dirut adalah dokumen terbit dan menjadi
        // dasar pertanggungjawaban perjalanan dinas.
        if ($skpd->status === 'disetujui') {
            return back()->with('error', 'SKPD yang sudah disetujui tidak dapat dihapus.');
        }

        // Berkas fisik dipertahankan agar SKPD masih dapat dipulihkan.
        ActivityHelper::log('Hapus SKPD', 'Menghapus SKPD nomor ' . $skpd->nomor_skpd);

        $skpd->delete();

        return redirect()->route('skpd.index')->with('success', 'SKPD berhasil dihapus');
    }

    public function verify($token)
    {
        $skpd = null;
        $parts = explode('-', $token, 2);
        
        if (count($parts) === 2) {
            $id = $parts[0];
            $candidate = Skpd::find($id);
            if ($candidate && $candidate->verify_token === $token) {
                $skpd = $candidate;
            }
        } elseif (is_numeric($token) && auth()->check()) {
            $skpd = Skpd::find($token);
        }

        if (!$skpd) {
            abort(404, 'Dokumen SKPD tidak ditemukan atau kode verifikasi tidak valid.');
        }

        $skpd->load(['user']);
        return view('skpd.verify', compact('skpd'));
    }

    private function getQrCodeBase64(Skpd $skpd)
    {
        $verifyUrl = route('skpd.verify', $skpd->verify_token);
        $qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);
        
        try {
            $arrContextOptions = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ];
            $qrContent = @file_get_contents($qrCodeApiUrl, false, stream_context_create($arrContextOptions));
            if ($qrContent) {
                return base64_encode($qrContent);
            }
        } catch (\Exception $e) {
            \Log::error('Gagal mengambil QR Code untuk SKPD: ' . $e->getMessage());
        }

        return '';
    }
}