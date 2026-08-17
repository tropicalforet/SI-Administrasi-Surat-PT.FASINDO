<?php

/**
 * Seluruh halaman di dalam aplikasi harus memakai layout utama, sehingga
 * sidebar dan bilah identitas selalu tampak. Sebelumnya sembilan halaman
 * ditulis sebagai halaman HTML berdiri sendiri, akibatnya menu menghilang
 * begitu pengguna membuka formulir.
 */

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;

function penggunaLayout(string $role, array $izin, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $permission = Permission::firstOrCreate(
            ['name' => $nama],
            ['label' => $nama, 'group' => 'uji']
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

function suratLayout(array $tambahan = []): SuratMasuk
{
    static $urut = 0;
    $urut++;

    return SuratMasuk::create(array_merge([
        'nomor_surat'      => sprintf('%03d/LAYOUT/2026', $urut),
        'kategori_surat'   => 'Undangan',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Undangan rapat',
        'status'           => 'baru',
        'penerima'         => 'Penerima',
    ], $tambahan));
}

/** Penanda bahwa layout utama benar-benar terpasang. */
function bertumpuLayout($respons): void
{
    $isi = $respons->assertOk()->getContent();

    expect($isi)->toContain('id="sidebar"')
        ->and($isi)->toContain('id="tombolMenu"')
        ->and($isi)->not->toContain('<!DOCTYPE html>' . "\n" . '<html lang="id">' . "\n" . '<head>');
}

test('halaman tambah surat masuk memakai layout utama', function () {
    $sekretaris = penggunaLayout('sekretaris', ['akses_surat_masuk'], 'pimpinan');

    bertumpuLayout($this->actingAs($sekretaris)->get('/surat-masuk/create'));
});

test('halaman edit surat masuk memakai layout utama', function () {
    $sekretaris = penggunaLayout('sekretaris', ['akses_surat_masuk'], 'pimpinan');
    $surat = suratLayout();

    bertumpuLayout($this->actingAs($sekretaris)->get('/surat-masuk/' . $surat->id . '/edit'));
});

test('halaman tambah surat keluar memakai layout utama', function () {
    $staff = penggunaLayout('staff', ['akses_surat_keluar']);

    bertumpuLayout($this->actingAs($staff)->get('/surat-keluar/create'));
});

test('halaman edit surat keluar memakai layout utama', function () {
    $staff = penggunaLayout('staff', ['akses_surat_keluar']);

    $surat = SuratKeluar::create([
        'dibuat_oleh'     => $staff->id,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $staff->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => 'Undangan rapat',
        'status'          => 'draft',
    ]);

    bertumpuLayout($this->actingAs($staff)->get('/surat-keluar/' . $surat->id . '/edit'));
});

test('halaman buat disposisi memakai layout utama', function () {
    $dirut = penggunaLayout('dirut', ['akses_disposisi'], 'pimpinan');
    penggunaLayout('direktur2', ['akses_disposisi'], 'teknik');

    $surat = suratLayout(['penerima_role' => 'dirut', 'penerima' => 'Direktur Utama']);

    bertumpuLayout($this->actingAs($dirut)->get('/disposisi/' . $surat->id . '/create'));
});

test('halaman tindak lanjut disposisi memakai layout utama', function () {
    $dirut = penggunaLayout('dirut', ['akses_disposisi'], 'pimpinan');
    $direktur = penggunaLayout('direktur2', ['akses_disposisi'], 'teknik');

    $surat = suratLayout();

    $disposisi = Disposisi::create([
        'surat_masuk_id'    => $surat->id,
        'dari_user_id'      => $dirut->id,
        'kepada_user_id'    => $direktur->id,
        'instruksi'         => 'Mohon ditindaklanjuti.',
        'tanggal_disposisi' => '2026-08-02',
        'status'            => 'menunggu',
    ]);

    bertumpuLayout($this->actingAs($direktur)->get('/disposisi/' . $disposisi->id . '/edit'));
});

test('halaman monitoring disposisi memakai layout utama', function () {
    $dirut = penggunaLayout('dirut', ['akses_monitoring'], 'pimpinan');

    bertumpuLayout($this->actingAs($dirut)->get('/monitoring-disposisi'));
});

test('halaman monitoring tidak memuat dua modal konfirmasi', function () {
    $dirut = penggunaLayout('dirut', ['akses_monitoring'], 'pimpinan');

    $isi = $this->actingAs($dirut)->get('/monitoring-disposisi')->assertOk()->getContent();

    // Layout sudah menyertakannya sekali; dulu halaman ini menyertakannya lagi.
    expect(substr_count($isi, 'id="confirm-overlay"'))->toBe(1);
});
