<?php

/**
 * Surat yang dikembalikan untuk direvisi tetap terlihat Sekretaris dan
 * pimpinan. Berbeda dengan draf yang belum pernah diajukan, surat berstatus
 * ditolak sudah masuk alur resmi - bahkan mungkin sudah memegang nomor -
 * sehingga menyembunyikannya akan meninggalkan lubang yang tidak dapat
 * dipertanggungjawabkan Sekretaris.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;

function pihakKeterlihatan(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratKeterlihatan(User $penyusun, string $status, ?string $nomor, string $perihal): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'nomor_surat'     => $nomor,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => $perihal,
        'status'          => $status,
        'catatan_revisi'  => $status === 'ditolak' ? 'Isi surat perlu diperbaiki.' : null,
    ]);
}

test('surat yang ditolak dirut tetap terlihat sekretaris', function () {
    $penyusun = pihakKeterlihatan('staff');
    $sekretaris = pihakKeterlihatan('sekretaris', 'pimpinan');

    $surat = suratKeterlihatan($penyusun, 'ditolak', '001/FI/SU PT-MITRA/VIII/2026', 'Surat dikembalikan Dirut');

    expect($surat->dapatDilihatOleh($sekretaris))->toBeTrue();

    $this->actingAs($sekretaris)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertSee('Surat dikembalikan Dirut');

    $this->actingAs($sekretaris)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('Isi surat perlu diperbaiki.');
});

test('surat yang ditolak tetap terlihat direktur utama', function () {
    $penyusun = pihakKeterlihatan('staff');
    $dirut = pihakKeterlihatan('dirut', 'pimpinan');

    $surat = suratKeterlihatan($penyusun, 'ditolak', '002/FI/SU PT-MITRA/VIII/2026', 'Surat menunggu revisi');

    expect($surat->dapatDilihatOleh($dirut))->toBeTrue();

    $this->actingAs($dirut)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertSee('Surat menunggu revisi');
});

test('draf yang belum pernah diajukan tetap tersembunyi dari sekretaris', function () {
    $penyusun = pihakKeterlihatan('staff');
    $sekretaris = pihakKeterlihatan('sekretaris', 'pimpinan');

    $draf = suratKeterlihatan($penyusun, 'draft', null, 'Konsep belum diajukan');

    // Pembeda utamanya: draf belum pernah masuk alur, surat ditolak sudah
    expect($draf->dapatDilihatOleh($sekretaris))->toBeFalse();

    $this->actingAs($sekretaris)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertDontSee('Konsep belum diajukan');
});

test('penyusun tetap dapat membuka dan memperbaiki suratnya yang ditolak', function () {
    $penyusun = pihakKeterlihatan('staff');

    $surat = suratKeterlihatan($penyusun, 'ditolak', '003/FI/SU PT-MITRA/VIII/2026', 'Surat perlu diperbaiki');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('Ajukan Ulang')
        ->assertSee('Isi surat perlu diperbaiki.');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id . '/edit')
        ->assertOk();
});

test('penyusun lain tetap tidak dapat membuka surat yang ditolak', function () {
    $penyusun = pihakKeterlihatan('staff');
    $penyusunLain = pihakKeterlihatan('staff');

    $surat = suratKeterlihatan($penyusun, 'ditolak', '004/FI/SU PT-MITRA/VIII/2026', 'Surat milik orang lain');

    // Bukan draf, tapi juga bukan miliknya dan bukan unitnya - tetap ditolak
    expect($surat->dapatDilihatOleh($penyusunLain))->toBeFalse();

    $this->actingAs($penyusunLain)
        ->get('/surat-keluar/' . $surat->id)
        ->assertForbidden();
});
