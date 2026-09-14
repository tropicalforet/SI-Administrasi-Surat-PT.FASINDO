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
    foreach ([orang('dirut', 'pimpinan'), orang('direktur2', 'teknik')] as $atasan) {
        $this->actingAs($atasan)
            ->get('/skpd')
            ->assertOk()
            ->assertSee('Buat Penugasan');
    }
});

test('sekretaris diajak mengajukan usulan, bukan menugaskan', function () {
    // Sekretaris tidak punya bawahan di bagan organisasi
    $sekretaris = orang('sekretaris', 'pimpinan');

    $this->actingAs($sekretaris)
        ->get('/skpd')
        ->assertOk()
        ->assertSee('Ajukan Penugasan')
        ->assertDontSee('Buat Penugasan');
});

test('form staf tidak menawarkan memilih pegawai lain', function () {
    $pegawai = orang('staff', 'teknik');
    User::factory()->create(['name' => 'Orang Lain', 'role' => 'staff', 'unit' => 'teknik']);

    $this->actingAs($pegawai)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Tujuan Perjalanan')
        ->assertDontSee('Pegawai yang Ditugaskan')
        ->assertDontSee('Orang Lain');
});

test('dirut dapat memilih siapa pun dalam struktur', function () {
    $dirut = orang('dirut', 'pimpinan');
    User::factory()->create(['name' => 'Budi Teknik', 'role' => 'manager', 'unit' => 'teknik']);
    User::factory()->create(['name' => 'Sari Keuangan', 'role' => 'staff', 'unit' => 'keuangan_administrasi']);

    $this->actingAs($dirut)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Pegawai yang Ditugaskan')
        ->assertSee('Budi Teknik')
        ->assertSee('Sari Keuangan');
});

test('administrator tidak muncul sebagai calon pegawai, di luar bagan', function () {
    $dirut = orang('dirut', 'pimpinan');
    User::factory()->create(['name' => 'Petugas Sistem', 'role' => 'administrator', 'unit' => null]);

    $this->actingAs($dirut)
        ->get('/skpd/create')
        ->assertOk()
        ->assertDontSee('Petugas Sistem');
});

test('direktur hanya ditawari bawahan direktoratnya sendiri', function () {
    $direktur = orang('direktur2', 'teknik');

    User::factory()->create(['name' => 'Budi Teknik', 'role' => 'manager', 'unit' => 'teknik']);
    User::factory()->create(['name' => 'Sari Keuangan', 'role' => 'staff', 'unit' => 'keuangan_administrasi']);
    User::factory()->create(['name' => 'Dirut Perusahaan', 'role' => 'dirut', 'unit' => 'pimpinan']);

    $this->actingAs($direktur)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Pegawai yang Ditugaskan')
        ->assertSee('Budi Teknik')
        ->assertDontSee('Sari Keuangan')
        ->assertDontSee('Dirut Perusahaan');
});

test('sekretaris tidak menugaskan siapa pun', function () {
    $sekretaris = orang('sekretaris', 'pimpinan');
    User::factory()->create(['name' => 'Budi Teknik', 'role' => 'manager', 'unit' => 'teknik']);

    $this->actingAs($sekretaris)
        ->get('/skpd/create')
        ->assertOk()
        ->assertDontSee('Pegawai yang Ditugaskan')
        ->assertDontSee('Budi Teknik');
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
    $pegawai = orang('manager', 'teknik');

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

test('penugasan langsung dari dirut tidak singgah ke direktur', function () {
    $dirut = orang('dirut', 'pimpinan');
    orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');

    $this->actingAs($dirut)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'ajukan',
    ]));

    // Dirut adalah keputusan terakhir, tidak meminta izin bawahannya
    expect(Skpd::first()->status)->toBe('menunggu_dirut');
});

test('penugasan direktur atas bawahannya langsung menuju dirut', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('manager', 'teknik');
    orang('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'ajukan',
    ]));

    // Direktur sudah menyatakan setuju lewat penugasannya, tidak perlu
    // menyetujui dokumen yang ia terbitkan sendiri.
    expect(Skpd::first()->status)->toBe('menunggu_dirut');
});

// ============================================================
// Batas kewenangan menugaskan
// ============================================================

test('direktur tidak dapat menugaskan pegawai direktorat lain', function () {
    $direkturTeknik = orang('direktur2', 'teknik');
    $orangKeuangan = orang('staff', 'keuangan_administrasi');

    $this->actingAs($direkturTeknik)
        ->post('/skpd', dataPenugasan([
            'user_id' => $orangKeuangan->id,
            'aksi'    => 'draft',
        ]))
        ->assertSessionHasErrors('user_id');

    expect(Skpd::count())->toBe(0);
});

test('direktur tidak dapat menugaskan direktur lain maupun dirut', function () {
    $direkturTeknik = orang('direktur2', 'teknik');
    $direkturKeuangan = orang('direktur1', 'keuangan_administrasi');
    $dirut = orang('dirut', 'pimpinan');

    foreach ([$direkturKeuangan, $dirut] as $diLuarJangkauan) {
        $this->actingAs($direkturTeknik)
            ->post('/skpd', dataPenugasan([
                'user_id' => $diLuarJangkauan->id,
                'aksi'    => 'draft',
            ]))
            ->assertSessionHasErrors('user_id');
    }

    expect(Skpd::count())->toBe(0);
});

test('dirut dapat menugaskan siapa pun tanpa terkecuali', function () {
    $dirut = orang('dirut', 'pimpinan');

    $semua = [
        orang('direktur1', 'keuangan_administrasi'),
        orang('direktur2', 'teknik'),
        orang('sekretaris', 'pimpinan'),
        orang('manager', 'teknik'),
        orang('staff', 'keuangan_administrasi'),
    ];

    foreach ($semua as $pegawai) {
        $this->actingAs($dirut)
            ->post('/skpd', dataPenugasan([
                'user_id' => $pegawai->id,
                'aksi'    => 'draft',
            ]))
            ->assertSessionHasNoErrors();
    }

    expect(Skpd::count())->toBe(count($semua));
});

test('direktur mengusulkan penugasan untuk dirinya sendiri, langsung ke dirut', function () {
    $direktur = orang('direktur2', 'teknik');
    orang('dirut', 'pimpinan');

    $this->actingAs($direktur)->post('/skpd', dataPenugasan([
        'user_id' => $direktur->id,
        'aksi'    => 'ajukan',
    ]));

    $skpd = Skpd::first();

    // Dokumen atas nama diri sendiri tetap usulan, dan tidak singgah ke
    // tahap direktur - ia akan menyetujui usulannya sendiri.
    expect($skpd->user_id)->toBe($direktur->id)
        ->and($skpd->asal_usul)->toBe('usulan')
        ->and($skpd->ditugaskan_oleh)->toBeNull()
        ->and($skpd->status)->toBe('menunggu_dirut');
});

test('usulan sekretaris juga langsung ke dirut karena tak berdirektur', function () {
    $sekretaris = orang('sekretaris', 'pimpinan');
    orang('direktur2', 'teknik');
    orang('dirut', 'pimpinan');

    // Sekretaris berada di unit pimpinan, tidak ada direktur di atasnya.
    // Sebelumnya pengajuan ini mentok dengan pesan "direktur belum ada".
    $this->actingAs($sekretaris)->post('/skpd', dataPenugasan([
        'user_id' => $sekretaris->id,
        'aksi'    => 'ajukan',
    ]));

    expect(Skpd::first()->status)->toBe('menunggu_dirut');
});

test('sekretaris hanya dapat mengajukan untuk dirinya sendiri', function () {
    $sekretaris = orang('sekretaris', 'pimpinan');
    $pegawai = orang('staff', 'teknik');

    // Pegawai yang dikirim di request diabaikan, bukan ditolak, agar
    // dokumennya tetap terbentuk sebagai usulan atas namanya sendiri.
    $this->actingAs($sekretaris)->post('/skpd', dataPenugasan([
        'user_id' => $pegawai->id,
        'aksi'    => 'draft',
    ]));

    $skpd = Skpd::first();

    expect($skpd->user_id)->toBe($sekretaris->id)
        ->and($skpd->asal_usul)->toBe('usulan')
        ->and($skpd->ditugaskan_oleh)->toBeNull();
});

test('direktur tetap menjadi penyetuju usulan bawahannya', function () {
    $direktur = orang('direktur2', 'teknik');
    $pegawai = orang('staff', 'teknik');
    orang('dirut', 'pimpinan');

    // Satu-satunya jalur penugasan bawahan kini: pegawai mengusulkan,
    // direktur menilai, dirut memutuskan.
    $this->actingAs($pegawai)->post('/skpd', dataPenugasan(['aksi' => 'ajukan']));
    $skpd = Skpd::first();

    expect($skpd->status)->toBe('menunggu_direktur');

    $this->actingAs($direktur)->put('/skpd/' . $skpd->id . '/setujui-direktur');

    expect($skpd->fresh()->status)->toBe('menunggu_dirut');
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

test('formulir tidak lagi menawarkan jenis penugasan', function () {
    $pegawai = orang('staff', 'teknik');

    // SKPD kembali menjadi satu macam dokumen saja
    $this->actingAs($pegawai)
        ->get('/skpd/create')
        ->assertOk()
        ->assertSee('Tujuan Perjalanan')
        ->assertSee('Tanggal Berangkat')
        ->assertDontSee('Jenis Penugasan')
        ->assertDontSee('Tugas Internal');
});

test('setiap skpd wajib menyebut tujuan perjalanan', function () {
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
