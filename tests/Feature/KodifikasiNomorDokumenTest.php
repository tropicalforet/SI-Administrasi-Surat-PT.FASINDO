<?php

/**
 * Kaidah kodifikasi: [urut]/FI/[kode]/[bulan Romawi]/[tahun].
 *
 * Dua hal yang dikunci di sini. Pertama bentuk nomornya - nama tujuan surat
 * dulu ikut diselipkan, membuat panjangnya berubah-ubah dan terpotong bila
 * tujuannya panjang. Kedua jaminan deret rapat: nomor hanya terbit ketika
 * dokumen dipastikan akan berlaku, sehingga pengajuan batal tidak memakannya.
 */

use App\Helpers\NomorDokumenHelper;
use App\Models\Permission;
use App\Models\Skpd;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Bagian bulan dan tahun nomor diambil dari waktu penerbitan, sehingga waktu
 * dibekukan agar tes tidak ikut berubah saat kalender berganti.
 */
beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-08-13 09:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function pihakKode(string $role, array $izin = [], ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $p = Permission::firstOrCreate(['name' => $nama], ['label' => $nama, 'group' => 'uji']);
        $user->permissions()->syncWithoutDetaching([$p->id]);
    }

    return $user;
}

function konsepKode(User $penyusun, string $kategori, string $tujuan): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'kategori_surat'  => $kategori,
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => $tujuan,
        'perihal'         => 'Perihal uji ' . $kategori,
        'status'          => 'menunggu_sekretaris',
    ]);
}

test('bentuk nomor mengikuti kaidah tunggal', function () {
    expect(NomorDokumenHelper::susun('SU', 12, 8, 2026))->toBe('012/FI/SU/VIII/2026')
        ->and(NomorDokumenHelper::susun('SKPD', 39, 8, 2026))->toBe('039/FI/SKPD/VIII/2026')
        ->and(NomorDokumenHelper::susun('INV', 3, 1, 2026))->toBe('003/FI/INV/I/2026')
        ->and(NomorDokumenHelper::susun('SP', 250, 12, 2026))->toBe('250/FI/SP/XII/2026');
});

test('kategori bebas ketik dibersihkan agar tidak merusak bentuk nomor', function () {
    // Penyusun boleh mengetik kategori sendiri lewat pilihan "Lainnya".
    expect(NomorDokumenHelper::kodeAman('Surat Perjanjian'))->toBe('SURATPERJA')
        ->and(NomorDokumenHelper::kodeAman('SPK/2026'))->toBe('SPK2026')
        ->and(NomorDokumenHelper::kodeAman('...'))->toBe('LAIN');
});

test('nomor surat keluar tidak lagi memuat nama tujuan', function () {
    $penyusun = pihakKode('staff', ['akses_surat_keluar']);
    $sekretaris = pihakKode('sekretaris', ['akses_surat_keluar'], 'pimpinan');

    $surat = konsepKode($penyusun, 'SU', 'Badan Pertanahan Nasional Kota Semarang');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    $nomor = $surat->fresh()->nomor_surat;

    expect($nomor)->toBe('001/FI/SU/VIII/2026')
        ->and($nomor)->not->toContain('BADAN')
        ->and($nomor)->not->toContain('SEMARANG');
});

test('setiap kategori punya deret nomornya sendiri', function () {
    $penyusun = pihakKode('staff', ['akses_surat_keluar']);
    $sekretaris = pihakKode('sekretaris', ['akses_surat_keluar'], 'pimpinan');

    $undangan = konsepKode($penyusun, 'SU', 'PT Semen Indonesia');
    $invoice  = konsepKode($penyusun, 'INV', 'PT Pupuk Indonesia');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $undangan->id . '/proses-sekretaris');
    $this->actingAs($sekretaris)->put('/surat-keluar/' . $invoice->id . '/proses-sekretaris');

    // Terbit berurutan, tetapi keduanya bernomor 001 karena kategorinya beda.
    expect($undangan->fresh()->nomor_surat)->toBe('001/FI/SU/VIII/2026')
        ->and($invoice->fresh()->nomor_surat)->toBe('001/FI/INV/VIII/2026');
});

test('pengajuan SKPD belum bernomor sebelum disetujui', function () {
    $dirut = pihakKode('dirut', ['akses_skpd'], 'pimpinan');
    $pegawai = pihakKode('staff', ['akses_skpd']);

    $this->actingAs($dirut)->post('/skpd', [
        'user_id'           => $pegawai->id,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-08-20',
        'tanggal_kembali'   => '2026-08-22',
    ]);

    $skpd = Skpd::first();

    expect($skpd->nomor_skpd)->toBeNull()
        ->and($skpd->label_nomor)->toBe('(Belum bernomor)');
});

test('nomor SKPD terbit saat disetujui direktur utama', function () {
    $dirut = pihakKode('dirut', ['akses_skpd'], 'pimpinan');
    $pegawai = pihakKode('staff', ['akses_skpd']);

    $this->actingAs($dirut)->post('/skpd', [
        'user_id'           => $pegawai->id,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-08-20',
        'tanggal_kembali'   => '2026-08-22',
        'aksi'              => 'ajukan',
    ]);

    $skpd = Skpd::first();
    expect($skpd->status)->toBe('menunggu_dirut');

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    expect($skpd->fresh()->nomor_skpd)->toBe('001/FI/SKPD/VIII/2026');
});

test('pengajuan yang ditolak tidak memakan nomor', function () {
    $dirut = pihakKode('dirut', ['akses_skpd'], 'pimpinan');
    $pegawai = pihakKode('staff', ['akses_skpd']);

    // Tiga pengajuan: dua ditolak, satu disetujui.
    foreach (['tolak', 'tolak', 'setuju'] as $perlakuan) {
        $this->actingAs($dirut)->post('/skpd', [
            'user_id'           => $pegawai->id,
            'tujuan_dinas'      => 'Kendal',
            'keperluan'         => 'Pemeriksaan lapangan',
            'tanggal_berangkat' => '2026-08-20',
            'tanggal_kembali'   => '2026-08-22',
            'aksi'              => 'ajukan',
        ]);

        $skpd = Skpd::latest('id')->first();

        if ($perlakuan === 'tolak') {
            $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/reject', [
                'catatan_revisi' => 'Keperluan belum jelas.',
            ]);
        } else {
            $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');
        }
    }

    // Yang disetujui tetap memperoleh nomor pertama - dua penolakan
    // sebelumnya tidak menyisakan lubang di deret nomor.
    expect(Skpd::whereNotNull('nomor_skpd')->count())->toBe(1)
        ->and(Skpd::whereNotNull('nomor_skpd')->first()->nomor_skpd)->toBe('001/FI/SKPD/VIII/2026');
});

test('SKPD yang sudah bernomor tidak berganti nomor saat disetujui ulang', function () {
    $dirut = pihakKode('dirut', ['akses_skpd'], 'pimpinan');
    $pegawai = pihakKode('staff', ['akses_skpd']);

    $skpd = Skpd::create([
        'user_id'           => $pegawai->id,
        'nomor_skpd'        => '007/FI/SKPD/VIII/2026',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-08-20',
        'tanggal_kembali'   => '2026-08-22',
        'status'            => 'menunggu_dirut',
    ]);

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    expect($skpd->fresh()->nomor_skpd)->toBe('007/FI/SKPD/VIII/2026');
});
