@php
    $skpdPendingCount = 0;
    $suratKeluarPendingCount = 0;
    $disposisiPendingCount = 0;
    if (auth()->check()) {
        $userRole = strtolower(auth()->user()->role);
        if ($userRole === 'sekretaris') {
            $skpdPendingCount = \App\Models\Skpd::where('status', 'pengajuan')->count();
        } elseif ($userRole === 'dirut') {
            $skpdPendingCount = \App\Models\Skpd::where('status', 'diperiksa')->count();
            $suratKeluarPendingCount = \App\Models\SuratKeluar::where('status', 'menunggu_dirut')->count();
        }
        $disposisiPendingCount = \App\Models\Disposisi::where('kepada_user_id', auth()->id())->where('status', 'menunggu')->count();
    }
@endphp

<!-- Sidebar -->
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shadow-2xl z-20 relative flex-shrink-0">
    <!-- Logo -->
    <div class="p-6 border-b border-slate-800 flex items-center gap-3">
        <div class="w-10 h-10 bg-white rounded-lg p-1">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
        </div>
        <div>
            <h1 class="text-lg font-bold text-white tracking-wide">E-Office</h1>
            <p class="text-[11px] text-slate-400 uppercase tracking-widest">Fasadetama</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto sidebar-scroll">
        
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>

        <!-- System Section (Admin Only) -->
        @if(in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin']))
            <div class="pt-4 pb-1">
                <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Master Data & Sistem</p>
            </div>

            <!-- User Management -->
            <a href="{{ route('users.index') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Manajemen User
            </a>

            <!-- Activity Log -->
            <a href="{{ route('activity.index') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('activity.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10V4M5 20h14"></path></svg>
                Activity Log
            </a>
        @endif

        <!-- General Employee Section (Including Admin for full access) -->
            
            <!-- Mail Section -->
            @if(in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin', 'sekretaris', 'dirut', 'direktur1', 'direktur2', 'staff']))
                <div class="pt-4 pb-1">
                    <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Persuratan</p>
                </div>

                <!-- Surat Masuk -->
                <a href="{{ route('surat-masuk.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('surat-masuk.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    Surat Masuk
                </a>

                <!-- Surat Keluar -->
                <a href="{{ route('surat-keluar.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('surat-keluar.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span>Surat Keluar</span>
                    @if($suratKeluarPendingCount > 0)
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">
                            {{ $suratKeluarPendingCount }}
                        </span>
                    @endif
                </a>
            @endif

            <!-- Perjalanan Dinas Section -->
            @if(!in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin']))
                <div class="pt-4 pb-1">
                    <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perjalanan Dinas</p>
                </div>

                <!-- SKPD Route Dynamic Label -->
                <a href="{{ route('skpd.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('skpd.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>
                        @if(strtolower(auth()->user()->role) == 'sekretaris')
                            Data SKPD
                        @elseif(strtolower(auth()->user()->role) == 'dirut')
                            Persetujuan SKPD
                        @else
                            SKPD Saya
                        @endif
                    </span>
                    @if($skpdPendingCount > 0)
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">
                            {{ $skpdPendingCount }}
                        </span>
                    @endif
                </a>
            @endif


            <!-- Tasks & Disposition Section -->
            @if(in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1', 'direktur2', 'sekretaris', 'staff']))
                <div class="pt-4 pb-1">
                    <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tugas & Disposisi</p>
                </div>

                <!-- Disposisi Saya -->
                <a href="{{ route('disposisi.saya') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('disposisi.saya*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Disposisi Saya</span>
                    @if($disposisiPendingCount > 0)
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">
                            {{ $disposisiPendingCount }}
                        </span>
                    @endif
                </a>
            @endif

            <!-- Monitoring (Dirut, Sekretaris only) -->
            @if(in_array(strtolower(auth()->user()->role), ['dirut', 'sekretaris']))
                <a href="{{ route('disposisi.monitoring') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('disposisi.monitoring*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Monitoring
                </a>
            @endif

            <!-- Laporan Section -->
            <div class="pt-4 pb-1">
                <p class="px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Laporan & Rekapitulasi</p>
            </div>

            <!-- Laporan Surat Masuk -->
            <a href="{{ route('laporan.surat-masuk') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('laporan.surat-masuk') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Lap. Surat Masuk
            </a>

            <!-- Laporan Surat Keluar -->
            <a href="{{ route('laporan.surat-keluar') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('laporan.surat-keluar') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Lap. Surat Keluar
            </a>

            <!-- Laporan Disposisi -->
            <a href="{{ route('laporan.disposisi') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('laporan.disposisi') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Lap. Disposisi
            </a>

            <!-- Laporan SKPD -->
            <a href="{{ route('laporan.skpd') }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all {{ request()->routeIs('laporan.skpd') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Lap. SKPD
            </a>


    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-slate-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                    class="flex items-center justify-center gap-3 w-full bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white px-4 py-3 rounded-xl font-medium transition-all font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </button>
        </form>
    </div>
</aside>
