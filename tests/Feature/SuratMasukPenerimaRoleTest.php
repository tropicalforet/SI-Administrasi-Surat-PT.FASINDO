<?php

use App\Models\SuratMasuk;
use App\Models\User;
use App\Notifications\SuratMasukDiterima;
use Illuminate\Support\Facades\Notification;

function sekretarisPenerima(): User
{
    return User::factory()->create(['role' => 'sekretaris']);
}

function dataSurat(array $tujuan, string $nomor = '001/ABC/2026'): array
{
    return array_merge([
        'nomor_surat'    => $nomor,
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-05',
        'pengirim'       => 'Dinas Contoh',
        'sifat'          => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'        => 'Undangan rapat',
    ], $tujuan);
}

test('surat dapat ditujukan ke sebuah role', function () {
    $sekretaris = sekretarisPenerima();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSurat([
            'penerima_tipe' => 'role',
            'penerima_role' => 'direktur1',
        ]))
        ->assertRedirect(route('surat-masuk.index'));

    $surat = SuratMasuk::first();

    expect($surat->penerima_role)->toBe('direktur1')
        ->and($surat->penerima_id)->toBeNull()
        ->and($surat->penerima)->toBe('Direktur Keuangan dan Administrasi');
});

test('semua pengguna dengan role tujuan langsung melihat suratnya', function () {
    $sekretaris = sekretarisPenerima();
    $direkturA = User::factory()->create(['role' => 'direktur1']);
    $direkturB = User::factory()->create(['role' => 'direktur1']);
    $direkturLain = User::factory()->create(['role' => 'direktur2']);

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSurat([
        'penerima_tipe' => 'role',
        'penerima_role' => 'direktur1',
    ]));

    // Kedua pemegang role melihat surat tanpa perlu disposisi lebih dulu
    foreach ([$direkturA, $direkturB] as $direktur) {
        $this->actingAs($direktur)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertSee('001/ABC/2026');
    }

    // Role lain tidak melihatnya
    $this->actingAs($direkturLain)
        ->get('/surat-masuk')
        ->assertOk()
        ->assertDontSee('001/ABC/2026');
});

test('seluruh pemegang role menerima notifikasi', function () {
    Notification::fake();

    $sekretaris = sekretarisPenerima();
    $direkturA = User::factory()->create(['role' => 'direktur1']);
    $direkturB = User::factory()->create(['role' => 'direktur1']);
    $lain = User::factory()->create(['role' => 'staff']);

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSurat([
        'penerima_tipe' => 'role',
        'penerima_role' => 'direktur1',
    ]));

    Notification::assertSentTo($direkturA, SuratMasukDiterima::class);
    Notification::assertSentTo($direkturB, SuratMasukDiterima::class);
    Notification::assertNotSentTo($lain, SuratMasukDiterima::class);
});

test('penujuan ke satu pengguna tetap berfungsi dan memberi notifikasi', function () {
    Notification::fake();

    $sekretaris = sekretarisPenerima();
    $staff = User::factory()->create(['role' => 'staff']);
    $staffLain = User::factory()->create(['role' => 'staff']);

    $this->actingAs($sekretaris)->post('/surat-masuk', dataSurat([
        'penerima_tipe' => 'user',
        'penerima_id'   => $staff->id,
    ]));

    $surat = SuratMasuk::first();

    expect($surat->penerima_id)->toBe($staff->id)
        ->and($surat->penerima_role)->toBeNull();

    Notification::assertSentTo($staff, SuratMasukDiterima::class);
    Notification::assertNotSentTo($staffLain, SuratMasukDiterima::class);

    // Rekan serole tidak otomatis melihat surat yang ditujukan perorangan
    $this->actingAs($staffLain)
        ->get('/surat-masuk')
        ->assertOk()
        ->assertDontSee('001/ABC/2026');
});

test('tujuan wajib diisi sesuai tipe yang dipilih', function () {
    $sekretaris = sekretarisPenerima();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSurat(['penerima_tipe' => 'role']))
        ->assertSessionHasErrors('penerima_role');

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSurat(['penerima_tipe' => 'user']))
        ->assertSessionHasErrors('penerima_id');
});

test('role tujuan di luar daftar ditolak', function () {
    $sekretaris = sekretarisPenerima();

    $this->actingAs($sekretaris)
        ->post('/surat-masuk', dataSurat([
            'penerima_tipe' => 'role',
            'penerima_role' => 'administrator',
        ]))
        ->assertSessionHasErrors('penerima_role');
});

test('mengubah tujuan surat memberi notifikasi hanya kepada penerima baru', function () {
    Notification::fake();

    $sekretaris = sekretarisPenerima();
    $direktur = User::factory()->create(['role' => 'direktur1']);
    $direkturBaru = User::factory()->create(['role' => 'direktur2']);

    $surat = SuratMasuk::create([
        'nomor_surat'   => '001/ABC/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat' => '2026-08-01',
        'pengirim'      => 'Dinas Contoh',
        'perihal'       => 'Undangan rapat',
        'status'        => 'baru',
        'penerima_role' => 'direktur1',
        'penerima'      => 'Direktur I',
    ]);

    $this->actingAs($sekretaris)->put('/surat-masuk/' . $surat->id, dataSurat([
        'penerima_tipe' => 'role',
        'penerima_role' => 'direktur2',
    ]));

    expect($surat->fresh()->penerima_role)->toBe('direktur2');

    Notification::assertSentTo($direkturBaru, SuratMasukDiterima::class);
    Notification::assertNotSentTo($direktur, SuratMasukDiterima::class);
});

// ============================================================
// Tujuan berbasis jabatan hanya untuk jabatan tunggal
// ============================================================

test('manager dan pelaksana tidak dapat dipilih sebagai jabatan tujuan', function () {
    $sekretaris = sekretarisPenerima();

    // Satu perusahaan punya beberapa manager dan pelaksana di unit berbeda,
    // sehingga tujuan seperti ini selalu terbaca melebar ke unit lain.
    foreach (['manager', 'staff'] as $jabatan) {
        $this->actingAs($sekretaris)
            ->post('/surat-masuk', dataSurat([
                'penerima_tipe' => 'role',
                'penerima_role' => $jabatan,
            ]))
            ->assertSessionHasErrors('penerima_role');
    }

    expect(SuratMasuk::count())->toBe(0);
});

test('formulir tidak lagi menawarkan manager sebagai jabatan tujuan', function () {
    $sekretaris = sekretarisPenerima();

    $this->actingAs($sekretaris)
        ->get('/surat-masuk/create')
        ->assertOk()
        ->assertSee('Direktur Teknik')
        ->assertDontSee('<option value="manager"', false)
        ->assertDontSee('<option value="staff"', false);
});

test('surat lama bertujuan manager berhenti terbaca seluruh manager', function () {
    $manager = User::factory()->create(['role' => 'manager', 'unit' => 'teknik']);
    $managerLain = User::factory()->create(['role' => 'manager', 'unit' => 'keuangan_administrasi']);

    // Data peninggalan aturan lama, sengaja tidak diubah agar tujuan asli
    // surat tetap tercatat apa adanya.
    $surat = SuratMasuk::create([
        'nomor_surat'    => '099/LAMA/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'pengirim'       => 'Dinas Contoh',
        'perihal'        => 'Surat era aturan lama',
        'status'         => 'baru',
        'penerima_role'  => 'manager',
        'penerima'       => 'Manager',
    ]);

    foreach ([$manager, $managerLain] as $orang) {
        expect($surat->bolehDibacaOleh($orang))->toBeFalse();

        $this->actingAs($orang)
            ->get('/surat-masuk')
            ->assertOk()
            ->assertDontSee('099/LAMA/2026');

        $this->actingAs($orang)
            ->get('/surat-masuk/' . $surat->id)
            ->assertForbidden();
    }
});

test('surat lama tetap terbaca sekretaris dan penerima disposisinya', function () {
    $sekretaris = sekretarisPenerima();
    $direktur = User::factory()->create(['role' => 'direktur1', 'unit' => 'keuangan_administrasi']);
    $manager = User::factory()->create(['role' => 'manager', 'unit' => 'keuangan_administrasi']);

    $surat = SuratMasuk::create([
        'nomor_surat'    => '099/LAMA/2026',
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'pengirim'       => 'Dinas Contoh',
        'perihal'        => 'Surat era aturan lama',
        'status'         => 'baru',
        'penerima_role'  => 'manager',
        'penerima'       => 'Manager',
    ]);

    $surat->disposisis()->create([
        'dari_user_id'      => $direktur->id,
        'kepada_user_id'    => $manager->id,
        'instruksi'         => 'Mohon ditindaklanjuti.',
        'tanggal_disposisi' => '2026-08-02',
        'status'            => 'belum',
    ]);

    // Yang benar-benar diberi tugas tetap dapat membacanya
    expect($surat->bolehDibacaOleh($manager))->toBeTrue()
        ->and($surat->bolehDibacaOleh($sekretaris))->toBeTrue();
});
