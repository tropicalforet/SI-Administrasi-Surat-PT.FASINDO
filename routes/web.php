<?php

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SkpdController;
use App\Http\Controllers\SuratMasukController;
use App\Http\Controllers\SuratKeluarController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {

    $totalSuratMasuk = SuratMasuk::count();

    $totalSuratKeluar = SuratKeluar::count();

    $totalDraft = SuratKeluar::where('status', 'draft')->count();

    $totalDikirim = SuratKeluar::where('status', 'dikirim')->count();

    $totalSelesai = SuratKeluar::where('status', 'selesai')->count();

    return view('dashboard', compact(
        'totalSuratMasuk',
        'totalSuratKeluar',
        'totalDraft',
        'totalDikirim',
        'totalSelesai'
    ));

})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth'])->group(function () {
    Route::resource('surat-masuk', SuratMasukController::class);

    Route::resource('surat-keluar', SuratKeluarController::class);

    Route::resource('skpd', SkpdController::class);

    Route::get(
        '/skpd/{skpd}/download-pdf',
        [SkpdController::class, 'downloadPdf']
    )->name('skpd.download-pdf');
});





require __DIR__.'/auth.php';
