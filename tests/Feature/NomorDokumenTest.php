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
    $sekretaris = User::factory()->create(['role' => 'sekretaris', 'unit' => 'pimpinan']);
    $pegawai = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $buat = function () use ($sekretaris, $pegawai) {
        $this->actingAs($sekretaris)->post('/skpd', [
            'user_id'           => $pegawai->id,
            'jenis'             => 'perjalanan_dinas',
            'keperluan'         => 'Kunjungan kerja',
            'tujuan_dinas'      => 'Surabaya',
            'tanggal_berangkat' => '2026-09-01',
            'tanggal_kembali'   => '2026-09-03',
            'aksi'              => 'draft',
        ]);

        return App\Models\Skpd::latest('id')->first();
    };

    $pertama = $buat();
    $kedua = $buat();

    // Skema lama memakai max(id)+1, sehingga menghapus data terakhir
    // membuat nomor berikutnya mengulang nomor yang sudah dipakai.
    $this->actingAs($sekretaris)->delete('/skpd/' . $kedua->id);

    $ketiga = $buat();

    $semua = [
        $pertama->nomor_skpd,
        $kedua->nomor_skpd,
        $ketiga->nomor_skpd,
    ];

    expect(array_unique($semua))->toHaveCount(3);
});
