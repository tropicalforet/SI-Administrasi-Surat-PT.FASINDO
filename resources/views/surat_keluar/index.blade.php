@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">
                        Data Surat Keluar
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola, tanda tangani (E-Sign), dan pantau semua daftar surat keluar dari sistem E-Office.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    @if(in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin']))
                        <form action="{{ route('surat-keluar.clear') }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Semua Surat Keluar',message:'Seluruh data surat keluar beserta dokumen lampirannya akan dihapus secara permanen. Anda yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Bersihkan'}).then(ok=>{if(ok)this.submit()})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 rounded-xl text-sm font-semibold transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Bersihkan Riwayat
                            </button>
                        </form>
                    @endif
                    @if(auth()->user()->role == 'sekretaris')
                        <a href="{{ route('surat-keluar.create') }}"
                           class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-xl transition duration-200 shadow-md shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Surat
                        </a>
                    @endif
                </div>
            </div>

            @if(session('error'))
            <div class="m-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            @endif

            <form method="GET" action="{{ route('surat-keluar.index') }}" class="m-6">
                <div class="grid md:grid-cols-5 gap-4">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari nomor surat, tujuan, perihal..."
                           class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 text-sm">

                    <select name="status"
                            class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 text-sm cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="menunggu_direktur" {{ request('status') == 'menunggu_direktur' ? 'selected' : '' }}>Menunggu Direktur</option>
                        <option value="menunggu_dirut" {{ request('status') == 'menunggu_dirut' ? 'selected' : '' }}>Menunggu Dirut</option>
                        <option value="terkirim" {{ request('status') == 'terkirim' ? 'selected' : '' }}>Terkirim (Disetujui)</option>
                        <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>

                    <input type="date"
                           name="tanggal_awal"
                           value="{{ request('tanggal_awal') }}"
                           class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 text-sm">

                    <input type="date"
                           name="tanggal_akhir"
                           value="{{ request('tanggal_akhir') }}"
                           class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 text-sm">

                    <div class="flex gap-2">
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded-xl transition duration-200 shadow-md shadow-blue-500/10 text-sm w-full">
                            Cari
                        </button>
                        <a href="{{ route('surat-keluar.index') }}"
                           class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium px-4 py-2 rounded-xl transition duration-200 text-sm flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6 font-semibold w-16">No</th>
                            <th class="py-4 px-6 font-semibold">Nomor Surat</th>
                            <th class="py-4 px-6 font-semibold">Tanggal</th>
                            <th class="py-4 px-6 font-semibold">Tujuan</th>
                            <th class="py-4 px-6 font-semibold">Perihal</th>
                            <th class="py-4 px-6 font-semibold text-center">Status</th>
                            <th class="py-4 px-6 font-semibold text-center">File</th>
                            <th class="py-4 px-6 font-semibold text-center w-64">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                        @forelse($data as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors duration-150 group">
                            
                            <td class="py-4 px-6 text-slate-500">
                                {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
                            </td>

                            <td class="py-4 px-6 font-semibold text-slate-800">
                                {{ $item->nomor_surat }}
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap text-slate-500">
                                {{ \Carbon\Carbon::parse($item->tanggal_surat)->locale('id')->translatedFormat('d M Y') }}
                            </td>

                            <td class="py-4 px-6">
                                {{ $item->tujuan }}
                            </td>

                            <td class="py-4 px-6">
                                <span class="line-clamp-2" title="{{ $item->perihal }}">
                                    {{ $item->perihal }}
                                </span>
                            </td>

                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                @if($item->status == 'draft')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                        Draft
                                    </span>
                                @elseif($item->status == 'menunggu_direktur')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                        Menunggu Direktur
                                    </span>
                                @elseif($item->status == 'menunggu_dirut')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Menunggu Dirut
                                    </span>
                                @elseif($item->status == 'terkirim')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        E-Signed / Terkirim
                                    </span>
                                @elseif($item->status == 'ditolak')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        Ditolak
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6 text-center">
                                @if($item->file)
                                    <a href="{{ route('surat-keluar.download', $item->id) }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                                       title="Unduh Berkas Surat">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                @else
                                    <span class="text-slate-300 inline-flex p-2" title="Tidak ada file">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6">
                                <div class="flex justify-center items-center gap-2">
                                    
                                    <a href="{{ route('surat-keluar.show', $item->id) }}"
                                       class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">
                                        Detail
                                    </a>

                                    @if(auth()->user()->role == 'sekretaris')
                                        @if($item->status == 'draft')
                                            <a href="{{ route('surat-keluar.edit', $item->id) }}"
                                               class="bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">
                                                Edit
                                            </a>

                                            <form action="{{ route('surat-keluar.submit', $item->id) }}"
                                                  method="POST"
                                                  onsubmit="event.preventDefault(); ConfirmModal.show({title:'Ajukan Persetujuan',message:'Surat ini akan dikirim ke Direktur Utama untuk ditinjau dan ditandatangani. Lanjutkan?',variant:'info',confirmText:'Ya, Ajukan'}).then(ok=>{if(ok)this.submit()})"
                                                  class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                        class="bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">
                                                    Ajukan
                                                </button>
                                            </form>

                                        @endif
                                    @endif

                                    @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']))
                                    <form action="{{ route('surat-keluar.destroy', $item->id) }}"
                                          method="POST"
                                          onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Surat Keluar',message:'Data surat keluar yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif

                                    {{-- DIRUT APPROVAL PENDING LINK --}}
                                    @if(auth()->user()->role == 'dirut' && $item->status == 'menunggu_dirut')
                                        <a href="{{ route('surat-keluar.show', $item->id) }}"
                                           class="bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-md text-xs font-bold transition-colors animate-pulse">
                                            E-Sign
                                        </a>
                                    @endif

                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    <p class="text-slate-500 font-medium">Belum ada data surat keluar.</p>
                                    @if(auth()->user()->role == 'sekretaris')
                                        <p class="text-sm mt-1">Silakan klik tambah surat keluar baru untuk memulai.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($data->hasPages())
                <div class="p-6 border-t border-slate-100">
                    {{ $data->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
@endsection