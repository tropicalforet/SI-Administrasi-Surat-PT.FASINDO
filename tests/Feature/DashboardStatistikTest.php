<?php

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;

function suratKeluarBerstatus(string $status, string $nomor): SuratKeluar
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

test('kartu surat keluar menghitung status yang benar-benar dipakai', function () {
    suratKeluarBerstatus('draft', '001/A');
    suratKeluarBerstatus('menunggu_dirut', '002/A');
    suratKeluarBerstatus('menunggu_dirut', '003/A');
    suratKeluarBerstatus('terkirim', '004/A');
    suratKeluarBerstatus('ditolak', '005/A');

    $this->actingAs(User::factory()->create(['role' => 'sekretaris']))
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totalSuratKeluar', 5)
        ->assertViewHas('totalDraft', 1)
        ->assertViewHas('totalMenungguDirut', 2)
        ->assertViewHas('totalTerkirim', 1)
        ->assertViewHas('totalDitolak', 1);
});

function skpdBerstatus(string $status, string $nomor, User $pemilik): void
{
    $suratTugas = App\Models\SuratTugas::create([
        'nomor_surat_tugas' => 'ST-' . $nomor,
        'user_id'           => $pemilik->id,
        'perihal_tugas'     => 'Kunjungan kerja',
        'tujuan'            => 'Surabaya',
        'tanggal_mulai'     => '2026-09-01',
        'tanggal_selesai'   => '2026-09-03',
        'status'            => 'diterbitkan',
    ]);

    App\Models\Skpd::create([
        'user_id'           => $pemilik->id,
        'surat_tugas_id'    => $suratTugas->id,
        'nomor_skpd'        => 'SKPD-' . $nomor,
        'nama_pegawai'      => $pemilik->name,
        'tujuan_dinas'      => 'Surabaya',
        'keperluan'         => 'Kunjungan kerja',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'durasi_hari'       => 3,
        'status'            => $status,
    ]);
}

test('kartu skpd menghitung seluruh pengajuan untuk dirut', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff']);

    skpdBerstatus('diperiksa', '001', $staff);
    skpdBerstatus('diperiksa', '002', $staff);
    skpdBerstatus('disetujui', '003', $staff);
    skpdBerstatus('ditolak', '004', $staff);

    $this->actingAs($dirut)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totalSkpd', 4)
        ->assertViewHas('skpdPending', 2)
        ->assertViewHas('skpdDisetujui', 1)
        ->assertViewHas('skpdDitolak', 1)
        // Pastikan kartunya benar-benar tampil, bukan sekadar datanya dikirim
        ->assertSee('Perjalanan Dinas (SKPD)')
        ->assertSee('Total SKPD')
        ->assertSee('Seluruh pengajuan perjalanan dinas.');
});

test('kartu skpd pegawai hanya menghitung pengajuannya sendiri', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $lain = User::factory()->create(['role' => 'staff']);

    skpdBerstatus('diperiksa', '001', $staff);
    skpdBerstatus('disetujui', '002', $staff);
    skpdBerstatus('disetujui', '003', $lain);
    skpdBerstatus('ditolak', '004', $lain);

    $this->actingAs($staff)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totalSkpd', 2)
        ->assertViewHas('skpdPending', 1)
        ->assertViewHas('skpdDisetujui', 1)
        ->assertViewHas('skpdDitolak', 0);
});

test('grafik status disposisi hanya menghitung disposisi milik pengguna biasa', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $lain = User::factory()->create(['role' => 'staff']);
    $pengirim = User::factory()->create(['role' => 'direktur1']);

    $suratMasuk = SuratMasuk::create([
        'nomor_surat'   => 'SM-001/VIII/2026',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);

    $buat = function (User $kepada, string $status) use ($suratMasuk, $pengirim) {
        Disposisi::create([
            'surat_masuk_id'    => $suratMasuk->id,
            'dari_user_id'      => $pengirim->id,
            'kepada_user_id'    => $kepada->id,
            'instruksi'         => 'Mohon ditindaklanjuti',
            'status'            => $status,
            'tanggal_disposisi' => now(),
        ]);
    };

    $buat($staff, 'menunggu');
    $buat($staff, 'selesai');
    $buat($lain, 'menunggu');
    $buat($lain, 'diproses');

    // [menunggu, diproses, selesai] milik $staff saja
    $this->actingAs($staff)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('statusDisposisi', [1, 0, 1]);
});

test('dirut tetap melihat status disposisi seluruh organisasi', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff']);

    $suratMasuk = SuratMasuk::create([
        'nomor_surat'   => 'SM-002/VIII/2026',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);

    foreach (['menunggu', 'diproses', 'selesai'] as $status) {
        Disposisi::create([
            'surat_masuk_id'    => $suratMasuk->id,
            'dari_user_id'      => $dirut->id,
            'kepada_user_id'    => $staff->id,
            'instruksi'         => 'Mohon ditindaklanjuti',
            'status'            => $status,
            'tanggal_disposisi' => now(),
        ]);
    }

    $this->actingAs($dirut)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('statusDisposisi', [1, 1, 1]);
});
