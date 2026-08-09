<?php

use App\Models\Disposisi;
use App\Models\Permission;
use App\Models\SuratMasuk;
use App\Models\User;

function pegawai(string $role, ?string $unit = null, ?string $jabatan = null): User
{
    $user = User::factory()->create([
        'role'    => $role,
        'unit'    => $unit,
        'jabatan' => $jabatan,
    ]);

    $izin = Permission::firstOrCreate(
        ['name' => 'akses_disposisi'],
        ['label' => 'akses_disposisi', 'group' => 'uji']
    );

    $user->permissions()->syncWithoutDetaching([$izin->id]);

    return $user;
}

function suratKomando(): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'      => 'SM-' . uniqid(),
        'kategori_surat'   => 'SU',
        'tanggal_surat'    => '2026-08-01',
        'pengirim'         => 'Dinas Contoh',
        'sifat'            => 'biasa',
        'jalur_penerimaan' => 'kurir',
        'perihal'          => 'Permohonan data',
        'status'           => 'baru',
    ]);
}

test('form disposisi direktur hanya memuat bawahan di unitnya', function () {
    $direkturTeknik = pegawai('direktur2', 'teknik');

    $managerTeknik = User::factory()->create([
        'name' => 'Budi Teknik', 'role' => 'manager', 'unit' => 'teknik',
    ]);
    $adminKeuangan = User::factory()->create([
        'name' => 'Sari Keuangan', 'role' => 'staff', 'unit' => 'keuangan_administrasi',
    ]);

    $this->actingAs($direkturTeknik)
        ->get('/disposisi/' . suratKomando()->id . '/create')
        ->assertOk()
        ->assertSee('Budi Teknik')
        ->assertDontSee('Sari Keuangan');
});

test('direktur tidak dapat mendisposisikan ke unit lain meski lewat POST', function () {
    $direkturTeknik = pegawai('direktur2', 'teknik');
    $adminKeuangan = pegawai('staff', 'keuangan_administrasi');
    $surat = suratKomando();

    $this->actingAs($direkturTeknik)
        ->post('/disposisi', [
            'surat_masuk_id' => $surat->id,
            'kepada_user_id' => [$adminKeuangan->id],
            'instruksi'      => 'Mohon ditindaklanjuti',
        ])
        ->assertSessionHasErrors('kepada_user_id.0');

    expect(Disposisi::count())->toBe(0);
});

test('direktur dapat mendisposisikan ke bawahan di unitnya', function () {
    $direkturTeknik = pegawai('direktur2', 'teknik');
    $managerTeknik = pegawai('manager', 'teknik');
    $surat = suratKomando();

    $this->actingAs($direkturTeknik)
        ->post('/disposisi', [
            'surat_masuk_id' => $surat->id,
            'kepada_user_id' => [$managerTeknik->id],
            'instruksi'      => 'Mohon ditindaklanjuti',
        ])
        ->assertRedirect(route('surat-masuk.index'));

    expect(Disposisi::count())->toBe(1);
});

test('manager kini dapat mendelegasikan ke pelaksana di unitnya', function () {
    $manager = pegawai('manager', 'teknik');
    $pelaksana = pegawai('staff', 'teknik');
    $direktur = pegawai('direktur2', 'teknik');

    $induk = Disposisi::create([
        'surat_masuk_id'    => suratKomando()->id,
        'dari_user_id'      => $direktur->id,
        'kepada_user_id'    => $manager->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($manager)->post('/disposisi/continue', [
        'parent_disposisi_id' => $induk->id,
        'kepada_user_id'      => [$pelaksana->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertRedirect(route('disposisi.saya'));

    expect(Disposisi::where('parent_disposisi_id', $induk->id)->count())->toBe(1);
});

test('manager tidak dapat mendelegasikan ke pelaksana unit lain', function () {
    $manager = pegawai('manager', 'teknik');
    $pelaksanaLain = pegawai('staff', 'keuangan_administrasi');
    $direktur = pegawai('direktur2', 'teknik');

    $induk = Disposisi::create([
        'surat_masuk_id'    => suratKomando()->id,
        'dari_user_id'      => $direktur->id,
        'kepada_user_id'    => $manager->id,
        'instruksi'         => 'Mohon ditindaklanjuti',
        'status'            => 'menunggu',
        'tanggal_disposisi' => now(),
    ]);

    $this->actingAs($manager)->post('/disposisi/continue', [
        'parent_disposisi_id' => $induk->id,
        'kepada_user_id'      => [$pelaksanaLain->id],
        'instruksi'           => 'Tolong dikerjakan',
    ])->assertSessionHasErrors('kepada_user_id.0');

    expect(Disposisi::where('parent_disposisi_id', $induk->id)->count())->toBe(0);
});

test('pelaksana tetap tidak dapat mendisposisikan', function () {
    $pelaksana = pegawai('staff', 'teknik');

    expect($pelaksana->rolesBawahan())->toBe([])
        ->and($pelaksana->bawahanSeunit())->toHaveCount(0);
});

test('pengguna tanpa unit tidak menjangkau siapa pun', function () {
    $direkturTanpaUnit = pegawai('direktur1', null);
    pegawai('staff', 'keuangan_administrasi');

    expect($direkturTanpaUnit->bawahanSeunit())->toHaveCount(0);
});

test('dirut dan sekretaris tetap menjangkau lintas direktorat', function () {
    $dirut = pegawai('dirut', 'pimpinan');
    $direkturKeuangan = pegawai('direktur1', 'keuangan_administrasi');
    $direkturTeknik = pegawai('direktur2', 'teknik');
    $surat = suratKomando();

    $this->actingAs($dirut)
        ->post('/disposisi', [
            'surat_masuk_id' => $surat->id,
            'kepada_user_id' => [$direkturKeuangan->id, $direkturTeknik->id],
            'instruksi'      => 'Mohon ditindaklanjuti',
        ])
        ->assertRedirect(route('surat-masuk.index'));

    expect(Disposisi::count())->toBe(2);
});

test('unit wajib diisi untuk role dalam bagan organisasi', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->post('/users', [
        'name'     => 'Manager Baru',
        'email'    => 'manager@example.com',
        'password' => 'rahasia123',
        'role'     => 'manager',
    ])->assertSessionHasErrors('unit');

    expect(User::where('email', 'manager@example.com')->exists())->toBeFalse();
});

test('unit dan jabatan tersimpan saat membuat pengguna', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->post('/users', [
        'name'     => 'Manager Baru',
        'email'    => 'manager@example.com',
        'password' => 'rahasia123',
        'role'     => 'manager',
        'unit'     => 'teknik',
        'jabatan'  => 'Manager Pengadaan dan Logistik',
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'manager@example.com')->first();

    expect($user->unit)->toBe('teknik')
        ->and($user->jabatan)->toBe('Manager Pengadaan dan Logistik')
        ->and($user->label_jabatan)->toBe('Manager Pengadaan dan Logistik')
        ->and($user->label_unit)->toBe('Teknik');
});

test('dirut dan sekretaris tidak diwajibkan punya unit', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->post('/users', [
        'name'     => 'Sekretaris Baru',
        'email'    => 'sek@example.com',
        'password' => 'rahasia123',
        'role'     => 'sekretaris',
    ])->assertRedirect(route('users.index'));

    expect(User::where('email', 'sek@example.com')->exists())->toBeTrue();
});
