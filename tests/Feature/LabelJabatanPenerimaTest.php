<?php

/**
 * Di layar mana pun pengguna memilih orang, yang tampil harus jabatan
 * sebenarnya - bukan kode role. "direktur2" tidak memberi tahu siapa pun
 * bahwa itu Direktur Teknik.
 */

use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function pemilihPenerima(string $role, string $izin, ?string $unit = 'pimpinan'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $permission = Permission::firstOrCreate(
        ['name' => $izin],
        ['label' => $izin, 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);

    return $user;
}

test('daftar penerima disposisi menyebut jabatan, bukan kode role', function () {
    $dirut = pemilihPenerima('dirut', 'akses_disposisi');

    User::factory()->create([
        'name' => 'Yetti Heryati',
        'role' => 'direktur1',
        'unit' => 'keuangan_administrasi',
    ]);

    User::factory()->create([
        'name' => 'Hendin Habudin',
        'role' => 'direktur2',
        'unit' => 'teknik',
    ]);

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
        ->assertSee('Direktur Keuangan dan Administrasi')
        ->assertSee('Direktur Teknik')
        ->assertDontSee('DIREKTUR1')
        ->assertDontSee('DIREKTUR2');
});

test('daftar penerima surat masuk menyebut jabatan beserta unitnya', function () {
    $sekretaris = pemilihPenerima('sekretaris', 'akses_surat_masuk');

    User::factory()->create(['name' => 'Juhaeri Saefudin', 'role' => 'manager', 'unit' => 'teknik']);
    User::factory()->create(['name' => 'Salsa Anggraini', 'role' => 'staff', 'unit' => 'keuangan_administrasi']);

    $isi = $this->actingAs($sekretaris)
        ->get('/surat-masuk/create')
        ->assertOk()
        ->assertSee('Manager (Teknik)')
        ->assertSee('Pelaksana / Admin (Keuangan dan Administrasi)')
        ->getContent();

    // Tanpa unit, tiga orang berjabatan Manager tampak identik di dropdown
    expect($isi)->not->toContain('(Manager)');
});

test('label penerima surat menyebut jabatan dan unit', function () {
    $penerima = User::factory()->create([
        'name' => 'Salsa Anggraini',
        'role' => 'staff',
        'unit' => 'keuangan_administrasi',
    ]);

    $surat = SuratMasuk::create([
        'nomor_surat'    => '001/A/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'pengirim'       => 'Dinas Contoh',
        'perihal'        => 'Undangan rapat',
        'status'         => 'baru',
        'penerima_id'    => $penerima->id,
        'penerima'       => $penerima->name,
    ]);

    expect($surat->label_penerima)
        ->toBe('Salsa Anggraini (Pelaksana / Admin - Keuangan dan Administrasi)');
});

test('jabatan yang diisi administrator mengalahkan label bawaan role', function () {
    $user = User::factory()->create([
        'name'    => 'Hendin Habudin',
        'role'    => 'direktur2',
        'unit'    => 'teknik',
        'jabatan' => 'Direktur Teknik dan Operasional',
    ]);

    expect($user->label_jabatan)->toBe('Direktur Teknik dan Operasional');
});
