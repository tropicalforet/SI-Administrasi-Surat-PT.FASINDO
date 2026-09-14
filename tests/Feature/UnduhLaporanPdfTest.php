<?php

/**
 * Keempat laporan harus dapat diunduh sebagai PDF.
 *
 * Di peladen produksi, dompdf melempar "Cannot resolve public path" karena
 * mencari folder publik lewat base_path('public') - folder yang tidak ada pada
 * susunan hosting yang memisahkan inti aplikasi dari document root. Pengaturan
 * dompdf.public_path menutup persoalan itu, dan chroot disesuaikan agar logo
 * pada laporan tetap dapat dibaca.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;

function pihakUnduh(string $role, array $izin, ?string $unit = 'pimpinan'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $p = Permission::firstOrCreate(['name' => $nama], ['label' => $nama, 'group' => 'uji']);
        $user->permissions()->syncWithoutDetaching([$p->id]);
    }

    return $user;
}

function isiLaporan(User $penyusun): void
{
    SuratMasuk::create([
        'nomor_surat'      => '001/UNDUH/2026',
        'kategori_surat'   => 'SPK',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Kantor Pertanahan Kota Bandar Lampung',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Digitalisasi Dokumen Warkah',
        'status'           => 'baru',
        'penerima'         => 'Direktur Utama',
        'penerima_role'    => 'dirut',
    ]);

    SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'nomor_surat'     => '001/FI/SK/VIII/2026',
        'kategori_surat'  => 'SK',
        'unit_verifikasi' => 'teknik',
        'tanggal_surat'   => '2026-08-02',
        'tujuan'          => 'PT. Pupuk Indonesia',
        'perihal'         => 'Perencanaan Pemetaan di Langkat',
        'status'          => 'terkirim',
    ]);
}

/** Berkas PDF selalu dimulai dengan penanda %PDF-. */
function pastikanPdf($respons): void
{
    $respons->assertOk();

    $isi = $respons->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
        ? $respons->streamedContent()
        : $respons->getContent();

    expect($respons->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($isi, 0, 5))->toBe('%PDF-');
}

test('laporan surat masuk dapat diunduh sebagai pdf', function () {
    $sekretaris = pihakUnduh('sekretaris', ['akses_laporan_surat_masuk']);
    isiLaporan($sekretaris);

    pastikanPdf($this->actingAs($sekretaris)->get('/laporan/surat-masuk/pdf'));
});

test('laporan surat keluar dapat diunduh sebagai pdf', function () {
    $sekretaris = pihakUnduh('sekretaris', ['akses_laporan_surat_keluar']);
    isiLaporan($sekretaris);

    pastikanPdf($this->actingAs($sekretaris)->get('/laporan/surat-keluar/pdf'));
});

test('laporan disposisi dapat diunduh sebagai pdf', function () {
    $dirut = pihakUnduh('dirut', []);
    isiLaporan($dirut);

    pastikanPdf($this->actingAs($dirut)->get('/laporan/disposisi/pdf'));
});

test('laporan skpd dapat diunduh sebagai pdf', function () {
    $sekretaris = pihakUnduh('sekretaris', ['akses_laporan_skpd']);
    isiLaporan($sekretaris);

    pastikanPdf($this->actingAs($sekretaris)->get('/laporan/skpd/pdf'));
});

test('laporan kosong tetap menghasilkan pdf yang sah', function () {
    $sekretaris = pihakUnduh('sekretaris', ['akses_laporan_surat_masuk']);

    // Tanpa satu pun surat tercatat, laporannya harus tetap terbit.
    pastikanPdf($this->actingAs($sekretaris)->get('/laporan/surat-masuk/pdf'));
});

test('pengaturan dompdf memakai bawaan paket bila folder publik tidak ditentukan', function () {
    // Di lingkungan pengembangan DOMPDF_PUBLIC_PATH tidak diisi, sehingga
    // seluruh pengaturan bawaan paket berlaku tanpa perubahan.
    expect(config('dompdf.public_path'))->toBeNull()
        ->and(config('dompdf.options.chroot'))->toBe(realpath(base_path()));
});
