<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\SuratMasukDiterima;

function penerimaSurat(string $role, ?string $unit = null): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratDetail(array $tujuan = []): SuratMasuk
{
    return SuratMasuk::create(array_merge([
        'nomor_surat'      => 'SM-100/VIII/2026',
        'kategori_surat'   => 'SU',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Pendidikan Kota',
        'sifat'            => 'penting',
        'jalur_penerimaan' => 'whatsapp',
        'perihal'          => 'Undangan rapat koordinasi tahunan',
        'status'           => 'baru',
    ], $tujuan));
}

test('penerima yang dituju lewat role dapat membuka detail suratnya', function () {
    $direktur = penerimaSurat('direktur2', 'teknik');

    $surat = suratDetail(['penerima_role' => 'direktur2', 'penerima' => 'Direktur Teknik']);

    $this->actingAs($direktur)
        ->get('/surat-masuk/' . $surat->id)
        ->assertOk()
        ->assertSee('SM-100/VIII/2026')
        ->assertSee('Dinas Pendidikan Kota')
        ->assertSee('Undangan rapat koordinasi tahunan')
        ->assertSee('WhatsApp')
        ->assertSee('Penting');
});

test('penerima perorangan dapat membuka detailnya', function () {
    $staff = penerimaSurat('staff', 'teknik');

    $surat = suratDetail(['penerima_id' => $staff->id, 'penerima' => $staff->name]);

    $this->actingAs($staff)
        ->get('/surat-masuk/' . $surat->id)
        ->assertOk();
});

test('pengguna yang tidak dituju ditolak', function () {
    $orangLain = penerimaSurat('staff', 'keuangan_administrasi');

    $surat = suratDetail(['penerima_role' => 'direktur2', 'penerima' => 'Direktur Teknik']);

    $this->actingAs($orangLain)
        ->get('/surat-masuk/' . $surat->id)
        ->assertForbidden();
});

test('penerima disposisi tetap dapat membuka suratnya', function () {
    $dirut = penerimaSurat('dirut', 'pimpinan');
    $staff = penerimaSurat('staff', 'teknik');

    // Surat ditujukan ke role lain, tetapi staf menerima disposisinya
    $surat = suratDetail(['penerima_role' => 'direktur2', 'penerima' => 'Direktur Teknik']);

    Disposisi::create([
        'surat_masuk_id'    => $surat->id,
        'dari_user_id'      => $dirut->id,
        'kepada_user_id'    => $staff->id,
        'instruksi'         => 'Mohon disiapkan bahannya',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($staff)
        ->get('/surat-masuk/' . $surat->id)
        ->assertOk();
});

test('riwayat disposisi tampil di halaman detail', function () {
    $dirut = penerimaSurat('dirut', 'pimpinan');
    $direktur = penerimaSurat('direktur2', 'teknik');

    $surat = suratDetail(['penerima_role' => 'dirut', 'penerima' => 'Direktur Utama']);

    Disposisi::create([
        'surat_masuk_id'        => $surat->id,
        'dari_user_id'          => $dirut->id,
        'kepada_user_id'        => $direktur->id,
        'instruksi'             => 'Tolong pelajari proposal ini',
        'status'                => 'diproses',
        'tanggal_disposisi'     => now(),
        'catatan_tindak_lanjut' => 'Sedang dikaji tim teknis',
    ]);

    $this->actingAs($dirut)
        ->get('/surat-masuk/' . $surat->id)
        ->assertOk()
        ->assertSee('Tolong pelajari proposal ini')
        ->assertSee('Sedang dikaji tim teknis')
        ->assertSee($direktur->name);
});

test('notifikasi mengantar langsung ke detail suratnya', function () {
    $direktur = penerimaSurat('direktur2', 'teknik');

    $surat = suratDetail(['penerima_role' => 'direktur2', 'penerima' => 'Direktur Teknik']);
    $direktur->notify(new SuratMasukDiterima($surat));

    $notifikasi = $direktur->notifications()->first();

    // Sebelumnya notifikasi hanya mengarah ke daftar, penerima harus mencari sendiri
    $this->actingAs($direktur)
        ->get('/notifikasi/' . $notifikasi->id . '/baca')
        ->assertRedirect(route('surat-masuk.show', $surat->id));
});

test('halaman buat surat tidak tertangkap route detail', function () {
    $sekretaris = User::factory()->create(['role' => 'sekretaris', 'unit' => 'pimpinan']);

    $this->actingAs($sekretaris)
        ->get('/surat-masuk/create')
        ->assertOk()
        ->assertSee('Pilih Role Tujuan');
});

test('tombol disposisikan hanya untuk yang berwenang', function () {
    $dirut = penerimaSurat('dirut', 'pimpinan');
    $pelaksana = penerimaSurat('staff', 'teknik');

    $surat = suratDetail(['penerima_role' => 'staff', 'penerima' => 'Pelaksana / Admin']);

    $this->actingAs($dirut)
        ->get('/surat-masuk/' . $surat->id)
        ->assertSee('Disposisikan');

    // Pelaksana tidak punya bawahan, jadi tidak ditawari mendisposisikan
    $this->actingAs($pelaksana)
        ->get('/surat-masuk/' . $surat->id)
        ->assertOk()
        ->assertDontSee('Disposisikan');
});
