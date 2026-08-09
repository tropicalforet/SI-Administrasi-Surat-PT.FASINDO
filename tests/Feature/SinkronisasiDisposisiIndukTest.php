<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\DisposisiSiapDikonfirmasi;
use Illuminate\Support\Facades\Notification;

function suratMasukSinkron(): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => 'SM-' . uniqid(),
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);
}

function penerimaBerizin(string $role = 'direktur1'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => 'teknik']);

    $permission = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);

    return $user;
}

function indukUntuk(User $kepada, string $status = 'menunggu'): Disposisi
{
    return Disposisi::create([
        'surat_masuk_id'    => suratMasukSinkron()->id,
        'dari_user_id'      => User::factory()->create(['role' => 'dirut'])->id,
        'kepada_user_id'    => $kepada->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => $status,
        'tanggal_disposisi' => now(),
    ]);
}

test('induk naik ke diproses saat disposisi diteruskan', function () {
    $direktur = penerimaBerizin();
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $induk = indukUntuk($direktur);
    expect($induk->status)->toBe('menunggu');

    $this->actingAs($direktur)->post('/disposisi/continue', [
        'parent_disposisi_id' => $induk->id,
        'kepada_user_id'      => [$staff->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertRedirect(route('disposisi.saya'));

    expect($induk->fresh()->status)->toBe('diproses');
});

test('induk tidak dapat ditandai selesai selama anaknya belum selesai', function () {
    $direktur = penerimaBerizin();
    $staff = penerimaBerizin('staff');

    $induk = indukUntuk($direktur, 'diproses');

    Disposisi::create([
        'parent_disposisi_id' => $induk->id,
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'dari_user_id'        => $direktur->id,
        'kepada_user_id'      => $staff->id,
        'instruksi'           => 'Tolong dikerjakan',
        'status'              => 'diproses',
        'tanggal_disposisi'   => now(),
    ]);

    $this->actingAs($direktur)->put('/disposisi/' . $induk->id, [
        'status'                => 'selesai',
        'catatan_tindak_lanjut' => 'Sudah saya cek',
    ]);

    expect($induk->fresh()->status)->toBe('diproses');
});

test('penerima induk diberi tahu saat seluruh anak selesai', function () {
    Notification::fake();

    $direktur = penerimaBerizin();
    $staffA = penerimaBerizin('staff');
    $staffB = penerimaBerizin('staff');

    $induk = indukUntuk($direktur, 'diproses');

    $anak = collect([$staffA, $staffB])->map(fn ($staff) => Disposisi::create([
        'parent_disposisi_id' => $induk->id,
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'dari_user_id'        => $direktur->id,
        'kepada_user_id'      => $staff->id,
        'instruksi'           => 'Tolong dikerjakan',
        'status'              => 'diproses',
        'tanggal_disposisi'   => now(),
    ]));

    // Anak pertama selesai: induk belum siap dikonfirmasi
    $this->actingAs($staffA)->put('/disposisi/' . $anak[0]->id, ['status' => 'selesai']);
    Notification::assertNothingSentTo($direktur);

    // Anak terakhir selesai: penerima induk diberi tahu
    $this->actingAs($staffB)->put('/disposisi/' . $anak[1]->id, ['status' => 'selesai']);
    Notification::assertSentTo($direktur, DisposisiSiapDikonfirmasi::class);
});

test('pemberitahuan siap konfirmasi hanya dikirim sekali', function () {
    Notification::fake();

    $direktur = penerimaBerizin();
    $staff = penerimaBerizin('staff');

    $induk = indukUntuk($direktur, 'diproses');

    $anak = Disposisi::create([
        'parent_disposisi_id' => $induk->id,
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'dari_user_id'        => $direktur->id,
        'kepada_user_id'      => $staff->id,
        'instruksi'           => 'Tolong dikerjakan',
        'status'              => 'diproses',
        'tanggal_disposisi'   => now(),
    ]);

    $this->actingAs($staff)->put('/disposisi/' . $anak->id, ['status' => 'selesai']);
    $this->actingAs($staff)->put('/disposisi/' . $anak->id, [
        'status'                => 'selesai',
        'catatan_tindak_lanjut' => 'Diperbarui lagi',
    ]);

    Notification::assertSentToTimes($direktur, DisposisiSiapDikonfirmasi::class, 1);
});

test('induk dapat ditandai selesai setelah seluruh anaknya selesai', function () {
    $direktur = penerimaBerizin();
    $staff = penerimaBerizin('staff');

    $induk = indukUntuk($direktur, 'diproses');

    $anak = Disposisi::create([
        'parent_disposisi_id' => $induk->id,
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'dari_user_id'        => $direktur->id,
        'kepada_user_id'      => $staff->id,
        'instruksi'           => 'Tolong dikerjakan',
        'status'              => 'selesai',
        'tanggal_disposisi'   => now(),
    ]);

    $this->actingAs($direktur)->put('/disposisi/' . $induk->id, [
        'status'                => 'selesai',
        'catatan_tindak_lanjut' => 'Hasil sudah diperiksa',
    ])->assertRedirect(route('disposisi.saya'));

    expect($induk->fresh()->status)->toBe('selesai');
});

test('disposisi tanpa anak tetap bisa diselesaikan langsung', function () {
    $staff = penerimaBerizin('staff');

    $disposisi = indukUntuk($staff, 'diproses');

    $this->actingAs($staff)->put('/disposisi/' . $disposisi->id, [
        'status'                => 'selesai',
        'catatan_tindak_lanjut' => 'Selesai dikerjakan',
    ])->assertRedirect(route('disposisi.saya'));

    expect($disposisi->fresh()->status)->toBe('selesai');
});
