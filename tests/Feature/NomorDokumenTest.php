<?php

use App\Helpers\NomorDokumenHelper;
use App\Models\SuratTugas;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('nomor yang diterbitkan selalu berurutan dan tidak pernah berulang', function () {
    $nomor = [];
    for ($i = 0; $i < 50; $i++) {
        $nomor[] = NomorDokumenHelper::next('skpd', 2026);
    }

    expect($nomor)->toBe(range(1, 50))
        ->and(array_unique($nomor))->toHaveCount(50);
});

test('counter terpisah antar jenis dokumen dan antar tahun', function () {
    NomorDokumenHelper::next('skpd', 2026);
    NomorDokumenHelper::next('skpd', 2026);

    expect(NomorDokumenHelper::next('surat_tugas', 2026))->toBe(1)
        ->and(NomorDokumenHelper::next('skpd', 2027))->toBe(1)
        ->and(NomorDokumenHelper::next('skpd', 2026))->toBe(3);
});

test('counter surat keluar terpisah per kategori', function () {
    NomorDokumenHelper::next('surat_keluar:Undangan', 2026);
    NomorDokumenHelper::next('surat_keluar:Undangan', 2026);

    expect(NomorDokumenHelper::next('surat_keluar:Pemberitahuan', 2026))->toBe(1)
        ->and(NomorDokumenHelper::next('surat_keluar:Undangan', 2026))->toBe(3);
});

test('nomor melanjutkan dari counter yang sudah terisi', function () {
    DB::table('document_counters')->insert([
        'jenis'       => 'skpd',
        'tahun'       => 2026,
        'nomor_akhir' => 31,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    expect(NomorDokumenHelper::next('skpd', 2026))->toBe(32);
});

test('nomor skpd tidak terulang setelah data dihapus', function () {
    $dirut = User::factory()->create(['role' => 'dirut', 'unit' => 'pimpinan']);
    $pegawai = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $ajukan = function (string $aksi) use ($dirut, $pegawai) {
        $this->actingAs($dirut)->post('/skpd', [
            'user_id'           => $pegawai->id,
            'keperluan'         => 'Kunjungan kerja',
            'tujuan_dinas'      => 'Surabaya',
            'tanggal_berangkat' => '2026-09-01',
            'tanggal_kembali'   => '2026-09-03',
            'aksi'              => $aksi,
        ]);

        return App\Models\Skpd::latest('id')->first();
    };

    $setujui = function (App\Models\Skpd $skpd) use ($dirut) {
        $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

        return $skpd->fresh();
    };

    $pertama = $setujui($ajukan('ajukan'));
    $kedua   = $setujui($ajukan('ajukan'));

    // Skema lama memakai max(id)+1, sehingga menghapus data terakhir membuat
    // nomor berikutnya mengulang nomor yang sudah dipakai. Penghitung terpisah
    // tidak pernah mundur, walau datanya dihapus.
    $dibuang = $ajukan('draft');
    $this->actingAs($dirut)->delete('/skpd/' . $dibuang->id);

    $ketiga = $setujui($ajukan('ajukan'));

    $semua = [$pertama->nomor_skpd, $kedua->nomor_skpd, $ketiga->nomor_skpd];

    expect(array_filter($semua))->toHaveCount(3)
        ->and(array_unique($semua))->toHaveCount(3);
});
