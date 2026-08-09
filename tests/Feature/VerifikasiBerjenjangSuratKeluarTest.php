<?php

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Notifications\SuratKeluarDiputuskan;
use App\Notifications\SuratKeluarMenungguTindakan;
use Illuminate\Support\Facades\Notification;

function pejabat(string $role, ?string $unit = null): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratKeluarVerifikasi(string $status = 'draft', string $unitVerifikasi = 'teknik'): SuratKeluar
{
    return SuratKeluar::create([
        'nomor_surat'    => 'SK-' . uniqid(),
        'kategori_surat' => 'Undangan',
        'unit_verifikasi'     => $unitVerifikasi,
        'tanggal_surat'  => '2026-08-01',
        'tujuan'         => 'PT Contoh',
        'perihal'        => 'Undangan rapat',
        'status'         => $status,
    ]);
}

test('pengajuan menuju verifikasi direktur, bukan langsung ke dirut', function () {
    Notification::fake();

    $sekretaris = pejabat('sekretaris', 'pimpinan');
    $direkturTeknik = pejabat('direktur2', 'teknik');
    $surat = suratKeluarVerifikasi();

    $this->actingAs($sekretaris)
        ->put('/surat-keluar/' . $surat->id . '/submit')
        ->assertRedirect(route('surat-keluar.index'));

    expect($surat->fresh()->status)->toBe('menunggu_direktur');

    Notification::assertSentTo($direkturTeknik, SuratKeluarMenungguTindakan::class);
});

test('pengajuan ditolak bila direktur unit tersebut belum ada', function () {
    $sekretaris = pejabat('sekretaris', 'pimpinan');
    $surat = suratKeluarVerifikasi('draft', 'keuangan_administrasi');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/submit');

    expect($surat->fresh()->status)->toBe('draft');
});

test('direktur unit terkait dapat memverifikasi dan surat naik ke dirut', function () {
    Notification::fake();

    $direkturTeknik = pejabat('direktur2', 'teknik');
    $dirut = pejabat('dirut', 'pimpinan');
    pejabat('sekretaris', 'pimpinan');

    $surat = suratKeluarVerifikasi('menunggu_direktur');

    $this->actingAs($direkturTeknik)->put('/surat-keluar/' . $surat->id . '/verifikasi');

    $surat->refresh();

    expect($surat->status)->toBe('menunggu_dirut')
        ->and($surat->approved_direktur_by)->toBe($direkturTeknik->id)
        ->and($surat->approved_direktur_at)->not->toBeNull();

    Notification::assertSentTo($dirut, SuratKeluarMenungguTindakan::class);
});

test('direktur unit lain tidak dapat memverifikasi', function () {
    $direkturKeuangan = pejabat('direktur1', 'keuangan_administrasi');
    $surat = suratKeluarVerifikasi('menunggu_direktur', 'teknik');

    $this->actingAs($direkturKeuangan)
        ->put('/surat-keluar/' . $surat->id . '/verifikasi')
        ->assertForbidden();

    expect($surat->fresh()->status)->toBe('menunggu_direktur');
});

test('dirut tidak dapat menyetujui surat yang belum diverifikasi', function () {
    $dirut = pejabat('dirut', 'pimpinan');
    $surat = suratKeluarVerifikasi('menunggu_direktur');

    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    expect($surat->fresh()->status)->toBe('menunggu_direktur');
});

test('direktur dapat mengembalikan surat ke sekretaris dengan catatan', function () {
    Notification::fake();

    $direkturTeknik = pejabat('direktur2', 'teknik');
    $sekretaris = pejabat('sekretaris', 'pimpinan');
    $surat = suratKeluarVerifikasi('menunggu_direktur');

    $this->actingAs($direkturTeknik)->put('/surat-keluar/' . $surat->id . '/reject', [
        'catatan_revisi' => 'Nominal anggaran belum sesuai.',
    ]);

    $surat->refresh();

    expect($surat->status)->toBe('ditolak')
        ->and($surat->catatan_revisi)->toBe('Nominal anggaran belum sesuai.');

    Notification::assertSentTo($sekretaris, SuratKeluarDiputuskan::class);
});

test('verifikasi lama dikosongkan saat surat diajukan ulang', function () {
    $sekretaris = pejabat('sekretaris', 'pimpinan');
    $direkturTeknik = pejabat('direktur2', 'teknik');

    $surat = suratKeluarVerifikasi('ditolak');
    $surat->update([
        'approved_direktur_by' => $direkturTeknik->id,
        'approved_direktur_at' => now(),
    ]);

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/submit');

    $surat->refresh();

    expect($surat->status)->toBe('menunggu_direktur')
        ->and($surat->approved_direktur_by)->toBeNull()
        ->and($surat->approved_direktur_at)->toBeNull();
});

test('direktur tidak dapat memverifikasi surat yang sudah di meja dirut', function () {
    $direkturTeknik = pejabat('direktur2', 'teknik');
    $surat = suratKeluarVerifikasi('menunggu_dirut');

    $this->actingAs($direkturTeknik)->put('/surat-keluar/' . $surat->id . '/verifikasi');

    expect($surat->fresh()->status)->toBe('menunggu_dirut');
});

test('unit verifikasi wajib dipilih saat menyusun surat', function () {
    $sekretaris = pejabat('sekretaris', 'pimpinan');

    $this->actingAs($sekretaris)->post('/surat-keluar', [
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-05',
        'tujuan'         => 'PT Contoh',
        'perihal'        => 'Undangan rapat',
    ])->assertSessionHasErrors('unit_verifikasi');

    expect(SuratKeluar::count())->toBe(0);
});
