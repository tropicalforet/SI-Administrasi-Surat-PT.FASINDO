<?php

/**
 * Tidak seorang pun boleh menyetujui dokumen atas namanya sendiri, sekalipun
 * ia seorang direktur dan dokumen itu berada di direktoratnya.
 */

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;

function pelakuSkpd(string $role, ?string $unit): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function usulanDinas(array $tambahan = []): array
{
    return array_merge([
        'keperluan'         => 'Kunjungan proyek',
        'tujuan_dinas'      => 'Surabaya',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
    ], $tambahan);
}

test('direktur tidak dapat menyetujui skpd atas namanya sendiri', function () {
    $direktur = pelakuSkpd('direktur1', 'keuangan_administrasi');
    pelakuSkpd('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', usulanDinas([
        'user_id' => $direktur->id,
        'aksi'    => 'ajukan',
    ]));

    $skpd = Skpd::first();

    // Tidak boleh berhenti di tahap direktur - penyetujunya adalah dia sendiri
    expect($skpd->status)->toBe('menunggu_dirut');

    // Tahap direktur ditolak karena bukan tahapnya
    $this->actingAs($direktur)->put('/skpd/' . $skpd->id . '/setujui-direktur');
    expect($skpd->fresh()->status)->toBe('menunggu_dirut');

    // Tahap dirut ditolak karena ia bukan Direktur Utama
    $this->actingAs($direktur)
        ->put('/skpd/' . $skpd->id . '/approve')
        ->assertForbidden();

    expect($skpd->fresh()->status)->toBe('menunggu_dirut');
});

test('tombol setujui dirut tidak muncul bagi direktur', function () {
    $direktur = pelakuSkpd('direktur2', 'teknik');
    pelakuSkpd('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', usulanDinas([
        'user_id' => $direktur->id,
        'aksi'    => 'ajukan',
    ]));

    $skpd = Skpd::first();

    $this->actingAs($direktur)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertDontSee('Setujui SKPD')
        ->assertDontSee('Setujui Usulan');
});

test('direktur tidak dapat menyetujui penugasan yang ia terbitkan untuk bawahannya', function () {
    $direktur = pelakuSkpd('direktur2', 'teknik');
    $bawahan = pelakuSkpd('manager', 'teknik');
    pelakuSkpd('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', usulanDinas([
        'user_id' => $bawahan->id,
        'aksi'    => 'ajukan',
    ]));

    $skpd = Skpd::first();

    expect($skpd->status)->toBe('menunggu_dirut');

    $this->actingAs($direktur)
        ->put('/skpd/' . $skpd->id . '/approve')
        ->assertForbidden();

    expect($skpd->fresh()->status)->toBe('menunggu_dirut');
});

test('tanda tangan dan qr hanya muncul setelah dirut menyetujui', function () {
    $direktur = pelakuSkpd('direktur1', 'keuangan_administrasi');
    $dirut = pelakuSkpd('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', usulanDinas([
        'user_id' => $direktur->id,
        'aksi'    => 'ajukan',
    ]));

    $skpd = Skpd::first();

    // Sebelum disetujui: bertanda pratinjau, tanpa QR
    $sebelum = view('skpd.pdf', [
        'skpd'           => $skpd->fresh(),
        'qrCodeBase64'   => 'PALSU',
        'belumDisetujui' => true,
    ])->render();

    expect($sebelum)->toContain('BELUM DISETUJUI')
        ->and($sebelum)->not->toContain('base64,PALSU');

    // Sesudah Dirut menyetujui
    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    $sesudah = view('skpd.pdf', [
        'skpd'           => $skpd->fresh(),
        'qrCodeBase64'   => 'PALSU',
        'belumDisetujui' => false,
    ])->render();

    expect($sesudah)->toContain('base64,PALSU')
        ->and($sesudah)->not->toContain('BELUM DISETUJUI');
});
