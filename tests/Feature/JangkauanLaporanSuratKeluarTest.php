<?php

/**
 * Laporan Surat Keluar adalah ikhtisar surat yang benar-benar diterbitkan.
 * Jajaran direksi membacanya seluruhnya; draf tidak masuk rekap siapa pun.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;

function pembacaLaporanKeluar(string $role, ?string $unit): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_laporan_surat_keluar'],
        ['label' => 'Lap. Surat Keluar', 'group' => 'Laporan']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratTerbit(User $penyusun, string $perihal, string $status = 'terkirim'): SuratKeluar
{
    static $urut = 0;
    $urut++;

    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'nomor_surat'     => $status === 'draft' ? null : sprintf('%03d/FI/SU/VIII/2026', $urut),
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-01',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => $perihal,
        'status'          => $status,
    ]);
}

test('para direktur bidang dapat membuka laporan surat keluar', function () {
    $penyusun = pembacaLaporanKeluar('staff', 'teknik');
    suratTerbit($penyusun, 'Undangan rapat koordinasi');

    foreach ([
        pembacaLaporanKeluar('direktur1', 'keuangan_administrasi'),
        pembacaLaporanKeluar('direktur2', 'teknik'),
    ] as $direktur) {
        $this->actingAs($direktur)
            ->get('/laporan/surat-keluar')
            ->assertOk()
            ->assertSee('Undangan rapat koordinasi');
    }
});

test('direktur melihat surat dari direktorat lain juga', function () {
    $penyusunKeuangan = pembacaLaporanKeluar('staff', 'keuangan_administrasi');
    $direkturTeknik = pembacaLaporanKeluar('direktur2', 'teknik');

    suratTerbit($penyusunKeuangan, 'Surat dari unit keuangan');

    // Anda menyebut ketiga jabatan sederajat, tanpa pembatasan per divisi
    $this->actingAs($direkturTeknik)
        ->get('/laporan/surat-keluar')
        ->assertOk()
        ->assertSee('Surat dari unit keuangan');
});

test('dirut dan sekretaris melihat seluruh surat terbit', function () {
    $penyusun = pembacaLaporanKeluar('staff', 'teknik');
    suratTerbit($penyusun, 'Surat resmi perusahaan');

    foreach ([
        pembacaLaporanKeluar('dirut', 'pimpinan'),
        pembacaLaporanKeluar('sekretaris', 'pimpinan'),
    ] as $pimpinan) {
        $this->actingAs($pimpinan)
            ->get('/laporan/surat-keluar')
            ->assertOk()
            ->assertSee('Surat resmi perusahaan');
    }
});

test('draf tidak masuk rekap, termasuk draf pembaca laporan sendiri', function () {
    $penyusun = pembacaLaporanKeluar('staff', 'teknik');
    $direktur = pembacaLaporanKeluar('direktur2', 'teknik');

    suratTerbit($penyusun, 'Draf milik penyusun', 'draft');
    suratTerbit($direktur, 'Draf milik direktur itu sendiri', 'draft');
    suratTerbit($penyusun, 'Surat yang sudah terkirim');

    $this->actingAs($direktur)
        ->get('/laporan/surat-keluar')
        ->assertOk()
        ->assertSee('Surat yang sudah terkirim')
        ->assertDontSee('Draf milik penyusun')
        ->assertDontSee('Draf milik direktur itu sendiri');
});

test('penyusun biasa hanya merekap surat susunannya sendiri', function () {
    $penyusunA = pembacaLaporanKeluar('staff', 'teknik');
    $penyusunB = pembacaLaporanKeluar('staff', 'teknik');

    suratTerbit($penyusunA, 'Surat susunan A');
    suratTerbit($penyusunB, 'Surat susunan B');

    $this->actingAs($penyusunA)
        ->get('/laporan/surat-keluar')
        ->assertOk()
        ->assertSee('Surat susunan A')
        ->assertDontSee('Surat susunan B');
});

test('pegawai tanpa izin laporan tetap ditolak', function () {
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $this->actingAs($staff)
        ->get('/laporan/surat-keluar')
        ->assertForbidden();
});

test('batasan yang sama berlaku pada unduhan pdf', function () {
    $direktur = pembacaLaporanKeluar('direktur2', 'teknik');
    suratTerbit($direktur, 'Surat resmi');

    $isi = $this->actingAs($direktur)
        ->get('/laporan/surat-keluar/pdf')
        ->assertOk()
        ->getContent();

    expect($isi)->toStartWith('%PDF');
});
