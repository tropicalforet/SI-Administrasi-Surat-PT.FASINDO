<?php

/**
 * Jangkauan rekap Laporan SKPD mengikuti garis komando:
 * Dirut dan Sekretaris merekap seluruh karyawan, Direktur bidang merekap
 * direktoratnya sendiri, selebihnya hanya miliknya sendiri.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pihakLaporan(string $role, ?string $unit): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_laporan_skpd'],
        ['label' => 'akses_laporan_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function skpdMilik(User $pegawai, string $tujuan): Skpd
{
    return Skpd::create([
        'user_id'           => $pegawai->id,
        'asal_usul'         => 'usulan',
        'nomor_skpd'        => 'SKPD-' . Skpd::withTrashed()->count() . '/08/2026',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => $tujuan,
        'keperluan'         => 'Kunjungan ke ' . $tujuan,
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'durasi_hari'       => 2,
        'status'            => 'disetujui',
    ]);
}

/** Siapkan satu perusahaan kecil dengan dua direktorat. */
function perusahaanUji(): array
{
    $dirut       = pihakLaporan('dirut', 'pimpinan');
    $sekretaris  = pihakLaporan('sekretaris', 'pimpinan');
    $dirKeuangan = pihakLaporan('direktur1', 'keuangan_administrasi');
    $dirTeknik   = pihakLaporan('direktur2', 'teknik');
    $adminKeu    = pihakLaporan('staff', 'keuangan_administrasi');
    $managerTek  = pihakLaporan('manager', 'teknik');

    skpdMilik($dirKeuangan, 'Medan');
    skpdMilik($dirTeknik, 'Bontang');
    skpdMilik($adminKeu, 'Surabaya');
    skpdMilik($managerTek, 'Merauke');

    return compact('dirut', 'sekretaris', 'dirKeuangan', 'dirTeknik', 'adminKeu', 'managerTek');
}

test('dirut dan sekretaris merekap seluruh karyawan', function () {
    ['dirut' => $dirut, 'sekretaris' => $sekretaris] = perusahaanUji();

    foreach ([$dirut, $sekretaris] as $pimpinan) {
        $this->actingAs($pimpinan)
            ->get('/laporan/skpd')
            ->assertOk()
            ->assertSee('Medan')
            ->assertSee('Bontang')
            ->assertSee('Surabaya')
            ->assertSee('Merauke');
    }
});

test('direktur keuangan hanya merekap direktoratnya', function () {
    ['dirKeuangan' => $direktur] = perusahaanUji();

    $this->actingAs($direktur)
        ->get('/laporan/skpd')
        ->assertOk()
        ->assertSee('Surabaya')   // Admin keuangan, bawahannya
        ->assertSee('Medan')      // Perjalanannya sendiri
        ->assertDontSee('Bontang')  // Direktur Teknik
        ->assertDontSee('Merauke'); // Manager teknik
});

test('direktur teknik hanya merekap direktoratnya', function () {
    ['dirTeknik' => $direktur] = perusahaanUji();

    $this->actingAs($direktur)
        ->get('/laporan/skpd')
        ->assertOk()
        ->assertSee('Merauke')
        ->assertSee('Bontang')
        ->assertDontSee('Surabaya')
        ->assertDontSee('Medan');
});

test('pegawai biasa hanya merekap miliknya sendiri', function () {
    ['managerTek' => $manager] = perusahaanUji();

    $this->actingAs($manager)
        ->get('/laporan/skpd')
        ->assertOk()
        ->assertSee('Merauke')
        ->assertDontSee('Bontang')
        ->assertDontSee('Surabaya')
        ->assertDontSee('Medan');
});

test('batasan yang sama berlaku pada unduhan pdf laporan', function () {
    ['dirTeknik' => $direktur] = perusahaanUji();

    // Tanpa ini, batasan di halaman bisa dilewati lewat tombol cetak
    $isi = $this->actingAs($direktur)
        ->get('/laporan/skpd/pdf')
        ->assertOk()
        ->getContent();

    expect($isi)->toStartWith('%PDF');
});

test('draf orang lain tetap tidak bocor lewat laporan', function () {
    ['dirTeknik' => $direktur, 'managerTek' => $manager] = perusahaanUji();

    skpdMilik($manager, 'KotaRahasia')->update(['status' => 'draft']);

    $this->actingAs($direktur)
        ->get('/laporan/skpd')
        ->assertOk()
        ->assertDontSee('KotaRahasia');
});
