<?php

/**
 * Nomor yang sudah terbit harus melekat pada suratnya, bahkan bila pengolahan
 * berkasnya gagal sesudahnya.
 *
 * Di server produksi, shell_exec dimatikan lewat disable_functions. Pemanggilan
 * fungsi yang dimatikan berakibat fatal pada PHP 8 dan tidak dapat diredam
 * tanda @. Karena urutan langkahnya dulu terbalik - berkas diolah dahulu, nomor
 * disimpan kemudian - satu kegagalan membuat penghitung sudah maju sementara
 * suratnya tetap tanpa nomor, meninggalkan lubang pada urutan nomor.
 */

use App\Helpers\NomorDokumenHelper;
use App\Models\Permission;
use App\Models\SuratKeluar;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-08-17 09:00:00'));

    // Berkas yang dibuat tes tidak boleh tertinggal di penyimpanan sungguhan.
    Storage::fake('public');
});

afterEach(function () {
    Carbon::setTestNow();
});

function pihakNomor(string $role, ?string $unit = 'teknik'): User
{
    $user = User::factory()->create(['role' => $role, 'unit' => $unit]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_surat_keluar'],
        ['label' => 'akses_surat_keluar', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function konsepNomor(User $penyusun, string $berkas = null): SuratKeluar
{
    return SuratKeluar::create([
        'dibuat_oleh'     => $penyusun->id,
        'kategori_surat'  => 'SK',
        'unit_verifikasi' => $penyusun->unit,
        'tanggal_surat'   => '2026-08-17',
        'tujuan'          => 'PT. Pupuk Indonesia',
        'perihal'         => 'Perencanaan Pemetaan di Langkat',
        'file'            => $berkas,
        'status'          => 'menunggu_sekretaris',
    ]);
}

test('nomor tetap melekat walau berkas Word gagal diolah', function () {
    $penyusun = pihakNomor('staff');
    $sekretaris = pihakNomor('sekretaris', 'pimpinan');
    pihakNomor('dirut', 'pimpinan');

    // Berkas .docx yang tercatat di basis data tetapi tidak ada di penyimpanan.
    // Keadaan ini membuat penyematan nomor maupun konversi PDF sama-sama gagal,
    // meniru keadaan di peladen yang tidak menyediakan LibreOffice.
    $surat = konsepNomor($penyusun, 'surat_keluar/berkas-tidak-ada.docx');

    $this->actingAs($sekretaris)
        ->put('/surat-keluar/' . $surat->id . '/proses-sekretaris')
        ->assertRedirect();

    $surat->refresh();

    expect($surat->nomor_surat)->toBe('001/FI/SK/VIII/2026')
        ->and($surat->status)->toBe('menunggu_dirut');
});

test('penghitung dan nomor surat tidak pernah berselisih', function () {
    $penyusun = pihakNomor('staff');
    $sekretaris = pihakNomor('sekretaris', 'pimpinan');
    pihakNomor('dirut', 'pimpinan');

    // Tiga surat berturut-turut, seluruhnya dengan berkas yang gagal diolah.
    foreach (range(1, 3) as $i) {
        $surat = konsepNomor($penyusun, 'surat_keluar/hilang-' . $i . '.docx');
        $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');
    }

    $nomor = SuratKeluar::whereNotNull('nomor_surat')->orderBy('id')->pluck('nomor_surat');

    // Tiga nomor terbit, berurutan rapat, tanpa satu pun yang hangus.
    expect($nomor->all())->toBe([
        '001/FI/SK/VIII/2026',
        '002/FI/SK/VIII/2026',
        '003/FI/SK/VIII/2026',
    ]);
});

test('surat tanpa berkas tetap memperoleh nomor', function () {
    $penyusun = pihakNomor('staff');
    $sekretaris = pihakNomor('sekretaris', 'pimpinan');
    pihakNomor('dirut', 'pimpinan');

    $surat = konsepNomor($penyusun, null);

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    expect($surat->fresh()->nomor_surat)->toBe('001/FI/SK/VIII/2026');
});

test('berkas PDF tidak melewati pengolahan dokumen Word', function () {
    $penyusun = pihakNomor('staff');
    $sekretaris = pihakNomor('sekretaris', 'pimpinan');
    pihakNomor('dirut', 'pimpinan');

    Storage::disk('public')->put('surat_keluar/sudah-pdf.pdf', 'isi berkas');

    $surat = konsepNomor($penyusun, 'surat_keluar/sudah-pdf.pdf');

    $this->actingAs($sekretaris)->put('/surat-keluar/' . $surat->id . '/proses-sekretaris');

    $surat->refresh();

    expect($surat->nomor_surat)->toBe('001/FI/SK/VIII/2026')
        ->and($surat->file)->toBe('surat_keluar/sudah-pdf.pdf');
});
