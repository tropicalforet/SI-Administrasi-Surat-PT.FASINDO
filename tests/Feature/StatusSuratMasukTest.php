<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function suratMasukStatus(string $nomor = 'SM-001/VIII/2026'): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => $nomor,
        'kategori_surat' => 'SU',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'penerima_role' => 'direktur1',
        'penerima'      => 'Direktur I',
        'status'        => 'baru',
    ]);
}

function penerimaDisposisi(string $role): User
{
    $user = User::factory()->create(['role' => $role]);

    $permission = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);

    return $user;
}

test('surat baru berstatus baru', function () {
    expect(suratMasukStatus()->status)->toBe('baru');
});

test('surat menjadi didisposisikan setelah didisposisikan', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $direktur = User::factory()->create(['role' => 'direktur1']);
    $surat = suratMasukStatus();

    $this->actingAs($dirut)->post('/disposisi', [
        'surat_masuk_id' => $surat->id,
        'kepada_user_id' => [$direktur->id],
        'instruksi'      => 'Mohon ditindaklanjuti',
    ]);

    expect($surat->fresh()->status)->toBe('didisposisikan');
});

test('surat menjadi selesai setelah seluruh disposisinya selesai', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $direkturA = penerimaDisposisi('direktur1');
    $direkturB = penerimaDisposisi('direktur2');
    $surat = suratMasukStatus();

    $this->actingAs($dirut)->post('/disposisi', [
        'surat_masuk_id' => $surat->id,
        'kepada_user_id' => [$direkturA->id, $direkturB->id],
        'instruksi'      => 'Mohon ditindaklanjuti',
    ]);

    $disposisi = Disposisi::where('surat_masuk_id', $surat->id)->get();

    // Satu selesai: surat masih berjalan
    $this->actingAs($direkturA)->put('/disposisi/' . $disposisi[0]->id, ['status' => 'selesai']);
    expect($surat->fresh()->status)->toBe('didisposisikan');

    // Seluruhnya selesai: surat tuntas
    $this->actingAs($direkturB)->put('/disposisi/' . $disposisi[1]->id, ['status' => 'selesai']);
    expect($surat->fresh()->status)->toBe('selesai');
});

test('surat kembali berjalan bila disposisi dibuka lagi', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $direktur = penerimaDisposisi('direktur1');
    $surat = suratMasukStatus();

    $this->actingAs($dirut)->post('/disposisi', [
        'surat_masuk_id' => $surat->id,
        'kepada_user_id' => [$direktur->id],
        'instruksi'      => 'Mohon ditindaklanjuti',
    ]);

    $disposisi = Disposisi::where('surat_masuk_id', $surat->id)->first();

    $this->actingAs($direktur)->put('/disposisi/' . $disposisi->id, ['status' => 'selesai']);
    expect($surat->fresh()->status)->toBe('selesai');

    $this->actingAs($direktur)->put('/disposisi/' . $disposisi->id, ['status' => 'diproses']);
    expect($surat->fresh()->status)->toBe('didisposisikan');
});

test('surat kembali baru bila disposisi terakhirnya dihapus', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $direktur = User::factory()->create(['role' => 'direktur1']);
    $surat = suratMasukStatus();

    $this->actingAs($dirut)->post('/disposisi', [
        'surat_masuk_id' => $surat->id,
        'kepada_user_id' => [$direktur->id],
        'instruksi'      => 'Mohon ditindaklanjuti',
    ]);

    $disposisi = Disposisi::where('surat_masuk_id', $surat->id)->first();

    $this->actingAs($dirut)->delete('/disposisi/' . $disposisi->id);

    expect($surat->fresh()->status)->toBe('baru');
});

test('filter status di laporan mengembalikan hasil', function () {
    $sekretaris = User::factory()->create(['role' => 'sekretaris']);

    suratMasukStatus('SM-001/VIII/2026');
    $selesai = suratMasukStatus('SM-002/VIII/2026');
    $selesai->update(['status' => 'selesai']);

    $this->actingAs($sekretaris)
        ->get('/laporan/surat-masuk?status=selesai')
        ->assertOk()
        ->assertSee('SM-002/VIII/2026')
        ->assertDontSee('SM-001/VIII/2026');
});

test('daftar surat masuk menampilkan label status', function () {
    $sekretaris = User::factory()->create(['role' => 'sekretaris']);
    suratMasukStatus();

    $this->actingAs($sekretaris)
        ->get('/surat-masuk')
        ->assertOk()
        ->assertSee('Baru');
});
