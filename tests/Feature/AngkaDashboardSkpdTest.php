<?php

/**
 * Angka SKPD di dashboard dan lencana sidebar harus mencerminkan keadaan
 * sebenarnya, dan tidak boleh menghitung dokumen yang tidak boleh dibuka.
 *
 * Dua kesalahan yang ditutup di sini. Pertama, keduanya mencari status
 * 'pengajuan' dan 'diperiksa' - status yang tidak pernah dipakai sistem -
 * sehingga kartu "Menunggu Persetujuan" dan lencana sidebar selamanya nol.
 * Kedua, rekapnya menarik seluruh baris bagi Dirut dan Sekretaris, termasuk
 * draf orang lain yang seharusnya tertutup.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pihakDashboard(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach (['akses_skpd', 'akses_surat_masuk'] as $nama) {
        $izin = Permission::firstOrCreate(['name' => $nama], ['label' => $nama, 'group' => 'uji']);
        $user->permissions()->syncWithoutDetaching([$izin->id]);
    }

    return $user;
}

function skpdUji(string $status, User $pegawai, ?User $penugas = null, string $keperluan = 'Pemeriksaan lapangan'): Skpd
{
    return Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => $penugas?->id,
        'asal_usul'         => $penugas ? 'penugasan' : 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => $keperluan,
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'status'            => $status,
    ]);
}

test('kartu menunggu persetujuan menghitung dokumen yang benar-benar berjalan', function () {
    $dirut = pihakDashboard('dirut', 'pimpinan');
    $pegawai = pihakDashboard('staff');

    skpdUji('menunggu_dirut', $pegawai);
    skpdUji('menunggu_direktur', $pegawai);
    skpdUji('disetujui', $pegawai);
    skpdUji('ditolak', $pegawai);

    $respons = $this->actingAs($dirut)->get('/dashboard')->assertOk();

    // Dua yang sedang menunggu keputusan, bukan nol seperti sebelumnya.
    expect($respons->viewData('skpdPending'))->toBe(2)
        ->and($respons->viewData('skpdDisetujui'))->toBe(1)
        ->and($respons->viewData('skpdDitolak'))->toBe(1);
});

test('draf orang lain tidak ikut terhitung pada rekap dirut', function () {
    $dirut = pihakDashboard('dirut', 'pimpinan');
    $pegawai = pihakDashboard('staff');

    skpdUji('draft', $pegawai);
    skpdUji('disetujui', $pegawai);

    $respons = $this->actingAs($dirut)->get('/dashboard')->assertOk();

    // Hanya yang sudah diajukan yang terhitung.
    expect($respons->viewData('totalSkpd'))->toBe(1);
});

test('draf orang lain tidak muncul pada daftar skpd terbaru', function () {
    $dirut = pihakDashboard('dirut', 'pimpinan');
    $pegawai = pihakDashboard('staff');

    skpdUji('draft', $pegawai, null, 'Rencana yang belum diajukan');

    $this->actingAs($dirut)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Rencana yang belum diajukan');
});

test('penyusun tetap melihat drafnya sendiri terhitung', function () {
    $pegawai = pihakDashboard('staff');

    skpdUji('draft', $pegawai);

    $respons = $this->actingAs($pegawai)->get('/dashboard')->assertOk();

    expect($respons->viewData('totalSkpd'))->toBe(1);
});

test('pegawai hanya menghitung skpd miliknya sendiri', function () {
    $pegawai = pihakDashboard('staff');
    $orangLain = pihakDashboard('staff');

    skpdUji('disetujui', $pegawai);
    skpdUji('disetujui', $orangLain);
    skpdUji('disetujui', $orangLain);

    $respons = $this->actingAs($pegawai)->get('/dashboard')->assertOk();

    expect($respons->viewData('totalSkpd'))->toBe(1);
});

/** Angka pada lencana merah di samping menu SKPD, atau nol bila tidak ada. */
function lencanaSkpd(string $isi): int
{
    // Lencana hanya muncul bila jumlahnya lebih dari nol.
    $cocok = preg_match(
        '#SKPD.*?bg-red-500 rounded-full">\s*(\d+)\s*</span>#s',
        $isi,
        $bagian
    );

    return $cocok ? (int) $bagian[1] : 0;
}

test('lencana sidebar dirut menghitung skpd yang menunggu tanda tangannya', function () {
    $dirut = pihakDashboard('dirut', 'pimpinan');
    $pegawai = pihakDashboard('staff');

    skpdUji('menunggu_dirut', $pegawai);
    skpdUji('menunggu_dirut', $pegawai);
    skpdUji('menunggu_direktur', $pegawai);
    skpdUji('disetujui', $pegawai);

    $isi = $this->actingAs($dirut)->get('/dashboard')->assertOk()->getContent();

    // Dua yang menunggu Dirut - dulu selalu nol karena mencari status mati.
    expect(lencanaSkpd($isi))->toBe(2);
});

test('lencana direktur bidang menghitung usulan di direktoratnya saja', function () {
    $direktur = pihakDashboard('direktur2', 'teknik');
    $bawahan = pihakDashboard('staff', 'teknik');
    $unitLain = pihakDashboard('staff', 'keuangan_administrasi');

    skpdUji('menunggu_direktur', $bawahan);
    skpdUji('menunggu_direktur', $unitLain);

    $isi = $this->actingAs($direktur)->get('/dashboard')->assertOk()->getContent();

    // Usulan direktorat lain bukan urusannya.
    expect(lencanaSkpd($isi))->toBe(1);
});

test('sekretaris tidak lagi memperoleh lencana skpd', function () {
    $sekretaris = pihakDashboard('sekretaris', 'pimpinan');
    $pegawai = pihakDashboard('staff');

    skpdUji('menunggu_dirut', $pegawai);
    skpdUji('menunggu_direktur', $pegawai);

    $isi = $this->actingAs($sekretaris)->get('/dashboard')->assertOk()->getContent();

    // Sekretaris tidak punya tahap apa pun pada alur SKPD.
    expect(lencanaSkpd($isi))->toBe(0);
});
