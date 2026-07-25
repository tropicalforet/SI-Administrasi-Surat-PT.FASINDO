<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Helpers\ActivityHelper;
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

    public function create()
    {
        return view('skpd.create');
    }

    public function store(Request $request)
    {
        $role = strtolower(auth()->user()->role);

        $rules = [
            'nama_pegawai'            => 'required|string',
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

        // Nilai biaya default 0, akan diisi oleh Direktur 2
        $biaya_transport = 0;
        $biaya_penginapan = 0;
        $biaya_konsumsi_per_hari = 0;
        $totalBiaya = 0;

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        // Pembuatan nomor urut otomatis
        $lastId = Skpd::max('id');
        $nomorUrut = $lastId ? $lastId + 1 : 1;
        $nomor_skpd = 'SKPD-' . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT) . '/' . date('m') . '/' . date('Y');

        // Semua pengajuan baru langsung diperiksa oleh Dirut
        $status = 'diperiksa';

        $skpd = Skpd::create([
            'user_id'                 => auth()->id(),
            'nomor_skpd'              => $nomor_skpd,
            'nama_pegawai'            => $request->nama_pegawai,
            'tujuan_dinas'            => $request->tujuan_dinas,
            'keperluan'               => $request->keperluan,
            'tanggal_berangkat'       => $request->tanggal_berangkat,
            'tanggal_kembali'         => $request->tanggal_kembali,
            'durasi_hari'             => $durasi,
            'biaya_transport'         => $biaya_transport,
            'biaya_penginapan'        => $biaya_penginapan,
            'biaya_konsumsi_per_hari' => $biaya_konsumsi_per_hari,
            'total_biaya'             => $totalBiaya,
            'file'                    => $filePath,
            'status'                  => $status,
        ]);

        ActivityHelper::log('Tambah SKPD', 'Membuat pengajuan SKPD nomor ' . $nomor_skpd . ' untuk ' . $request->nama_pegawai);

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
            'nama_pegawai'            => 'required|string',
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

        $biaya_transport = $skpd->biaya_transport;
        $biaya_penginapan = $skpd->biaya_penginapan;
        $biaya_konsumsi_per_hari = $skpd->biaya_konsumsi_per_hari;

        $totalBiaya = $biaya_transport + $biaya_penginapan + ($biaya_konsumsi_per_hari * $durasi);

        $filePath = $skpd->file;
        if ($request->hasFile('file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        $status = $skpd->status;
        if ($status === 'ditolak') {
            // Pengajuan ulang setelah direvisi kembali ke 'menunggu_keuangan'
            $status = 'menunggu_keuangan';
        }

        $skpd->update([
            'nama_pegawai'            => $request->nama_pegawai,
            'tujuan_dinas'            => $request->tujuan_dinas,
            'keperluan'               => $request->keperluan,
            'tanggal_berangkat'       => $request->tanggal_berangkat,
            'tanggal_kembali'         => $request->tanggal_kembali,
            'durasi_hari'             => $durasi,
            'biaya_transport'         => $biaya_transport,
            'biaya_penginapan'        => $biaya_penginapan,
            'biaya_konsumsi_per_hari' => $biaya_konsumsi_per_hari,
            'total_biaya'             => $totalBiaya,
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

        if ($skpd->status !== 'disetujui' && $skpd->status !== 'diperiksa' && $skpd->status !== 'menunggu_keuangan' && !in_array($role, ['sekretaris', 'dirut', 'direktur1'])) {
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

        if ($skpd->file && Storage::disk('public')->exists($skpd->file)) {
            Storage::disk('public')->delete($skpd->file);
        }

        ActivityHelper::log('Hapus SKPD', 'Menghapus SKPD nomor ' . $skpd->nomor_skpd);

        $skpd->delete();

        return redirect()->route('skpd.index')->with('success', 'SKPD berhasil dihapus');
    }

    public function verify(Skpd $skpd)
    {
        $skpd->load(['user']);
        return view('skpd.verify', compact('skpd'));
    }

    private function getQrCodeBase64(Skpd $skpd)
    {
        $verifyUrl = route('skpd.verify', $skpd->id);
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