<?php

/**
 * Probe audit. Setiap tes di berkas ini menyatakan perilaku yang SEHARUSNYA
 * berlaku. Yang gagal berarti lubangnya nyata.
 */

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function pihakAudit(string $role, array $izin, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $p = Permission::firstOrCreate(['name' => $nama], ['label' => $nama, 'group' => 'uji']);
        $user->permissions()->syncWithoutDetaching([$p->id]);
    }

    return $user;
}

function suratMasukAudit(array $tambahan = []): SuratMasuk
{
    static $urut = 0;
    $urut++;

    return SuratMasuk::create(array_merge([
        'nomor_surat'      => sprintf('%03d/AUDIT/2026', $urut),
        'kategori_surat'   => 'Undangan',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Surat rahasia direksi',
        'status'           => 'baru',
        'penerima'         => 'Direktur Keuangan dan Administrasi',
        'penerima_role'    => 'direktur1',
    ], $tambahan));
}

test('berkas surat keluar orang lain tidak dapat diunduh', function () {
    $penyusun = pihakAudit('staff', ['akses_surat_keluar'], 'keuangan_administrasi');
    $orangLain = pihakAudit('staff', ['akses_surat_keluar'], 'teknik');

    $surat = SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'kategori_surat'  => 'SU',
        'unit_verifikasi' => 'keuangan_administrasi',
        'tanggal_surat'   => '2026-08-10',
        'tujuan'          => 'PT Mitra Sejahtera',
        'perihal'         => 'Konsep rahasia',
        'status'          => 'draft',
        'file'            => 'surat_keluar/rahasia.pdf',
    ]);

    // Berkasnya benar-benar ada, supaya kegagalan bukan sekadar 404 berkas
    // hilang melainkan benar-benar soal kewenangan.
    Storage::disk('public')->put('surat_keluar/rahasia.pdf', 'isi rahasia');

    // Halaman detailnya sudah dijaga; berkasnya harus dijaga dengan aturan sama.
    expect($surat->dapatDilihatOleh($orangLain))->toBeFalse();

    $this->actingAs($orangLain)
        ->get('/surat-keluar/' . $surat->id . '/download')
        ->assertForbidden();
});

test('surat masuk yang tidak boleh dibaca tidak dapat didisposisikan', function () {
    // Surat ditujukan ke Direktur Keuangan; manager teknik tidak berkepentingan.
    $surat = suratMasukAudit();

    $manager = pihakAudit('manager', ['akses_disposisi'], 'teknik');
    pihakAudit('staff', ['akses_disposisi'], 'teknik');

    expect($surat->bolehDibacaOleh($manager))->toBeFalse();

    $this->actingAs($manager)
        ->get('/disposisi/' . $surat->id . '/create')
        ->assertForbidden();
});

test('disposisi tidak dapat diterbitkan atas surat yang tidak boleh dibaca', function () {
    $surat = suratMasukAudit();

    $manager = pihakAudit('manager', ['akses_disposisi'], 'teknik');
    $bawahan = pihakAudit('staff', ['akses_disposisi'], 'teknik');

    $this->actingAs($manager)->post('/disposisi', [
        'surat_masuk_id' => $surat->id,
        'kepada_user_id' => [$bawahan->id],
        'instruksi'      => 'Tolong ditindaklanjuti.',
    ]);

    // Lubang sebenarnya ada di sini: form boleh saja disembunyikan, tetapi
    // POST langsung tetap harus ditolak.
    expect(Disposisi::where('surat_masuk_id', $surat->id)->count())->toBe(0);
});

test('menghapus pengguna tidak ikut menghapus SKPD yang sudah disetujui', function () {
    $admin = pihakAudit('admin', [], null);
    $pegawai = pihakAudit('staff', [], 'teknik');

    $skpd = App\Models\Skpd::create([
        'user_id'          => $pegawai->id,
        'nomor_skpd'       => '099/FI/SKPD/VIII/2026',
        'nama_pegawai'     => $pegawai->name,
        'tujuan_dinas'     => 'Kendal',
        'keperluan'        => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-08-20',
        'tanggal_kembali'  => '2026-08-22',
        'status'           => 'disetujui',
    ]);

    $this->actingAs($admin)->delete('/users/' . $pegawai->id);

    // Dokumen terbit adalah dasar pertanggungjawaban; ia harus tetap ada
    // meskipun pegawainya sudah tidak tercatat lagi.
    expect(App\Models\Skpd::withTrashed()->find($skpd->id))->not->toBeNull();
});

test('menghapus pengguna tidak menghapus jejak aktivitasnya', function () {
    $admin = pihakAudit('admin', [], null);
    $pegawai = pihakAudit('staff', [], 'teknik');

    $jejak = App\Models\ActivityLog::create([
        'user_id'       => $pegawai->id,
        'nama_pengguna' => $pegawai->name,
        'aktivitas'     => 'Login',
        'deskripsi'     => 'Masuk ke sistem',
    ]);

    $this->actingAs($admin)->delete('/users/' . $pegawai->id);

    $jejak->refresh();

    // Barisnya bertahan, dan yang terpenting masih menyebut siapa pelakunya -
    // audit trail tanpa nama pelaku kehilangan seluruh gunanya.
    expect($jejak->exists)->toBeTrue()
        ->and($jejak->label_pelaku)->toContain($pegawai->name);
});
