<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('staff tidak dapat mengakses manajemen pengguna', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)->get('/users')->assertForbidden();
    $this->actingAs($staff)->get('/users/create')->assertForbidden();
});

test('staff tidak dapat membuat akun baru berrole dirut', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)->post('/users', [
        'name'     => 'Penyusup',
        'email'    => 'penyusup@example.com',
        'password' => 'rahasia123',
        'role'     => 'dirut',
    ])->assertForbidden();

    expect(User::where('email', 'penyusup@example.com')->exists())->toBeFalse();
});

test('direktur dan dirut juga tidak dapat mengakses manajemen pengguna', function () {
    foreach (['dirut', 'direktur1', 'direktur2', 'sekretaris'] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get('/users')->assertForbidden();
    }
});

test('admin dapat mengakses manajemen pengguna', function () {
    foreach (['admin', 'administrator', 'superadmin'] as $role) {
        $admin = User::factory()->create(['role' => $role]);
        $this->actingAs($admin)->get('/users')->assertOk();
    }
});

test('role di luar daftar yang diizinkan ditolak', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/users', [
        'name'     => 'Role Aneh',
        'email'    => 'aneh@example.com',
        'password' => 'rahasia123',
        'role'     => 'superuser',
    ])->assertSessionHasErrors('role');

    expect(User::where('email', 'aneh@example.com')->exists())->toBeFalse();
});

test('route show pengguna tidak terdaftar dan tidak lagi error 500', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'staff']);

    // URI users/{user} masih dipakai PUT/PATCH/DELETE, sehingga GET ditolak
    // sebagai 405 Method Not Allowed - bukan lagi 500 karena show() tak ada.
    $this->actingAs($admin)
        ->get('/users/' . $target->id)
        ->assertStatus(405);

    expect(Route::has('users.show'))->toBeFalse();
});

test('admin tidak dapat mengubah role akunnya sendiri', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/users/' . $admin->id, [
        'name'  => $admin->name,
        'email' => $admin->email,
        'role'  => 'staff',
    ]);

    expect($admin->fresh()->role)->toBe('admin');
});
