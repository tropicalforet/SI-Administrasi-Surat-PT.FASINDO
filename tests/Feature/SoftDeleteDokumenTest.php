<?php

use App\Models\Disposisi;
use App\Models\Skpd;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\SuratTugas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function adminUji(): User
{
    return User::factory()->create(['role' => 'administrator']);
}

function suratMasukArsip(string $nomor = 'SM-001/VIII/2026', ?string $file = null): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => $nomor,
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
        'file'          => $file,
    ]);
}

function suratKeluarArsip(string $status, string $nomor): SuratKeluar
{
    return SuratKeluar::create([
        'nomor_surat'    => $nomor,
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'tujuan'         => 'PT Contoh',
        'perihal'        => 'Undangan rapat',
        'status'         => $status,
    ]);
}

test('surat masuk yang dihapus masuk arsip, bukan hilang', function () {
    $surat = suratMasukArsip();

    $this->actingAs(adminUji())
        ->delete('/surat-masuk/' . $surat->id)
        ->assertRedirect(route('surat-masuk.index'));

    expect(SuratMasuk::find($surat->id))->toBeNull()
        ->and(SuratMasuk::withTrashed()->find($surat->id))->not->toBeNull()
        ->and(SuratMasuk::withTrashed()->find($surat->id)->trashed())->toBeTrue();
});

test('berkas lampiran tetap tersimpan setelah dokumen dihapus', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->create('surat.pdf', 50)->store('surat_masuk', 'public');
    $surat = suratMasukArsip('SM-002/VIII/2026', $path);

    $this->actingAs(adminUji())->delete('/surat-masuk/' . $surat->id);

    Storage::disk('public')->assertExists($path);
});

test('hapus semua surat masuk tidak menghapus berkas maupun data permanen', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->create('surat.pdf', 50)->store('surat_masuk', 'public');
    suratMasukArsip('SM-003/VIII/2026', $path);

    $this->actingAs(adminUji())->delete('/surat-masuk-clear');

    Storage::disk('public')->assertExists($path);
    expect(SuratMasuk::count())->toBe(0)
        ->and(SuratMasuk::withTrashed()->count())->toBe(1);
});

test('surat keluar yang sudah ditandatangani tidak dapat dihapus', function () {
    $surat = suratKeluarArsip('terkirim', '001/A');

    $this->actingAs(adminUji())->delete('/surat-keluar/' . $surat->id);

    expect(SuratKeluar::find($surat->id))->not->toBeNull();
});

test('hapus semua surat keluar menyisakan surat yang sudah ditandatangani', function () {
    suratKeluarArsip('draft', '001/A');
    suratKeluarArsip('ditolak', '002/A');
    $terkirim = suratKeluarArsip('terkirim', '003/A');

    $this->actingAs(adminUji())->delete('/surat-keluar-clear');

    expect(SuratKeluar::count())->toBe(1)
        ->and(SuratKeluar::first()->id)->toBe($terkirim->id);
});

test('skpd yang sudah disetujui tidak dapat dihapus', function () {
    $sekretaris = User::factory()->create(['role' => 'sekretaris']);
    $staff = User::factory()->create(['role' => 'staff']);

    $suratTugas = SuratTugas::create([
        'nomor_surat_tugas' => 'ST-001/08/2026',
        'user_id'           => $staff->id,
        'perihal_tugas'     => 'Kunjungan kerja',
        'tujuan'            => 'Surabaya',
        'tanggal_mulai'     => '2026-09-01',
        'tanggal_selesai'   => '2026-09-03',
        'status'            => 'diterbitkan',
    ]);

    $skpd = Skpd::create([
        'user_id'           => $staff->id,
        'surat_tugas_id'    => $suratTugas->id,
        'nomor_skpd'        => 'SKPD-001/08/2026',
        'nama_pegawai'      => $staff->name,
        'tujuan_dinas'      => 'Surabaya',
        'keperluan'         => 'Kunjungan kerja',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'durasi_hari'       => 3,
        'status'            => 'disetujui',
    ]);

    $this->actingAs($sekretaris)->delete('/skpd/' . $skpd->id);

    expect(Skpd::find($skpd->id))->not->toBeNull();
});

test('hanya pemberi disposisi yang dapat menghapusnya', function () {
    $pemberi = User::factory()->create(['role' => 'dirut']);
    $penerima = User::factory()->create(['role' => 'direktur1']);
    $orangLain = User::factory()->create(['role' => 'direktur2']);

    $disposisi = Disposisi::create([
        'surat_masuk_id'    => suratMasukArsip('SM-004/VIII/2026')->id,
        'dari_user_id'      => $pemberi->id,
        'kepada_user_id'    => $penerima->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($orangLain)
        ->delete('/disposisi/' . $disposisi->id)
        ->assertForbidden();

    expect(Disposisi::find($disposisi->id))->not->toBeNull();

    $this->actingAs($pemberi)->delete('/disposisi/' . $disposisi->id);

    expect(Disposisi::find($disposisi->id))->toBeNull()
        ->and(Disposisi::withTrashed()->find($disposisi->id))->not->toBeNull();
});

test('admin dapat memulihkan dokumen dari arsip', function () {
    $surat = suratMasukArsip('SM-005/VIII/2026');
    $surat->delete();

    $this->actingAs(adminUji())
        ->post('/arsip-terhapus/surat-masuk/' . $surat->id . '/pulihkan')
        ->assertRedirect();

    expect(SuratMasuk::find($surat->id))->not->toBeNull();
});

test('penghapusan permanen hanya lewat arsip', function () {
    $surat = suratMasukArsip('SM-006/VIII/2026');
    $surat->delete();

    $this->actingAs(adminUji())
        ->delete('/arsip-terhapus/surat-masuk/' . $surat->id);

    expect(SuratMasuk::withTrashed()->find($surat->id))->toBeNull();
});

test('arsip terhapus hanya dapat diakses administrator', function () {
    foreach (['sekretaris', 'dirut', 'staff'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get('/arsip-terhapus')
            ->assertForbidden();
    }

    $this->actingAs(adminUji())->get('/arsip-terhapus')->assertOk();
});
