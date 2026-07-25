<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Disposisi - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 p-4 sm:p-6 lg:p-8 min-h-screen">

<div class="max-w-7xl mx-auto">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-slate-800 tracking-tight">
                Monitoring Disposisi
            </h1>
            <p class="text-slate-500 text-sm mt-1.5">
                Pantau status dan tindak lanjut dari seluruh instruksi disposisi yang telah dikirim.
            </p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-xl text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Grid Container -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @forelse($data as $item)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 hover:shadow-md transition-shadow flex flex-col">
            
            <!-- Info Utama Disposisi -->
            <div>
                <div class="flex justify-between items-start mb-3 gap-4">
                    <h2 class="font-bold text-lg text-blue-700 leading-tight">
                        {{ $item->suratMasuk->nomor_surat }}
                    </h2>
                    
                    <div>
                        @if($item->status == 'menunggu')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200 whitespace-nowrap">
                                Menunggu
                            </span>
                        @elseif($item->status == 'diproses')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                                Diproses
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                Selesai
                            </span>
                        @endif
                    </div>
                </div>

                <p class="text-slate-600 text-sm leading-relaxed line-clamp-2 mb-4" title="{{ $item->suratMasuk->perihal }}">
                    {{ $item->suratMasuk->perihal }}
                </p>
            </div>

            <!-- Detail Kepada & Tanggal -->
            <div class="space-y-2 text-sm text-slate-500 mb-5">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Kepada: <span class="font-semibold text-slate-700">{{ $item->kepadaUser->name }}</span></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Dikirim: <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($item->tanggal_disposisi)->translatedFormat('d M Y') }}</span></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Tenggat: 
                        @if($item->batas_waktu)
                            <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($item->batas_waktu)->translatedFormat('d M Y') }}</span>
                            @php
                                $isOverdue = $item->status !== 'selesai' && \Carbon\Carbon::parse($item->batas_waktu)->isPast() && !\Carbon\Carbon::parse($item->batas_waktu)->isToday();
                                $isDueToday = $item->status !== 'selesai' && \Carbon\Carbon::parse($item->batas_waktu)->isToday();
                            @endphp
                            @if($isOverdue)
                                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-800 border border-red-200">Terlambat</span>
                            @elseif($isDueToday)
                                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Hari Ini</span>
                            @endif
                        @else
                            <span class="text-slate-400 italic">Tanpa Batas</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Children / Disposisi Lanjutan -->
            @if($item->children->count())
                <div class="flex-1 mt-2 border-t border-slate-100 pt-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                        Disposisi Lanjutan ({{ $item->children->count() }})
                    </h4>
                    
                    <div class="space-y-3">
                        @foreach($item->children->take(3) as $child) <!-- Batasi tampilan awal agar card tidak terlalu panjang -->
                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 relative overflow-hidden">
                                @php
                                    $stripColor = match(strtolower($child->status)) {
                                        'selesai' => 'bg-emerald-400',
                                        'diproses' => 'bg-blue-400',
                                        default => 'bg-yellow-400',
                                    };
                                @endphp
                                <div class="absolute left-0 top-0 bottom-0 w-1 {{ $stripColor }}"></div>

                                <div class="flex justify-between items-start mb-1.5 pl-1.5">
                                    <div class="font-bold text-sm text-slate-700">
                                        {{ $child->kepadaUser->name }}
                                    </div>
                                    <div>
                                        @if(strtolower($child->status) == 'menunggu')
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded-full">Menunggu</span>
                                        @elseif(strtolower($child->status) == 'diproses')
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">Diproses</span>
                                        @else
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">Selesai</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="pl-1.5 text-xs text-slate-600 mb-2 line-clamp-1">
                                    <span class="font-semibold text-slate-500">Instruksi:</span> {{ $child->instruksi }}
                                </div>

                                @if($child->catatan_tindak_lanjut)
                                    <div class="pl-1.5 text-xs">
                                        <span class="font-semibold text-emerald-700">Tindak Lanjut:</span> 
                                        <span class="text-emerald-800 line-clamp-1">{{ $child->catatan_tindak_lanjut }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        
                        @if($item->children->count() > 3)
                            <div class="text-center text-xs text-slate-400 font-medium pt-1">
                                + {{ $item->children->count() - 3 }} disposisi lanjutan lainnya
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Action Button -->
            <div class="mt-5 pt-5 border-t border-slate-100 {{ !$item->children->count() ? 'mt-auto' : '' }} flex flex-col gap-2">
                <a href="{{ route('disposisi.monitoring.show', $item->id) }}"
                   class="flex items-center justify-center gap-1.5 w-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 hover:border-blue-600 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    Lihat Detail Selengkapnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
                
                @if(in_array(strtolower(auth()->user()->role ?? ''), ['dirut', 'direktur1', 'direktur2', 'sekretaris', 'staff']))
                <form action="{{ route('disposisi.destroy', $item->id) }}"
                      method="POST"
                      onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Disposisi',message:'Data disposisi yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})"
                      class="w-full">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="flex items-center justify-center gap-1.5 w-full bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-100 hover:border-red-600 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                        Hapus
                    </button>
                </form>
                @endif
            </div>
            
        </div>
        @empty

        <div class="col-span-full py-16 px-6 bg-white rounded-2xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-1">Belum Ada Disposisi</h3>
            <p class="text-slate-500 text-sm max-w-md mx-auto">
                Saat ini belum ada data disposisi yang dikirimkan. Anda dapat memonitor status instruksi di sini setelah disposisi dibuat.
            </p>
        </div>

        @endforelse

    </div>

    <!-- Pagination Section -->
    @if($data->hasPages())
    <div class="mt-8">
        {{ $data->links() }}
    </div>
    @endif

</div>

@include('layouts.partials.confirm-modal')
</body>
</html>