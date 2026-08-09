<?php

use App\Models\User;

test('dashboard menampilkan nama jabatan yang diisi administrator', function () {
    $user = User::factory()->create([
        'role'    => 'manager',
        'unit'    => 'teknik',
        'jabatan' => 'Manager Pengadaan dan Logistik',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Manager Pengadaan dan Logistik')
        ->assertSee('Unit Teknik');
});

test('role tanpa jabatan tetap tampil sebagai nama jabatan yang terbaca', function () {
    $direktur = User::factory()->create([
        'role'    => 'direktur1',
        'unit'    => 'keuangan_administrasi',
        'jabatan' => null,
    ]);

    $this->actingAs($direktur)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Direktur Keuangan dan Administrasi')
        ->assertSee('Unit Keuangan dan Administrasi');
});

test('role manager tidak lagi tampil mentah', function () {
    // Sebelumnya daftar label di dashboard tidak memuat 'manager',
    // sehingga yang tampil adalah nilai kolomnya apa adanya.
    $manager = User::factory()->create(['role' => 'manager', 'unit' => 'teknik']);

    $this->actingAs($manager)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Manager')
        ->assertDontSee('>manager<', false);
});

test('administrator tampil sebagai administrator sistem tanpa unit', function () {
    $admin = User::factory()->create(['role' => 'administrator', 'unit' => null]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Administrator Sistem')
        ->assertDontSee('Unit Belum ditetapkan');
});
