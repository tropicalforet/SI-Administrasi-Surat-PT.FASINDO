@extends('layouts.app')

@section('content')
<div class="p-8">
    @php
    // Nama jabatan diambil dari akun: mengutamakan jabatan sebenarnya yang
    // diisi administrator, lalu jatuh ke label role. Daftar label sengaja
    // tidak ditulis ulang di sini agar tidak ada role yang tampil mentah
    // saat rolenya bertambah.
    $pengguna = auth()->user();
    $jabatan = $pengguna->label_jabatan;
    $unitKerja = $pengguna->unit ? $pengguna->label_unit : null;

    $hour = now()->format('H');
    if($hour < 11){
        $greeting = 'Selamat Pagi';
    }elseif($hour < 15){
        $greeting = 'Selamat Siang';
    }elseif($hour < 18){
        $greeting = 'Selamat Sore';
    }else{
        $greeting = 'Selamat Malam';
    }
    @endphp

    <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-800 rounded-2xl p-8 text-white mb-8 shadow-lg shadow-blue-500/20 relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white opacity-5 blur-2xl"></div>
        
        <div class="flex justify-between items-center relative z-10">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold mb-2">
                    {{ $greeting }}, {{ auth()->user()->name ?? 'User' }} 👋
                </h1>
                <div class="flex items-center gap-3 text-blue-100 flex-wrap">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm">
                        {{ $jabatan }}
                    </span>

                    @if($unitKerja)
                        <span class="bg-white/10 px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm">
                            Unit {{ $unitKerja }}
                        </span>
                    @endif

                    <span class="text-sm">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                </div>
                <p class="text-blue-50 mt-5 opacity-90">
                    Selamat datang kembali di Sistem Informasi E-Office PT. Fasadetama Indonesia.
                </p>
            </div>

            <div class="hidden lg:block">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-6 py-5 shadow-inner">
                    <p class="text-xs text-blue-200 uppercase tracking-wider mb-1">Informasi Akun</p>
                    <h2 class="text-xl font-bold">{{ $pengguna->name ?? 'User' }}</h2>
                    <p class="text-sm text-blue-100">{{ $jabatan }}</p>
                    @if($unitKerja)
                        <p class="text-xs text-blue-200 mt-0.5">Unit {{ $unitKerja }}</p>
                    @endif
                    <p class="text-xs text-blue-200 mt-1">{{ $pengguna->email ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sembunyikan Statistik & Daftar Surat Khusus Administrator -->
    @if(!in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']))
        
        <h3 class="text-lg font-bold text-slate-800 mb-4">Surat &amp; Disposisi</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Surat Masuk</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalSuratMasuk ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Surat Keluar</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalSuratKeluar ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Draft</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalDraft ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Menunggu Persetujuan</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalMenungguDirut ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Terkirim</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalTerkirim ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Ditolak</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalDitolak ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Menunggu</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $disposisiMenunggu ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <h3 class="text-lg font-bold text-slate-800 mb-1">Perjalanan Dinas (SKPD)</h3>
        <p class="text-sm text-slate-500 mb-4">
            @if(in_array(strtolower(auth()->user()->role ?? ''), ['dirut', 'sekretaris']))
                Seluruh pengajuan perjalanan dinas.
            @else
                Pengajuan perjalanan dinas milik Anda.
            @endif
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total SKPD</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalSkpd ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Menunggu Persetujuan</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $skpdPending ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Disetujui</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $skpdDisetujui ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-slate-500">Ditolak</p>
                    <h2 class="text-2xl font-bold text-slate-800 mt-1">{{ $skpdDitolak ?? 0 }}</h2>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Statistik Surat</h3>
                        <p class="text-sm text-slate-500 mt-1">Perbandingan volume Surat Masuk dan Surat Keluar per bulan.</p>
                    </div>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="chartSurat"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8 flex flex-col">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Status Disposisi</h3>
                    <p class="text-sm text-slate-500 mt-1">Distribusi status tugas secara keseluruhan.</p>
                </div>
                <div class="relative flex-1 flex items-center justify-center w-full min-h-[250px]">
                    <canvas id="chartDisposisi"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Disposisi Saya</h3>
                    <a href="{{ route('disposisi.saya') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Semua</a>
                </div>
                <div class="flex-1 p-2">
                    @forelse($disposisiSaya ?? [] as $item)
                        <div class="p-4 rounded-lg hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0 group">
                            <div class="flex justify-between items-start mb-1">
                                <div class="font-semibold text-blue-700 group-hover:text-blue-800 transition-colors">
                                    {{ $item->suratMasuk->nomor_surat ?? '-' }}
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-orange-100 text-orange-700">
                                    {{ $item->status ?? '-' }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-700 mb-3 line-clamp-1">
                                {{ $item->suratMasuk->perihal ?? '-' }}
                            </p>
                            <div class="flex justify-between items-center text-sm">
                                <div class="flex items-center gap-1.5 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <span>Dari: {{ $item->dariUser->name ?? '-' }}</span>
                                </div>
                                <a href="{{ route('disposisi.edit', $item->id) }}"
                                   class="flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium">
                                    Detail <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 flex flex-col items-center justify-center h-full">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <p>Tidak ada disposisi saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Surat Masuk Terbaru</h3>
                    <a href="{{ route('surat-masuk.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Semua</a>
                </div>
                <div class="flex-1 p-2">
                    @forelse($suratTerbaru ?? [] as $surat)
                        <div class="p-4 rounded-lg hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0 group">
                            <div class="flex justify-between items-start mb-1">
                                <div class="font-semibold text-slate-800 group-hover:text-blue-700 transition-colors">
                                    {{ $surat->nomor_surat }}
                                </div>
                                <div class="text-xs text-slate-500 flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-md">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d/m/Y') }}
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 mt-2 line-clamp-2 leading-relaxed">
                                {{ $surat->perihal }}
                            </p>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 flex flex-col items-center justify-center h-full">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p>Belum ada surat masuk.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    @else
        <!-- Tampilan Dashboard Khusus Administrator -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Pusat Kendali Sistem</h2>
            <p class="text-slate-500 max-w-xl mx-auto mb-8">Anda login sebagai Administrator. Gunakan menu di sebelah kiri untuk mengelola pengguna atau memantau log aktivitas sistem.</p>
            
            <div class="flex justify-center gap-4">
                <a href="{{ route('users.index') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors shadow-sm">Kelola Pengguna</a>
                <a href="{{ route('activity.index') }}" class="px-6 py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-medium transition-colors shadow-sm">Lihat Log Aktivitas</a>
            </div>
        </div>
    @endif
</div>
@endsection

@if(!in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function renderDashboardCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(renderDashboardCharts, 50); // Tunggu sampai Chart.js termuat
            return;
        }

        if (window.dashboardChartSurat) {
            window.dashboardChartSurat.destroy();
        }
        if (window.dashboardChartDisposisi) {
            window.dashboardChartDisposisi.destroy();
        }

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b'; 

        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        const canvasSurat = document.getElementById('chartSurat');
        if (canvasSurat) {
            window.dashboardChartSurat = new Chart(canvasSurat, {
                type: 'bar',
                data: {
                    labels: bulan,
                    datasets: [
                        {
                            label: 'Surat Masuk',
                            data: @json($suratMasukBulanan ?? []),
                            backgroundColor: '#3b82f6', 
                            borderRadius: 4,            
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Surat Keluar',
                            data: @json($suratKeluarBulanan ?? []),
                            backgroundColor: '#10b981', 
                            borderRadius: 4,
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } },
                        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
                    },
                    scales: {
                        y: { grid: { color: '#f1f5f9', drawBorder: false }, border: { display: false } },
                        x: { grid: { display: false, drawBorder: false }, border: { display: false } }
                    }
                }
            });
        }

        const canvasDisposisi = document.getElementById('chartDisposisi');
        if (canvasDisposisi) {
            window.dashboardChartDisposisi = new Chart(canvasDisposisi, {
                type: 'doughnut',
                data: {
                    labels: ['Menunggu', 'Diproses', 'Selesai'],
                    datasets: [{
                        data: @json($statusDisposisi ?? []),
                        backgroundColor: ['#facc15', '#3b82f6', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%', 
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } },
                        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
                    }
                }
            });
        }
    }
    renderDashboardCharts();
</script>
@endpush
@endif
