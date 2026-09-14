<?php

/**
 * Sebutan menyesuaikan perbuatan yang sebenarnya terjadi.
 *
 * Direktur Utama yang menerbitkan penugasan sendiri tidak sedang menyetujui
 * perintahnya sendiri - keputusan itu sudah diambil saat perintahnya dibuat.
 * Yang tersisa hanyalah menandatangani, dan seluruh sebutan di layar maupun
 * pemberitahuan menyebut demikian.
 *
 * Alurnya sendiri tidak berubah sedikit pun: status, tahapan, dan kewenangan
 * tetap sama persis seperti sebelumnya.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pelakuSebutan(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function ajukanDinas($test, User $pembuat, User $pegawai): Skpd
{
    $test->actingAs($pembuat)->post('/skpd', [
        'user_id'           => $pegawai->id,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'aksi'              => 'ajukan',
    ]);

    return Skpd::latest('id')->first();
}

test('dirut menandatangani penugasan yang ia terbitkan, bukan menyetujuinya', function () {
    $dirut = pelakuSebutan('dirut', 'pimpinan');
    $pegawai = pelakuSebutan('staff');

    $skpd = ajukanDinas($this, $dirut, $pegawai);

    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Penerbitan Dokumen SKPD')
        ->assertSee('Anda terbitkan sendiri')
        ->assertDontSee('Persetujuan Dokumen SKPD')
        ->assertDontSee('Setujui SKPD');
});

test('penugasan dari direktur bidang tetap disebut persetujuan bagi dirut', function () {
    $direktur = pelakuSebutan('direktur2', 'teknik');
    $pegawai = pelakuSebutan('staff', 'teknik');
    $dirut = pelakuSebutan('dirut', 'pimpinan');

    $skpd = ajukanDinas($this, $direktur, $pegawai);

    // Bukan perintah Direktur Utama, jadi langkahnya memang persetujuan.
    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Persetujuan Dokumen SKPD')
        ->assertSee('Setujui SKPD')
        ->assertDontSee('Penerbitan Dokumen SKPD');
});

test('usulan pegawai tetap disebut persetujuan bagi dirut', function () {
    $pegawai = pelakuSebutan('staff', 'pimpinan');
    $dirut = pelakuSebutan('dirut', 'pimpinan');

    $this->actingAs($pegawai)->post('/skpd', [
        'user_id'           => $pegawai->id,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'aksi'              => 'ajukan',
    ]);

    $skpd = Skpd::latest('id')->first();

    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Setujui SKPD');
});

test('pemberitahuan menyebut tanda tangan bila dirut sendiri yang menugaskan', function () {
    $dirut = pelakuSebutan('dirut', 'pimpinan');
    $pegawai = pelakuSebutan('staff');

    ajukanDinas($this, $dirut, $pegawai);

    $data = $dirut->notifications()->first()->data;

    expect($data['judul'])->toBe('SKPD menunggu tanda tangan Anda')
        ->and($data['pesan'])->toContain('yang Anda terbitkan')
        ->and($data['pesan'])->not->toContain('menunggu persetujuan');
});

test('pemberitahuan tetap menyebut persetujuan bila yang menugaskan orang lain', function () {
    $direktur = pelakuSebutan('direktur2', 'teknik');
    $pegawai = pelakuSebutan('staff', 'teknik');
    $dirut = pelakuSebutan('dirut', 'pimpinan');

    ajukanDinas($this, $direktur, $pegawai);

    $data = $dirut->notifications()->first()->data;

    expect($data['judul'])->toBe('SKPD menunggu persetujuan Anda');
});

test('pemberitahuan menyebut tujuan dinas, bukan ruang kosong', function () {
    $direktur = pelakuSebutan('direktur2', 'teknik');
    $pegawai = pelakuSebutan('staff', 'teknik');
    $dirut = pelakuSebutan('dirut', 'pimpinan');

    ajukanDinas($this, $direktur, $pegawai);

    // Dulu tertulis $skpd->tujuan - kolom yang tidak pernah ada - sehingga
    // pesannya berbunyi "penugasan ke ." tanpa tujuan sama sekali.
    expect($dirut->notifications()->first()->data['pesan'])->toContain('Kendal');
});

test('alur dan statusnya tidak berubah sama sekali', function () {
    $dirut = pelakuSebutan('dirut', 'pimpinan');
    $pegawai = pelakuSebutan('staff');

    $skpd = ajukanDinas($this, $dirut, $pegawai);

    // Sebutan boleh berubah, tahapan tidak - agar tetap sesuai dokumen.
    expect($skpd->status)->toBe('menunggu_dirut');

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    expect($skpd->fresh()->status)->toBe('disetujui')
        ->and($skpd->fresh()->nomor_skpd)->not->toBeNull();
});
