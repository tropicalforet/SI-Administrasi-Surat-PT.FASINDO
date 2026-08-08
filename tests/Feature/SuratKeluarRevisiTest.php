<?php

use App\Models\SuratKeluar;
use App\Models\User;

function sekretaris(): User
{
    return User::factory()->create(['role' => 'sekretaris']);
}

function suratDitolak(): SuratKeluar
{
    return SuratKeluar::create([
        'nomor_surat'    => '001/FI/Undangan TEST/VIII/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'tujuan'         => 'PT Contoh',
        'perihal'        => 'Undangan rapat',
        'status'         => 'ditolak',
        'catatan_revisi' => 'Perbaiki tanggal rapat.',
    ]);
}

test('surat yang ditolak dapat dibuka untuk revisi', function () {
    $surat = suratDitolak();

    $this->actingAs(sekretaris())
        ->get('/surat-keluar/' . $surat->id . '/edit')
        ->assertOk();
});

test('surat yang ditolak dapat diperbarui tanpa kehilangan catatan revisi', function () {
    $surat = suratDitolak();

    $this->actingAs(sekretaris())->put('/surat-keluar/' . $surat->id, [
        'tanggal_surat' => '2026-08-05',
        'tujuan'        => 'PT Contoh',
        'perihal'       => 'Undangan rapat (revisi)',
    ]);

    $surat->refresh();

    expect($surat->perihal)->toBe('Undangan rapat (revisi)')
        ->and($surat->status)->toBe('ditolak')
        ->and($surat->catatan_revisi)->toBe('Perbaiki tanggal rapat.');
});

test('surat yang ditolak dapat diajukan ulang ke dirut', function () {
    $surat = suratDitolak();

    $this->actingAs(sekretaris())
        ->put('/surat-keluar/' . $surat->id . '/submit')
        ->assertRedirect(route('surat-keluar.index'));

    $surat->refresh();

    expect($surat->status)->toBe('menunggu_dirut')
        ->and($surat->catatan_revisi)->toBeNull();
});

test('surat yang sudah disetujui tidak dapat diajukan ulang', function () {
    $surat = suratDitolak();
    $surat->update(['status' => 'terkirim', 'catatan_revisi' => null]);

    $this->actingAs(sekretaris())->put('/surat-keluar/' . $surat->id . '/submit');

    expect($surat->fresh()->status)->toBe('terkirim');
});

test('surat yang menunggu dirut tidak dapat diubah', function () {
    $surat = suratDitolak();
    $surat->update(['status' => 'menunggu_dirut']);

    $this->actingAs(sekretaris())
        ->get('/surat-keluar/' . $surat->id . '/edit')
        ->assertRedirect(route('surat-keluar.index'));
});
