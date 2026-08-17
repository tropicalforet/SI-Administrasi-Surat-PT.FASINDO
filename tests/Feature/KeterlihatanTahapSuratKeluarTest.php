<?php

/**
 * Melihat tidak sama dengan berwenang. Direktur Utama boleh memantau surat
 * yang masih di tahap Sekretaris, tetapi belum dapat menandatangani maupun
 * mengembalikannya sampai tahapnya benar-benar tiba.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;

function pihakTahap(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratTahap(User $penyusun, string $status, string $perihal): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => $perihal,
        'status'          => $status,
    ]);
}

test('dirut dapat melihat surat yang masih menunggu sekretaris', function () {
    $penyusun = pihakTahap('staff');
    $dirut = pihakTahap('dirut', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_sekretaris', 'Surat menunggu penomoran');

    expect($surat->dapatDilihatOleh($dirut))->toBeTrue();

    $this->actingAs($dirut)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertSee('Surat menunggu penomoran');

    $this->actingAs($dirut)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk();
});

test('tombol tanda tangan belum muncul sebelum surat dinomori', function () {
    $penyusun = pihakTahap('staff');
    $dirut = pihakTahap('dirut', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_sekretaris', 'Surat menunggu penomoran');

    $this->actingAs($dirut)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertDontSee('Persetujuan Surat Keluar');
});

test('dirut tidak dapat menandatangani surat yang belum dinomori', function () {
    $penyusun = pihakTahap('staff');
    $dirut = pihakTahap('dirut', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_sekretaris', 'Surat menunggu penomoran');

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris')
        ->and($surat->fresh()->nomor_surat)->toBeNull();
});

test('dirut tidak dapat mengembalikan surat yang bukan tahapnya', function () {
    $penyusun = pihakTahap('staff');
    $dirut = pihakTahap('dirut', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_sekretaris', 'Surat menunggu penomoran');

    $this->actingAs($dirut)
        ->put('/surat-keluar/' . $surat->id . '/reject', ['catatan_revisi' => 'Coba tolak'])
        ->assertForbidden();

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');
});

test('sekretaris tidak dapat mengembalikan surat yang sudah di meja dirut', function () {
    $penyusun = pihakTahap('staff');
    $sekretaris = pihakTahap('sekretaris', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_dirut', 'Surat menunggu tanda tangan');

    // Kebalikannya juga dijaga: tahap sudah lewat, bukan lagi wewenangnya
    $this->actingAs($sekretaris)
        ->put('/surat-keluar/' . $surat->id . '/reject', ['catatan_revisi' => 'Coba tolak'])
        ->assertForbidden();

    expect($surat->fresh()->status)->toBe('menunggu_dirut');
});

test('tombol tanda tangan muncul setelah surat dinomori', function () {
    $penyusun = pihakTahap('staff');
    $sekretaris = pihakTahap('sekretaris', 'pimpinan');
    $dirut = pihakTahap('dirut', 'pimpinan');

    $surat = suratTahap($penyusun, 'menunggu_sekretaris', 'Surat menunggu penomoran');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    $this->actingAs($dirut)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('Persetujuan Surat Keluar');
});
