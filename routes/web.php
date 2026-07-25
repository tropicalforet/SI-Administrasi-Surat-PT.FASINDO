<?php

use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use App\Models\Disposisi;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SkpdController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\SuratMasukController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;


Route::get('/', function () {
    return redirect('/dashboard');
});

 Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard'); 

Route::get('/surat-keluar/{surat_keluar}/verify', [SuratKeluarController::class, 'verify'])->name('surat-keluar.verify');
      
Route::middleware(['auth'])->group(function () {
    Route::get(
    '/monitoring-disposisi',
    [DisposisiController::class, 'monitoring']
)->name('disposisi.monitoring');

Route::get(
    '/monitoring-disposisi/{disposisi}',
    [DisposisiController::class, 'showMonitoring']
)->name('disposisi.monitoring.show');

Route::get(
    '/disposisi/{disposisi}/continue',
    [DisposisiController::class,'continue']
)->name('disposisi.continue');

Route::post(
    '/disposisi/continue',
    [DisposisiController::class,'continueStore']
)->name('disposisi.continue.store');

Route::get(
    '/activity-log',
    [ActivityLogController::class,'index']
)->name('activity.index');

Route::delete(
    '/activity-log/clear',
    [ActivityLogController::class,'clear']
)->name('activity.clear');


    /*
    |--------------------------------------------------------------------------
    | Surat Masuk
    |--------------------------------------------------------------------------
    */
    Route::resource(
        'surat-masuk',
        SuratMasukController::class
    );
    Route::delete(
        '/surat-masuk-clear',
        [SuratMasukController::class, 'clear']
    )->name('surat-masuk.clear');

    /*
    |--------------------------------------------------------------------------
    | Surat Keluar
    |--------------------------------------------------------------------------
    */
    Route::resource(
        'surat-keluar',
        SuratKeluarController::class
    );
    Route::delete(
        '/surat-keluar-clear',
        [SuratKeluarController::class, 'clear']
    )->name('surat-keluar.clear');
    Route::put(
    '/surat-keluar/{surat_keluar}/submit',
    [SuratKeluarController::class, 'submit']
    )->name('surat-keluar.submit');

    Route::put(
    '/surat-keluar/{surat_keluar}/approve',
    [SuratKeluarController::class, 'approve']
    )->name('surat-keluar.approve');

    Route::put(
    '/surat-keluar/{surat_keluar}/reject',
    [SuratKeluarController::class, 'reject']
    )->name('surat-keluar.reject');

    Route::get(
    '/surat-keluar/{surat_keluar}/download',
    [SuratKeluarController::class, 'download']
    )->name('surat-keluar.download');

    /*
    |--------------------------------------------------------------------------
    | SKPD
    |--------------------------------------------------------------------------
    */
    Route::resource(
        'skpd',
        SkpdController::class
    );

    Route::get(
        '/skpd/{skpd}/download-pdf',
        [SkpdController::class, 'downloadPdf']
    )->name('skpd.download-pdf');

    Route::get(
        '/skpd/{skpd}/preview-pdf',
        [SkpdController::class, 'previewPdf']
    )->name('skpd.preview-pdf');

    Route::get(
        '/skpd/{skpd}/verify',
        [SkpdController::class, 'verify']
    )->name('skpd.verify');

    Route::put(
        '/skpd/{skpd}/approve',
        [SkpdController::class, 'approve']
    )->name('skpd.approve')->middleware(['role:dirut']);

    Route::put(
        '/skpd/{skpd}/reject',
        [SkpdController::class, 'reject']
    )->name('skpd.reject')->middleware(['role:dirut']);

    /*
    |--------------------------------------------------------------------------
    | Disposisi
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/disposisi/{suratMasuk}/create',
        [DisposisiController::class, 'create']
    )->name('disposisi.create');

    Route::post(
        '/disposisi',
        [DisposisiController::class, 'store']
    )->name('disposisi.store');

    Route::delete(
        '/disposisi/{disposisi}',
        [DisposisiController::class, 'destroy']
    )->name('disposisi.destroy');

    /*
    |--------------------------------------------------------------------------
    | Test Role
    |--------------------------------------------------------------------------
    */

    Route::get('/test-dirut', function () {
        return 'Halaman Direktur Utama';
    })->middleware(['role:dirut']);

    Route::get('/test-sekretaris', function () {
        return 'Halaman Sekretaris';
    })->middleware(['role:sekretaris']);

    Route::get(
    '/disposisi-saya',
    [DisposisiController::class, 'index']
    )->name('disposisi.saya');

    Route::get(
    '/disposisi/{disposisi}/edit',
    [DisposisiController::class, 'edit']
)->name('disposisi.edit');
/*
|--------------------------------------------------------------------------
| Manajemen User
|--------------------------------------------------------------------------
*/

Route::resource(
    'users',
    App\Http\Controllers\UserController::class
);

Route::put(
    '/disposisi/{disposisi}',
    [DisposisiController::class, 'update']
)->name('disposisi.update');

/*
|--------------------------------------------------------------------------
| Laporan (Reports)
|--------------------------------------------------------------------------
*/
Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/surat-masuk', [App\Http\Controllers\ReportController::class, 'suratMasuk'])->name('surat-masuk');
    Route::get('/surat-masuk/pdf', [App\Http\Controllers\ReportController::class, 'suratMasukPdf'])->name('surat-masuk.pdf');
    
    Route::get('/surat-keluar', [App\Http\Controllers\ReportController::class, 'suratKeluar'])->name('surat-keluar');
    Route::get('/surat-keluar/pdf', [App\Http\Controllers\ReportController::class, 'suratKeluarPdf'])->name('surat-keluar.pdf');
    
    Route::get('/disposisi', [App\Http\Controllers\ReportController::class, 'disposisi'])->name('disposisi');
    Route::get('/disposisi/pdf', [App\Http\Controllers\ReportController::class, 'disposisiPdf'])->name('disposisi.pdf');
    
    Route::get('/skpd', [App\Http\Controllers\ReportController::class, 'skpd'])->name('skpd');
    Route::get('/skpd/pdf', [App\Http\Controllers\ReportController::class, 'skpdPdf'])->name('skpd.pdf');
});

});

require __DIR__ . '/auth.php';