<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Support\Facades\DB; // Tambahkan facade DB

class DashboardController extends Controller
{
    public function index()
    {
        $currentYear = date('Y');
        $role = strtolower(auth()->user()->role);

        // 1. Optimasi Status Surat Keluar (1 Query untuk 4 perhitungan)
        $keluarStats = SuratKeluar::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as dikirim,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        ")->first();

        // 2. Data Sederhana
        $totalSuratMasuk = SuratMasuk::count();
        
        $disposisiMenunggu = Disposisi::where('kepada_user_id', auth()->id())
            ->where('status', 'menunggu')
            ->count();

        // 3. Eager Loading (Sudah benar dan optimal)
        $disposisiSaya = Disposisi::with(['suratMasuk', 'dariUser'])
            ->where('kepada_user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $suratTerbaru = SuratMasuk::latest()
            ->take(5)
            ->get();

        // 4. SKPD Stats & Terbaru (Role-based)
        if ($role === 'dirut' || $role === 'sekretaris') {
            $skpdStats = \App\Models\Skpd::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pengajuan' OR status = 'diperiksa' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui
            ")->first();
            
            $skpdTerbaru = \App\Models\Skpd::latest()->take(5)->get();
        } else {
            $skpdStats = \App\Models\Skpd::where('user_id', auth()->id())
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pengajuan' OR status = 'diperiksa' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui
                ")->first();
                
            $skpdTerbaru = \App\Models\Skpd::where('user_id', auth()->id())->latest()->take(5)->get();
        }

        // 5. Data Chart Bulanan (Surat Masuk & Surat Keluar)
        $smBulananData = SuratMasuk::whereYear('tanggal_surat', $currentYear)
            ->selectRaw('MONTH(tanggal_surat) as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $skBulananData = SuratKeluar::whereYear('tanggal_surat', $currentYear)
            ->selectRaw('MONTH(tanggal_surat) as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        // 6. Data Chart Bulanan (SKPD)
        $skpdBulananQuery = \App\Models\Skpd::whereYear('tanggal_berangkat', $currentYear);
        if ($role !== 'dirut' && $role !== 'sekretaris') {
            $skpdBulananQuery->where('user_id', auth()->id());
        }
        $skpdBulananData = $skpdBulananQuery
            ->selectRaw('MONTH(tanggal_berangkat) as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $suratMasukBulanan = [];
        $suratKeluarBulanan = [];
        $skpdBulanan = [];

        // Memasukkan data dari database ke array 12 bulan
        for ($i = 1; $i <= 12; $i++) {
            $suratMasukBulanan[] = $smBulananData->get($i, 0);
            $suratKeluarBulanan[] = $skBulananData->get($i, 0);
            $skpdBulanan[] = $skpdBulananData->get($i, 0);
        }

        // 7. Status Disposisi
        $disposisiStats = Disposisi::selectRaw("
            SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
            SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        ")->first();

        $statusDisposisi = [
            (int) $disposisiStats->menunggu,
            (int) $disposisiStats->diproses,
            (int) $disposisiStats->selesai,
        ];

        return view('dashboard', [
            'totalSuratMasuk'    => $totalSuratMasuk,
            'totalSuratKeluar'   => $keluarStats->total ?? 0,
            'totalDraft'         => $keluarStats->draft ?? 0,
            'totalDikirim'       => $keluarStats->dikirim ?? 0,
            'totalSelesai'       => $keluarStats->selesai ?? 0,
            'disposisiMenunggu'  => $disposisiMenunggu,
            
            // SKPD Stats
            'totalSkpd'          => $skpdStats->total ?? 0,
            'skpdPending'        => $skpdStats->pending ?? 0,
            'skpdDisetujui'      => $skpdStats->disetujui ?? 0,
            
            'disposisiSaya'      => $disposisiSaya,
            'suratTerbaru'       => $suratTerbaru,
            'skpdTerbaru'        => $skpdTerbaru,
            
            'suratMasukBulanan'  => $suratMasukBulanan,
            'suratKeluarBulanan' => $suratKeluarBulanan,
            'skpdBulanan'        => $skpdBulanan,
            'statusDisposisi'    => $statusDisposisi
        ]);
    }
}