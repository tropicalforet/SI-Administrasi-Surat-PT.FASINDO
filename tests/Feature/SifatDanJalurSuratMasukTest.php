<?php

use App\Models\SuratMasuk;
use App\Models\User;

function sekretarisSifat(): User
{
    return User::factory()->create(['role' => 'sekretaris']);
}

function dataSuratSifat(array $tambahan = [], string $nomor = '001/ABC/2026'): array
{
    return array_merge([
        'nomor_surat'      => $nomor,
        'kategori_surat'   => 'SU',
        'tanggal_surat'    => '2026-08-05',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'penting',
        'jalur_penerimaan' => 'whatsapp',
        'penerima_tipe'    => 'role',
        'penerima_role'    => 'direktur1',
        'perihal'          => 'Undangan rapat',
    ], $tambahan);
}

test('sifat dan jalur penerimaan tersimpan', function () {
    $this->actingAs(sekretarisSifat())
        ->post('/surat-masuk', dataSuratSifat())
        ->assertRedirect(route('surat-masuk.index'));

    $surat = SuratMasuk::first();

    expect($surat->sifat)->toBe('penting')
        ->and($surat->jalur_penerimaan)->toBe('whatsapp')
        ->and($surat->label_sifat)->toBe('Penting')
        ->and($surat->label_jalur)->toBe('WhatsApp');
});

test('sifat wajib diisi dan hanya menerima nilai yang dikenal', function () {
    $sekretaris = sekretarisSifat();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratSifat(['sifat' => '']))
        ->assertSessionHasErrors('sifat');

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratSifat(['sifat' => 'darurat']))
        ->assertSessionHasErrors('sifat');
});

test('jalur penerimaan wajib diisi dan hanya menerima nilai yang dikenal', function () {
    $sekretaris = sekretarisSifat();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratSifat(['jalur_penerimaan' => '']))
        ->assertSessionHasErrors('jalur_penerimaan');

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSuratSifat(['jalur_penerimaan' => 'merpati']))
        ->assertSessionHasErrors('jalur_penerimaan');
});

test('daftar menandai surat penting dan segera, tidak menandai yang biasa', function () {
    $sekretaris = sekretarisSifat();

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSuratSifat(
        ['sifat' => 'segera', 'jalur_penerimaan' => 'kurir'],
        '001/SEGERA/2026'
    ));

    $this->actingAs($sekretaris)
        ->get('/surat-masuk')
        ->assertOk()
        ->assertSee('Segera')
        ->assertSee('via Kurir / Ekspedisi');
});

test('laporan dapat disaring berdasarkan sifat', function () {
    $sekretaris = sekretarisSifat();

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSuratSifat(
        ['sifat' => 'segera'],
        '001/SEGERA/2026'
    ));
    $this->actingAs($sekretaris)->post('/surat-masuk', dataSuratSifat(
        ['sifat' => 'biasa'],
        '002/BIASA/2026'
    ));

    $this->actingAs($sekretaris)
        ->get('/laporan/surat-masuk?sifat=segera')
        ->assertOk()
        ->assertSee('001/SEGERA/2026')
        ->assertDontSee('002/BIASA/2026');
});

test('surat lama tanpa jalur penerimaan tetap tampil wajar', function () {
    $sekretaris = sekretarisSifat();

    SuratMasuk::create([
        'nomor_surat'   => '999/LAMA/2026',
        'kategori_surat' => 'SU',
        'tanggal_surat' => '2026-01-01',
        'pengirim'      => 'Dinas Lama',
        'perihal'       => 'Surat lama',
        'status'        => 'baru',
    ]);

    $surat = SuratMasuk::where('nomor_surat', '999/LAMA/2026')->first();

    // Default kolom berlaku, jalur kosong ditampilkan sebagai '-'
    expect($surat->sifat)->toBe('biasa')
        ->and($surat->jalur_penerimaan)->toBeNull()
        ->and($surat->label_jalur)->toBe('-');

    $this->actingAs($sekretaris)->get('/surat-masuk')->assertOk();
});

test('laporan menampilkan surat yang ditujukan ke role walau belum didisposisikan', function () {
    $sekretaris = sekretarisSifat();
    $direktur = User::factory()->create(['role' => 'direktur1']);

    // Role direktur tidak di-bypass PermissionMiddleware, izinnya diberikan eksplisit
    $izin = App\Models\Permission::firstOrCreate(
        ['name' => 'akses_laporan_surat_masuk'],
        ['label' => 'akses_laporan_surat_masuk', 'group' => 'uji']
    );
    $direktur->permissions()->syncWithoutDetaching([$izin->id]);

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSuratSifat());

    $this->actingAs($direktur)
        ->get('/laporan/surat-masuk')
        ->assertOk()
        ->assertSee('001/ABC/2026');
});
