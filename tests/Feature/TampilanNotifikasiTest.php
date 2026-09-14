<?php

use App\Models\User;
use App\Notifications\SuratMasukDiterima;
use App\Models\SuratMasuk;

function penerimaNotifikasi(): User
{
    return User::factory()->create(['role' => 'staff', 'unit' => 'teknik']);
}

function suratUntukNotifikasi(string $nomor): SuratMasuk
{
    return SuratMasuk::create([
        'nomor_surat'    => $nomor,
        'kategori_surat' => 'Undangan',
        'tanggal_surat'  => '2026-08-01',
        'pengirim'       => 'Dinas Contoh',
        'perihal'        => 'Perihal ' . $nomor,
        'status'         => 'baru',
        'penerima'       => 'Penerima',
    ]);
}

test('notifikasi belum dibaca ditandai berbeda dari yang sudah dibaca', function () {
    $user = penerimaNotifikasi();

    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('001/A/2026')));
    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('002/A/2026')));

    // Satu ditandai sudah dibaca
    $user->notifications()->first()->markAsRead();

    $isi = $this->actingAs($user)->get('/notifikasi')->assertOk()->getContent();

    expect($isi)->toContain('data-status="belum-dibaca"')
        ->and($isi)->toContain('data-status="dibaca"');
});

test('jumlah yang belum dibaca ditampilkan di judul halaman', function () {
    $user = penerimaNotifikasi();

    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('001/A/2026')));
    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('002/A/2026')));
    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('003/A/2026')));

    $user->notifications()->first()->markAsRead();

    $this->actingAs($user)
        ->get('/notifikasi')
        ->assertOk()
        ->assertSee('2 belum dibaca');
});

test('penanda jumlah hilang saat semua sudah dibaca', function () {
    $user = penerimaNotifikasi();

    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('001/A/2026')));
    $user->unreadNotifications->markAsRead();

    $isi = $this->actingAs($user)->get('/notifikasi')->assertOk()->getContent();

    expect($isi)->not->toContain('belum dibaca')
        ->and($isi)->not->toContain('data-status="belum-dibaca"');
});

test('notifikasi peninggalan modul lama tidak berujung halaman 404', function () {
    $user = penerimaNotifikasi();

    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('001/A/2026')));

    $notif = $user->notifications()->first();

    // Meniru peninggalan modul Surat Tugas yang rutenya sudah dilepas
    $data = $notif->data;
    $data['url'] = url('/surat-tugas/4');
    $notif->update(['data' => $data]);

    $this->actingAs($user)
        ->get('/notifikasi/' . $notif->id . '/baca')
        ->assertRedirect(route('notifikasi.index'))
        ->assertSessionHas('error');

    // Tetap ditandai terbaca, agar tidak menggantung sebagai "baru" selamanya
    expect($notif->fresh()->read_at)->not->toBeNull();
});

test('yang belum dibaca selalu berada di atas walau lebih lama', function () {
    $user = penerimaNotifikasi();

    // Notifikasi lama yang belum dibaca
    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('001/LAMA/2026')));
    $user->notifications()->latest()->first()->update(['created_at' => now()->subDays(3)]);

    // Notifikasi baru yang sudah dibaca
    $user->notify(new SuratMasukDiterima(suratUntukNotifikasi('002/BARU/2026')));
    $user->notifications()->latest()->first()->markAsRead();

    $isi = $this->actingAs($user)->get('/notifikasi')->assertOk()->getContent();

    // Sebelumnya urutannya murni berdasar waktu, sehingga yang belum dibaca
    // bisa tenggelam di bawah notifikasi lama yang sudah dibaca.
    expect(strpos($isi, 'data-status="belum-dibaca"'))
        ->toBeLessThan(strpos($isi, 'data-status="dibaca"'));
});
