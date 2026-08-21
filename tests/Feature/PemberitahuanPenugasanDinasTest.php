<?php

/**
 * Pegawai baru melihat penugasan setelah Direktur Utama menandatanganinya.
 * Karena itu ia harus dikabari tepat pada saat itu - kalau tidak, perintah
 * dinas hanya menunggu di daftar sampai kebetulan ia membukanya.
 *
 * Bunyinya pun berbeda dari usulan. Pada usulan, kabarnya adalah hasil atas
 * sesuatu yang ia ajukan sendiri. Pada penugasan, ia tidak pernah mengajukan
 * apa pun, sehingga yang perlu disebut adalah siapa yang menugaskan, ke mana,
 * dan kapan.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;
use App\Notifications\PenugasanDinasDiterima;
use App\Notifications\SkpdDiputuskan;
use Illuminate\Support\Facades\Notification;

function pihakPemberitahuan(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function skpdMenungguDirut(User $pegawai, ?User $penugas = null): Skpd
{
    return Skpd::create([
        'user_id'           => $pegawai->id,
        'ditugaskan_oleh'   => $penugas?->id,
        'asal_usul'         => $penugas ? 'penugasan' : 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Bontang',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-09-03',
        'tanggal_kembali'   => '2026-09-05',
        'durasi_hari'       => 3,
        'status'            => 'menunggu_dirut',
    ]);
}

test('pegawai dikabari saat penugasannya ditandatangani', function () {
    Notification::fake();

    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $skpd = skpdMenungguDirut($pegawai, $dirut);

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    Notification::assertSentTo($pegawai, PenugasanDinasDiterima::class);
});

test('isi pemberitahuan menyebut pemberi tugas, tujuan, dan tanggalnya', function () {
    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $skpd = skpdMenungguDirut($pegawai, $dirut);
    $skpd->update(['nomor_skpd' => '012/FI/SKPD/IX/2026', 'status' => 'disetujui']);

    $isi = (new PenugasanDinasDiterima($skpd->fresh()))->toArray($pegawai);

    expect($isi['judul'])->toBe('Anda mendapat penugasan dinas')
        ->and($isi['pesan'])->toContain($dirut->name)
        ->and($isi['pesan'])->toContain('Bontang')
        ->and($isi['pesan'])->toContain('Pemeriksaan lapangan')
        ->and($isi['pesan'])->toContain('012/FI/SKPD/IX/2026')
        // Rentang sebulan cukup menyebut bulannya sekali.
        ->and($isi['pesan'])->toContain('03 s/d 05 September 2026');
});

test('penugasan sehari tidak ditulis sebagai rentang', function () {
    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $skpd = skpdMenungguDirut($pegawai, $dirut);
    $skpd->update(['tanggal_kembali' => '2026-09-03']);

    $isi = (new PenugasanDinasDiterima($skpd->fresh()))->toArray($pegawai);

    expect($isi['pesan'])->toContain('03 September 2026')
        ->and($isi['pesan'])->not->toContain('s/d');
});

test('tautan pemberitahuan menuju dokumen yang benar-benar dapat dibuka', function () {
    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $skpd = skpdMenungguDirut($pegawai, $dirut);

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    $kabar = $pegawai->notifications()->reorder()->first();

    expect($kabar)->not->toBeNull();

    // Pemberitahuan tidak boleh menjanjikan halaman yang berujung 403.
    $this->actingAs($pegawai)
        ->get($kabar->data['url'])
        ->assertOk();
});

test('usulan sendiri tetap dikabari sebagai keputusan, bukan penugasan', function () {
    Notification::fake();

    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $usulan = skpdMenungguDirut($pegawai);

    $this->actingAs($dirut)->put('/skpd/' . $usulan->id . '/approve');

    Notification::assertSentTo($pegawai, SkpdDiputuskan::class);
    Notification::assertNotSentTo($pegawai, PenugasanDinasDiterima::class);
});

test('penolakan penugasan dikabarkan ke atasan penyusunnya', function () {
    Notification::fake();

    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $direktur = pihakPemberitahuan('direktur2', 'teknik');
    $pegawai = pihakPemberitahuan('staff', 'teknik');

    $skpd = skpdMenungguDirut($pegawai, $direktur);

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/reject', [
        'catatan_revisi' => 'Tanggal keberangkatan perlu dimundurkan.',
    ]);

    // Yang harus memperbaiki adalah penyusunnya.
    Notification::assertSentTo($direktur, SkpdDiputuskan::class);

    // Pegawai belum tahu perintah ini ada, dan dokumennya pun belum terbuka
    // baginya - mengabarinya hanya menunjuk halaman yang menolak.
    Notification::assertNotSentTo($pegawai, SkpdDiputuskan::class);
});

test('penolakan usulan tetap dikabarkan ke pengusulnya', function () {
    Notification::fake();

    $dirut = pihakPemberitahuan('dirut', 'pimpinan');
    $pegawai = pihakPemberitahuan('staff');

    $usulan = skpdMenungguDirut($pegawai);

    $this->actingAs($dirut)->put('/skpd/' . $usulan->id . '/reject', [
        'catatan_revisi' => 'Keperluannya belum jelas.',
    ]);

    Notification::assertSentTo($pegawai, SkpdDiputuskan::class);
});
