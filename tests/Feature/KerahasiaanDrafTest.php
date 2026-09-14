<?php

/**
 * Draf adalah kertas kerja penyusunnya. Sebelum diajukan ia belum menjadi
 * dokumen kantor, jadi tidak seorang pun boleh melihat draf orang lain -
 * termasuk sekretaris, Direktur Utama, dan administrator.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\SuratKeluar;
use App\Models\User;

function pemilikDraf(string $role, array $izin, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $permission = Permission::firstOrCreate(
            ['name' => $nama],
            ['label' => $nama, 'group' => 'uji']
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

function drafSuratKeluar(User $penyusun): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'    => $penyusun->id,
        'kategori_surat' => 'SU',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'  => '2026-08-10',
        'tujuan'         => 'PT Mitra Sejahtera',
        'perihal'        => 'Konsep rahasia milik penyusun',
        'status'         => 'draft',
    ]);
}

// ============================================================
// Surat keluar
// ============================================================

test('draf surat keluar tidak terlihat sekretaris, dirut, maupun administrator', function () {
    $penyusun = pemilikDraf('staff', ['akses_surat_keluar']);
    $draf = drafSuratKeluar($penyusun);

    $pengintip = [
        pemilikDraf('sekretaris', ['akses_surat_keluar'], 'pimpinan'),
        pemilikDraf('dirut', ['akses_surat_keluar'], 'pimpinan'),
        pemilikDraf('administrator', ['akses_surat_keluar'], null),
        pemilikDraf('direktur2', ['akses_surat_keluar'], 'teknik'),
    ];

    foreach ($pengintip as $orang) {
        expect($draf->dapatDilihatOleh($orang))->toBeFalse();

        $this->actingAs($orang)
            ->get('/surat-keluar')
            ->assertOk()
            ->assertDontSee('Konsep rahasia milik penyusun');

        $this->actingAs($orang)
            ->get('/surat-keluar/' . $draf->id)
            ->assertForbidden();
    }
});

test('penyusun tetap melihat drafnya sendiri', function () {
    $penyusun = pemilikDraf('staff', ['akses_surat_keluar']);
    $draf = drafSuratKeluar($penyusun);

    expect($draf->dapatDilihatOleh($penyusun))->toBeTrue();

    $this->actingAs($penyusun)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertSee('Konsep rahasia milik penyusun');
});

test('begitu diajukan, sekretaris dan dirut langsung melihatnya', function () {
    $penyusun = pemilikDraf('staff', ['akses_surat_keluar']);
    $sekretaris = pemilikDraf('sekretaris', ['akses_surat_keluar'], 'pimpinan');

    $draf = drafSuratKeluar($penyusun);
    $draf->update(['status' => 'menunggu_sekretaris']);

    expect($draf->fresh()->dapatDilihatOleh($sekretaris))->toBeTrue();

    $this->actingAs($sekretaris)
        ->get('/surat-keluar')
        ->assertOk()
        ->assertSee('Konsep rahasia milik penyusun');
});

test('laporan surat keluar tidak membocorkan draf orang lain', function () {
    $penyusun = pemilikDraf('staff', ['akses_surat_keluar']);
    drafSuratKeluar($penyusun);

    $sekretaris = pemilikDraf('sekretaris', ['akses_laporan_surat_keluar'], 'pimpinan');

    // Laporan sempat menarik data tanpa saringan apa pun
    $this->actingAs($sekretaris)
        ->get('/laporan/surat-keluar')
        ->assertOk()
        ->assertDontSee('Konsep rahasia milik penyusun');
});

// ============================================================
// SKPD
// ============================================================

test('draf skpd tidak terlihat pimpinan maupun direktur unitnya', function () {
    $pegawai = pemilikDraf('staff', ['akses_skpd']);

    $draf = Skpd::create([
        'user_id'           => $pegawai->id,
        'asal_usul'         => 'usulan',
        'nomor_skpd'        => 'SKPD-099/08/2026',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kota Rahasia',
        'keperluan'         => 'Rencana yang belum diajukan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'durasi_hari'       => 2,
        'status'            => 'draft',
    ]);

    $pengintip = [
        pemilikDraf('sekretaris', ['akses_skpd'], 'pimpinan'),
        pemilikDraf('dirut', ['akses_skpd'], 'pimpinan'),
        pemilikDraf('direktur2', ['akses_skpd'], 'teknik'),
    ];

    foreach ($pengintip as $orang) {
        expect($draf->dapatDilihatOleh($orang))->toBeFalse();

        $this->actingAs($orang)
            ->get('/skpd/' . $draf->id)
            ->assertForbidden();
    }

    // Pemiliknya sendiri tetap bisa membukanya
    expect($draf->dapatDilihatOleh($pegawai))->toBeTrue();
});

test('draf penugasan belum terlihat pegawai yang akan ditugaskan', function () {
    $direktur = pemilikDraf('direktur2', ['akses_skpd'], 'teknik');
    $bawahan = pemilikDraf('manager', ['akses_skpd'], 'teknik');

    $this->actingAs($direktur)->post('/skpd', [
        'user_id'           => $bawahan->id,
        'keperluan'         => 'Penugasan yang masih disusun',
        'tujuan_dinas'      => 'Surabaya',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'aksi'              => 'draft',
    ]);

    $draf = Skpd::first();

    // Selama atasannya masih menyusun, belum ada perintah apa pun yang perlu
    // diketahui bawahannya. Dulu bawahan sudah melihatnya karena kolom user_id
    // menunjuk kepadanya, padahal penyusunnya adalah atasan.
    expect($draf->status)->toBe('draft')
        ->and($draf->dibuatOleh($direktur))->toBeTrue()
        ->and($draf->dapatDilihatOleh($direktur))->toBeTrue()
        ->and($draf->dapatDilihatOleh($bawahan))->toBeFalse();

    $this->actingAs($bawahan)
        ->get('/skpd/' . $draf->id)
        ->assertForbidden();

    $this->actingAs($bawahan)
        ->get('/skpd')
        ->assertOk()
        ->assertDontSee('Penugasan yang masih disusun');
});

test('penugasan baru muncul di layar pegawai setelah dokumennya terbit', function () {
    $direktur = pemilikDraf('direktur2', ['akses_skpd'], 'teknik');
    $bawahan = pemilikDraf('manager', ['akses_skpd'], 'teknik');
    pemilikDraf('dirut', ['akses_skpd'], 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', [
        'user_id'           => $bawahan->id,
        'keperluan'         => 'Penugasan yang masih disusun',
        'tujuan_dinas'      => 'Surabaya',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'aksi'              => 'draft',
    ]);

    $draf = Skpd::first();
    $this->actingAs($direktur)->put('/skpd/' . $draf->id . '/ajukan');

    // Diajukan saja belum cukup. Selama Direktur Utama belum menandatangani,
    // perintahnya belum resmi dan belum perlu diketahui pegawainya.
    expect($draf->fresh()->dapatDilihatOleh($bawahan))->toBeFalse();

    $this->actingAs($bawahan)
        ->get('/skpd/' . $draf->id)
        ->assertForbidden();

    $draf->update(['status' => 'disetujui']);

    expect($draf->fresh()->dapatDilihatOleh($bawahan))->toBeTrue();

    $this->actingAs($bawahan)
        ->get('/skpd/' . $draf->id)
        ->assertOk();
});

test('pegawai tidak dapat mengajukan draf penugasan yang belum selesai disusun', function () {
    $direktur = pemilikDraf('direktur2', ['akses_skpd'], 'teknik');
    $bawahan = pemilikDraf('manager', ['akses_skpd'], 'teknik');
    pemilikDraf('dirut', ['akses_skpd'], 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', [
        'user_id'           => $bawahan->id,
        'keperluan'         => 'Penugasan yang masih disusun',
        'tujuan_dinas'      => 'Surabaya',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
        'aksi'              => 'draft',
    ]);

    $draf = Skpd::first();

    // Yang tidak terlihat, tidak pula boleh diurus.
    $this->actingAs($bawahan)
        ->put('/skpd/' . $draf->id . '/ajukan')
        ->assertForbidden();

    expect($draf->fresh()->status)->toBe('draft');
});

test('sekretaris pun tidak melihat draf skpd orang lain', function () {
    $pegawai = pemilikDraf('staff', ['akses_skpd']);
    $sekretaris = pemilikDraf('sekretaris', ['akses_skpd'], 'pimpinan');

    $draf = Skpd::create([
        'user_id'           => $pegawai->id,
        'asal_usul'         => 'usulan',
        'nama_pegawai'      => $pegawai->name,
        'tujuan_dinas'      => 'Kota Rahasia',
        'keperluan'         => 'Rencana yang belum diajukan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'status'            => 'draft',
    ]);

    $this->actingAs($sekretaris)
        ->get('/skpd')
        ->assertOk()
        ->assertDontSee('Rencana yang belum diajukan');

    $this->actingAs($sekretaris)
        ->put('/skpd/' . $draf->id . '/ajukan')
        ->assertForbidden();
});
