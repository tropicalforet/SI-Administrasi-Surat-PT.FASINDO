<?php

/**
 * Wewenang yang tidak konsisten antara middleware dan controller membuat menu
 * terlihat lalu berujung 403, dan menyisakan hak pada jabatan yang sudah tidak
 * berperan lagi. Tes ini mengunci keduanya.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pihakWewenang(string $role, array $izin = [], ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    foreach ($izin as $nama) {
        $p = Permission::firstOrCreate(['name' => $nama], ['label' => $nama, 'group' => 'uji']);
        $user->permissions()->syncWithoutDetaching([$p->id]);
    }

    return $user;
}

function skpdWewenang(User $pemilik, string $status = 'draft'): Skpd
{
    static $urut = 0;
    $urut++;

    return Skpd::create([
        'user_id'           => $pemilik->id,
        'nomor_skpd'        => sprintf('%03d/FI/SKPD/VIII/2026', $urut),
        'nama_pegawai'      => $pemilik->name,
        'tujuan_dinas'      => 'Kendal',
        'keperluan'         => 'Pemeriksaan lapangan',
        'tanggal_berangkat' => '2026-08-20',
        'tanggal_kembali'   => '2026-08-22',
        'status'            => $status,
    ]);
}

test('administrator dapat menghapus SKPD orang lain', function () {
    $admin = pihakWewenang('admin', ['akses_skpd'], null);
    $pegawai = pihakWewenang('staff', ['akses_skpd']);

    $skpd = skpdWewenang($pegawai);

    $this->actingAs($admin)->delete('/skpd/' . $skpd->id);

    expect(Skpd::find($skpd->id))->toBeNull()
        ->and(Skpd::withTrashed()->find($skpd->id))->not->toBeNull();
});

test('sekretaris tidak lagi dapat menghapus SKPD orang lain', function () {
    $sekretaris = pihakWewenang('sekretaris', ['akses_skpd'], 'pimpinan');
    $pegawai = pihakWewenang('staff', ['akses_skpd']);

    $skpd = skpdWewenang($pegawai);

    // Sekretaris sudah tidak berperan di alur SKPD sejak kewenangan
    // menugaskan dipegang Dirut dan para direktur.
    $this->actingAs($sekretaris)
        ->delete('/skpd/' . $skpd->id)
        ->assertForbidden();

    expect(Skpd::find($skpd->id))->not->toBeNull();
});

test('pemilik tetap dapat menghapus SKPD-nya sendiri', function () {
    $pegawai = pihakWewenang('staff', ['akses_skpd']);

    $skpd = skpdWewenang($pegawai);

    $this->actingAs($pegawai)->delete('/skpd/' . $skpd->id);

    expect(Skpd::find($skpd->id))->toBeNull();
});

test('administrator tidak lagi ditolak di monitoring disposisi', function () {
    $admin = pihakWewenang('admin', ['akses_monitoring'], null);

    // Dulu middleware meloloskannya, lalu controller menolaknya: menunya
    // terlihat tetapi selalu berujung 403.
    $this->actingAs($admin)->get('/monitoring-disposisi')->assertOk();
});

test('pelaksana tetap tidak dapat membuka monitoring disposisi', function () {
    $staff = pihakWewenang('staff', ['akses_monitoring']);

    $this->actingAs($staff)->get('/monitoring-disposisi')->assertForbidden();
});

test('route debug bawaan pengembangan sudah tidak ada', function () {
    $dirut = pihakWewenang('dirut', [], 'pimpinan');

    $this->actingAs($dirut)->get('/test-dirut')->assertNotFound();
    $this->actingAs($dirut)->get('/test-sekretaris')->assertNotFound();
});

test('filter status alur lama tidak lagi ditawarkan', function () {
    $sekretaris = pihakWewenang('sekretaris', ['akses_surat_keluar'], 'pimpinan');

    $isi = $this->actingAs($sekretaris)->get('/surat-keluar')->assertOk()->getContent();

    expect($isi)->not->toContain('value="menunggu_direktur"')
        ->and($isi)->toContain('value="menunggu_sekretaris"');
});
