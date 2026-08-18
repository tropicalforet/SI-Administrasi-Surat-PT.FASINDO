<?php

/**
 * Penugasan adalah dokumen atasan yang menyusunnya, bukan dokumen pegawainya.
 *
 * Kolom user_id menunjuk pegawai yang ditugaskan, bukan yang mengajukan. Selama
 * kolom itu dibaca sebagai penanda pemilik, seorang bawahan memperoleh tombol
 * Hapus atas perintah yang diberikan atasannya, dan penugasan yang belum
 * ditandatangani sudah tampil di layarnya seolah pengajuan miliknya sendiri.
 *
 * Aturannya: pegawai baru melihat penugasan setelah dokumennya resmi terbit,
 * dan sesudah itu pun hanya melihat.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pihakKepemilikan(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function penugasanBerstatus(User $atasan, User $pegawai, string $status): Skpd
{
    return Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => $atasan->id,
        'asal_usul'         => 'penugasan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Bontang',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'durasi_hari'       => 1,
        'status'            => $status,
        'nomor_skpd'        => $status === 'disetujui' ? '001/FI/SKPD/IX/2026' : null,
    ]);
}

// ============================================================
// Keterlihatan
// ============================================================

test('penugasan belum tampil di layar pegawai sebelum disetujui', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('direktur2', 'teknik');

    foreach (['draft', 'menunggu_dirut', 'ditolak'] as $status) {
        $skpd = penugasanBerstatus($dirut, $pegawai, $status);

        expect($skpd->dapatDilihatOleh($pegawai))->toBeFalse();

        $this->actingAs($pegawai)
            ->get('/skpd/' . $skpd->id)
            ->assertForbidden();

        $skpd->delete();
    }
});

test('penugasan tampil di layar pegawai setelah dokumennya terbit', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('direktur2', 'teknik');

    $skpd = penugasanBerstatus($dirut, $pegawai, 'disetujui');

    expect($skpd->dapatDilihatOleh($pegawai))->toBeTrue();

    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id)
        ->assertOk();

    $this->actingAs($pegawai)
        ->get('/skpd')
        ->assertOk()
        ->assertSee('Bontang');
});

test('atasan yang menyusun melihat penugasannya pada tahap apa pun', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    foreach (['draft', 'menunggu_dirut', 'ditolak', 'disetujui'] as $status) {
        $skpd = penugasanBerstatus($dirut, $pegawai, $status);

        expect($skpd->dapatDilihatOleh($dirut))->toBeTrue();

        $skpd->delete();
    }
});

test('usulan pegawai tetap terlihat pengusulnya sejak awal', function () {
    $pegawai = pihakKepemilikan('staff');

    $usulan = Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => null,
        'asal_usul'         => 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Bontang',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'status'            => 'menunggu_dirut',
    ]);

    expect($usulan->dapatDilihatOleh($pegawai))->toBeTrue();
});

test('daftar dan halaman detail sepakat', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('direktur2', 'teknik');

    penugasanBerstatus($dirut, $pegawai, 'menunggu_dirut');

    // Yang tidak muncul di daftar juga harus ditolak saat dibuka, dan sebaliknya.
    $this->actingAs($pegawai)
        ->get('/skpd')
        ->assertOk()
        ->assertDontSee('Bontang');

    expect(Skpd::terlihatOleh($pegawai)->count())->toBe(0);
});

// ============================================================
// Kewenangan
// ============================================================

test('pegawai tidak dapat menghapus penugasan dari atasannya', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    $skpd = penugasanBerstatus($dirut, $pegawai, 'menunggu_dirut');

    $this->actingAs($pegawai)
        ->delete('/skpd/' . $skpd->id)
        ->assertForbidden();

    expect(Skpd::find($skpd->id))->not->toBeNull();
});

test('pegawai tidak dapat mengubah penugasan yang dikembalikan atasannya', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    $skpd = penugasanBerstatus($dirut, $pegawai, 'ditolak');

    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id . '/edit')
        ->assertForbidden();
});

test('atasan yang menyusun dapat membatalkan penugasannya', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    $skpd = penugasanBerstatus($dirut, $pegawai, 'menunggu_dirut');

    $this->actingAs($dirut)->delete('/skpd/' . $skpd->id);

    expect(Skpd::find($skpd->id))->toBeNull();
});

test('pengusul tetap dapat membatalkan usulannya sendiri', function () {
    $pegawai = pihakKepemilikan('staff');

    $usulan = Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => null,
        'asal_usul'         => 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Bontang',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'status'            => 'menunggu_dirut',
    ]);

    $this->actingAs($pegawai)->delete('/skpd/' . $usulan->id);

    expect(Skpd::find($usulan->id))->toBeNull();
});

// ============================================================
// Tampilan
// ============================================================

test('daftar menyebut siapa atasan yang menugaskan', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    penugasanBerstatus($dirut, $pegawai, 'menunggu_dirut');

    // Tanpa nama pemberi tugas, penugasan terbaca seolah diajukan sendiri
    // oleh pegawai yang namanya tercantum.
    $this->actingAs($dirut)
        ->get('/skpd')
        ->assertOk()
        ->assertSee('Penugasan Atasan')
        ->assertSee('oleh ' . $dirut->name);
});

/*
 * Alamat hapus dan alamat detail sama-sama /skpd/{id}, hanya berbeda metode,
 * sehingga tombolnya dikenali dari form DELETE-nya, bukan dari URL.
 */
function adaFormHapus(string $isi): bool
{
    return str_contains($isi, 'name="_method" value="DELETE"');
}

test('tombol hapus dan edit tidak tampil bagi pegawai yang ditugaskan', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    $skpd = penugasanBerstatus($dirut, $pegawai, 'disetujui');

    // Dokumennya sudah terbit sehingga terlihat, tetapi tetap bukan miliknya.
    $isi = $this->actingAs($pegawai)->get('/skpd')->assertOk()->getContent();

    expect($isi)->toContain('Bontang')
        ->and(adaFormHapus($isi))->toBeFalse()
        ->and($isi)->not->toContain(route('skpd.edit', $skpd->id));
});

test('tombol hapus tetap tampil bagi atasan yang menyusunnya', function () {
    $dirut = pihakKepemilikan('dirut', 'pimpinan');
    $pegawai = pihakKepemilikan('staff');

    penugasanBerstatus($dirut, $pegawai, 'menunggu_dirut');

    $isi = $this->actingAs($dirut)->get('/skpd')->assertOk()->getContent();

    expect(adaFormHapus($isi))->toBeTrue();
});
