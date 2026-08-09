@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

    <!-- Tombol Kembali -->
    <div class="mb-4">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <!-- Header Section -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    Disposisi Saya
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Daftar surat masuk yang didisposisikan dan memerlukan tindak lanjut Anda.
                </p>
            </div>
        </div>

        <!-- Table Section -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <th class="py-4 px-6 font-semibold w-16 text-center">No</th>
                        <th class="py-4 px-6 font-semibold">Nomor Surat</th>
                        <th class="py-4 px-6 font-semibold">Perihal</th>
                        <th class="py-4 px-6 font-semibold">Dari</th>
                        <th class="py-4 px-6 font-semibold">Instruksi</th>
                        <th class="py-4 px-6 font-semibold">Tenggat</th>
                        <th class="py-4 px-6 font-semibold text-center">Status</th>
                        <th class="py-4 px-6 font-semibold text-center w-36">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    
                    @forelse($data as $item)
                    <tr class="hover:bg-slate-50/80 transition-colors duration-150 group">
                        
                        <td class="py-4 px-6 text-slate-500 text-center">
                            {{ $data->firstItem() + $loop->index }}
                        </td>

                        <td class="py-4 px-6 font-semibold text-slate-800 whitespace-nowrap">
                            {{ $item->suratMasuk?->nomor_surat }}
                        </td>

                        <td class="py-4 px-6 max-w-xs">
                            <span class="line-clamp-2" title="{{ $item->suratMasuk?->perihal }}">
                                {{ $item->suratMasuk?->perihal }}
                            </span>
                        </td>

                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                    {{ substr($item->dariUser->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-slate-700">{{ $item->dariUser->name }}</span>
                            </div>
                        </td>

                        <td class="py-4 px-6 max-w-xs text-slate-600 italic">
                            <span class="line-clamp-2" title="{{ $item->instruksi }}">
                                "{{ $item->instruksi }}"
                            </span>
                        </td>

                        <td class="py-4 px-6 whitespace-nowrap text-xs">
                            @if($item->batas_waktu)
                                @php
                                    $isOverdue = $item->status !== 'selesai' && \Carbon\Carbon::parse($item->batas_waktu)->isPast() && !\Carbon\Carbon::parse($item->batas_waktu)->isToday();
                                    $isDueToday = $item->status !== 'selesai' && \Carbon\Carbon::parse($item->batas_waktu)->isToday();
                                @endphp
                                
                                @if($isOverdue)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-red-700 bg-red-50 border border-red-200 font-semibold" title="Sudah melewati batas waktu!">
                                        {{ \Carbon\Carbon::parse($item->batas_waktu)->translatedFormat('d M Y') }} (Terlambat)
                                    </span>
                                @elseif($isDueToday)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-amber-700 bg-amber-50 border border-amber-200 font-semibold" title="Batas waktu hari ini!">
                                        {{ \Carbon\Carbon::parse($item->batas_waktu)->translatedFormat('d M Y') }} (Hari Ini)
                                    </span>
                                @else
                                    <span class="text-slate-600 font-medium">
                                        {{ \Carbon\Carbon::parse($item->batas_waktu)->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                            @else
                                <span class="text-slate-400 italic">Tanpa Batas</span>
                            @endif
                        </td>

                        <td class="py-4 px-6 text-center">
                            @if(strtolower($item->status) == 'menunggu')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                    Menunggu
                                </span>
                            @elseif(strtolower($item->status) == 'diproses')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    Diproses
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Selesai
                                </span>
                            @endif
                            
                            @if($item->file_tindak_lanjut)
                                <div class="mt-1.5">
                                    <a href="{{ asset('storage/' . $item->file_tindak_lanjut) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100/70 border border-emerald-100 px-1.5 py-0.5 rounded transition-colors"
                                       title="Unduh/Lihat Laporan Kerja">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Laporan
                                    </a>
                                </div>
                            @endif
                        </td>

                        <td class="py-4 px-6">
                            <div class="flex justify-center items-center gap-2">
                                <a href="{{ route('disposisi.edit', $item->id) }}"
                                   class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 border border-blue-100 px-4 py-2 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    Tindak Lanjut
                                </a>

                                @if(in_array(strtolower(auth()->user()->role ?? ''), ['dirut', 'direktur1', 'direktur2', 'sekretaris', 'staff']))
                                <form action="{{ route('disposisi.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Disposisi',message:'Data disposisi yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 border border-red-100 px-4 py-2 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap flex items-center shadow-sm">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @empty

                    <tr>
                        <td colspan="7" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                <p class="text-slate-500 font-medium">Yeay! Anda tidak memiliki disposisi.</p>
                                <p class="text-sm mt-1">Semua pekerjaan atau instruksi Anda sudah terselesaikan.</p>
                            </div>
                        </td>
                    </tr>
                    
                    @endforelse

                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        @if($data->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            {{ $data->links() }}
        </div>
        @endif

    </div>
</div>
@endsection