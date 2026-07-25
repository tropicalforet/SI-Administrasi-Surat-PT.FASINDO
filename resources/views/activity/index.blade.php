@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 font-ui">
<div class="max-w-7xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <div class="mb-8 border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Activity Log
            </h1>
            <p class="text-slate-500 text-sm mt-1.5 pl-11">
                Riwayat seluruh aktivitas pengguna pada sistem informasi E-Office.
            </p>
        </div>
        
        @if(in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin']))
        <div>
            <form action="{{ route('activity.clear') }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Semua Log Aktivitas',message:'Seluruh riwayat aktivitas sistem akan dihapus secara permanen. Anda yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Bersihkan'}).then(ok=>{if(ok)this.submit()})">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Bersihkan Riwayat
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-6 mb-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari aktivitas..."
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 text-sm placeholder-slate-400">
                </div>

                <div>
                    <select name="user"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 text-sm cursor-pointer">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="date"
                           name="tanggal_awal"
                           value="{{ request('tanggal_awal') }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 text-sm"
                           title="Tanggal Awal">
                </div>

                <div>
                    <input type="date"
                           name="tanggal_akhir"
                           value="{{ request('tanggal_akhir') }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 text-sm"
                           title="Tanggal Akhir">
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('activity.index') }}"
                   class="px-5 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition-colors">
                    Reset Filter
                </a>
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider w-16 text-center">No</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Waktu</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Pengguna</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Aktivitas</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi Lengkap</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                            <td class="py-4 px-6 text-slate-500 text-center">
                                {{ $loop->iteration }}
                            </td>
                            <td class="py-4 px-6 text-slate-500 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $log->created_at->format('d M Y - H:i') }}
                                </div>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-700 whitespace-nowrap">
                                {{ $log->user->name }}
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                @php
                                    $warna = 'bg-slate-50 text-slate-700 border-slate-200';
                                    if(str_contains($log->aktivitas, 'Tambah')) {
                                        $warna = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif(str_contains($log->aktivitas, 'Edit')) {
                                        $warna = 'bg-amber-50 text-amber-700 border-amber-200';
                                    } elseif(str_contains($log->aktivitas, 'Hapus')) {
                                        $warna = 'bg-red-50 text-red-700 border-red-200';
                                    } elseif(str_contains($log->aktivitas, 'Disposisi')) {
                                        $warna = 'bg-blue-50 text-blue-700 border-blue-200';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border {{ $warna }}">
                                    {{ $log->aktivitas }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-600 leading-relaxed">
                                {{ $log->deskripsi }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 px-6 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="text-slate-500 font-medium">Belum ada aktivitas terekam.</p>
                                    <p class="text-sm mt-1">Coba sesuaikan filter pencarian Anda jika data tidak ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="mt-6 flex justify-end">
            {{ $logs->links() }}
        </div>
    @endif

</div>
</div>
@endsection