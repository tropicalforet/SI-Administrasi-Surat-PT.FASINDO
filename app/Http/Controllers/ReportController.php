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
    // ==========================================
    // SURAT MASUK
    // ==========================================
    private function querySuratMasuk(Request $request)
    {
        // Jangkauan rekap ditentukan sekali di model - lihat
        // SuratMasuk::scopeLaporanUntuk.
        $query = SuratMasuk::laporanUntuk(auth()->user());

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }
        
        if ($request->filled('pengirim')) {
            $query->where('pengirim', 'like', '%' . $request->pengirim . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sifat')) {
            $query->where('sifat', $request->sifat);
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
        // Jangkauan rekap dan pengecualian draf ditentukan sekali di model -
        // lihat SuratKeluar::scopeLaporanUntuk.
        $query = SuratKeluar::laporanUntuk(auth()->user());

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_surat', [$request->start_date, $request->end_date]);
        }
        
        if ($request->filled('tujuan')) {
            $query->where('tujuan', 'like', '%' . $request->tujuan . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        // Tidak ada penyempitan per pengguna: yang sampai ke sini hanya
        // Direktur Utama, Sekretaris, dan administrator - dan justru rekam
        // jejak menyeluruh itulah gunanya laporan ini.
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
        // Jangkauan rekap mengikuti garis komando, bukan disamakan untuk semua
        // role - lihat Skpd::scopeLaporanUntuk.
        $query = Skpd::with('user')
            ->tanpaDrafOrangLain(auth()->user())
            ->laporanUntuk(auth()->user());

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_berangkat', [$request->start_date, $request->end_date]);
        }

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
