<?php

use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\DisposisiDiterima;
use App\Notifications\DisposisiMendekatiTenggat;
use App\Notifications\DisposisiTerlambat;
use Illuminate\Support\Facades\Notification;

function suratMasukUji(string $nomor = 'SM-001/VIII/2026'): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'   => $nomor,
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Permohonan data',
        'status'        => 'baru',
    ]);
}

/**
 * Role selain admin/dirut/sekretaris tidak di-bypass PermissionMiddleware,
 * sehingga izinnya harus diberikan secara eksplisit.
 */
function beriIzin(User $user, string $izin): void
{
    $permission = App\Models\Permission::firstOrCreate(
        ['name' => $izin],
        ['label' => $izin, 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$permission->id]);
}

function disposisiUji(User $dari, User $kepada, ?string $batasWaktu, string $status = 'menunggu'): Disposisi
{
    return Disposisi::create([
        'surat_masuk_id'    => suratMasukUji('SM-' . uniqid())->id,
        'dari_user_id'      => $dari->id,
        'kepada_user_id'    => $kepada->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => $status,
        'tanggal_disposisi' => now(),
        'batas_waktu'       => $batasWaktu,
    ]);
}

test('penerima diberi notifikasi saat disposisi dibuat', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $direktur = User::factory()->create(['role' => 'direktur1', 'unit' => 'teknik']);
    $suratMasuk = suratMasukUji();

    $this->actingAs($dirut)->post('/disposisi', [
        'surat_masuk_id' => $suratMasuk->id,
        'kepada_user_id' => [$direktur->id],
        'instruksi'      => 'Mohon ditindaklanjuti',
        'batas_waktu'    => now()->addDays(3)->toDateString(),
    ])->assertRedirect(route('surat-masuk.index'));

    Notification::assertSentTo($direktur, DisposisiDiterima::class);
});

test('setiap penerima diberi notifikasi saat disposisi diteruskan', function () {
    Notification::fake();

    $direktur = User::factory()->create(['role' => 'direktur1', 'unit' => 'teknik']);
    beriIzin($direktur, 'akses_disposisi');

    $staffA = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);
    $staffB = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $induk = disposisiUji(
        User::factory()->create(['role' => 'dirut']),
        $direktur,
        now()->addDays(5)->toDateString()
    );

    $this->actingAs($direktur)->post('/disposisi/continue', [
        'parent_disposisi_id' => $induk->id,
        'surat_masuk_id'      => $induk->surat_masuk_id,
        'kepada_user_id'      => [$staffA->id, $staffB->id],
        'instruksi'           => 'Tolong dikerjakan',
        'batas_waktu'         => now()->addDays(4)->toDateString(),
    ])->assertRedirect(route('disposisi.saya'));

    Notification::assertSentTo($staffA, DisposisiDiterima::class);
    Notification::assertSentTo($staffB, DisposisiDiterima::class);
});

test('pengingat dikirim untuk disposisi yang jatuh tempo hari ini dan besok', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    disposisiUji($dirut, $staff, now()->toDateString());
    disposisiUji($dirut, $staff, now()->addDay()->toDateString());
    disposisiUji($dirut, $staff, now()->addDays(5)->toDateString()); // masih jauh

    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();

    Notification::assertSentToTimes($staff, DisposisiMendekatiTenggat::class, 2);
});

test('disposisi terlambat dieskalasi ke pemberi disposisi', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    disposisiUji($dirut, $staff, now()->subDays(2)->toDateString());

    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();

    // Pelaksana diberi tahu, pemberi disposisi menerima eskalasi
    Notification::assertSentTo($staff, DisposisiTerlambat::class, function ($notification) {
        return $notification->sebagaiEskalasi === false;
    });

    Notification::assertSentTo($dirut, DisposisiTerlambat::class, function ($notification) {
        return $notification->sebagaiEskalasi === true;
    });
});

test('disposisi yang sudah selesai tidak diingatkan maupun dieskalasi', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    disposisiUji($dirut, $staff, now()->subDays(3)->toDateString(), 'selesai');
    disposisiUji($dirut, $staff, now()->toDateString(), 'selesai');

    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();

    Notification::assertNothingSent();
});

test('notifikasi tenggat tidak dikirim ulang saat perintah berjalan lagi', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    disposisiUji($dirut, $staff, now()->subDay()->toDateString());

    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();
    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();

    Notification::assertSentToTimes($staff, DisposisiTerlambat::class, 1);
    Notification::assertSentToTimes($dirut, DisposisiTerlambat::class, 1);
});

test('disposisi tanpa batas waktu diabaikan', function () {
    Notification::fake();

    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    disposisiUji($dirut, $staff, null);

    $this->artisan('disposisi:periksa-tenggat')->assertSuccessful();

    Notification::assertNothingSent();
});

test('halaman notifikasi menampilkan pemberitahuan milik pengguna', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $disposisi = disposisiUji($dirut, $staff, now()->addDay()->toDateString());
    $staff->notify(new DisposisiDiterima($disposisi));

    $this->actingAs($staff)
        ->get('/notifikasi')
        ->assertOk()
        ->assertSee('Disposisi baru untuk Anda');

    expect($staff->unreadNotifications()->count())->toBe(1);
});

test('membuka notifikasi menandainya sudah dibaca', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $disposisi = disposisiUji($dirut, $staff, now()->addDay()->toDateString());
    $staff->notify(new DisposisiDiterima($disposisi));

    $notifikasi = $staff->notifications()->first();

    $this->actingAs($staff)
        ->get('/notifikasi/' . $notifikasi->id . '/baca')
        ->assertRedirect(route('disposisi.edit', $disposisi->id));

    expect($staff->fresh()->unreadNotifications()->count())->toBe(0);
});

test('pengguna tidak dapat membuka notifikasi milik orang lain', function () {
    $dirut = User::factory()->create(['role' => 'dirut']);
    $staff = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);
    $penyusup = User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);

    $disposisi = disposisiUji($dirut, $staff, now()->addDay()->toDateString());
    $staff->notify(new DisposisiDiterima($disposisi));

    $notifikasi = $staff->notifications()->first();

    $this->actingAs($penyusup)
        ->get('/notifikasi/' . $notifikasi->id . '/baca')
        ->assertNotFound();
});
