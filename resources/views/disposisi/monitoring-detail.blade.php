@php
    if (!function_exists('collectDisposisiChain')) {
        function collectDisposisiChain($node) {
            $items = [$node];
            foreach ($node->children as $child) {
                $items = array_merge($items, collectDisposisiChain($child));
            }
            return $items;
        }
    }
    $chain = collectDisposisiChain($disposisi);
    usort($chain, function($a, $b) {
        return strcmp($a->tanggal_disposisi, $b->tanggal_disposisi);
    });
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Monitoring Disposisi - E-Office</title>
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
                Detail Monitoring
            </h1>
            <p class="text-slate-500 text-sm mt-1.5">
                Pantau detail instruksi, tindak lanjut, dan alur disposisi surat.
            </p>
        </div>

        <a href="{{ route('disposisi.monitoring') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-xl text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Monitoring
        </a>
    </div>

    <!-- Layout Split -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

        <!-- Kolom Kiri: Info Surat & Timeline (5 span) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Card: Informasi Surat -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h2 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Informasi Surat
                    </h2>
                    
                    <!-- Status Badge Utama -->
                    @if($disposisi->status == 'menunggu')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">Menunggu</span>
                    @elseif($disposisi->status == 'diproses')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Diproses</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                    @endif
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Nomor Surat</span>
                        <span class="font-bold text-slate-800">{{ $disposisi->suratMasuk->nomor_surat }}</span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Pengirim</span>
                        <span class="text-slate-800">{{ $disposisi->suratMasuk->pengirim }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Perihal</span>
                        <span class="text-slate-800 leading-relaxed">{{ $disposisi->suratMasuk->perihal }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Ditujukan Kepada</span>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-xs font-bold">
                                {{ substr($disposisi->kepadaUser->name, 0, 1) }}
                            </div>
                            <span class="font-medium text-slate-800">{{ $disposisi->kepadaUser->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tombol File -->
                <div class="mt-8 pt-5 border-t border-slate-100">
                    @if($disposisi->suratMasuk->file)
                        <a href="{{ asset('storage/'.$disposisi->suratMasuk->file) }}" target="_blank"
                           class="flex items-center justify-center gap-2 w-full bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            Lihat Dokumen Lampiran
                        </a>
                    @else
                        <div class="flex items-center justify-center gap-2 w-full bg-slate-50 text-slate-400 border border-slate-200 px-4 py-2.5 rounded-xl text-sm border-dashed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            Tidak ada lampiran dokumen
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Kolom Kanan: Alur Disposisi Berjenjang (7 span) -->
        <div class="lg:col-span-7">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h2 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                        Alur Delegasi & Tindak Lanjut
                    </h2>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">
                        {{ count($chain) }} Tingkat Delegasi
                    </span>
                </div>

                <div class="p-6 sm:p-8">
                    
                    <div class="relative pl-6 sm:pl-8 space-y-8">
                        <!-- Line -->
                        <div class="absolute left-[17px] sm:left-[21px] top-3 bottom-3 w-0.5 bg-slate-200"></div>

                        @foreach($chain as $index => $item)
                            @php
                                $statusColor = match($item->status) {
                                    'selesai' => 'bg-emerald-500 text-white',
                                    'diproses' => 'bg-blue-500 text-white',
                                    default => 'bg-yellow-500 text-slate-900',
                                };
                                $statusLabel = match($item->status) {
                                    'selesai' => 'Selesai',
                                    'diproses' => 'Diproses',
                                    default => 'Menunggu',
                                };
                                
                                $isStepOverdue = $item->status !== 'selesai' && $item->batas_waktu && \Carbon\Carbon::parse($item->batas_waktu)->isPast() && !\Carbon\Carbon::parse($item->batas_waktu)->isToday();
                                $isStepDueToday = $item->status !== 'selesai' && $item->batas_waktu && \Carbon\Carbon::parse($item->batas_waktu)->isToday();
                            @endphp

                            <div class="relative">
                                <!-- Step Circle -->
                                <div class="absolute -left-[35px] sm:-left-[41px] top-1 w-7 h-7 sm:w-9 sm:h-9 rounded-full bg-white border-2 border-slate-200 shadow-sm flex items-center justify-center font-bold text-xs sm:text-sm text-slate-500 transition-colors">
                                    {{ $index + 1 }}
                                </div>

                                <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-300 transition-all hover:shadow-md relative overflow-hidden">
                                    
                                    <!-- Status strip -->
                                    <div class="absolute left-0 top-0 bottom-0 w-1.5 @if($item->status == 'selesai') bg-emerald-500 @elseif($item->status == 'diproses') bg-blue-500 @else bg-yellow-500 @endif"></div>

                                    <!-- Step Header -->
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4 pl-2">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-slate-800 text-sm sm:text-base">{{ $item->dariUser->name }}</span>
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                <span class="font-bold text-slate-800 text-sm sm:text-base">{{ $item->kepadaUser->name }}</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                {{ \Carbon\Carbon::parse($item->tanggal_disposisi)->translatedFormat('d M Y - H:i') }} WIB
                                            </p>
                                        </div>

                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Step Content -->
                                    <div class="space-y-4 pl-2 text-xs sm:text-sm">
                                        <!-- Tenggat jika ada -->
                                        @if($item->batas_waktu)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tenggat:</span>
                                                <span class="font-semibold text-slate-700">
                                                    {{ \Carbon\Carbon::parse($item->batas_waktu)->translatedFormat('d M Y') }}
                                                </span>
                                                @if($isStepOverdue)
                                                    <span class="px-1.5 py-0.5 bg-red-100 text-red-800 border border-red-200 rounded text-[9px] font-bold uppercase">Terlambat</span>
                                                @elseif($isStepDueToday)
                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 border border-amber-200 rounded text-[9px] font-bold uppercase">Hari Ini</span>
                                                @endif
                                            </div>
                                        @endif

                                        <div>
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Instruksi:</span>
                                            <div class="bg-blue-50/40 border border-blue-100 rounded-xl p-3.5 text-slate-700 leading-relaxed italic">
                                                "{{ $item->instruksi }}"
                                            </div>
                                        </div>

                                        @if($item->catatan_tindak_lanjut || $item->file_tindak_lanjut)
                                            <div>
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Respon Tindak Lanjut:</span>
                                                <div class="bg-emerald-50/30 border border-emerald-100 rounded-xl p-3.5 space-y-3">
                                                    @if($item->catatan_tindak_lanjut)
                                                        <p class="text-emerald-900 leading-relaxed m-0">{{ $item->catatan_tindak_lanjut }}</p>
                                                    @else
                                                        <p class="text-slate-400 italic m-0">Menyerahkan laporan tanpa catatan.</p>
                                                    @endif

                                                    @if($item->file_tindak_lanjut)
                                                        <div class="pt-2 border-t border-emerald-100/50">
                                                            <a href="{{ asset('storage/' . $item->file_tindak_lanjut) }}" 
                                                               target="_blank" 
                                                               class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-100 hover:bg-emerald-200 border border-emerald-200 px-3 py-1.5 rounded-lg transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                                Lihat Bukti / Laporan Kerja
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            @if($item->status !== 'menunggu')
                                                <p class="text-xs text-slate-400 italic pl-1">Tindak lanjut sedang diproses.</p>
                                            @endif
                                        @endif
                                    </div>

                                </div>
                            </div>
                        @endforeach

                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>