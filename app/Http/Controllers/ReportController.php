<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use App\Models\Disposisi;
use App\Models\Skpd;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function isAdminLevel()
    {
        return in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris']);
    }

    // ==========================================
    // SURAT MASUK
    // ==========================================
    private function querySuratMasuk(Request $request)
    {
        $query = SuratMasuk::query();
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }
        
        if ($request->filled('pengirim')) {
            $query->where('pengirim', 'like', '%' . $request->pengirim . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (!$this->isAdminLevel()) {
            // Hanya lihat surat masuk yang ditujukan kepada user (disposisi)
            $query->whereHas('disposisis', function($q) {
                $q->where('kepada_user_id', auth()->id());
            });
        }

        return $query->latest('tanggal_surat')->get();
    }

    public function suratMasuk(Request $request)
    {
        $data = $this->querySuratMasuk($request);
        $pengirimList = SuratMasuk::select('pengirim')->distinct()->pluck('pengirim');
        return view('reports.surat_masuk', compact('data', 'pengirimList'));
    }

    public function suratMasukPdf(Request $request)
    {
        $data = $this->querySuratMasuk($request);
        $pdf = Pdf::loadView('reports.pdf.surat_masuk', compact('data', 'request'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Surat_Masuk.pdf');
    }

    // ==========================================
    // SURAT KELUAR
    // ==========================================
    private function querySuratKeluar(Request $request)
    {
        $query = SuratKeluar::query();
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }
        
        if ($request->filled('tujuan')) {
            $query->where('tujuan', 'like', '%' . $request->tujuan . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Untuk surat keluar, saat ini tidak ada relasi user pembuat. 
        // Anggap saja semua direktur bisa lihat surat keluar perusahaannya.
        // Jika perlu dibatasi, sesuaikan logikanya di sini.
        // Tapi sementara kita perlihatkan semua, karena di menu utama Surat Keluar juga terbuka (kecuali draft).
        if (!$this->isAdminLevel()) {
            $query->where('status', '!=', 'draft'); // staff dkk tidak lihat draft
        }

        return $query->latest('tanggal_surat')->get();
    }

    public function suratKeluar(Request $request)
    {
        $data = $this->querySuratKeluar($request);
        $tujuanList = SuratKeluar::select('tujuan')->distinct()->pluck('tujuan');
        return view('reports.surat_keluar', compact('data', 'tujuanList'));
    }

    public function suratKeluarPdf(Request $request)
    {
        $data = $this->querySuratKeluar($request);
        $pdf = Pdf::loadView('reports.pdf.surat_keluar', compact('data', 'request'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Surat_Keluar.pdf');
    }

    // ==========================================
    // DISPOSISI
    // ==========================================
    private function queryDisposisi(Request $request)
    {
        $query = Disposisi::with('suratMasuk');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (!$this->isAdminLevel()) {
            $query->where('kepada_user_id', auth()->id());
        }

        return $query->latest()->get();
    }

    public function disposisi(Request $request)
    {
        $data = $this->queryDisposisi($request);
        return view('reports.disposisi', compact('data'));
    }

    public function disposisiPdf(Request $request)
    {
        $data = $this->queryDisposisi($request);
        $pdf = Pdf::loadView('reports.pdf.disposisi', compact('data', 'request'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Disposisi.pdf');
    }

    // ==========================================
    // SKPD
    // ==========================================
    private function querySkpd(Request $request)
    {
        $query = Skpd::with('user');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_berangkat', [$request->start_date, $request->end_date]);
        }

        // Terapkan ke semua role: Laporan SKPD hanya menampilkan data milik sendiri
        $query->where('user_id', auth()->id());

        return $query->latest('tanggal_berangkat')->get();
    }

    public function skpd(Request $request)
    {
        $data = $this->querySkpd($request);
        return view('reports.skpd', compact('data'));
    }

    public function skpdPdf(Request $request)
    {
        $data = $this->querySkpd($request);
        $pdf = Pdf::loadView('reports.pdf.skpd', compact('data', 'request'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_SKPD.pdf');
    }
}
