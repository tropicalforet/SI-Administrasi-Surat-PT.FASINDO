<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Models\User;
use App\Helpers\ActivityHelper;
use App\Helpers\NomorDokumenHelper;
use App\Notifications\SuratKeluarMenungguTindakan;
use App\Notifications\SuratKeluarDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratKeluarController extends Controller
{
    public function index(Request $request)
    {
        $query = SuratKeluar::with('pembuat')->terlihatOleh(auth()->user());
        
        // Pencarian Dinamis
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('tujuan', 'like', "%{$search}%")
                  ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal_awal')) {
            $query->whereDate('tanggal_surat', '>=', $request->tanggal_awal);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal_surat', '<=', $request->tanggal_akhir);
        }

        // Optimasi: Gunakan paginate untuk menghindari Out of Memory
        $data = $query->latest()->paginate(10)->withQueryString();

        return view('surat_keluar.index', compact('data'));
    }

    public function create()
    {
        // Konsep surat disusun pihak yang membutuhkannya - staf maupun manager -
        // bukan lagi hanya sekretaris. Sekretaris berperan di tahap penomoran.
        return view('surat_keluar.create', [
            'unitBawaan' => $this->unitAsal(auth()->user()),
        ]);
    }

    /**
     * Unit asal penyusun, dicatat sebagai keterangan dari direktorat mana
     * surat ini berasal. Tidak lagi menentukan alur karena tahap verifikasi
     * direktur sudah dilepas.
     */
    private function unitAsal(\App\Models\User $user): ?string
    {
        return in_array($user->unit, ['keuangan_administrasi', 'teknik']) ? $user->unit : null;
    }

    /**
     * Konsep hanya boleh diubah penyusunnya sendiri, atau sekretaris yang
     * memang bertugas merapikan surat sebelum ditandatangani.
     */
    private function pastikanPenyusun(SuratKeluar $surat_keluar): void
    {
        if (strtolower(auth()->user()->role) !== 'sekretaris'
            && $surat_keluar->dibuat_oleh !== auth()->id()) {
            abort(403, 'Akses ditolak. Konsep ini bukan susunan Anda.');
        }
    }

    private function simpanBerkas(Request $request, ?string $berkasLama = null): ?string
    {
        if (!$request->hasFile('file')) {
            return $berkasLama;
        }

        if ($berkasLama && Storage::disk('public')->exists($berkasLama)) {
            Storage::disk('public')->delete($berkasLama);
        }

        $file = $request->file('file');
        $safePerihal = substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->perihal), 0, 100);

        return $file->storeAs(
            'surat_keluar',
            $safePerihal . '_' . time() . '.' . $file->getClientOriginalExtension(),
            'public'
        );
    }

    /**
     * Susun nomor surat resmi. Dipanggil di tahap sekretaris, saat surat sudah
     * dipastikan akan terbit.
     */
    /**
     * Bentuk nomor mengikuti kaidah tunggal di NomorDokumenHelper, sama
     * dengan SKPD, sehingga seluruh dokumen perusahaan berformat seragam.
     */
    private function terbitkanNomor(SuratKeluar $surat_keluar): string
    {
        $kode = NomorDokumenHelper::kodeAman($surat_keluar->kategori_surat);

        return NomorDokumenHelper::terbitkan('surat_keluar:' . $kode, $kode);
    }

    /**
     * Tahap sekretaris: menerbitkan nomor dan memastikan formatnya sesuai
     * standar, sebelum surat naik ke meja Direktur Utama.
     */
    public function prosesSekretaris(SuratKeluar $surat_keluar)
    {
        abort_unless(
            strtolower(auth()->user()->role) === 'sekretaris',
            403,
            'Akses ditolak. Hanya sekretaris yang menomori surat keluar.'
        );

        if ($surat_keluar->status !== 'menunggu_sekretaris') {
            return back()->with('error', 'Surat ini tidak sedang menunggu penomoran.');
        }

        // Nomor hanya diterbitkan sekali; pengajuan ulang memakai nomor yang sama.
        $nomor = $surat_keluar->nomor_surat ?: $this->terbitkanNomor($surat_keluar);

        // Nomor disimpan lebih dahulu, sebelum berkasnya diolah. Urutan ini
        // penting: bila pengolahan dokumen gagal, nomor yang sudah terbit
        // tetap melekat pada suratnya. Sebelumnya urutannya terbalik,
        // sehingga satu kegagalan menghanguskan nomor yang sudah diambil dari
        // penghitung dan meninggalkan lubang pada urutan nomor.
        $surat_keluar->update([
            'nomor_surat' => $nomor,
            'status'      => 'menunggu_dirut',
        ]);

        // Penyematan nomor ke dokumen Word bersifat usaha terbaik.
        if ($surat_keluar->file && str_ends_with(strtolower($surat_keluar->file), '.docx')) {
            $this->imprintNomorSuratToWord(Storage::disk('public')->path($surat_keluar->file), $nomor);
            $this->convertDocxToPdf($surat_keluar->file);
        }

        $this->beritahuDirut($surat_keluar);

        ActivityHelper::log('Penomoran Surat Keluar', 'Menerbitkan nomor ' . $nomor . ' dan meneruskan ke Direktur Utama');

        return back()->with('success', 'Nomor ' . $nomor . ' diterbitkan. Surat diteruskan ke Direktur Utama.');
    }

    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'tujuan'         => 'required|string',
            'perihal'        => 'required|string',
            'file'           => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'aksi'           => 'nullable|in:draft,ajukan',
        ]);
        
        $kategori = $validated['kategori_surat'] === 'Lainnya' ? $validated['kategori_surat_lainnya'] : $validated['kategori_surat'];

        // Nomor sengaja BELUM diterbitkan di sini. Draf yang dibatalkan atau
        // ditolak tidak boleh memakan nomor, sehingga rangkaiannya tetap utuh.
        // Penomoran dilakukan sekretaris saat surat dipastikan akan terbit.
        $filePath = $this->simpanBerkas($request);

        $suratKeluar = SuratKeluar::create([
            'dibuat_oleh'     => auth()->id(),
            'nomor_surat'     => null,
            'kategori_surat'  => $kategori,
            'unit_verifikasi' => $this->unitAsal(auth()->user()),
            'tanggal_surat'   => $validated['tanggal_surat'],
            'tujuan'          => $request->tujuan,
            'perihal'         => $request->perihal,
            'file'            => $filePath,
            'status'          => 'draft',
        ]);

        ActivityHelper::log('Tambah Surat Keluar', 'Menyusun konsep surat: ' . $suratKeluar->perihal);

        if ($request->input('aksi') === 'ajukan') {
            return $this->submit($suratKeluar);
        }

        return redirect()->route('surat-keluar.show', $suratKeluar->id)
            ->with('success', 'Konsep surat tersimpan sebagai draft.');
    }

    public function edit(SuratKeluar $surat_keluar)
    {
        $this->pastikanPenyusun($surat_keluar);

        // Surat yang ditolak Dirut boleh diperbaiki, lalu diajukan ulang
        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return redirect()->route('surat-keluar.index')->with('error', 'Surat yang sudah diajukan tidak dapat diubah.');
        }

        return view('surat_keluar.edit', compact('surat_keluar'));
    }
    
    public function update(Request $request, SuratKeluar $surat_keluar)
    {
        $this->pastikanPenyusun($surat_keluar);

        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Surat yang sudah diajukan tidak dapat diubah.');
        }

        $request->validate([
            'tanggal_surat' => 'required|date',
            'tujuan'        => 'required|string',
            'perihal'       => 'required|string',
            'file'          => 'nullable|mimes:pdf,jpg,jpeg,png,docx|max:5120',
        ]);

        $filePath = $surat_keluar->file;

        if ($request->hasFile('file')) {
            if ($surat_keluar->file && Storage::disk('public')->exists($surat_keluar->file)) {
                Storage::disk('public')->delete($surat_keluar->file);
            }
            $file = $request->file('file');
            $safePerihal = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->perihal);
            $safePerihal = substr($safePerihal, 0, 100);
            $fileName = $safePerihal . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('surat_keluar', $fileName, 'public');

            if (strtolower($file->getClientOriginalExtension()) === 'docx') {
                $fullPath = Storage::disk('public')->path($filePath);
                $this->imprintNomorSuratToWord($fullPath, $surat_keluar->nomor_surat);
                $this->convertDocxToPdf($filePath);
            }
        }

        $surat_keluar->update([
            'tanggal_surat' => $request->tanggal_surat,
            'tujuan'        => $request->tujuan,
            'perihal'       => $request->perihal,
            'file'          => $filePath,
        ]);

        ActivityHelper::log('Edit Surat Keluar', 'Mengubah surat ' . $surat_keluar->nomor_surat);

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil diperbarui.');
    }

    public function submit(SuratKeluar $surat_keluar)
    {
        $this->pastikanPenyusun($surat_keluar);

        // Hanya surat yang masih disusun atau baru ditolak yang boleh diajukan,
        // agar surat yang sudah disetujui tidak bisa dikembalikan ke antrean.
        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Surat ini sudah diajukan atau sudah disetujui.');
        }

        $diajukanUlang = $surat_keluar->status === 'ditolak';

        // Konsep langsung menuju sekretaris untuk dinomori dan diperiksa
        // formatnya; tidak ada lagi tahap verifikasi direktur bidang.
        $surat_keluar->update([
            'status'         => 'menunggu_sekretaris',
            'catatan_revisi' => null,
        ]);

        $this->beritahuSekretarisPengajuan($surat_keluar);

        ActivityHelper::log(
            $diajukanUlang ? 'Ajukan Ulang Surat Keluar' : 'Ajukan Surat Keluar',
            'Mengajukan konsep "' . $surat_keluar->perihal . '" untuk penomoran sekretaris'
        );

        return redirect()->route('surat-keluar.show', $surat_keluar->id)
            ->with('success', 'Konsep diajukan ke sekretaris untuk penomoran.');
    }

    private function beritahuDirut(SuratKeluar $surat_keluar): void
    {
        foreach (\App\Models\User::where('role', 'dirut')->get() as $dirut) {
            $dirut->notify(new SuratKeluarMenungguTindakan(
                $surat_keluar,
                'persetujuan',
                auth()->user()?->name
            ));
        }
    }

    /**
     * Konsep yang diajukan penyusun menunggu penomoran sekretaris. Ini giliran
     * kerja, bukan keputusan - karena itu memakai SuratKeluarMenungguTindakan.
     */
    private function beritahuSekretarisPengajuan(SuratKeluar $surat_keluar): void
    {
        foreach ($this->paraSekretaris() as $sekretaris) {
            // Sekretaris yang mengajukan konsepnya sendiri tidak perlu
            // diberitahu atas tindakannya sendiri.
            if ($sekretaris->id === auth()->id()) {
                continue;
            }

            $sekretaris->notify(new SuratKeluarMenungguTindakan(
                $surat_keluar,
                'penomoran',
                auth()->user()->name
            ));
        }
    }

    /**
     * Beritahu pihak yang perlu tahu atas keputusan terhadap surat keluar.
     *
     * Penyusun selalu termasuk - dialah yang harus memperbaiki bila surat
     * dikembalikan, dan yang paling menunggu kabar bila surat terkirim.
     * Sebelumnya hanya sekretaris yang diberitahu, sehingga penyusun harus
     * membuka daftar sendiri untuk mengetahui suratnya ditolak.
     *
     * Sekretaris ikut diberitahu karena ia mengelola arsip persuratan, dan
     * pengambil keputusan dilewati karena ia sendiri yang bertindak.
     */
    private function beritahuKeputusan(SuratKeluar $surat_keluar, string $keputusan, User $pengambilKeputusan): void
    {
        $penerima = collect([$surat_keluar->pembuat])
            ->merge($this->paraSekretaris())
            ->filter()
            ->unique('id')
            ->reject(fn (User $orang) => $orang->id === $pengambilKeputusan->id);

        foreach ($penerima as $orang) {
            $orang->notify(new SuratKeluarDiputuskan($surat_keluar, $keputusan, $pengambilKeputusan->name));
        }
    }

    private function paraSekretaris()
    {
        return User::where('role', 'sekretaris')->get();
    }

    public function approve(SuratKeluar $surat_keluar)
    {
        $user = auth()->user();
        abort_unless($user->role === 'dirut', 403, 'Akses ditolak. Hanya Direktur Utama yang dapat menyetujui surat keluar.');

        // Approval: Direktur Utama
        if ($surat_keluar->status === 'menunggu_dirut') {
            
            // PROSES E-SIGN JIKA BERKAS ADALAH WORD (.docx)
            if ($surat_keluar->file) {
                $fullPath = Storage::disk('public')->path($surat_keluar->file);
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                
                if ($ext === 'docx' && file_exists($fullPath)) {
                    try {
                        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($fullPath);
                        
                        // 1. Ganti TTD Dirut
                        $ttdPath = public_path('images/ttd-direktur.png');
                        if (file_exists($ttdPath)) {
                            $templateProcessor->setImageValue('ttd_dirut', [
                                'path' => $ttdPath,
                                'width' => 110,
                                'height' => 70,
                                'ratio' => false
                            ]);
                        }
                        
                        // 2. Ganti QR Code
                        $verifyUrl = route('surat-keluar.verify', $surat_keluar->verify_token);
                        $qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);
                        
                        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
                        $qrContent = @file_get_contents($qrCodeApiUrl);
                        if ($qrContent) {
                            file_put_contents($tempQrPath, $qrContent);
                            $templateProcessor->setImageValue('qr_code', [
                                'path' => $tempQrPath,
                                'width' => 85,
                                'height' => 85,
                                'ratio' => false
                            ]);
                        }
                        
                        // 3. Ganti Text Placeholders
                        $templateProcessor->setValue('nomor_surat', $surat_keluar->nomor_surat);
                        $templateProcessor->setValue('no_surat', $surat_keluar->nomor_surat);
                        $templateProcessor->setValue('nama_dirut', $user->name);
                        $templateProcessor->setValue('tanggal_ttd', \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y'));
                        
                        $hash = hash('sha256', $surat_keluar->id . now());
                        $templateProcessor->setValue('hash_verify', substr($hash, 0, 16) . '...');
                        
                        // Simpan dokumen kembali
                        $templateProcessor->saveAs($fullPath);
                        
                        // Hapus file QR temp
                        if (file_exists($tempQrPath)) {
                            @unlink($tempQrPath);
                        }

                        // 4. Konversi otomatis ke PDF jika LibreOffice tersedia di server
                        if ($this->convertDocxToPdf($surat_keluar->file)) {
                            $pdfRelativePath = str_replace('.docx', '.pdf', $surat_keluar->file);
                            $surat_keluar->update([
                                'file' => $pdfRelativePath
                            ]);
                        }
                    } catch (\Throwable $e) {
                        // \Throwable, bukan \Exception: fungsi yang dimatikan
                        // peladen melempar \Error, dan \Error tidak akan
                        // tertangkap oleh catch (\Exception).
                        \Log::error('Gagal menyisipkan E-Sign ke berkas DOCX: ' . $e->getMessage());
                    }
                }
            }

            $surat_keluar->update([
                'status'            => 'terkirim',
                'approved_dirut_by' => $user->id,
                'approved_dirut_at' => now()
            ]);

            $this->beritahuKeputusan($surat_keluar, 'disetujui', $user);

            ActivityHelper::log('Approval Surat', 'Direktur Utama menyetujui surat ' . $surat_keluar->nomor_surat);
            return back()->with('success', 'Surat berhasil disetujui & E-Sign disematkan ke dalam dokumen.');
        }

        return back()->with('error', 'Status surat tidak valid untuk disetujui saat ini.');
    }

    public function reject(Request $request, SuratKeluar $surat_keluar)
    {
        $user = auth()->user();
        $role = strtolower($user->role);

        // Surat dapat dikembalikan di dua titik: saat sekretaris memeriksa
        // formatnya, dan saat menunggu tanda tangan Direktur Utama.
        if ($role === 'dirut') {
            $bolehMenolak = $surat_keluar->status === 'menunggu_dirut';
        } elseif ($role === 'sekretaris') {
            // Format belum sesuai standar: dikembalikan ke penyusunnya.
            $bolehMenolak = $surat_keluar->status === 'menunggu_sekretaris';
        } else {
            abort(403, 'Hanya sekretaris atau Direktur Utama yang dapat mengembalikan Surat Keluar.');
        }

        if (!$bolehMenolak) {
            abort(403, 'Surat ini tidak sedang menunggu keputusan Anda.');
        }

        $request->validate([
            'catatan_revisi' => 'required|string'
        ]);

        $surat_keluar->update([
            'status'         => 'ditolak',
            'catatan_revisi' => $request->catatan_revisi
        ]);

        $this->beritahuKeputusan($surat_keluar, 'ditolak', $user);

        ActivityHelper::log('Reject Surat Keluar', 'Menolak Surat Keluar nomor ' . $surat_keluar->nomor_surat . ' dengan alasan: ' . $request->catatan_revisi);

        return redirect()->route('surat-keluar.show', $surat_keluar->id)->with('success', 'Surat Keluar berhasil ditolak dengan catatan revisi.');
    }

    public function download(SuratKeluar $surat_keluar)
    {
        // Berkas dijaga dengan aturan yang sama seperti halaman detailnya.
        // Tanpa ini, siapa pun yang punya izin surat keluar dapat mengunduh
        // draf milik orang lain hanya dengan menebak nomor id.
        if (!$surat_keluar->dapatDilihatOleh(auth()->user())) {
            abort(403, 'Akses ditolak. Surat ini bukan kewenangan Anda.');
        }

        if (!$surat_keluar->file) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $filePath = Storage::disk('public')->path($surat_keluar->file);
        
        if (!file_exists($filePath)) {
            abort(404, 'Berkas fisik tidak ditemukan di server.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // Bersihkan nama perihal agar aman sebagai nama berkas
        $safePerihal = preg_replace('/[^A-Za-z0-9_\-]/', '_', $surat_keluar->perihal);
        $safePerihal = substr($safePerihal, 0, 100); // Batasi 100 karakter
        
        $downloadName = $safePerihal . '.' . $extension;

        return response()->download($filePath, $downloadName);
    }

    public function destroy(SuratKeluar $surat_keluar)
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus data.');

        // Surat yang sudah disetujui dan ditandatangani secara elektronik
        // merupakan dokumen resmi yang telah terbit, sehingga tidak boleh
        // dihapus meskipun oleh administrator.
        if ($surat_keluar->status === 'terkirim') {
            return back()->with('error', 'Surat yang sudah disetujui dan ditandatangani tidak dapat dihapus.');
        }

        // Berkas fisik dipertahankan agar surat masih dapat dipulihkan.
        ActivityHelper::log('Hapus Surat Keluar', 'Menghapus surat ' . $surat_keluar->nomor_surat);

        $surat_keluar->delete();

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil dihapus.');
    }

    public function show(SuratKeluar $surat_keluar)
    {
        // Aturan yang sama persis dengan filter daftar, supaya tidak ada surat
        // yang tampil di daftar tapi ditolak saat dibuka. Draf tetap tertutup
        // bagi orang lain, tetapi penyusunnya sendiri jelas boleh melihatnya.
        if (!$surat_keluar->dapatDilihatOleh(auth()->user())) {
            abort(403, 'Akses ditolak. Surat ini bukan kewenangan Anda.');
        }

        $surat_keluar->load(['pembuat', 'approvedDirektur', 'approvedDirut']);
        return view('surat_keluar.show', compact('surat_keluar'));
    }

    public function verify($token)
    {
        $surat_keluar = null;
        $parts = explode('-', $token, 2);
        
        if (count($parts) === 2) {
            $id = $parts[0];
            $candidate = SuratKeluar::find($id);
            if ($candidate && $candidate->verify_token === $token) {
                $surat_keluar = $candidate;
            }
        } elseif (is_numeric($token) && auth()->check()) {
            $surat_keluar = SuratKeluar::find($token);
        }

        if (!$surat_keluar) {
            abort(404, 'Dokumen Surat Keluar tidak ditemukan atau kode verifikasi tidak valid.');
        }

        $surat_keluar->load(['approvedDirektur', 'approvedDirut']);
        return view('surat_keluar.verify', compact('surat_keluar'));
    }

    /**
     * Konversi dokumen Word ke PDF, bila peladen menyediakan LibreOffice.
     *
     * Bersifat usaha terbaik: pada layanan shared hosting, shell_exec umumnya
     * dimatikan lewat disable_functions dan LibreOffice tidak terpasang.
     * Kegagalan di sini tidak boleh menghentikan alur persuratan, karena
     * dokumen Word-nya sendiri sudah tersimpan dan tetap dapat diunduh.
     */
    private function convertDocxToPdf($docxRelativePath)
    {
        // Memanggil fungsi yang dimatikan peladen berakibat fatal pada PHP 8,
        // dan tanda @ tidak meredamnya. Karena itu diperiksa lebih dahulu.
        if (!function_exists('shell_exec')) {
            return false;
        }

        $fullPath = Storage::disk('public')->path($docxRelativePath);
        if (!file_exists($fullPath)) {
            return false;
        }

        $sofficePath = null;
        $possiblePaths = [
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
            'soffice'
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'soffice' || file_exists($path)) {
                $sofficePath = $path;
                break;
            }
        }

        if ($sofficePath) {
            $outdir = dirname($fullPath);
            $cmd = sprintf('"%s" --headless --convert-to pdf --outdir "%s" "%s"', $sofficePath, $outdir, $fullPath);
            @shell_exec($cmd);
            
            $expectedPdfPath = str_replace('.docx', '.pdf', $fullPath);
            return file_exists($expectedPdfPath);
        }

        return false;
    }

    private function imprintNomorSuratToWord($fullPath, $nomorSurat)
    {
        if (!file_exists($fullPath)) return false;
        
        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($fullPath);
            $templateProcessor->setValue('nomor_surat', $nomorSurat);
            $templateProcessor->setValue('no_surat', $nomorSurat);
            $templateProcessor->saveAs($fullPath);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function clear()
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya admin yang dapat menghapus semua data.');

        ActivityHelper::log('Hapus Semua Surat Keluar', 'Menghapus seluruh data surat keluar');

        // Surat yang sudah ditandatangani tetap dipertahankan; sisanya pindah
        // ke arsip terhapus beserta berkasnya.
        SuratKeluar::where('status', '!=', 'terkirim')->delete();

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar yang belum disetujui dipindahkan ke arsip terhapus. Surat yang sudah ditandatangani tetap dipertahankan.');
    }
}