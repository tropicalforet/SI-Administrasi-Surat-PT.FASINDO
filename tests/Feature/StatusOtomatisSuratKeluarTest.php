<?php

/**
 * Status surat keluar berubah mengikuti alur persetujuan, bukan dipilih
 * sendiri oleh pengguna. Formulir edit karenanya tidak boleh menawarkan
 * pilihan status, dan controller tidak boleh menerimanya dari request.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;

function penyusunSurat(string $role = 'staff', ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function konsepMilik(User $penyusun, string $status = 'draft'): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => 'Undangan rapat koordinasi',
        'status'          => $status,
    ]);
}

test('formulir edit tidak menawarkan pilihan status', function () {
    $penyusun = penyusunSurat();
    $surat = konsepMilik($penyusun);

    $isi = $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id . '/edit')
        ->assertOk()
        ->getContent();

    expect($isi)->not->toContain('name="status"')
        ->and($isi)->not->toContain('<option value="dikirim"')
        ->and($isi)->not->toContain('<option value="selesai"');
});

test('status yang dikirim lewat request diabaikan', function () {
    $penyusun = penyusunSurat();
    $surat = konsepMilik($penyusun);

    // Meniru pengiriman status secara paksa lewat request
    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id, [
        'tanggal_surat' => '2026-08-12',
        'tujuan'        => 'PT Mitra Sejahtera',
        'perihal'       => 'Undangan rapat koordinasi (revisi)',
        'status'        => 'terkirim',
    ]);

    $surat->refresh();

    expect($surat->perihal)->toBe('Undangan rapat koordinasi (revisi)')
        ->and($surat->status)->toBe('draft');
});

test('status berubah sendiri mengikuti alur persetujuan', function () {
    $penyusun = penyusunSurat();
    $sekretaris = penyusunSurat('sekretaris', 'pimpinan');
    $dirut = penyusunSurat('dirut', 'pimpinan');

    $surat = konsepMilik($penyusun);
    expect($surat->status)->toBe('draft');

    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id . '/submit');
    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');
    expect($surat->fresh()->status)->toBe('menunggu_dirut');

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');
    expect($surat->fresh()->status)->toBe('terkirim');
});

test('formulir edit menampilkan status terkini apa adanya', function () {
    $penyusun = penyusunSurat();
    $surat = konsepMilik($penyusun, 'ditolak');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id . '/edit')
        ->assertOk()
        ->assertSee('Ditolak')
        ->assertSee('(Belum bernomor)');
});
