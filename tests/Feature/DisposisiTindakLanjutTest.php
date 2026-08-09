<?php

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('file tindak lanjut dapat diganti tanpa error', function () {
    Storage::fake('public');

    $penerima = User::factory()->create(['role' => 'sekretaris']);
    $pengirim = User::factory()->create(['role' => 'dirut']);

    $suratMasuk = SuratMasuk::create([
        'nomor_surat'   => 'SM-001/VIII/2026',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);

    // File lama sudah ada, sehingga jalur penghapusan file ikut dieksekusi
    Storage::disk('public')->put('disposisi_laporan/lama.pdf', 'isi lama');

    $disposisi = Disposisi::create([
        'surat_masuk_id'     => $suratMasuk->id,
        'dari_user_id'       => $pengirim->id,
        'kepada_user_id'     => $penerima->id,
        'instruksi'          => 'Mohon ditindaklanjuti',
        'status'             => 'diproses',
        'tanggal_disposisi'  => now(),
        'file_tindak_lanjut' => 'disposisi_laporan/lama.pdf',
    ]);

    $this->actingAs($penerima)
        ->put('/disposisi/' . $disposisi->id, [
            'status'                => 'selesai',
            'catatan_tindak_lanjut' => 'Sudah dikerjakan',
            'file_tindak_lanjut'    => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('disposisi.saya'));

    $disposisi->refresh();

    expect($disposisi->status)->toBe('selesai')
        ->and($disposisi->file_tindak_lanjut)->not->toBe('disposisi_laporan/lama.pdf');

    Storage::disk('public')->assertMissing('disposisi_laporan/lama.pdf');
    Storage::disk('public')->assertExists($disposisi->file_tindak_lanjut);
});
