<?php

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Notifications\SuratKeluarMenungguTindakan;
use Illuminate\Support\Facades\Notification;

function pelaku(string $role, ?string $unit = null): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function konsepSurat(array $tambahan = []): array
{
    return array_merge([
        'kategori_surat' => 'SU',
        'tanggal_surat'  => '2026-08-10',
        'tujuan'         => 'PT Mitra Sejahtera',
        'perihal'        => 'Undangan rapat koordinasi',
        'aksi'           => 'ajukan',
    ], $tambahan);
}

test('langkah 1: staff dapat menyusun konsep surat keluar', function () {
    $staff = pelaku('staff', 'teknik');

    $this->actingAs($staff)
        ->post('/surat-keluar', konsepSurat(['aksi' => 'draft']))
        ->assertRedirect();

    $surat = SuratKeluar::first();

    expect($surat)->not->toBeNull()
        ->and($surat->dibuat_oleh)->toBe($staff->id)
        ->and($surat->status)->toBe('draft')
        ->and($surat->nomor_surat)->toBeNull()
        ->and($surat->label_nomor)->toBe('(Belum bernomor)');
});

test('penyusun langsung dapat membuka dan mengajukan draf yang baru disimpannya', function () {
    $staff = pelaku('staff', 'teknik');

    $this->actingAs($staff)
        ->post('/surat-keluar', konsepSurat(['aksi' => 'draft']))
        ->assertRedirect(route('surat-keluar.show', SuratKeluar::first()->id));

    $surat = SuratKeluar::first();

    $this->actingAs($staff)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('Ajukan Konsep');

    $this->actingAs($staff)->put('/surat-keluar/' . $surat->id . '/submit');

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');
});

test('pengajuan langsung ke sekretaris, tanpa singgah ke direktur', function () {
    Notification::fake();

    $staff = pelaku('staff', 'teknik');
    $direktur = pelaku('direktur2', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());

    $surat = SuratKeluar::first();

    expect($surat->status)->toBe('menunggu_sekretaris');

    // Pengajuan adalah giliran kerja, bukan keputusan atas surat.
    Notification::assertSentTo($sekretaris, SuratKeluarMenungguTindakan::class);
    Notification::assertNothingSentTo($direktur);
});

test('pengajuan tetap berjalan meski unit penyusun belum punya direktur', function () {
    // Dulu pengajuan ditolak bila direktur unitnya belum ada.
    $staff = pelaku('staff', 'keuangan_administrasi');
    pelaku('sekretaris', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());

    expect(SuratKeluar::first()->status)->toBe('menunggu_sekretaris');
});

test('langkah 2: sekretaris menerbitkan nomor dan meneruskan ke dirut', function () {
    $staff = pelaku('staff', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');
    pelaku('dirut', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    $surat->refresh();

    expect($surat->status)->toBe('menunggu_dirut')
        ->and($surat->nomor_surat)->not->toBeNull()
        // Nama tujuan tidak lagi diselipkan ke dalam nomor.
        ->and($surat->nomor_surat)->toMatch('#^\d{3}/FI/SU/[IVX]+/\d{4}$#');
});

test('nomor urut hanya dipakai surat yang benar-benar lanjut', function () {
    $staff = pelaku('staff', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');

    // Konsep pertama dibatalkan sebelum sampai sekretaris
    $this->actingAs($staff)->post('/surat-keluar', konsepSurat(['aksi' => 'draft']));
    SuratKeluar::first()->delete();

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();
    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    expect($surat->fresh()->nomor_surat)->toStartWith('001/');
});

test('sekretaris dapat mengembalikan surat bila formatnya belum sesuai', function () {
    $staff = pelaku('staff', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Kop surat belum sesuai standar perusahaan.',
    ]);

    $surat->refresh();

    expect($surat->status)->toBe('ditolak')
        ->and($surat->catatan_revisi)->toBe('Kop surat belum sesuai standar perusahaan.')
        ->and($surat->nomor_surat)->toBeNull();
});

test('surat yang dikembalikan dapat diperbaiki lalu diajukan ulang penyusunnya', function () {
    $staff = pelaku('staff', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Perbaiki nama instansi tujuan.',
    ]);

    $this->actingAs($staff)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('Ajukan Ulang')
        ->assertSee('Perbaiki nama instansi tujuan.');

    $this->actingAs($staff)->put('/surat-keluar/' . $surat->id . '/submit');

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');
});

test('langkah 3: dirut menandatangani setelah sekretaris', function () {
    $staff = pelaku('staff', 'teknik');
    $sekretaris = pelaku('sekretaris', 'pimpinan');
    $dirut = pelaku('dirut', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');
    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    expect($surat->fresh()->status)->toBe('terkirim');
});

test('dirut tidak dapat menandatangani sebelum melewati sekretaris', function () {
    $staff = pelaku('staff', 'teknik');
    $dirut = pelaku('dirut', 'pimpinan');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');
});

test('direktur tidak lagi punya tahap persetujuan surat keluar', function () {
    $staff = pelaku('staff', 'teknik');
    $direktur = pelaku('direktur2', 'teknik');

    $this->actingAs($staff)->post('/surat-keluar', konsepSurat());
    $surat = SuratKeluar::first();

    // Rutenya sudah dilepas sepenuhnya
    expect(Illuminate\Support\Facades\Route::has('surat-keluar.verifikasi'))->toBeFalse();

    // Dan direktur tidak dapat mengembalikan surat yang bukan tahapnya
    $this->actingAs($direktur)
        ->put('/surat-keluar/' . $surat->id . '/reject', ['catatan_revisi' => 'coba tolak'])
        ->assertForbidden();

    expect($surat->fresh()->status)->toBe('menunggu_sekretaris');
});

test('penyusun hanya melihat konsep buatannya sendiri', function () {
    $staffA = pelaku('staff', 'teknik');
    $staffB = pelaku('staff', 'teknik');

    $this->actingAs($staffA)->post('/surat-keluar', konsepSurat([
        'perihal' => 'Konsep milik A',
        'aksi'    => 'draft',
    ]));

    $this->actingAs($staffB)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertDontSee('Konsep milik A');
});

test('penyusun lain tidak dapat membuka maupun mengubah konsep orang', function () {
    $staffA = pelaku('staff', 'teknik');
    $staffB = pelaku('staff', 'teknik');

    $this->actingAs($staffA)->post('/surat-keluar', konsepSurat(['aksi' => 'draft']));
    $surat = SuratKeluar::first();

    $this->actingAs($staffB)->get('/surat-keluar/' . $surat->id)->assertForbidden();
    $this->actingAs($staffB)->get('/surat-keluar/' . $surat->id . '/edit')->assertForbidden();
});
