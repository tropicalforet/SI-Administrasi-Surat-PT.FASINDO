<?php

/**
 * Penyusun surat harus diberitahu atas keputusan terhadap konsepnya.
 * Sebelumnya hanya sekretaris yang menerima pemberitahuan, sehingga penyusun
 * tidak pernah tahu suratnya dikembalikan kecuali membuka daftar sendiri.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Notifications\SuratKeluarDiputuskan;
use Illuminate\Support\Facades\Notification;

function pihakSuratKeluar(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function konsepSuratKeluar(User $penyusun, string $status, ?string $nomor = null): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'nomor_surat'     => $nomor,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => 'Undangan rapat koordinasi',
        'status'          => $status,
    ]);
}

test('penyusun diberitahu saat suratnya dikembalikan sekretaris', function () {
    Notification::fake();

    $penyusun = pihakSuratKeluar('staff');
    $sekretaris = pihakSuratKeluar('sekretaris', 'pimpinan');

    $surat = konsepSuratKeluar($penyusun, 'menunggu_sekretaris');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Kop surat belum sesuai standar.',
    ]);

    Notification::assertSentTo($penyusun, SuratKeluarDiputuskan::class);
});

test('sekretaris tidak memberitahu dirinya sendiri saat menolak', function () {
    Notification::fake();

    $penyusun = pihakSuratKeluar('staff');
    $sekretaris = pihakSuratKeluar('sekretaris', 'pimpinan');

    $surat = konsepSuratKeluar($penyusun, 'menunggu_sekretaris');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Perbaiki nama instansi tujuan.',
    ]);

    Notification::assertNotSentTo($sekretaris, SuratKeluarDiputuskan::class);
});

test('penyusun dan sekretaris diberitahu saat dirut mengembalikan surat', function () {
    Notification::fake();

    $penyusun = pihakSuratKeluar('staff');
    $sekretaris = pihakSuratKeluar('sekretaris', 'pimpinan');
    $dirut = pihakSuratKeluar('dirut', 'pimpinan');

    $surat = konsepSuratKeluar($penyusun, 'menunggu_dirut', '001/FI/SU PT-MITRA/VIII/2026');

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Isi surat perlu diperbaiki.',
    ]);

    Notification::assertSentTo($penyusun, SuratKeluarDiputuskan::class);
    Notification::assertSentTo($sekretaris, SuratKeluarDiputuskan::class);
    Notification::assertNotSentTo($dirut, SuratKeluarDiputuskan::class);
});

test('penyusun diberitahu saat suratnya disetujui dan terkirim', function () {
    Notification::fake();

    $penyusun = pihakSuratKeluar('staff');
    $sekretaris = pihakSuratKeluar('sekretaris', 'pimpinan');
    $dirut = pihakSuratKeluar('dirut', 'pimpinan');

    $surat = konsepSuratKeluar($penyusun, 'menunggu_dirut', '002/FI/SU PT-MITRA/VIII/2026');

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    expect($surat->fresh()->status)->toBe('terkirim');

    Notification::assertSentTo($penyusun, SuratKeluarDiputuskan::class);
    Notification::assertSentTo($sekretaris, SuratKeluarDiputuskan::class);
});

test('nomor surat dipakai kembali setelah diajukan ulang', function () {
    $penyusun = pihakSuratKeluar('staff');
    $sekretaris = pihakSuratKeluar('sekretaris', 'pimpinan');
    pihakSuratKeluar('dirut', 'pimpinan');

    $surat = konsepSuratKeluar($penyusun, 'menunggu_sekretaris');

    // Sekretaris menerbitkan nomor, lalu Dirut mengembalikannya
    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');
    $nomorAwal = $surat->fresh()->nomor_surat;

    expect($nomorAwal)->not->toBeNull();

    $dirut = User::where('role', 'dirut')->first();
    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Perbaiki lampirannya.',
    ]);

    // Penyusun memperbaiki dan mengajukan ulang
    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id . '/submit');
    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    // Nomor lama melekat, tidak terbit nomor baru
    expect($surat->fresh()->nomor_surat)->toBe($nomorAwal);
});
