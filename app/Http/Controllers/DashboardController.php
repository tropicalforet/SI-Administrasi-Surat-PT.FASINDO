<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use Illuminate\Support\Facades\DB; // Tambahkan facade DB

class DashboardController extends Controller
{
    /**
     * Hitung jumlah baris per bulan, dikelompokkan dari kolom tanggal.
     *
     * MONTH() hanya dikenal MySQL, sehingga ekspresinya disesuaikan dengan
     * driver yang sedang dipakai agar dashboard tetap jalan di database lain.
     */
    private function hitungPerBulan($query, string $kolom)
    {
        $ekspresi = DB::connection()->getDriverName() === 'sqlite'
            ? "cast(strftime('%m', {$kolom}) as integer)"
            : "MONTH({$kolom})";

        return $query
            ->selectRaw("{$ekspresi} as month, count(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');
    }

    public function index()
    {
        $currentYear = date('Y');
        $role = strtolower(auth()->user()->role);

        // 1. Optimasi Status Surat Keluar (1 Query untuk 4 perhitungan)
        // Status yang benar-benar dipakai SuratKeluarController: draft,
        // menunggu_dirut, terkirim, dan ditolak.
        $keluarStats = SuratKeluar::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'menunggu_dirut' THEN 1 ELSE 0 END) as menunggu_dirut,
            SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as terkirim,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
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

        /*
         * 4. Rekap dan daftar SKPD
         *
         * Jangkauannya memakai aturan yang sama dengan daftar SKPD - lihat
         * Skpd::scopeTerlihatOleh - sehingga angka di dashboard tidak pernah
         * memuat dokumen yang tidak boleh dibuka orangnya, termasuk draf yang
         * masih disusun orang lain.
         *
         * Sebelumnya dashboard menarik seluruh baris tanpa saringan bagi Dirut
         * dan Sekretaris, lalu menyaring per pengguna dengan aturannya sendiri.
         */
        $skpdStats = \App\Models\Skpd::terlihatOleh(auth()->user())->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('menunggu_direktur', 'menunggu_dirut') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
        ")->first();

        $skpdTerbaru = \App\Models\Skpd::with('user')
            ->terlihatOleh(auth()->user())
            ->latest()
            ->take(5)
            ->get();

        // 5. Data Chart Bulanan (Surat Masuk & Surat Keluar)
        $smBulananData = $this->hitungPerBulan(
            SuratMasuk::whereYear('tanggal_surat', $currentYear),
            'tanggal_surat'
        );

        $skBulananData = $this->hitungPerBulan(
            SuratKeluar::whereYear('tanggal_surat', $currentYear),
            'tanggal_surat'
        );

        // 6. Data Chart Bulanan (SKPD) - jangkauannya sama dengan rekap di atas
        $skpdBulananData = $this->hitungPerBulan(
            \App\Models\Skpd::terlihatOleh(auth()->user())
                ->whereYear('tanggal_berangkat', $currentYear),
            'tanggal_berangkat'
        );

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
        // Dirut dan sekretaris melihat seluruh organisasi lewat menu monitoring;
        // pengguna lain hanya boleh melihat disposisi yang ditujukan kepadanya,
        // supaya angka grafik konsisten dengan kartu "Menunggu" di atasnya.
        $disposisiStatsQuery = Disposisi::query();
        if ($role !== 'dirut' && $role !== 'sekretaris') {
            $disposisiStatsQuery->where('kepada_user_id', auth()->id());
        }

        $disposisiStats = $disposisiStatsQuery->selectRaw("
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
            'totalMenungguDirut' => $keluarStats->menunggu_dirut ?? 0,
            'totalTerkirim'      => $keluarStats->terkirim ?? 0,
            'totalDitolak'       => $keluarStats->ditolak ?? 0,
            'disposisiMenunggu'  => $disposisiMenunggu,
            
            // SKPD Stats
            'totalSkpd'          => $skpdStats->total ?? 0,
            'skpdPending'        => $skpdStats->pending ?? 0,
            'skpdDisetujui'      => $skpdStats->disetujui ?? 0,
            'skpdDitolak'        => $skpdStats->ditolak ?? 0,
            
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