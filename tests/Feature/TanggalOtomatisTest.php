<?php

/**
 * Isian tanggal pada formulir pembuatan diisi hari ini agar tidak perlu
 * membuka pemilih tanggal untuk kasus yang paling lazim. Nilainya tetap
 * dapat diubah - ini hanya nilai awal.
 *
 * Kolom tanggal pada filter laporan sengaja dibiarkan kosong: bila diisi
 * hari ini, laporan akan diam-diam menyaring dirinya sendiri.
 */

use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function pemakaiForm(string $role, string $izin, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $permission = Permission::firstOrCreate(
        ['name' => $izin],
        ['label' => $izin, 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);

    return $user;
}

function hariIni(): string
{
    return now()->toDateString();
}

test('tanggal surat masuk terisi hari ini', function () {
    $sekretaris = pemakaiForm('sekretaris', 'akses_surat_masuk', 'pimpinan');

    $this->actingAs($sekretaris)
        ->get('/surat-masuk/create')
        ->assertOk()
        ->assertSee('name="tanggal_surat"', false)
        ->assertSee('value="' . hariIni() . '"', false);
});

test('tanggal surat keluar terisi hari ini', function () {
    $staff = pemakaiForm('staff', 'akses_surat_keluar');

    $this->actingAs($staff)
        ->get('/surat-keluar/create')
        ->assertOk()
        ->assertSee('value="' . hariIni() . '"', false);
});

test('tanggal berangkat dan kembali skpd terisi hari ini', function () {
    $staff = pemakaiForm('staff', 'akses_skpd');

    $isi = $this->actingAs($staff)->get('/skpd/create')->assertOk()->getContent();

    // Dua kolom tanggal, keduanya terisi
    expect(substr_count($isi, 'value="' . hariIni() . '"'))->toBeGreaterThanOrEqual(2);
});

test('tenggat disposisi terisi hari ini', function () {
    $dirut = pemakaiForm('dirut', 'akses_disposisi', 'pimpinan');
    User::factory()->create(['role' => 'direktur2', 'unit' => 'teknik']);

    $surat = SuratMasuk::create([
        'nomor_surat'    => '001/A/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'pengirim'       => 'Dinas Contoh',
        'perihal'        => 'Undangan rapat',
        'status'         => 'baru',
        'penerima_role'  => 'dirut',
        'penerima'       => 'Direktur Utama',
    ]);

    $this->actingAs($dirut)
        ->get('/disposisi/' . $surat->id . '/create')
        ->assertOk()
        ->assertSee('name="batas_waktu"', false)
        ->assertSee('value="' . hariIni() . '"', false);
});

test('nilai awal tidak menimpa isian yang gagal validasi', function () {
    $sekretaris = pemakaiForm('sekretaris', 'akses_surat_masuk', 'pimpinan');

    // Setelah validasi gagal, tanggal yang tadi diketik harus kembali,
    // bukan tertimpa hari ini.
    $this->actingAs($sekretaris)
        ->from('/surat-masuk/create')
        ->post('/surat-masuk', ['tanggal_surat' => '2026-01-15'])
        ->assertRedirect('/surat-masuk/create');

    $this->actingAs($sekretaris)
        ->get('/surat-masuk/create')
        ->assertOk()
        ->assertSee('value="2026-01-15"', false);
});

test('filter tanggal pada laporan tetap kosong', function () {
    $sekretaris = pemakaiForm('sekretaris', 'akses_laporan_surat_masuk', 'pimpinan');

    // Laporan yang menyaring dirinya sendiri ke hari ini akan terlihat
    // seperti kehilangan data.
    $this->actingAs($sekretaris)
        ->get('/laporan/surat-masuk')
        ->assertOk()
        ->assertDontSee('value="' . hariIni() . '"', false);
});
