<?php

/**
 * Laporan Disposisi adalah alat kontrol manajemen: hanya Direktur Utama dan
 * Sekretaris yang membacanya, dan mereka membaca seluruh rekam jejak.
 */

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function pihakLaporanDisposisi(string $role, ?string $unit): User
{
    return User::factory()->create(['role' => $role, 'unit' => $unit]);
}

function jejakDisposisi(User $dari, User $kepada, string $instruksi): Disposisi
{
    static $urut = 0;
    $urut++;

    $surat = SuratMasuk::create([
        'nomor_surat'      => sprintf('%03d/DISP/2026', $urut),
        'kategori_surat'   => 'Undangan',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Undangan rapat',
        'status'           => 'didisposisikan',
        'penerima'         => 'Penerima',
    ]);

    return Disposisi::create([
        'surat_masuk_id'    => $surat->id,
        'dari_user_id'      => $dari->id,
        'kepada_user_id'    => $kepada->id,
        'instruksi'         => $instruksi,
        'tanggal_disposisi' => '2026-08-02',
        'status'            => 'belum',
    ]);
}

test('dirut dan sekretaris dapat membuka laporan disposisi', function () {
    $direktur = pihakLaporanDisposisi('direktur2', 'teknik');
    $manager = pihakLaporanDisposisi('manager', 'teknik');

    jejakDisposisi($direktur, $manager, 'Tindak lanjuti undangan ini');

    foreach ([
        pihakLaporanDisposisi('dirut', 'pimpinan'),
        pihakLaporanDisposisi('sekretaris', 'pimpinan'),
    ] as $pengendali) {
        $this->actingAs($pengendali)
            ->get('/laporan/disposisi')
            ->assertOk()
            ->assertSee('Tindak lanjuti undangan ini');
    }
});

test('direktur bidang tidak dapat membuka laporan disposisi', function () {
    foreach ([
        pihakLaporanDisposisi('direktur1', 'keuangan_administrasi'),
        pihakLaporanDisposisi('direktur2', 'teknik'),
    ] as $direktur) {
        $this->actingAs($direktur)->get('/laporan/disposisi')->assertForbidden();
        $this->actingAs($direktur)->get('/laporan/disposisi/pdf')->assertForbidden();
    }
});

test('izin lama tidak lagi membuka pintu bagi direktur', function () {
    $direktur = pihakLaporanDisposisi('direktur2', 'teknik');

    // Meniru pemberian izin lewat menu Manajemen User: sejak aksesnya
    // melekat pada jabatan, centang semacam ini tidak berpengaruh.
    $izin = Permission::firstOrCreate(
        ['name' => 'akses_laporan_disposisi'],
        ['label' => 'Lap. Disposisi', 'group' => 'Laporan']
    );
    $direktur->permissions()->syncWithoutDetaching([$izin->id]);

    $this->actingAs($direktur)->get('/laporan/disposisi')->assertForbidden();
});

test('manager dan pelaksana tetap ditolak', function () {
    foreach ([
        pihakLaporanDisposisi('manager', 'teknik'),
        pihakLaporanDisposisi('staff', 'keuangan_administrasi'),
    ] as $pegawai) {
        $this->actingAs($pegawai)->get('/laporan/disposisi')->assertForbidden();
    }
});

test('laporan memuat seluruh rekam jejak, bukan hanya disposisi si pembaca', function () {
    $dirut = pihakLaporanDisposisi('dirut', 'pimpinan');
    $direktur = pihakLaporanDisposisi('direktur2', 'teknik');
    $manager = pihakLaporanDisposisi('manager', 'teknik');

    // Rantai yang sama sekali tidak melibatkan Dirut
    jejakDisposisi($direktur, $manager, 'Perintah antar bawahan');

    $this->actingAs($dirut)
        ->get('/laporan/disposisi')
        ->assertOk()
        ->assertSee('Perintah antar bawahan');
});

test('menu laporan disposisi tidak muncul di sidebar direktur', function () {
    $direktur = pihakLaporanDisposisi('direktur2', 'teknik');

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_laporan_skpd'],
        ['label' => 'Lap. SKPD', 'group' => 'Laporan']
    );
    $direktur->permissions()->syncWithoutDetaching([$izin->id]);

    // Menu SKPD tetap ada, menu Disposisi hilang - sekaligus memastikan
    // blok "Laporan & Rekapitulasi" tidak ikut hilang seluruhnya.
    $this->actingAs($direktur)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Lap. SKPD')
        ->assertDontSee('Lap. Disposisi');
});

test('menu laporan disposisi muncul untuk sekretaris', function () {
    $sekretaris = pihakLaporanDisposisi('sekretaris', 'pimpinan');

    $this->actingAs($sekretaris)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Lap. Disposisi');
});
