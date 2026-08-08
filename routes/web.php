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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuratTugasController;
use App\Http\Controllers\NotifikasiController;


Route::get('/', function () {
    return redirect('/dashboard');
});

 Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard'); 

Route::get('/surat-keluar/verify/{token}', [SuratKeluarController::class, 'verify'])->name('surat-keluar.verify');
Route::get('/skpd/verify/{token}', [SkpdController::class, 'verify'])->name('skpd.verify');
      
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profil Pengguna (Semua Role)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Notifikasi (Semua Role)
    |--------------------------------------------------------------------------
    */
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::delete('/notifikasi/{id}', [NotifikasiController::class, 'destroy'])->name('notifikasi.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::middleware('permission:akses_monitoring')->group(function () {
        Route::get(
            '/monitoring-disposisi',
            [DisposisiController::class, 'monitoring']
        )->name('disposisi.monitoring');

        Route::get(
            '/monitoring-disposisi/{disposisi}',
            [DisposisiController::class, 'showMonitoring']
        )->name('disposisi.monitoring.show');
    });

Route::middleware('permission:akses_disposisi')->group(function () {
    Route::get(
        '/disposisi/{disposisi}/continue',
        [DisposisiController::class,'continue']
    )->name('disposisi.continue');

    Route::post(
        '/disposisi/continue',
        [DisposisiController::class,'continueStore']
    )->name('disposisi.continue.store');
});

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
    Route::get('/surat-masuk', [SuratMasukController::class, 'index'])->name('surat-masuk.index');
    
    Route::middleware('permission:akses_surat_masuk')->group(function () {
        Route::get('/surat-masuk/create', [SuratMasukController::class, 'create'])->name('surat-masuk.create');
        Route::post('/surat-masuk', [SuratMasukController::class, 'store'])->name('surat-masuk.store');
        Route::get('/surat-masuk/{surat_masuk}/edit', [SuratMasukController::class, 'edit'])->name('surat-masuk.edit');
        Route::put('/surat-masuk/{surat_masuk}', [SuratMasukController::class, 'update'])->name('surat-masuk.update');
        Route::delete('/surat-masuk/{surat_masuk}', [SuratMasukController::class, 'destroy'])->name('surat-masuk.destroy');

        Route::delete(
            '/surat-masuk-clear',
            [SuratMasukController::class, 'clear']
        )->name('surat-masuk.clear');
    });

    /*
    |--------------------------------------------------------------------------
    | Surat Keluar
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:akses_surat_keluar')->group(function () {
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
    });

    /*
    |--------------------------------------------------------------------------
    | SKPD & Surat Tugas
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:akses_skpd')->group(function () {
        Route::resource('surat-tugas', SuratTugasController::class);
        Route::put('/surat-tugas/{surat_tugas}/approve', [SuratTugasController::class, 'approve'])->name('surat-tugas.approve');

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

        Route::put(
            '/skpd/{skpd}/approve',
            [SkpdController::class, 'approve']
        )->name('skpd.approve')->middleware(['role:dirut']);

        Route::put(
            '/skpd/{skpd}/reject',
            [SkpdController::class, 'reject']
        )->name('skpd.reject')->middleware(['role:dirut']);
    });

    /*
    |--------------------------------------------------------------------------
    | Disposisi
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:akses_disposisi')->group(function () {
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
    });

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

    Route::middleware('permission:akses_disposisi')->group(function () {
        Route::get(
            '/disposisi-saya',
            [DisposisiController::class, 'index']
        )->name('disposisi.saya');

        Route::get(
            '/disposisi/{disposisi}/edit',
            [DisposisiController::class, 'edit']
        )->name('disposisi.edit');
    });
/*
|--------------------------------------------------------------------------
| Manajemen User
|--------------------------------------------------------------------------
*/

// UserController tidak menyediakan show(); detail pengguna dilihat lewat form edit.
Route::resource(
    'users',
    App\Http\Controllers\UserController::class
)->except(['show'])->middleware('role:admin,administrator,superadmin');

/*
|--------------------------------------------------------------------------
| Arsip Terhapus (Pemulihan Dokumen)
|--------------------------------------------------------------------------
*/
Route::middleware('role:admin,administrator,superadmin')->group(function () {
    Route::get('/arsip-terhapus', [App\Http\Controllers\ArsipTerhapusController::class, 'index'])->name('arsip.index');
    Route::post('/arsip-terhapus/{jenis}/{id}/pulihkan', [App\Http\Controllers\ArsipTerhapusController::class, 'restore'])->name('arsip.restore');
    Route::delete('/arsip-terhapus/{jenis}/{id}', [App\Http\Controllers\ArsipTerhapusController::class, 'forceDelete'])->name('arsip.force-delete');
});

Route::middleware('permission:akses_disposisi')->group(function () {
    Route::put(
        '/disposisi/{disposisi}',
        [DisposisiController::class, 'update']
    )->name('disposisi.update');
});

/*
|--------------------------------------------------------------------------
| Laporan (Reports)
|--------------------------------------------------------------------------
*/
Route::prefix('laporan')->name('laporan.')->group(function () {

    // Laporan Surat Masuk & Keluar
    Route::middleware('permission:akses_laporan_surat_masuk')->group(function () {
        Route::get('/surat-masuk', [App\Http\Controllers\ReportController::class, 'suratMasuk'])->name('surat-masuk');
        Route::get('/surat-masuk/pdf', [App\Http\Controllers\ReportController::class, 'suratMasukPdf'])->name('surat-masuk.pdf');
    });

    Route::middleware('permission:akses_laporan_surat_keluar')->group(function () {
        Route::get('/surat-keluar', [App\Http\Controllers\ReportController::class, 'suratKeluar'])->name('surat-keluar');
        Route::get('/surat-keluar/pdf', [App\Http\Controllers\ReportController::class, 'suratKeluarPdf'])->name('surat-keluar.pdf');
    });

    // Laporan Disposisi
    Route::middleware('permission:akses_laporan_disposisi')->group(function () {
        Route::get('/disposisi', [App\Http\Controllers\ReportController::class, 'disposisi'])->name('disposisi');
        Route::get('/disposisi/pdf', [App\Http\Controllers\ReportController::class, 'disposisiPdf'])->name('disposisi.pdf');
    });

    // Laporan SKPD
    Route::middleware('permission:akses_laporan_skpd')->group(function () {
        Route::get('/skpd', [App\Http\Controllers\ReportController::class, 'skpd'])->name('skpd');
        Route::get('/skpd/pdf', [App\Http\Controllers\ReportController::class, 'skpdPdf'])->name('skpd.pdf');
    });
});

});

require __DIR__ . '/auth.php';