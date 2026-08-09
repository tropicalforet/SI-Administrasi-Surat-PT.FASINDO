<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Models\User;
use App\Helpers\ActivityHelper;
use App\Notifications\SkpdMenungguTindakan;
use App\Notifications\SkpdDiputuskan;
use App\Helpers\NomorDokumenHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SkpdController extends Controller
{
    public function index()
    {
        $data = Skpd::with(['user', 'ditugaskanOleh'])
            ->terlihatOleh(auth()->user())
            ->latest()
            ->paginate(10);

        return view('skpd.index', compact('data'));
    }

    public function create()
    {
        $users = collect();

        if (in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1', 'direktur2', 'sekretaris'])) {
            $users = User::orderBy('role')->orderBy('name')->get();
        }

        return view('skpd.create', compact('users'));
    }

    public function store(Request $request)
    {
        // Dua arah yang sama-sama sah, dibedakan dengan tegas: atasan
        // MENUGASKAN pegawai, pegawai MENGUSULKAN untuk dirinya sendiri.
        // Usulan belum menjadi penugasan sampai direkturnya menyetujui.
        $role = strtolower(auth()->user()->role);
        $bolehMenugaskan = in_array($role, ['dirut', 'direktur1', 'direktur2', 'sekretaris']);

        $rules = [
            'jenis'             => 'required|in:' . implode(',', array_keys(Skpd::JENIS)),
            'keperluan'         => 'required|string',
            'tujuan_dinas'      => 'required_if:jenis,perjalanan_dinas|nullable|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali'   => 'required|date|after_or_equal:tanggal_berangkat',
            'file'              => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'aksi'              => 'nullable|in:draft,ajukan',
        ];

        if ($bolehMenugaskan) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules, [
            'tujuan_dinas.required_if' => 'Tujuan wajib diisi untuk penugasan berupa perjalanan dinas.',
        ]);

        $userId = $bolehMenugaskan ? $request->user_id : auth()->id();
        $pegawai = User::find($userId);

        $mulai = Carbon::parse($request->tanggal_berangkat);
        $selesai = Carbon::parse($request->tanggal_kembali);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        // Nomor urut dari counter terkunci per tahun.
        $nomorUrut = NomorDokumenHelper::next('skpd', (int) date('Y'));
        $nomor_skpd = 'SKPD-' . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT) . '/' . date('m') . '/' . date('Y');

        $skpd = Skpd::create([
            'user_id'           => $userId,
            'ditugaskan_oleh'   => $bolehMenugaskan ? auth()->id() : null,
            'asal_usul'         => $bolehMenugaskan ? 'penugasan' : 'usulan',
            'nomor_skpd'        => $nomor_skpd,
            'jenis'             => $validated['jenis'],
            'nama_pegawai'      => $pegawai?->name,
            'tujuan_dinas'      => $request->tujuan_dinas,
            'keperluan'         => $validated['keperluan'],
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali'   => $request->tanggal_kembali,
            'durasi_hari'       => $mulai->diffInDays($selesai) + 1,
            'file'              => $filePath,
            'status'            => 'draft',
        ]);

        ActivityHelper::log('Tambah SKPD', 'Membuat SKPD nomor ' . $nomor_skpd . ' untuk ' . ($pegawai?->name ?? '-'));

        // "Simpan & Ajukan" menyatukan dua langkah yang dulu terpisah.
        if ($request->input('aksi') === 'ajukan') {
            return $this->ajukan($skpd);
        }

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD tersimpan sebagai draft.');
    }

    /**
     * Ajukan draft ke tahap berikutnya. Usulan pegawai singgah dulu ke
     * direktur unitnya; penugasan dari pihak berwenang langsung ke Dirut.
     */
    public function ajukan(Skpd $skpd)
    {
        $this->pastikanBolehMengurus($skpd);

        if (!in_array($skpd->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'SKPD ini sudah diajukan.');
        }

        if ($skpd->perluPersetujuanDirektur()) {
            $direktur = $skpd->direkturPenyetuju();

            if (!$direktur) {
                return back()->with('error', 'Direktur untuk unit pegawai ini belum ada. Hubungi administrator.');
            }

            $skpd->update([
                'status'                => 'menunggu_direktur',
                'catatan_revisi'        => null,
                'disetujui_direktur_by' => null,
                'disetujui_direktur_at' => null,
            ]);

            $direktur->notify(new SkpdMenungguTindakan($skpd, 'persetujuan_direktur'));

            ActivityHelper::log('Ajukan SKPD', 'Mengajukan ' . $skpd->nomor_skpd . ' untuk persetujuan ' . $direktur->label_jabatan);

            return redirect()->route('skpd.show', $skpd->id)
                ->with('success', 'Diajukan ke ' . $direktur->label_jabatan . ' untuk disetujui.');
        }

        $skpd->update(['status' => 'menunggu_dirut', 'catatan_revisi' => null]);
        $this->beritahuDirut($skpd);

        ActivityHelper::log('Ajukan SKPD', 'Mengajukan ' . $skpd->nomor_skpd . ' untuk persetujuan Direktur Utama');

        return redirect()->route('skpd.show', $skpd->id)
            ->with('success', 'Diajukan ke Direktur Utama untuk disetujui.');
    }

    /**
     * Persetujuan direktur atas usulan pegawai. Di sinilah usulan berubah
     * menjadi penugasan resmi.
     */
    public function setujuiDirektur(Skpd $skpd)
    {
        $user = auth()->user();

        if (!$user->isDirektur() || $skpd->user?->unit !== $user->unit) {
            abort(403, 'Akses ditolak. Pegawai ini bukan bawahan Anda.');
        }

        if ($skpd->status !== 'menunggu_direktur') {
            return back()->with('error', 'SKPD ini tidak sedang menunggu persetujuan Anda.');
        }

        $skpd->update([
            'status'                => 'menunggu_dirut',
            'disetujui_direktur_by' => $user->id,
            'disetujui_direktur_at' => now(),
            'catatan_revisi'        => null,
        ]);

        $this->beritahuDirut($skpd);

        if ($skpd->user) {
            $skpd->user->notify(new SkpdDiputuskan($skpd, 'disetujui_direktur', $user->name));
        }

        ActivityHelper::log('Setujui Usulan SKPD', $user->name . ' menyetujui ' . $skpd->nomor_skpd);

        return back()->with('success', 'Usulan disetujui dan diteruskan ke Direktur Utama.');
    }

    private function beritahuDirut(Skpd $skpd): void
    {
        foreach (User::where('role', 'dirut')->get() as $dirut) {
            $dirut->notify(new SkpdMenungguTindakan($skpd, 'persetujuan_dirut'));
        }
    }

    private function pastikanBolehMengurus(Skpd $skpd): void
    {
        $role = strtolower(auth()->user()->role);

        if ($role !== 'sekretaris'
            && $skpd->user_id !== auth()->id()
            && $skpd->ditugaskan_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }
    }

    public function show(Skpd $skpd)
    {
        if (!$skpd->dapatDilihatOleh(auth()->user())) {
            abort(403, 'Akses ditolak.');
        }

        $skpd->load(['user', 'ditugaskanOleh', 'disetujuiDirektur']);

        return view('skpd.show', compact('skpd'));
    }

    public function edit(Skpd $skpd)
    {
        $this->pastikanBolehMengurus($skpd);

        if (!in_array($skpd->status, ['draft', 'ditolak'])) {
            abort(403, 'SKPD sedang dalam proses persetujuan dan tidak dapat diedit.');
        }

        $users = collect();
        if (in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1', 'direktur2', 'sekretaris'])) {
            $users = User::orderBy('role')->orderBy('name')->get();
        }

        return view('skpd.edit', compact('skpd', 'users'));
    }

    public function update(Request $request, Skpd $skpd)
    {
        $this->pastikanBolehMengurus($skpd);

        if (!in_array($skpd->status, ['draft', 'ditolak'])) {
            abort(403, 'SKPD sedang dalam proses persetujuan dan tidak dapat diupdate.');
        }

        $validated = $request->validate([
            'jenis'             => 'required|in:' . implode(',', array_keys(Skpd::JENIS)),
            'keperluan'         => 'required|string',
            'tujuan_dinas'      => 'required_if:jenis,perjalanan_dinas|nullable|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali'   => 'required|date|after_or_equal:tanggal_berangkat',
            'file'              => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'tujuan_dinas.required_if' => 'Tujuan wajib diisi untuk penugasan berupa perjalanan dinas.',
        ]);

        $mulai = Carbon::parse($request->tanggal_berangkat);
        $selesai = Carbon::parse($request->tanggal_kembali);

        $filePath = $skpd->file;
        if ($request->hasFile('file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        $skpd->update([
            'jenis'             => $validated['jenis'],
            'tujuan_dinas'      => $request->tujuan_dinas,
            'keperluan'         => $validated['keperluan'],
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali'   => $request->tanggal_kembali,
            'durasi_hari'       => $mulai->diffInDays($selesai) + 1,
            'file'              => $filePath,
        ]);

        ActivityHelper::log('Edit SKPD', 'Memperbarui data SKPD nomor ' . $skpd->nomor_skpd);

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD berhasil diperbarui.');
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

    /**
     * Pratinjau memakai aturan keterlihatan yang sama dengan halaman detail:
     * siapa pun yang berhak melihat dokumennya berhak melihat wujud cetaknya,
     * termasuk pemiliknya sendiri saat masih draft. Dokumen yang belum
     * disetujui diberi tanda agar hasil cetaknya tidak disangka final.
     */
    public function previewPdf(Skpd $skpd)
    {
        if (!$skpd->dapatDilihatOleh(auth()->user())) {
            abort(403, 'Akses ditolak.');
        }

        $qrCodeBase64 = $this->getQrCodeBase64($skpd);
        $belumDisetujui = $skpd->status !== 'disetujui';

        $pdf = Pdf::loadView('skpd.pdf', compact('skpd', 'qrCodeBase64', 'belumDisetujui'));

        return $pdf->stream('SKPD_Preview.pdf');
    }

    public function approve(Skpd $skpd)
    {
        if (strtolower(auth()->user()->role) !== 'dirut') {
            abort(403, 'Hanya Direktur Utama yang dapat menyetujui SKPD.');
        }

        // Hanya yang sudah melewati tahap sebelumnya, agar draft tidak bisa
        // langsung disetujui tanpa diajukan.
        if ($skpd->status !== 'menunggu_dirut') {
            return back()->with('error', 'SKPD ini belum diajukan untuk disetujui.');
        }

        $skpd->update([
            'status'         => 'disetujui',
            'catatan_revisi' => null
        ]);

        if ($skpd->user) {
            $skpd->user->notify(new SkpdDiputuskan($skpd, 'disetujui', auth()->user()->name));
        }

        ActivityHelper::log('Approve SKPD', 'Menyetujui SKPD nomor ' . $skpd->nomor_skpd);

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD berhasil disetujui.');
    }

    public function reject(Request $request, Skpd $skpd)
    {
        $user = auth()->user();
        $role = strtolower($user->role);

        // Dapat ditolak di dua titik sesuai tahapnya.
        if ($role === 'dirut') {
            $boleh = $skpd->status === 'menunggu_dirut';
        } elseif ($user->isDirektur()) {
            $boleh = $skpd->status === 'menunggu_direktur' && $skpd->user?->unit === $user->unit;
        } else {
            abort(403, 'Hanya Direktur atau Direktur Utama yang dapat menolak SKPD.');
        }

        if (!$boleh) {
            abort(403, 'SKPD ini tidak sedang menunggu keputusan Anda.');
        }

        $request->validate([
            'catatan_revisi' => 'required|string'
        ]);

        $skpd->update([
            'status'         => 'ditolak',
            'catatan_revisi' => $request->catatan_revisi
        ]);

        if ($skpd->user) {
            $skpd->user->notify(new SkpdDiputuskan($skpd, 'ditolak', $user->name));
        }

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