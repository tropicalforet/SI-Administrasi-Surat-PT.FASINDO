<?php

/**
 * Bunyi pemberitahuan harus sesuai kejadiannya.
 *
 * Dulu pengajuan konsep dikirim memakai SuratKeluarDiputuskan dengan keputusan
 * 'diajukan'. Nilai itu tidak punya arm di match, sehingga jatuh ke default dan
 * terbaca "Surat keluar dikembalikan untuk revisi" di layar sekretaris -
 * padahal suratnya baru saja diajukan.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Notifications\SuratKeluarDiputuskan;
use App\Notifications\SuratKeluarMenungguTindakan;

function pihakBunyi(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function konsepBunyi(User $penyusun, string $status = 'draft'): SuratKeluar
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

test('pengajuan konsep tidak berbunyi seperti penolakan', function () {
    $penyusun = pihakBunyi('staff');
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');

    $surat = konsepBunyi($penyusun);

    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id . '/submit');

    $data = $sekretaris->notifications()->first()->data;

    expect($data['judul'])->not->toContain('dikembalikan')
        ->and($data['judul'])->not->toContain('revisi')
        ->and($data['pesan'])->not->toContain('dikembalikan');
});

test('sekretaris diberitahu ada konsep yang menunggu penomoran', function () {
    $penyusun = pihakBunyi('staff');
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');

    $surat = konsepBunyi($penyusun);

    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id . '/submit');

    $data = $sekretaris->notifications()->first()->data;

    expect($data['tipe'])->toBe('surat_keluar_perlu_penomoran')
        ->and($data['judul'])->toBe('Surat keluar menunggu penomoran Anda')
        ->and($data['pesan'])->toContain('Undangan rapat koordinasi')
        ->and($data['pesan'])->toContain('diajukan oleh ' . $penyusun->name)
        ->and($data['pesan'])->toContain('menunggu penomoran');
});

test('sekretaris yang mengajukan konsepnya sendiri tidak memberitahu dirinya', function () {
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');

    $surat = konsepBunyi($sekretaris);

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/submit');

    expect($sekretaris->notifications()->count())->toBe(0);
});

test('konsep belum bernomor tidak disebut sebagai surat bernomor', function () {
    $penyusun = pihakBunyi('staff');
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');

    $surat = konsepBunyi($penyusun);

    $this->actingAs($penyusun)->put('/surat-keluar/' . $surat->id . '/submit');

    $data = $sekretaris->notifications()->first()->data;

    // Pada tahap ini nomor memang belum terbit; menyebut "(Belum bernomor)"
    // di badan kalimat hanya membingungkan pembacanya.
    expect($data['pesan'])->not->toContain('Belum bernomor');
});

test('dirut diberitahu surat sudah dinomori dan menunggu persetujuan', function () {
    $penyusun = pihakBunyi('staff');
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');
    $dirut = pihakBunyi('dirut', 'pimpinan');

    $surat = konsepBunyi($penyusun, 'menunggu_sekretaris');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    $data = $dirut->notifications()->first()->data;

    expect($data['tipe'])->toBe('surat_keluar_perlu_persetujuan')
        ->and($data['judul'])->toBe('Surat keluar menunggu persetujuan Anda')
        ->and($data['pesan'])->toContain($surat->fresh()->nomor_surat)
        ->and($data['pesan'])->toContain('dinomori oleh ' . $sekretaris->name);
});

test('penolakan tetap berbunyi sebagai pengembalian', function () {
    $penyusun = pihakBunyi('staff');
    $sekretaris = pihakBunyi('sekretaris', 'pimpinan');

    $surat = konsepBunyi($penyusun, 'menunggu_sekretaris');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Kop surat belum sesuai standar.',
    ]);

    $data = $penyusun->notifications()->first()->data;

    expect($data['judul'])->toBe('Surat keluar dikembalikan untuk revisi')
        ->and($data['pesan'])->toContain('Kop surat belum sesuai standar.');
});

test('keputusan yang tidak dikenal ditolak, bukan diam-diam jadi penolakan', function () {
    $penyusun = pihakBunyi('staff');
    $surat = konsepBunyi($penyusun);

    // Inilah yang dulu lolos tanpa suara dan melahirkan kalimat keliru itu.
    $notifikasi = new SuratKeluarDiputuskan($surat, 'diajukan', 'Salsa');

    expect(fn () => $notifikasi->toArray($penyusun))
        ->toThrow(InvalidArgumentException::class);
});

test('tindakan yang tidak dikenal juga ditolak', function () {
    $penyusun = pihakBunyi('staff');
    $surat = konsepBunyi($penyusun);

    $notifikasi = new SuratKeluarMenungguTindakan($surat, 'entah', 'Salsa');

    expect(fn () => $notifikasi->toArray($penyusun))
        ->toThrow(InvalidArgumentException::class);
});
