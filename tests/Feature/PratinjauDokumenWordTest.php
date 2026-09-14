<?php

/**
 * Dokumen Word ditampilkan lewat penampil Microsoft Office Online.
 *
 * Peramban tidak dapat membuka .docx, dan konversi ke PDF memerlukan
 * LibreOffice yang tidak tersedia pada shared hosting. Dua hal yang dikunci
 * di sini: penampil hanya dipakai bila alamat situs dapat dijangkau dari luar,
 * dan alamat berkasnya membawa penanda versi supaya dokumen sesudah
 * ditandatangani tidak tersaji dari simpanan render yang lama.
 */

use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan dipalsukan agar berkas yang dibuat tes tidak tertinggal di
 * storage/app/public yang sungguhan. Tanpa ini, berkas sisa satu tes akan
 * mengubah cabang yang diambil tes lain pada putaran berikutnya.
 */
beforeEach(function () {
    Storage::fake('public');
});

function pihakPratinjau(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratPratinjau(User $penyusun, string $berkas, string $status = 'menunggu_dirut', ?string $nomor = null): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'nomor_surat'     => $nomor,
        'kategori_surat'  => 'SK',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-17',
        'tujuan'          => 'PT. Pupuk Indonesia',
        'perihal'         => 'Perencanaan Pemetaan di Langkat',
        'file'            => $berkas,
        'status'          => $status,
    ]);
}

test('dokumen word ditampilkan lewat penampil office online', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');
    $surat = suratPratinjau($penyusun, 'surat_keluar/konsep.docx');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertSee('view.officeapps.live.com/op/embed.aspx', false)
        ->assertSee('Unduh Berkas DOCX Asli');
});

test('alamat berkas membawa penanda versi agar render lama tidak tersaji', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');
    $surat = suratPratinjau($penyusun, 'surat_keluar/konsep.docx');

    $isi = $this->actingAs($penyusun)->get('/surat-keluar/' . $surat->id)->assertOk()->getContent();

    // Penanda versi diambil dari waktu perubahan terakhir suratnya.
    expect($isi)->toContain(urlencode('?v=' . $surat->updated_at->timestamp));
});

test('penanda versi berubah setelah surat ditandatangani', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');
    $dirut = pihakPratinjau('dirut', 'pimpinan');
    pihakPratinjau('sekretaris', 'pimpinan');

    $surat = suratPratinjau($penyusun, 'surat_keluar/konsep.docx', 'menunggu_dirut', '004/FI/SK/VIII/2026');

    $sebelum = $surat->updated_at->timestamp;

    $this->travel(5)->seconds();
    $this->actingAs($dirut)->put('/surat-keluar/' . $surat->id . '/approve');

    $isi = $this->actingAs($penyusun)->get('/surat-keluar/' . $surat->id)->assertOk()->getContent();

    // Berbeda dari sebelumnya, sehingga penampil Microsoft mengambil ulang
    // berkasnya dan memperlihatkan tanda tangan yang baru tersemat.
    expect($surat->fresh()->updated_at->timestamp)->toBeGreaterThan($sebelum)
        ->and($isi)->toContain(urlencode('?v=' . $surat->fresh()->updated_at->timestamp));
});

test('keterangan pratinjau membedakan draf dan dokumen final', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');

    $draf = suratPratinjau($penyusun, 'surat_keluar/a.docx', 'menunggu_dirut');
    $final = suratPratinjau($penyusun, 'surat_keluar/b.docx', 'terkirim', '004/FI/SK/VIII/2026');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $draf->id)
        ->assertSee('belum bernomor dan belum ditandatangani');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $final->id)
        ->assertSee('Nomor, E-Sign, dan QR Code sudah tersemat');
});

test('penampil office tidak dipakai saat dijalankan di komputer sendiri', function () {
    config(['app.url' => 'http://127.0.0.1:8000']);

    $penyusun = pihakPratinjau('staff');
    $surat = suratPratinjau($penyusun, 'surat_keluar/konsep.docx');

    // Penampil Microsoft mengambil berkas dari luar, jadi mustahil menjangkau
    // alamat lokal. Yang tampil kembali menjadi kotak keterangan biasa.
    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertDontSee('view.officeapps.live.com', false)
        ->assertSee('Dokumen Word (DOCX) Terdeteksi');
});

test('berkas PDF tetap ditampilkan langsung tanpa penampil luar', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');
    $surat = suratPratinjau($penyusun, 'surat_keluar/final.pdf');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertDontSee('view.officeapps.live.com', false);
});

test('PDF pendamping lebih diutamakan daripada penampil luar', function () {
    config(['app.url' => 'https://maroon-rook-249302.hostingersite.com']);

    $penyusun = pihakPratinjau('staff');

    // Bila konversi ke PDF pernah berhasil, dokumennya ditampilkan dari PDF itu
    // sendiri - lebih rapi dan tidak perlu mengirim berkas ke pihak luar.
    Storage::disk('public')->put('surat_keluar/konsep.pdf', 'isi pdf');

    $surat = suratPratinjau($penyusun, 'surat_keluar/konsep.docx');

    $this->actingAs($penyusun)
        ->get('/surat-keluar/' . $surat->id)
        ->assertOk()
        ->assertDontSee('view.officeapps.live.com', false)
        ->assertSee('Pratinjau Draf Dokumen Word (Read-Only)');
});
