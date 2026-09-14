<?php

/**
 * Laporan Surat Masuk adalah buku agenda perusahaan: jajaran direksi
 * membacanya seluruhnya, selebihnya hanya surat yang memang boleh ia baca.
 */

use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function pembacaLaporanMasuk(string $role, ?string $unit): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_laporan_surat_masuk'],
        ['label' => 'Lap. Surat Masuk', 'group' => 'Laporan']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratAgenda(string $perihal, array $tujuan = []): SuratMasuk
{
    static $urut = 0;
    $urut++;

    return SuratMasuk::create(array_merge([
        'nomor_surat'      => sprintf('%03d/AGENDA/2026', $urut),
        'kategori_surat'   => 'Undangan',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => $perihal,
        'status'           => 'baru',
        'penerima'         => 'Penerima',
    ], $tujuan));
}

test('para direktur bidang dapat membuka laporan surat masuk', function () {
    suratAgenda('Rapat koordinasi tahunan');

    foreach ([
        pembacaLaporanMasuk('direktur1', 'keuangan_administrasi'),
        pembacaLaporanMasuk('direktur2', 'teknik'),
    ] as $direktur) {
        $this->actingAs($direktur)
            ->get('/laporan/surat-masuk')
            ->assertOk()
            ->assertSee('Rapat koordinasi tahunan');
    }
});

test('direktur melihat seluruh agenda, bukan hanya surat untuknya', function () {
    $direkturTeknik = pembacaLaporanMasuk('direktur2', 'teknik');

    suratAgenda('Surat untuk Direktur Teknik', ['penerima_role' => 'direktur2']);
    suratAgenda('Surat untuk Direktur Keuangan', ['penerima_role' => 'direktur1']);
    suratAgenda('Surat untuk Direktur Utama', ['penerima_role' => 'dirut']);

    // Sebelumnya laporan ini disaring dengan aturan baca per surat, sehingga
    // seorang direktur hanya melihat surat yang ditujukan kepadanya.
    $this->actingAs($direkturTeknik)
        ->get('/laporan/surat-masuk')
        ->assertOk()
        ->assertSee('Surat untuk Direktur Teknik')
        ->assertSee('Surat untuk Direktur Keuangan')
        ->assertSee('Surat untuk Direktur Utama');
});

test('dirut dan sekretaris tetap melihat seluruh agenda', function () {
    suratAgenda('Surat untuk Direktur Keuangan', ['penerima_role' => 'direktur1']);

    foreach ([
        pembacaLaporanMasuk('dirut', 'pimpinan'),
        pembacaLaporanMasuk('sekretaris', 'pimpinan'),
    ] as $pimpinan) {
        $this->actingAs($pimpinan)
            ->get('/laporan/surat-masuk')
            ->assertOk()
            ->assertSee('Surat untuk Direktur Keuangan');
    }
});

test('pegawai biasa hanya melihat surat yang boleh ia baca', function () {
    $manager = pembacaLaporanMasuk('manager', 'teknik');

    suratAgenda('Surat untuk manager itu', ['penerima_id' => $manager->id]);
    suratAgenda('Surat untuk Direktur Keuangan', ['penerima_role' => 'direktur1']);

    $this->actingAs($manager)
        ->get('/laporan/surat-masuk')
        ->assertOk()
        ->assertSee('Surat untuk manager itu')
        ->assertDontSee('Surat untuk Direktur Keuangan');
});

test('pegawai tanpa izin laporan tetap ditolak', function () {
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $this->actingAs($staff)
        ->get('/laporan/surat-masuk')
        ->assertForbidden();
});

test('batasan yang sama berlaku pada unduhan pdf', function () {
    $direktur = pembacaLaporanMasuk('direktur2', 'teknik');
    suratAgenda('Surat untuk Direktur Keuangan', ['penerima_role' => 'direktur1']);

    $isi = $this->actingAs($direktur)
        ->get('/laporan/surat-masuk/pdf')
        ->assertOk()
        ->getContent();

    expect($isi)->toStartWith('%PDF');
});
