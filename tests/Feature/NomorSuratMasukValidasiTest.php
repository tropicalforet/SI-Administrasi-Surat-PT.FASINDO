<?php

use App\Models\SuratMasuk;
use App\Models\User;

function sekretarisUji(): User
{
    return User::factory()->create(['role' => 'sekretaris']);
}

function suratMasukTersimpan(string $nomor): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => $nomor,
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);
}

function dataSuratBaru(string $nomor, User $penerima): array
{
    return [
        'nomor_surat'    => $nomor,
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-05',
        'pengirim'       => 'Dinas Lain',
        'sifat'          => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'penerima_tipe'  => 'user',
        'penerima_id'    => $penerima->id,
        'perihal'        => 'Undangan rapat',
    ];
}

test('bentrok dengan surat aktif memberi pesan biasa', function () {
    $sekretaris = sekretarisUji();
    suratMasukTersimpan('123/ABC/2026');

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratBaru('123/ABC/2026', $sekretaris))
        ->assertSessionHasErrors(['nomor_surat' => 'Nomor surat sudah digunakan.']);
});

test('bentrok dengan surat di arsip terhapus menjelaskan penyebabnya', function () {
    $sekretaris = sekretarisUji();
    suratMasukTersimpan('123/ABC/2026')->delete();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratBaru('123/ABC/2026', $sekretaris))
        ->assertSessionHasErrors([
            'nomor_surat' => 'Nomor surat ini dipakai dokumen di arsip terhapus. Pulihkan dokumen tersebut atau gunakan nomor lain.',
        ]);
});

test('nomor yang belum dipakai tetap dapat disimpan', function () {
    $sekretaris = sekretarisUji();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratBaru('999/XYZ/2026', $sekretaris))
        ->assertRedirect(route('surat-masuk.index'));

    expect(SuratMasuk::where('nomor_surat', '999/XYZ/2026')->exists())->toBeTrue();
});

test('surat dapat diedit tanpa dianggap bentrok dengan dirinya sendiri', function () {
    $sekretaris = sekretarisUji();
    $surat = suratMasukTersimpan('123/ABC/2026');

    $this->actingAs($sekretaris)
        ->put('/surat-masuk/' . $surat->id, [
            'nomor_surat'    => '123/ABC/2026',
            'kategori_surat' => 'Undangan',
            'tanggal_surat'  => '2026-08-05',
            'pengirim'       => 'Dinas Contoh',
            'sifat'          => 'biasa',
            'jalur_penerimaan' => 'kurir',
            'penerima_tipe'  => 'user',
            'penerima_id'    => $sekretaris->id,
            'perihal'        => 'Perihal diperbarui',
        ])
        ->assertRedirect(route('surat-masuk.index'));

    expect($surat->fresh()->perihal)->toBe('Perihal diperbarui');
});

test('pesan arsip juga muncul saat mengedit surat lain', function () {
    $sekretaris = sekretarisUji();
    suratMasukTersimpan('123/ABC/2026')->delete();
    $surat = suratMasukTersimpan('456/DEF/2026');

    $this->actingAs($sekretaris)
        ->put('/surat-masuk/' . $surat->id, [
            'nomor_surat'    => '123/ABC/2026',
            'kategori_surat' => 'Undangan',
            'tanggal_surat'  => '2026-08-05',
            'pengirim'       => 'Dinas Contoh',
            'sifat'          => 'biasa',
            'jalur_penerimaan' => 'kurir',
            'penerima_tipe'  => 'user',
            'penerima_id'    => $sekretaris->id,
            'perihal'        => 'Coba pakai nomor arsip',
        ])
        ->assertSessionHasErrors([
            'nomor_surat' => 'Nomor surat ini dipakai dokumen di arsip terhapus. Pulihkan dokumen tersebut atau gunakan nomor lain.',
        ]);
});
