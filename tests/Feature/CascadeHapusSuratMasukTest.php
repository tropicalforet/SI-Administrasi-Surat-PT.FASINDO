<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function suratCascade(): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'      => 'SM-' . uniqid(),
        'kategori_surat'   => 'SU',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Permohonan data',
        'status'           => 'baru',
    ]);
}

function disposisiCascade(SuratMasuk $surat, User $kepada): Disposisi
{
    return Disposisi::create([
        'surat_masuk_id'    => $surat->id,
        'dari_user_id'      => User::factory()->create(['role' => 'dirut', 'unit' => 'pimpinan'])->id,
        'kepada_user_id'    => $kepada->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);
}

function stafBerizin(): User
{
    $user = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

test('menghapus surat masuk ikut menyembunyikan disposisinya', function () {
    $staff = stafBerizin();
    $surat = suratCascade();
    $disposisi = disposisiCascade($surat, $staff);

    $surat->delete();

    expect(Disposisi::find($disposisi->id))->toBeNull()
        ->and(Disposisi::withTrashed()->find($disposisi->id)->trashed())->toBeTrue();
});

test('halaman disposisi saya tetap terbuka setelah suratnya dihapus', function () {
    $staff = stafBerizin();
    $suratHidup = suratCascade();
    $suratDihapus = suratCascade();

    disposisiCascade($suratHidup, $staff);
    disposisiCascade($suratDihapus, $staff);

    $suratDihapus->delete();

    // Sebelum perbaikan, disposisi yatim membuat halaman ini gagal dimuat
    // karena suratMasuk-nya null.
    $this->actingAs($staff)
        ->get('/disposisi-saya')
        ->assertOk()
        ->assertSee($suratHidup->nomor_surat)
        ->assertDontSee($suratDihapus->nomor_surat);
});

test('memulihkan surat mengembalikan disposisi yang terhapus bersamanya', function () {
    $staff = stafBerizin();
    $surat = suratCascade();
    $disposisi = disposisiCascade($surat, $staff);

    $surat->delete();
    $surat->restore();

    expect(Disposisi::find($disposisi->id))->not->toBeNull();
});

test('disposisi yang dihapus tersendiri tidak ikut dipulihkan', function () {
    $staff = stafBerizin();
    $surat = suratCascade();

    $dihapusDuluan = disposisiCascade($surat, $staff);
    $ikutTerhapus = disposisiCascade($surat, $staff);

    // Dihapus lebih dulu dan tersendiri, jadi bukan bagian dari cascade
    $dihapusDuluan->delete();

    $surat->delete();
    $surat->restore();

    expect(Disposisi::find($ikutTerhapus->id))->not->toBeNull()
        ->and(Disposisi::find($dihapusDuluan->id))->toBeNull();
});

test('hapus permanen surat membuang disposisinya sekaligus', function () {
    $staff = stafBerizin();
    $surat = suratCascade();
    $disposisi = disposisiCascade($surat, $staff);

    $surat->delete();
    $surat->forceDelete();

    expect(Disposisi::withTrashed()->find($disposisi->id))->toBeNull();
});
