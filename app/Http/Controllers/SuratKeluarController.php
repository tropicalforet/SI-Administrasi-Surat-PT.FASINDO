<?php

namespace App\Http\Controllers;

use App\Models\SuratKeluar;
use App\Helpers\ActivityHelper;
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

        // Optimasi Performa: Hanya pluck nomor_surat alih-alih seluruh objek database
        $nomorSuratTahunIni = SuratKeluar::where('kategori_surat', $kategori)
            ->whereYear('created_at', $tahun)
            ->where('nomor_surat', 'not like', 'DRAFT-%')
            ->pluck('nomor_surat');

        $nomorTerakhir = 0;
        foreach ($nomorSuratTahunIni as $nomor) {
            $parts = explode('/', $nomor);
            if (isset($parts[0])) {
                $angka = (int) $parts[0];
                if ($angka > $nomorTerakhir) {
                    $nomorTerakhir = $angka;
                }
            }
        }

        $nomorBaru = $nomorTerakhir + 1;

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

        if ($surat_keluar->status !== 'draft') {
            return redirect()->route('surat-keluar.index')->with('error', 'Surat yang sudah diajukan tidak dapat diubah.');
        }

        return view('surat_keluar.edit', compact('surat_keluar'));
    }
    
    public function update(Request $request, SuratKeluar $surat_keluar)
    {
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        if ($surat_keluar->status !== 'draft') {
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
                $fullPath = storage_path('app/public/' . $filePath);
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
        abort_unless(auth()->user()->role === 'sekretaris', 403, 'Akses ditolak.');

        $surat_keluar->update([
            'status' => 'menunggu_dirut',
        ]);
        
        ActivityHelper::log('Ajukan Persetujuan', 'Mengajukan surat ' . $surat_keluar->nomor_surat . ' untuk persetujuan Direktur Utama');

        return redirect()->route('surat-keluar.index')->with('success', 'Surat berhasil diajukan untuk persetujuan.');
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
                        $verifyUrl = route('surat-keluar.verify', $surat_keluar->id);
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

            ActivityHelper::log('Approval Surat', 'Direktur Utama menyetujui surat ' . $surat_keluar->nomor_surat);
            return back()->with('success', 'Surat berhasil disetujui & E-Sign disematkan ke dalam dokumen.');
        }

        return back()->with('error', 'Status surat tidak valid untuk disetujui saat ini.');
    }

    public function reject(Request $request, SuratKeluar $surat_keluar)
    {
        if (strtolower(auth()->user()->role) !== 'dirut') {
            abort(403, 'Hanya Direktur Utama yang dapat menolak/merevisi Surat Keluar.');
        }

        $request->validate([
            'catatan_revisi' => 'required|string'
        ]);

        $surat_keluar->update([
            'status'         => 'ditolak',
            'catatan_revisi' => $request->catatan_revisi
        ]);

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

        // Validasi draft dihapus sesuai request admin

        if ($surat_keluar->file && Storage::disk('public')->exists($surat_keluar->file)) {
            Storage::disk('public')->delete($surat_keluar->file);
        }
        
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

    public function verify(SuratKeluar $surat_keluar)
    {
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

        // Hapus semua file di folder surat_keluar
        Storage::disk('public')->deleteDirectory('surat_keluar');
        Storage::disk('public')->makeDirectory('surat_keluar');

        ActivityHelper::log('Hapus Semua Surat Keluar', 'Menghapus seluruh data surat keluar');
        
        // Hapus menggunakan Eloquent agar event dan foreign key cascade (jika ada) terpicu
        SuratKeluar::query()->delete();

        return redirect()->route('surat-keluar.index')->with('success', 'Semua riwayat surat keluar berhasil dihapus secara permanen.');
    }
}