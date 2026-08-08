<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function suratMasukTeruskan(string $nomor): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => $nomor,
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);
}

function direkturBerizin(): User
{
    $user = User::factory()->create(['role' => 'direktur1']);

    $permission = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);

    return $user;
}

test('pengguna tidak dapat meneruskan disposisi milik orang lain', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $pemilik = direkturBerizin();
    $penyusup = direkturBerizin();
    $staff = User::factory()->create(['role' => 'staff']);

    $disposisi = Disposisi::create([
        'surat_masuk_id'    => suratMasukTeruskan('SM-001/VIII/2026')->id,
        'dari_user_id'      => $dirut->id,
        'kepada_user_id'    => $pemilik->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($penyusup)->post('/disposisi/continue', [
        'parent_disposisi_id' => $disposisi->id,
        'surat_masuk_id'      => $disposisi->surat_masuk_id,
        'kepada_user_id'      => [$staff->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertForbidden();

    expect(Disposisi::where('parent_disposisi_id', $disposisi->id)->count())->toBe(0);
});

test('surat masuk pada disposisi lanjutan diambil dari induk, bukan dari input', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $pemilik = direkturBerizin();
    $staff = User::factory()->create(['role' => 'staff']);

    $suratAsli = suratMasukTeruskan('SM-001/VIII/2026');
    $suratLain = suratMasukTeruskan('SM-999/VIII/2026');

    $disposisi = Disposisi::create([
        'surat_masuk_id'    => $suratAsli->id,
        'dari_user_id'      => $dirut->id,
        'kepada_user_id'    => $pemilik->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    // Input surat_masuk_id sengaja dipalsukan ke surat lain
    $this->actingAs($pemilik)->post('/disposisi/continue', [
        'parent_disposisi_id' => $disposisi->id,
        'surat_masuk_id'      => $suratLain->id,
        'kepada_user_id'      => [$staff->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertRedirect(route('disposisi.saya'));

    $anak = Disposisi::where('parent_disposisi_id', $disposisi->id)->first();

    expect($anak->surat_masuk_id)->toBe($suratAsli->id);
});

test('penerima disposisi tetap dapat meneruskannya', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $pemilik = direkturBerizin();
    $staff = User::factory()->create(['role' => 'staff']);

    $disposisi = Disposisi::create([
        'surat_masuk_id'    => suratMasukTeruskan('SM-001/VIII/2026')->id,
        'dari_user_id'      => $dirut->id,
        'kepada_user_id'    => $pemilik->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($pemilik)->post('/disposisi/continue', [
        'parent_disposisi_id' => $disposisi->id,
        'kepada_user_id'      => [$staff->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertRedirect(route('disposisi.saya'));

    expect(Disposisi::where('parent_disposisi_id', $disposisi->id)->count())->toBe(1);
});
