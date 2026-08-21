<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Models\User;
use App\Helpers\ActivityHelper;
use App\Notifications\SkpdMenungguTindakan;
use App\Notifications\SkpdDiputuskan;
use App\Notifications\PenugasanDinasDiterima;
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
        // Daftarnya dibatasi garis komando, bukan sekadar disaring di tampilan.
        $users = Skpd::calonPegawai(auth()->user());

        return view('skpd.create', compact('users'));
    }

    public function store(Request $request)
    {
        // Dua arah yang sama-sama sah, dibedakan dengan tegas: atasan
        // MENUGASKAN bawahannya, pegawai MENGUSULKAN untuk dirinya sendiri.
        // Usulan belum menjadi penugasan sampai direkturnya menyetujui.
        $penugas = auth()->user();
        $bolehMenugaskan = Skpd::bolehMenugaskan($penugas);

        $rules = [
            'keperluan'         => 'required|string',
            'tujuan_dinas'      => 'required|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali'   => 'required|date|after_or_equal:tanggal_berangkat',
            'file'              => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'aksi'              => 'nullable|in:draft,ajukan',
        ];

        if ($bolehMenugaskan) {
            // Batas kewenangan ditegakkan di sini, bukan hanya dengan
            // memendekkan daftar di formulir.
            $rules['user_id'] = [
                'required',
                'exists:users,id',
                function ($atribut, $nilai, $gagal) use ($penugas) {
                    if (!Skpd::bolehMenugaskanPegawai($penugas, $nilai)) {
                        $gagal('Anda hanya dapat menugaskan pegawai yang berada di bawah kewenangan Anda.');
                    }
                },
            ];
        }

        $validated = $request->validate($rules);

        $userId = $bolehMenugaskan ? (int) $request->user_id : auth()->id();
        $pegawai = User::find($userId);

        // Dokumen atas nama diri sendiri tetap usulan, sekalipun dibuat oleh
        // atasan - agar tidak ada yang tercatat menugaskan dirinya sendiri.
        $untukDiriSendiri = $userId === (int) auth()->id();

        $mulai = Carbon::parse($request->tanggal_berangkat);
        $selesai = Carbon::parse($request->tanggal_kembali);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('skpd', 'public');
        }

        // Nomor sengaja BELUM diterbitkan di sini, sama seperti surat keluar.
        // Pengajuan yang dibatalkan atau ditolak karenanya tidak memakan
        // nomor, sehingga deret nomor perusahaan tetap rapat. Nomor terbit
        // saat Direktur Utama menyetujui - lihat approve().
        $skpd = Skpd::create([
            'user_id'           => $userId,
            'ditugaskan_oleh'   => $untukDiriSendiri ? null : auth()->id(),
            'asal_usul'         => $untukDiriSendiri ? 'usulan' : 'penugasan',
            'nomor_skpd'        => null,
            'nama_pegawai'      => $pegawai?->name,
            'tujuan_dinas'      => $validated['tujuan_dinas'],
            'keperluan'         => $validated['keperluan'],
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali'   => $request->tanggal_kembali,
            'durasi_hari'       => $mulai->diffInDays($selesai) + 1,
            'file'              => $filePath,
            'status'            => 'draft',
        ]);

        ActivityHelper::log('Tambah SKPD', 'Menyusun pengajuan dinas ke ' . $skpd->tujuan_dinas . ' untuk ' . ($pegawai?->name ?? '-'));

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

            // Dokumen belum bernomor pada tahap ini - nomor baru terbit saat
            // Direktur Utama menyetujui - sehingga yang dicatat perihalnya.
            ActivityHelper::log(
                'Ajukan SKPD',
                'Mengajukan usulan dinas ke ' . $skpd->tujuan_dinas . ' untuk persetujuan ' . $direktur->label_jabatan
            );

            return redirect()->route('skpd.show', $skpd->id)
                ->with('success', 'Diajukan ke ' . $direktur->label_jabatan . ' untuk disetujui.');
        }

        $skpd->update(['status' => 'menunggu_dirut', 'catatan_revisi' => null]);
        $this->beritahuDirut($skpd);

        // Bila Direktur Utama sendiri yang menerbitkan penugasannya, tahap
        // berikutnya adalah penandatanganan - bukan permintaan persetujuan
        // kepada dirinya sendiri.
        $miliknyaSendiri = (int) $skpd->ditugaskan_oleh === (int) auth()->id()
            && strtolower(auth()->user()->role) === 'dirut';

        ActivityHelper::log(
            'Ajukan SKPD',
            $miliknyaSendiri
                ? 'Menerbitkan penugasan dinas ke ' . $skpd->tujuan_dinas . ', siap ditandatangani'
                : 'Mengajukan penugasan dinas ke ' . $skpd->tujuan_dinas . ' untuk persetujuan Direktur Utama'
        );

        return redirect()->route('skpd.show', $skpd->id)
            ->with('success', $miliknyaSendiri
                ? 'Penugasan diterbitkan. Tinggal dibubuhi tanda tangan elektronik Anda.'
                : 'Diajukan ke Direktur Utama untuk disetujui.');
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
        // Draf hanya boleh diurus penyusunnya. Orang lain bahkan tidak
        // melihatnya, sehingga tidak sepantasnya dapat mengubah atau
        // mengajukan dokumen yang masih disusun orang.
        if ($skpd->status === 'draft') {
            abort_unless($skpd->dibuatOleh(auth()->user()), 403, 'Akses ditolak.');

            return;
        }

        // Selebihnya pun hanya penyusunnya. Pegawai yang ditugaskan boleh
        // melihat dokumennya, tetapi tidak mengubah atau mengajukan perintah
        // yang bukan ia yang menyusunnya.
        abort_unless($skpd->dibuatOleh(auth()->user()), 403, 'Akses ditolak.');
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

        // Daftarnya dibatasi garis komando, bukan sekadar disaring di tampilan.
        $users = Skpd::calonPegawai(auth()->user());

        return view('skpd.edit', compact('skpd', 'users'));
    }

    public function update(Request $request, Skpd $skpd)
    {
        $this->pastikanBolehMengurus($skpd);

        if (!in_array($skpd->status, ['draft', 'ditolak'])) {
            abort(403, 'SKPD sedang dalam proses persetujuan dan tidak dapat diupdate.');
        }

        $validated = $request->validate([
            'keperluan'         => 'required|string',
            'tujuan_dinas'      => 'required|string',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali'   => 'required|date|after_or_equal:tanggal_berangkat',
            'file'              => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
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
            'tujuan_dinas'      => $validated['tujuan_dinas'],
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

        // Nomor terbit di sini, saat dokumen dipastikan akan berlaku. Sekali
        // terbit ia melekat: pengajuan yang pernah disetujui lalu diajukan
        // ulang tetap memakai nomor semula.
        $nomor = $skpd->nomor_skpd ?: NomorDokumenHelper::terbitkan('skpd', 'SKPD');

        $skpd->update([
            'nomor_skpd'     => $nomor,
            'status'         => 'disetujui',
            'catatan_revisi' => null
        ]);

        if ($skpd->user) {
            /*
             * Inilah saat pegawai pertama kali mengetahui perintahnya, karena
             * penugasan yang belum ditandatangani memang belum tampil di
             * layarnya. Bunyinya pun berbeda: pada penugasan ia diberi tahu
             * bahwa ia ditugaskan, bukan bahwa pengajuannya disetujui -
             * sesuatu yang tidak pernah ia ajukan.
             */
            $skpd->user->notify(
                $skpd->merupakanPenugasan()
                    ? new PenugasanDinasDiterima($skpd)
                    : new SkpdDiputuskan($skpd, 'disetujui', auth()->user()->name)
            );
        }

        ActivityHelper::log('Approve SKPD', 'Menyetujui SKPD dan menerbitkan nomor ' . $nomor);

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

        /*
         * Yang dikabari adalah pihak yang harus memperbaikinya, yaitu
         * penyusunnya. Pada penugasan, mengabari pegawai yang ditugaskan
         * justru menyesatkan: dokumennya belum sah sehingga belum terbuka
         * baginya, dan pemberitahuannya berujung pada halaman yang menolak.
         */
        $penyusun = $skpd->merupakanPenugasan() ? $skpd->ditugaskanOleh : $skpd->user;

        if ($penyusun) {
            $penyusun->notify(new SkpdDiputuskan($skpd, 'ditolak', $user->name));
        }

        ActivityHelper::log('Reject SKPD', 'Menolak SKPD ' . $skpd->label_nomor . ' dengan alasan: ' . $request->catatan_revisi);

        return redirect()->route('skpd.show', $skpd->id)->with('success', 'SKPD berhasil ditolak dengan catatan revisi.');
    }

    public function destroy(Skpd $skpd)
    {
        // Sekretaris tidak lagi berperan di alur SKPD sejak kewenangan
        // menugaskan dipegang Dirut dan para direktur, sehingga ia tidak lagi
        // berhak menghapus pengajuan orang lain. Administrator menggantikannya
        // sebagai katup pengaman, sama seperti pada modul surat.
        $role = strtolower(auth()->user()->role ?? '');
        $isAdmin = in_array($role, ['admin', 'administrator', 'superadmin']);

        // Yang berhak membatalkan adalah penyusunnya. Pada penugasan, kolom
        // user_id menunjuk pegawai yang ditugaskan - bila itu yang dipakai,
        // seorang bawahan dapat menghapus perintah yang diberikan atasannya.
        if (!$isAdmin && !$skpd->dibuatOleh(auth()->user())) {
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