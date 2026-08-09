<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
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
        $query = SuratKeluar::query();
        
        $role = strtolower(auth()->user()->role);
        
        // Surat keluar berstatus draft hanya boleh dilihat oleh sekretaris dan admin
        if (!in_array($role, ['admin', 'administrator', 'superadmin', 'sekretaris'])) {
            $query->where('status', '!=', 'draft');
        }
        
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
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');
        return view('surat_keluar.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');
        
        $validated = $request->validate([
            'kategori_surat' => 'required|string',
            'kategori_surat_lainnya' => 'required_if:kategori_surat,Lainnya|string|nullable',
            'tanggal_surat'  => 'required|date',
            'unit_verifikasi' => 'required|in:' . implode(',', array_keys(\App\Models\User::UNIT)),
            'tujuan'         => 'required|string',
            'perihal'        => 'required|string',
            'file'           => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
        
        $kategori = $validated['kategori_surat'] === 'Lainnya' ? $validated['kategori_surat_lainnya'] : $validated['kategori_surat'];
        $tahun = date('Y');
        
        $bulanRomawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];
        $bulan = $bulanRomawi[date('n')];

        // Nomor diambil dari counter terkunci, bukan dari hasil pembacaan
        // seluruh surat, agar dua sekretaris tidak mendapat nomor yang sama.
        $nomorBaru = NomorDokumenHelper::next('surat_keluar:' . $kategori, (int) $tahun);

        // Buat slug tujuan agar aman sebagai penomoran surat
        $slugTujuan = strtoupper(preg_replace('/[^A-Za-z0-9]/', '-', $request->tujuan));
        $slugTujuan = preg_replace('/-+/', '-', $slugTujuan);
        $slugTujuan = trim($slugTujuan, '-');
        $slugTujuan = substr($slugTujuan, 0, 30); // Batasi panjang slug

        // Format Baru: [Nomor]/FI/[Kategori] [Slug_Tujuan]/[Bulan_Romawi]/[Tahun]
        $nomor_surat = str_pad($nomorBaru, 3, '0', STR_PAD_LEFT) . '/FI/' . $kategori . ' ' . $slugTujuan . '/' . $bulan . '/' . $tahun;

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $safePerihal = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->perihal);
            $safePerihal = substr($safePerihal, 0, 100);
            $fileName = $safePerihal . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('surat_keluar', $fileName, 'public');

            if (strtolower($file->getClientOriginalExtension()) === 'docx') {
                $fullPath = storage_path('app/public/' . $filePath);
                $this->imprintNomorSuratToWord($fullPath, $nomor_surat);
                $this->convertDocxToPdf($filePath);
            }
        }

        $suratKeluar = SuratKeluar::create([
            'nomor_surat'    => $nomor_surat,
            'kategori_surat' => $kategori,
            'unit_verifikasi' => $validated['unit_verifikasi'],
            'tanggal_surat'  => $validated['tanggal_surat'],
            'tujuan'         => $request->tujuan,
            'perihal'        => $request->perihal,
            'file'           => $filePath,
            'status'         => 'draft',
        ]);
        
        ActivityHelper::log('Tambah Surat Keluar', 'Menambahkan surat ' . $nomor_surat);

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil ditambahkan.');
    }

    public function edit(SuratKeluar $surat_keluar)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        // Surat yang ditolak Dirut boleh diperbaiki, lalu diajukan ulang
        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return redirect()->route('surat-keluar.index')->with('error', 'Surat yang sudah diajukan tidak dapat diubah.');
        }

        return view('surat_keluar.edit', compact('surat_keluar'));
    }
    
    public function update(Request $request, SuratKeluar $surat_keluar)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Surat yang sudah diajukan tidak dapat diubah.');
        }

        $request->validate([
            'tanggal_surat' => 'required|date',
            'unit_verifikasi' => 'required|in:' . implode(',', array_keys(\App\Models\User::UNIT)),
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
                $fullPath = storage_path('app/public/' . $filePath);
                $this->imprintNomorSuratToWord($fullPath, $surat_keluar->nomor_surat);
                $this->convertDocxToPdf($filePath);
            }
        }

        $surat_keluar->update([
            'unit_verifikasi' => $request->unit_verifikasi,
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
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        // Hanya surat yang masih disusun atau baru ditolak yang boleh diajukan,
        // agar surat yang sudah disetujui tidak bisa dikembalikan ke antrean.
        if (!in_array($surat_keluar->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Surat ini sudah diajukan atau sudah disetujui.');
        }

        $pemaraf = $surat_keluar->direkturVerifikator();

        if (!$pemaraf) {
            return back()->with('error', 'Belum ada direktur pada unit ' . $surat_keluar->label_unit_verifikasi . '. Hubungi administrator sebelum mengajukan surat ini.');
        }

        $diajukanUlang = $surat_keluar->status === 'ditolak';

        // Verifikasi direktur terkait mendahului persetujuan Direktur Utama.
        // Verifikasi lama dikosongkan agar pengajuan ulang diperiksa dari awal.
        $surat_keluar->update([
            'status'               => 'menunggu_direktur',
            'catatan_revisi'       => null,
            'approved_direktur_by' => null,
            'approved_direktur_at' => null,
        ]);

        $pemaraf->notify(new SuratKeluarMenungguTindakan($surat_keluar, 'verifikasi'));

        ActivityHelper::log(
            $diajukanUlang ? 'Ajukan Ulang Persetujuan' : 'Ajukan Persetujuan',
            'Mengajukan surat ' . $surat_keluar->nomor_surat . ' untuk verifikasi ' . $pemaraf->label_jabatan
        );

        return redirect()->route('surat-keluar.index')->with('success', 'Surat diajukan untuk verifikasi ' . $pemaraf->label_jabatan . '.');
    }

    /**
     * Verifikasi direktur terkait. Tahap ini memastikan surat sudah diperiksa
     * pejabat bidangnya sebelum sampai ke meja Direktur Utama.
     */
    public function verifikasi(SuratKeluar $surat_keluar)
    {
        $user = auth()->user();

        if (!$user->isDirektur()) {
            abort(403, 'Akses ditolak. Hanya Direktur yang dapat memverifikasi surat keluar.');
        }

        if ($surat_keluar->status !== 'menunggu_direktur') {
            return back()->with('error', 'Surat ini tidak sedang menunggu verifikasi.');
        }

        // Direktur hanya memverifikasi surat pada unitnya sendiri, mengikuti
        // pembagian direktorat pada bagan organisasi.
        if ($surat_keluar->unit_verifikasi !== $user->unit) {
            abort(403, 'Akses ditolak. Surat ini bukan kewenangan direktorat Anda.');
        }

        $surat_keluar->update([
            'status'               => 'menunggu_dirut',
            'approved_direktur_by' => $user->id,
            'approved_direktur_at' => now(),
        ]);

        $this->beritahuDirut($surat_keluar);
        $this->beritahuSekretaris($surat_keluar, 'diverifikasi', $user->name);

        ActivityHelper::log('Verifikasi Surat Keluar', $user->name . ' memverifikasi surat ' . $surat_keluar->nomor_surat);

        return back()->with('success', 'Surat berhasil diverifikasi dan diteruskan ke Direktur Utama.');
    }

    private function beritahuDirut(SuratKeluar $surat_keluar): void
    {
        foreach (\App\Models\User::where('role', 'dirut')->get() as $dirut) {
            $dirut->notify(new SuratKeluarMenungguTindakan($surat_keluar, 'persetujuan'));
        }
    }

    private function beritahuSekretaris(SuratKeluar $surat_keluar, string $keputusan, ?string $oleh = null): void
    {
        foreach (\App\Models\User::where('role', 'sekretaris')->get() as $sekretaris) {
            $sekretaris->notify(new SuratKeluarDiputuskan($surat_keluar, $keputusan, $oleh));
        }
    }

    public function approve(SuratKeluar $surat_keluar)
    {
        $user = auth()->user();
        abort_unless($user->role === 'dirut', 403, 'Akses ditolak. Hanya Direktur Utama yang dapat menyetujui surat keluar.');

        // Approval: Direktur Utama
        if ($surat_keluar->status === 'menunggu_dirut') {
            
            // PROSES E-SIGN JIKA BERKAS ADALAH WORD (.docx)
            if ($surat_keluar->file) {
                $fullPath = storage_path('app/public/' . $surat_keluar->file);
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
                    } catch (\Exception $e) {
                        \Log::error('Gagal menyisipkan E-Sign ke berkas DOCX: ' . $e->getMessage());
                    }
                }
            }

            $surat_keluar->update([
                'status'            => 'terkirim',
                'approved_dirut_by' => $user->id,
                'approved_dirut_at' => now()
            ]);

            $this->beritahuSekretaris($surat_keluar, 'disetujui', $user->name);

            ActivityHelper::log('Approval Surat', 'Direktur Utama menyetujui surat ' . $surat_keluar->nomor_surat);
            return back()->with('success', 'Surat berhasil disetujui & E-Sign disematkan ke dalam dokumen.');
        }

        return back()->with('error', 'Status surat tidak valid untuk disetujui saat ini.');
    }

    public function reject(Request $request, SuratKeluar $surat_keluar)
    {
        $user = auth()->user();
        $role = strtolower($user->role);

        // Surat dapat dikembalikan di dua titik: saat menunggu verifikasi direktur
        // bidangnya, dan saat menunggu persetujuan Direktur Utama.
        if ($role === 'dirut') {
            $bolehMenolak = $surat_keluar->status === 'menunggu_dirut';
        } elseif ($user->isDirektur()) {
            $bolehMenolak = $surat_keluar->status === 'menunggu_direktur'
                && $surat_keluar->unit_verifikasi === $user->unit;
        } else {
            abort(403, 'Hanya Direktur atau Direktur Utama yang dapat menolak Surat Keluar.');
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

        $this->beritahuSekretaris($surat_keluar, 'ditolak', $user->name);

        ActivityHelper::log('Reject Surat Keluar', 'Menolak Surat Keluar nomor ' . $surat_keluar->nomor_surat . ' dengan alasan: ' . $request->catatan_revisi);

        return redirect()->route('surat-keluar.show', $surat_keluar->id)->with('success', 'Surat Keluar berhasil ditolak dengan catatan revisi.');
    }

    public function download(SuratKeluar $surat_keluar)
    {
        if (!$surat_keluar->file) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $filePath = storage_path('app/public/' . $surat_keluar->file);
        
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
        $role = strtolower(auth()->user()->role);
        
        // Cek jika surat masih draft, hanya sekretaris dan admin yang bisa melihat
        if ($surat_keluar->status === 'draft' && !in_array($role, ['admin', 'administrator', 'superadmin', 'sekretaris'])) {
            abort(403, 'Akses ditolak. Surat masih dalam bentuk draft.');
        }

        $surat_keluar->load(['approvedDirektur', 'approvedDirut']);
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

    private function convertDocxToPdf($docxRelativePath)
    {
        $fullPath = storage_path('app/public/' . $docxRelativePath);
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
        } catch (\Exception $e) {
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