<?php

use App\Models\Permission;
use App\Models\Skpd;
use App\Models\User;
use App\Notifications\SkpdMenungguTindakan;
use Illuminate\Support\Facades\Notification;

function orang(string $role, ?string $unit = null): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_skpd'],
        ['label' => 'akses_skpd', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function dataPenugasan(array $tambahan = []): array
{
    return array_merge([
        'jenis'             => 'perjalanan_dinas',
        'keperluan'         => 'Kunjungan proyek',
        'tujuan_dinas'      => 'Surabaya',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-03',
    ], $tambahan);
}

test('staf punya pintu masuk untuk mengajukan dari daftar SKPD', function () {
    $pegawai = orang('staff', 'teknik');

    // Tanpa tombol ini staf tidak punya cara memulai sama sekali,
    // karena SKPD tidak lagi lahir dari Surat Tugas.
    $this->actingAs($pegawai)
        ->get('/skpd')
        ->assertOk()
        ->assertSee('Ajukan Penugasan')
        ->assertSee(route('skpd.create'), false);
});

test('atasan melihat ajakan membuat penugasan, bukan mengajukan', function () {
    $direktur = orang('direktur2', 'teknik');

    $this->actingAs($direktur)
        ->get('/skpd')
        ->assertOk()
        ->assertSee('Buat Penugasan');
});

test('form staf tidak menawarkan memilih pegawai lain', function () {
    $pegawai = orang('staff', 'teknik');
    User::factory()->create(['name' => 'Orang Lain', 'role' => 'staff', 'unit' => 'teknik']);

    $this->actingAs($pegawai)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Jenis Penugasan')
        ->assertDontSee('Pegawai yang Ditugaskan')
        ->assertDontSee('Orang Lain');
});

test('atasan dapat memilih pegawai yang ditugaskan', function () {
    $direktur = orang('direktur2', 'teknik');
    User::factory()->create(['name' => 'Budi Teknik', 'role' => 'staff', 'unit' => 'teknik']);

    $this->actingAs($direktur)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Pegawai yang Ditugaskan')
        ->assertSee('Budi Teknik');
});

test('pemilik dapat melihat pratinjau dokumennya sendiri sejak draft', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    // Sebelumnya pemilik ditolak selama dokumennya belum sampai ke Dirut
    $respons = $this->actingAs($pegawai)->get('/skpd/' . $skpd->id . '/preview-pdf');

    $respons->assertOk();
    expect($respons->headers->get('content-type'))->toContain('application/pdf');
});

test('pratinjau dokumen yang belum disetujui diberi penanda', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    $isi = $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id . '/preview-pdf')
        ->getContent();

    // Teks tertanam di PDF, jadi cukup pastikan berkasnya terbentuk dan
    // penanda dirender lewat blade-nya.
    expect($isi)->toStartWith('%PDF');

    $html = view('skpd.pdf', [
        'skpd' => $skpd->fresh(),
        'qrCodeBase64' => '',
        'belumDisetujui' => true,
    ])->render();

    expect($html)->toContain('BELUM DISETUJUI');
});

test('pengguna di luar jangkauan tetap ditolak melihat pratinjau', function () {
    $pegawai = orang('staff', 'teknik');
    $orangLain = orang('staff', 'keuangan_administrasi');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    $this->actingAs($orangLain)
        ->get('/skpd/' . $skpd->id . '/preview-pdf')
        ->assertForbidden();
});

test('unduhan tetap hanya untuk dokumen yang sudah disetujui', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id . '/download-pdf')
        ->assertForbidden();
});

test('usulan pegawai ditandai sebagai usulan, bukan penugasan', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));

    $skpd = Skpd::first();

    expect($skpd->asal_usul)->toBe('usulan')
        ->and($skpd->ditugaskan_oleh)->toBeNull()
        ->and($skpd->user_id)->toBe($pegawai->id)
        ->and($skpd->status)->toBe('draft');
});

test('penugasan dari direktur ditandai sebagai penugasan', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($direktur)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'draft',
    ]));

    $skpd = Skpd::first();

    expect($skpd->asal_usul)->toBe('penugasan')
        ->and($skpd->ditugaskan_oleh)->toBe($direktur->id)
        ->and($skpd->user_id)->toBe($pegawai->id)
        ->and($skpd->nama_pegawai)->toBe($pegawai->name);
});

test('simpan dan ajukan menyatukan dua langkah', function () {
    Notification::fake();

    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));

    // Langsung terajukan tanpa perlu membuka detail dan menekan Ajukan
    expect(Skpd::first()->status)->toBe('menunggu_direktur');

    Notification::assertSentTo($direktur, SkpdMenungguTindakan::class);
});

test('usulan pegawai harus lewat direkturnya dulu, tidak langsung ke dirut', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    $this->actingAs($pegawai)->put('/skpd/' . $skpd->id . '/ajukan');

    expect($skpd->fresh()->status)->toBe('menunggu_direktur');
});

test('penugasan direktur atas bawahannya langsung menuju dirut', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');
    orang('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'ajukan',
    ]));

    // Direktur adalah atasan langsungnya, tidak perlu persetujuan siapa pun lagi
    expect(Skpd::first()->status)->toBe('menunggu_dirut');
});

test('penugasan oleh sekretaris tetap perlu persetujuan direktur', function () {
    $sekretaris = orang('sekretaris', 'pimpinan');
    orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($sekretaris)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'ajukan',
    ]));

    // Sekretaris tidak punya wewenang lini atas pegawai teknik
    expect(Skpd::first()->status)->toBe('menunggu_direktur');
});

test('direktur menyetujui usulan lalu naik ke dirut', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');
    orang('dirut', 'pimpinan');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($direktur)->put('/skpd/' . $skpd->id . '/setujui-direktur');

    $skpd->refresh();

    expect($skpd->status)->toBe('menunggu_dirut')
        ->and($skpd->disetujui_direktur_by)->toBe($direktur->id);
});

test('direktur dapat membuka usulan bawahannya untuk disetujui', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($direktur)
        ->get('/skpd/' . $skpd->id)
        ->assertOk()
        ->assertSee('Setujui Usulan');
});

test('direktur unit lain tidak dapat membuka maupun menyetujui', function () {
    $direkturLain = orang('direktur1', 'keuangan_administrasi');
    orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($direkturLain)->get('/skpd/' . $skpd->id)->assertForbidden();

    $this->actingAs($direkturLain)
        ->put('/skpd/' . $skpd->id . '/setujui-direktur')
        ->assertForbidden();

    expect($skpd->fresh()->status)->toBe('menunggu_direktur');
});

test('dirut tidak dapat menyetujui usulan yang belum lewat direktur', function () {
    $dirut = orang('dirut', 'pimpinan');
    orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    expect($skpd->fresh()->status)->toBe('menunggu_direktur');
});

test('dirut menyetujui setelah direktur, dokumen terbit', function () {
    $dirut = orang('dirut', 'pimpinan');
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($direktur)->put('/skpd/' . $skpd->id . '/setujui-direktur');
    $this->actingAs($dirut)->put('/skpd/' . $skpd->id . '/approve');

    expect($skpd->fresh()->status)->toBe('disetujui');
});

test('direktur dapat menolak usulan dengan catatan', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($direktur)->put('/skpd/' . $skpd->id . '/reject', [
        'catatan_revisi' => 'Kunjungan dapat diwakilkan lewat daring.',
    ]);

    $skpd->refresh();

    expect($skpd->status)->toBe('ditolak')
        ->and($skpd->catatan_revisi)->toBe('Kunjungan dapat diwakilkan lewat daring.');
});

test('tugas internal tidak memerlukan tujuan perjalanan', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', [
        'jenis'             => 'internal',
        'keperluan'         => 'Panitia HUT perusahaan',
        'tanggal_berangkat' => '2026-09-01',
        'tanggal_kembali'   => '2026-09-02',
        'aksi'              => 'draft',
    ]);

    $skpd = Skpd::first();

    expect($skpd)->not->toBeNull()
        ->and($skpd->jenis)->toBe('internal')
        ->and($skpd->tujuan_dinas)->toBeNull()
        ->and($skpd->berupaPerjalanan())->toBeFalse();
});

test('perjalanan dinas tetap mewajibkan tujuan', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)
        ->post('/skpd', dataPenugasan(['tujuan_dinas' => '', 'aksi' => 'draft']))
        ->assertSessionHasErrors('tujuan_dinas');

    expect(Skpd::count())->toBe(0);
});

test('durasi dihitung ulang saat tanggal diubah', function () {
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'draft']));
    $skpd = Skpd::first();

    expect($skpd->durasi_hari)->toBe(3);

    $this->actingAs($pegawai)->put('/skpd/' . $skpd->id, dataPenugasan([
        'tanggal_kembali' => '2026-09-05',
    ]));

    expect($skpd->fresh()->durasi_hari)->toBe(5);
});

test('skpd yang sedang diproses tidak dapat diedit', function () {
    $pegawai = orang('staff', 'teknik');
    orang('direktur2', 'teknik');

    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    $this->actingAs($pegawai)
        ->get('/skpd/' . $skpd->id . '/edit')
        ->assertForbidden();
});
