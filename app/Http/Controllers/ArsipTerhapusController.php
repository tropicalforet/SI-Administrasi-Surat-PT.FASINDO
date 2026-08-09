<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\Disposisi;
use App\Models\Skpd;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\SuratTugas;
use Illuminate\Http\Request;

class ArsipTerhapusController extends Controller
{
    /**
     * Jenis dokumen yang dapat dipulihkan, beserta label dan kolom yang
     * dipakai sebagai judul pada daftar.
     */
    private const JENIS = [
        'surat-masuk'  => ['model' => SuratMasuk::class,  'label' => 'Surat Masuk',  'nomor' => 'nomor_surat',       'perihal' => 'perihal'],
        'surat-keluar' => ['model' => SuratKeluar::class, 'label' => 'Surat Keluar', 'nomor' => 'nomor_surat',       'perihal' => 'perihal'],
        'surat-tugas'  => ['model' => SuratTugas::class,  'label' => 'Surat Tugas',  'nomor' => 'nomor_surat_tugas', 'perihal' => 'perihal_tugas'],
        'skpd'         => ['model' => Skpd::class,        'label' => 'SKPD',         'nomor' => 'nomor_skpd',        'perihal' => 'keperluan'],
        'disposisi'    => ['model' => Disposisi::class,   'label' => 'Disposisi',    'nomor' => 'id',                'perihal' => 'instruksi'],
    ];

    public function index(Request $request)
    {
        $jenis = $request->query('jenis', 'surat-masuk');

        abort_unless(isset(self::JENIS[$jenis]), 404);

        $konfigurasi = self::JENIS[$jenis];
        $model = $konfigurasi['model'];

        $data = $model::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();

        $jumlah = [];
        foreach (self::JENIS as $kunci => $item) {
            $jumlah[$kunci] = $item['model']::onlyTrashed()->count();
        }

        return view('arsip.index', [
            'data'        => $data,
            'jenis'       => $jenis,
            'konfigurasi' => $konfigurasi,
            'daftarJenis' => self::JENIS,
            'jumlah'      => $jumlah,
        ]);
    }

    public function restore(string $jenis, int $id)
    {
        $model = $this->modelUntuk($jenis);

        $dokumen = $model::onlyTrashed()->findOrFail($id);
        $dokumen->restore();

        ActivityHelper::log(
            'Pulihkan Dokumen',
            'Memulihkan ' . self::JENIS[$jenis]['label'] . ' ID ' . $id . ' dari arsip terhapus'
        );

        return back()->with('success', self::JENIS[$jenis]['label'] . ' berhasil dipulihkan.');
    }

    /**
     * Penghapusan permanen adalah jalan terakhir dan hanya dari halaman ini,
     * sehingga tidak mungkin terjadi karena salah klik pada daftar biasa.
     */
    public function forceDelete(string $jenis, int $id)
    {
        $model = $this->modelUntuk($jenis);

        $dokumen = $model::onlyTrashed()->findOrFail($id);
        $dokumen->forceDelete();

        ActivityHelper::log(
            'Hapus Permanen',
            'Menghapus permanen ' . self::JENIS[$jenis]['label'] . ' ID ' . $id
        );

        return back()->with('success', self::JENIS[$jenis]['label'] . ' dihapus permanen.');
    }

    private function modelUntuk(string $jenis): string
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);

        return self::JENIS[$jenis]['model'];
    }
}
