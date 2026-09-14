<?php

/**
 * Penutupan disposisi adalah keputusan pihak yang memberi perintah.
 *
 * Sebelumnya penerima disposisi dapat menandai pekerjaannya sendiri selesai,
 * sehingga status "Selesai" hanya berarti "penerima merasa sudah selesai" -
 * tanpa seorang pun memeriksanya. Sekarang penerima menyatakan rampung,
 * pemberi disposisi memverifikasi, barulah disposisinya ditutup.
 *
 * Ini menindaklanjuti catatan revisi sidang: "Status selesai dilakukan oleh
 * siapa?", sekaligus menyembunyikan tombol Tindak Lanjut ketika disposisinya
 * sudah tidak lagi dapat dikerjakan.
 */

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\DisposisiHasilVerifikasi;
use App\Notifications\DisposisiMenungguVerifikasi;
use Illuminate\Support\Facades\Notification;

function pihakVerifikasi(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratUjiVerifikasi(): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => 'SM-' . uniqid() . '/VIII/2026',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data lahan',
        'status'        => 'baru',
    ]);
}

function disposisiVerifikasi(User $pemberi, User $penerima, string $status = 'diproses'): Disposisi
{
    return Disposisi::create([
        'surat_masuk_id'    => suratUjiVerifikasi()->id,
        'dari_user_id'      => $pemberi->id,
        'kepada_user_id'    => $penerima->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => $status,
        'tanggal_disposisi' => now(),
    ]);
}

// ============================================================
// Penerima menyatakan rampung
// ============================================================

test('penerima tidak dapat menutup sendiri disposisinya', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai);

    $this->actingAs($pegawai)
        ->put('/disposisi/' . $disposisi->id, [
            'status'                => 'selesai',
            'catatan_tindak_lanjut' => 'Sudah saya kerjakan',
        ])
        ->assertSessionHasErrors('status');

    expect($disposisi->fresh()->status)->toBe('diproses');
});

test('penerima menyatakan rampung dan pemberi dikabari', function () {
    Notification::fake();

    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai);

    $this->actingAs($pegawai)
        ->put('/disposisi/' . $disposisi->id, [
            'status'                => 'menunggu_verifikasi',
            'catatan_tindak_lanjut' => 'Data sudah dikirim',
        ])
        ->assertRedirect(route('disposisi.saya'));

    expect($disposisi->fresh()->status)->toBe('menunggu_verifikasi');

    Notification::assertSentTo($dirut, DisposisiMenungguVerifikasi::class);
});

test('penerima tidak dapat mengubah pekerjaannya selama diperiksa', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($pegawai)
        ->put('/disposisi/' . $disposisi->id, [
            'status'                => 'diproses',
            'catatan_tindak_lanjut' => 'Diubah diam-diam',
        ])
        ->assertForbidden();
});

// ============================================================
// Pemberi memverifikasi
// ============================================================

test('pemberi disposisi menutup disposisi setelah memverifikasi', function () {
    Notification::fake();

    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($dirut)->put('/disposisi/' . $disposisi->id . '/verifikasi');

    $disposisi->refresh();

    expect($disposisi->status)->toBe('selesai')
        ->and($disposisi->diverifikasi_pada)->not->toBeNull();

    Notification::assertSentTo($pegawai, DisposisiHasilVerifikasi::class);
});

test('pemberi dapat mengembalikan pekerjaan beserta catatannya', function () {
    Notification::fake();

    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($dirut)->put('/disposisi/' . $disposisi->id . '/kembalikan', [
        'catatan_verifikasi' => 'Lampiran bukti belum disertakan.',
    ]);

    $disposisi->refresh();

    // Kembali terbuka bagi penerimanya, lengkap dengan alasannya.
    expect($disposisi->status)->toBe('diproses')
        ->and($disposisi->catatan_verifikasi)->toBe('Lampiran bukti belum disertakan.')
        ->and($disposisi->bolehDitindaklanjutiOleh($pegawai))->toBeTrue();

    Notification::assertSentTo($pegawai, DisposisiHasilVerifikasi::class);
});

test('pengembalian wajib menyertakan catatan', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($dirut)
        ->put('/disposisi/' . $disposisi->id . '/kembalikan', ['catatan_verifikasi' => ''])
        ->assertSessionHasErrors('catatan_verifikasi');

    expect($disposisi->fresh()->status)->toBe('menunggu_verifikasi');
});

test('catatan pengembalian dibersihkan setelah pekerjaannya diperbaiki', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($dirut)->put('/disposisi/' . $disposisi->id . '/kembalikan', [
        'catatan_verifikasi' => 'Belum lengkap.',
    ]);

    $this->actingAs($pegawai)->put('/disposisi/' . $disposisi->id, [
        'status'                => 'menunggu_verifikasi',
        'catatan_tindak_lanjut' => 'Sudah dilengkapi',
    ]);

    // Catatan lama tidak boleh tertinggal, agar tidak terbaca seolah masih berlaku.
    expect($disposisi->fresh()->catatan_verifikasi)->toBeNull();
});

// ============================================================
// Kewenangan
// ============================================================

test('orang lain tidak dapat memverifikasi disposisi', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');
    $orangLain = pihakVerifikasi('sekretaris', 'pimpinan');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $this->actingAs($orangLain)
        ->put('/disposisi/' . $disposisi->id . '/verifikasi')
        ->assertForbidden();

    // Penerimanya sendiri pun tidak boleh, sebab itu sama saja menutup sendiri.
    $this->actingAs($pegawai)
        ->put('/disposisi/' . $disposisi->id . '/verifikasi')
        ->assertForbidden();

    expect($disposisi->fresh()->status)->toBe('menunggu_verifikasi');
});

test('disposisi yang belum dinyatakan rampung tidak dapat diverifikasi', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'diproses');

    $this->actingAs($dirut)
        ->put('/disposisi/' . $disposisi->id . '/verifikasi')
        ->assertForbidden();
});

test('disposisi tidak dapat ditutup selama lanjutannya berjalan', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $direktur = pihakVerifikasi('direktur2', 'teknik');
    $bawahan = pihakVerifikasi('staff', 'teknik');

    $induk = disposisiVerifikasi($dirut, $direktur, 'menunggu_verifikasi');

    Disposisi::create([
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'dari_user_id'        => $direktur->id,
        'kepada_user_id'      => $bawahan->id,
        'parent_disposisi_id' => $induk->id,
        'instruksi'           => 'Lanjutkan',
        'status'              => 'diproses',
        'tanggal_disposisi'   => now(),
    ]);

    $this->actingAs($dirut)->put('/disposisi/' . $induk->id . '/verifikasi');

    expect($induk->fresh()->status)->toBe('menunggu_verifikasi');
});

// ============================================================
// Tampilan
// ============================================================

test('tombol tindak lanjut hilang setelah disposisi selesai', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'selesai');

    $isi = $this->actingAs($pegawai)->get('/disposisi-saya')->assertOk()->getContent();

    expect($isi)->not->toContain('Tindak Lanjut')
        ->and($disposisi->bolehDitindaklanjutiOleh($pegawai))->toBeFalse();
});

test('tombol tindak lanjut hilang selama menunggu verifikasi', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    disposisiVerifikasi($dirut, $pegawai, 'menunggu_verifikasi');

    $isi = $this->actingAs($pegawai)->get('/disposisi-saya')->assertOk()->getContent();

    expect($isi)->not->toContain('Tindak Lanjut')
        ->and($isi)->toContain('Menunggu Verifikasi');
});

test('tombol tindak lanjut tetap ada selama disposisi berjalan', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    disposisiVerifikasi($dirut, $pegawai, 'diproses');

    $this->actingAs($pegawai)
        ->get('/disposisi-saya')
        ->assertOk()
        ->assertSee('Tindak Lanjut');
});

test('halaman tindak lanjut menutup formulir saat sudah selesai', function () {
    $dirut = pihakVerifikasi('dirut', 'pimpinan');
    $pegawai = pihakVerifikasi('staff');

    $disposisi = disposisiVerifikasi($dirut, $pegawai, 'selesai');

    $this->actingAs($pegawai)
        ->get('/disposisi/' . $disposisi->id . '/edit')
        ->assertOk()
        ->assertSee('Disposisi ini sudah selesai')
        ->assertDontSee('Simpan Tindak Lanjut');
});
