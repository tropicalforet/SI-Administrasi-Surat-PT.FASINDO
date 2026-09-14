<?php

/**
 * Yang memperbaiki dokumen adalah yang menulisnya.
 *
 * Tautan perbaikan dulu hanya tampil bagi pegawai yang ditugaskan. Pada
 * penugasan dari atasan, akibatnya justru orang yang tidak menulis dokumen itu
 * yang diminta memperbaikinya, sementara penulisnya - yang tahu persis apa yang
 * ingin diubah - tidak melihat tautan apa pun. Padahal kewenangan di controller
 * sejak awal sudah membolehkan atasan yang menugaskan untuk mengubahnya.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pelakuPerbaikan(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function penugasanDitolak(User $penugas, User $pegawai): Skpd
{
    return Skpd::create([
        'user_id'         => $pegawai->id,
        'ditugaskan_oleh' => $penugas->id,
        'asal_usul'       => 'penugasan',
        'nama_pegawai'    => $pegawai->name,
        'tujuan_dinas'    => 'Kendal',
        'keperluan'       => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'status'          => 'ditolak',
        'catatan_revisi'  => 'Tanggal keberangkatan perlu dimundurkan.',
    ]);
}

test('penerbit penugasan melihat tautan perbaikan atas dokumennya sendiri', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');

    $skpd = penugasanDitolak($dirut, $pegawai);

    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Perbaiki Dokumen Ini')
        ->assertSee('Dikembalikan untuk Diperbaiki');
});

test('penerbit penugasan benar-benar dapat membuka halaman ubah', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');

    $skpd = penugasanDitolak($dirut, $pegawai);

    // Tautannya tidak boleh menjanjikan sesuatu yang berujung 403.
    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id . '/edit')
        ->assertOk();
});

test('pegawai yang ditugaskan tidak ikut memperbaiki perintah atasannya', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');

    $skpd = penugasanDitolak($dirut, $pegawai);

    // Isi penugasan adalah kehendak atasan yang menerbitkannya. Pegawai yang
    // namanya tercantum bukan penyusunnya, jadi tidak berwenang mengubahnya -
    // dan selama belum terbit, dokumennya pun belum tampil di layarnya.
    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id)
        ->assertForbidden();

    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id . '/edit')
        ->assertForbidden();
});

test('pegawai lain tidak melihat tautan perbaikan dan tidak dapat mengubah', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');
    $orangLain = pelakuPerbaikan('staff');

    $skpd = penugasanDitolak($dirut, $pegawai);

    $this->actingAs($orangLain)
        ->get('/skpd/' . $skpd->id . '/edit')
        ->assertForbidden();
});

test('penerbit penugasan dapat mengajukan ulang setelah memperbaiki', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');

    $skpd = penugasanDitolak($dirut, $pegawai);

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/ajukan');

    expect($skpd->fresh()->status)->toBe('menunggu_dirut')
        ->and($skpd->fresh()->catatan_revisi)->toBeNull();
});

test('kata tolak tidak dipakai saat penerbit mengembalikan dokumennya sendiri', function () {
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');
    $pegawai = pelakuPerbaikan('staff');

    $skpd = Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => $dirut->id,
        'asal_usul'         => 'penugasan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'status'            => 'menunggu_dirut',
    ]);

    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Kembalikan untuk Diperbaiki')
        ->assertSee('Catatan Perbaikan')
        ->assertSee('Kembalikan Dokumen')
        ->assertSee('tersimpan pada riwayat dokumen')
        ->assertDontSee('Alasan Penolakan')
        ->assertDontSee('Kirim Penolakan');
});

test('usulan pegawai tetap memakai sebutan penolakan seperti semula', function () {
    $pegawai = pelakuPerbaikan('staff', 'pimpinan');
    $dirut = pelakuPerbaikan('dirut', 'pimpinan');

    $skpd = Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => null,
        'asal_usul'         => 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'status'            => 'menunggu_dirut',
    ]);

    $this->actingAs($dirut)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Tolak / Perlu Revisi')
        ->assertSee('Alasan Penolakan')
        ->assertSee('Kirim Penolakan')
        ->assertDontSee('Kembalikan untuk Diperbaiki')
        ->assertDontSee('Kembalikan Dokumen');
});
