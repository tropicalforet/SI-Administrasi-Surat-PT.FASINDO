@extends('layouts.app')

@section('content')
<style>
    /* UI Font */
    .font-ui { font-family: 'Inter', sans-serif; }
    
    /* Document Font */
    .font-doc { font-family: 'Times New Roman', Times, serif; }

    /* Print Settings untuk 1 Halaman A4 Pas */
    @media print {
        @page {
            size: A4 portrait;
            margin: 1cm 1.5cm;
        }
        body {
            background-color: white !important;
            -webkit-print-color-adjust: exact;
        }
        aside {
            display: none !important;
        }
        main {
            padding: 0 !important;
        }
        .no-print {
            display: none !important;
        }
        .print-area {
            box-shadow: none !important;
            padding: 0 !important;
            border: none !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="py-8 px-4 sm:px-8 font-ui max-w-7xl mx-auto">

    <!-- TOP CONTROL BAR (no-print) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4 no-print">
        
        <a href="{{ route('skpd.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors">
           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>

        <div class="flex items-center gap-3">
            @php
                $role = strtolower(auth()->user()->role);
                $isApproved = strtolower($skpd->status) === 'disetujui';
                $canDownload = $isApproved || $role === 'sekretaris' || $role === 'dirut';
            @endphp

            @if($canDownload)
                <a href="{{ route('skpd.download-pdf', $skpd->id) }}"
                   class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download PDF
                </a>
            @endif
        </div>
        
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT COLUMN: PDF PREVIEW (Takes 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 bg-white">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Pratinjau Dokumen SKPD
                </h3>
                
                <div class="w-full h-[680px] rounded-xl overflow-hidden border border-slate-200 shadow-inner bg-slate-50">
                    <iframe src="{{ route('skpd.preview-pdf', $skpd->id) }}" class="w-full h-full" frameborder="0"></iframe>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: METADATA & ACTIONS (Takes 1 col) -->
        <div class="space-y-6">
            
            <!-- REJECTION ALERT BOX (no-print) -->
            @if(strtolower($skpd->status) === 'ditolak' && $skpd->catatan_revisi)
                <div class="p-5 bg-red-50 border border-red-200 rounded-xl flex gap-3 no-print">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h4 class="text-md font-bold text-red-800 text-sm">Status: Ditolak / Perlu Revisi</h4>
                        <p class="text-xs text-red-700 mt-1 font-medium italic">"{{ $skpd->catatan_revisi }}"</p>
                        @if(($skpd->user_id ?? null) === auth()->id())
                            <a href="{{ route('skpd.edit', $skpd->id) }}" class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-red-600 hover:text-red-800 underline">
                                Edit & Perbaiki Sekarang
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- APPROVAL ACTION CARD FOR DIRUT (no-print) -->
            @if(strtolower(auth()->user()->role) === 'dirut' && strtolower($skpd->status) === 'diperiksa')
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 no-print">
                    <h3 class="text-base font-bold text-slate-800 mb-2">Persetujuan Dokumen SKPD</h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">Sebagai Direktur Utama, Anda dapat menyetujui dokumen perjalanan dinas ini atau mengembalikannya untuk direvisi.</p>
                    
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <form action="{{ route('skpd.approve', $skpd->id) }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Setujui SKPD',message:'Dokumen SKPD ini akan disetujui dan ditandatangani secara digital (E-Sign). Lanjutkan?',variant:'approve',confirmText:'Ya, Setujui & E-Sign'}).then(ok=>{if(ok)this.submit()})" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-5 rounded-xl transition duration-200 shadow-md shadow-emerald-500/10 text-xs cursor-pointer">
                                    Setujui SKPD
                                </button>
                            </form>
                            
                            <button onclick="document.getElementById('reject-form-container').classList.toggle('hidden')" class="bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-2.5 px-5 border border-red-100 rounded-xl transition duration-200 text-xs cursor-pointer">
                                Tolak / Perlu Revisi
                            </button>
                        </div>

                        <div id="reject-form-container" class="hidden mt-4 pt-4 border-t border-slate-100">
                            <form action="{{ route('skpd.reject', $skpd->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <label class="block text-xs font-semibold text-slate-700 mb-2">
                                    Alasan Penolakan / Catatan Revisi <span class="text-red-500">*</span>
                                </label>
                                <textarea name="catatan_revisi" rows="3" required placeholder="Tuliskan detail poin revisi atau alasan penolakan..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:bg-white outline-none transition-all text-slate-800 text-xs resize-none"></textarea>
                                
                                <div class="flex justify-end gap-3 mt-3">
                                    <button type="button" onclick="document.getElementById('reject-form-container').classList.add('hidden')" class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700">
                                        Batal
                                    </button>
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1.5 px-3 rounded-lg text-xs shadow-md shadow-red-500/10">
                                        Kirim Penolakan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif


            <!-- METADATA DETAILS CARD -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <h3 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Rincian Perjalanan Dinas
                </h3>
                
                <div class="space-y-4 text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Nomor SKPD</span>
                        <span class="font-bold text-slate-800 text-sm block mt-0.5">{{ $skpd->nomor_skpd ?? 'Belum Terbit (Draft)' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Pelaksana Dinas</span>
                        <span class="font-bold text-slate-800 block text-lg">{{ $skpd->nama_pegawai }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Tujuan Dinas</span>
                        <span class="font-semibold text-slate-800 block mt-0.5">{{ $skpd->tujuan_dinas }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Keperluan Dinas</span>
                        <span class="font-semibold text-slate-800 block mt-0.5 leading-relaxed">{{ $skpd->keperluan }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Mulai</span>
                            <span class="font-semibold text-slate-800 block mt-0.5">{{ \Carbon\Carbon::parse($skpd->tanggal_berangkat)->locale('id')->translatedFormat('d M Y') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Kembali</span>
                            <span class="font-semibold text-slate-800 block mt-0.5">{{ \Carbon\Carbon::parse($skpd->tanggal_kembali)->locale('id')->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Durasi Dinas</span>
                        <span class="font-semibold text-slate-800 block mt-0.5">{{ $skpd->durasi_hari }} Hari Kerja</span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wider">Status Dokumen</span>
                        @php
                            $badgeColor = match(strtolower($skpd->status)) {
                                'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'diperiksa' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'ditolak' => 'bg-red-50 text-red-700 border-red-200',
                                default => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            };
                            $badgeLabel = match(strtolower($skpd->status)) {
                                'disetujui' => 'Disetujui',
                                'diperiksa' => 'Diperiksa',
                                'ditolak' => 'Ditolak',
                                default => 'Pengajuan',
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $badgeColor }}">
                            {{ $badgeLabel }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
        
    </div>

</div>
@endsection