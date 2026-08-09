@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 font-ui">
    <div class="max-w-7xl mx-auto">

        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('surat-keluar.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-xl text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Daftar Surat Keluar
            </a>
        </div>

        <!-- REJECTION ALERT BOX (no-print) -->
        @if(strtolower($surat_keluar->status) === 'ditolak' && $surat_keluar->catatan_revisi)
            <div class="mb-6 p-5 bg-red-50 border border-red-200 rounded-xl flex gap-3 no-print">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <h4 class="text-md font-bold text-red-800 text-sm">Status: Ditolak / Perlu Revisi</h4>
                    <p class="text-xs text-red-700 mt-1 font-medium italic">"{{ $surat_keluar->catatan_revisi }}"</p>
                    @if(auth()->user()->role === 'sekretaris')
                        <a href="{{ route('surat-keluar.edit', $surat_keluar->id) }}" class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-red-600 hover:text-red-800 underline">
                            Edit & Perbaiki Sekarang
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT COLUMN: DOCUMENT PREVIEW (Takes 2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 bg-white">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Pratinjau Dokumen
                    </h3>

                    @if($surat_keluar->file)
                        @php
                            $extension = pathinfo($surat_keluar->file, PATHINFO_EXTENSION);
                            $isPdf = strtolower($extension) === 'pdf';
                            $isDocx = strtolower($extension) === 'docx';
                        @endphp

                        @if($isPdf)
                            <div class="w-full h-[650px] rounded-xl overflow-hidden border border-slate-200 shadow-inner">
                                <iframe src="{{ asset('storage/'.$surat_keluar->file) }}" class="w-full h-full" frameborder="0"></iframe>
                            </div>
                        @elseif($isDocx)
                            @php
                                $companionPdf = str_replace('.docx', '.pdf', $surat_keluar->file);
                                $hasCompanion = file_exists(storage_path('app/public/' . $companionPdf));
                            @endphp

                            @if($hasCompanion)
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-600">
                                        <span class="flex items-center gap-1.5 font-medium text-slate-700">
                                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            Pratinjau Draf Dokumen Word (Read-Only)
                                        </span>
                                        <a href="{{ route('surat-keluar.download', $surat_keluar->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            Unduh Berkas DOCX Asli
                                        </a>
                                    </div>
                                    <div class="w-full h-[650px] rounded-xl overflow-hidden border border-slate-200 shadow-inner">
                                        <iframe src="{{ asset('storage/'.$companionPdf) }}" class="w-full h-full" frameborder="0"></iframe>
                                    </div>
                                </div>
                            @else
                                <div class="py-16 border border-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-500 bg-slate-50">
                                    <svg class="w-16 h-16 mb-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="font-bold text-slate-800 text-sm">Dokumen Word (DOCX) Terdeteksi</p>
                                    <p class="text-xs text-slate-400 mt-1 mb-6 text-center max-w-sm px-4">Tanda tangan digital (E-Sign) dan QR Code akan otomatis disisipkan langsung ke dalam dokumen ini saat disetujui oleh Direktur Utama.</p>
                                    <a href="{{ route('surat-keluar.download', $surat_keluar->id) }}" target="_blank"
                                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-xs transition duration-150 shadow-md shadow-blue-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Unduh Dokumen Berkas
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="w-full border border-slate-200 rounded-xl overflow-hidden bg-slate-50 flex items-center justify-center p-4">
                                <img src="{{ asset('storage/'.$surat_keluar->file) }}" alt="Preview Berkas" class="max-w-full max-h-[600px] object-contain shadow-md rounded-lg">
                            </div>
                        @endif
                    @else
                        <div class="py-24 border border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-400 bg-slate-50/50">
                            <svg class="w-16 h-16 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <p class="font-medium text-slate-500 text-sm">Tidak ada berkas fisik dilampirkan.</p>
                            <p class="text-xs text-slate-400 mt-1">Hanya menyimpan rincian informasi metadata surat.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- RIGHT COLUMN: METADATA & E-SIGNATURES (Takes 1 col) -->
            <div class="space-y-6">

                <!-- Metadata Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 bg-white">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Rincian Surat Keluar
                    </h3>

                    <div class="space-y-3.5 text-sm">
                        <div>
                            <span class="text-slate-400 text-xs uppercase tracking-wider block font-semibold">Nomor Surat</span>
                            <span class="font-bold text-slate-800 block mt-0.5">{{ $surat_keluar->nomor_surat }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-xs uppercase tracking-wider block font-semibold">Kategori</span>
                            <span class="font-medium text-slate-700 block mt-0.5">{{ $surat_keluar->kategori_surat }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-xs uppercase tracking-wider block font-semibold">Tanggal Surat</span>
                            <span class="font-medium text-slate-700 block mt-0.5">{{ \Carbon\Carbon::parse($surat_keluar->tanggal_surat)->locale('id')->translatedFormat('d F Y') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-xs uppercase tracking-wider block font-semibold">Tujuan Penerima</span>
                            <span class="font-semibold text-slate-800 block mt-0.5">{{ $surat_keluar->tujuan }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-xs uppercase tracking-wider block font-semibold">Perihal</span>
                            <span class="font-medium text-slate-700 block mt-0.5">{{ $surat_keluar->perihal }}</span>
                        </div>
                    </div>
                </div>

                <!-- Approval & E-Sign Action Card -->
                @php
                    $user = auth()->user();
                    $isDirut = $user->role === 'dirut';
                    $showDirutAction = $isDirut && $surat_keluar->status === 'menunggu_dirut';

                    // Direktur hanya memverifikasi surat pada direktoratnya sendiri.
                    $showVerifikasiAction = $user->isDirektur()
                        && $surat_keluar->status === 'menunggu_direktur'
                        && $surat_keluar->unit_verifikasi === $user->unit;
                @endphp

                @if($showVerifikasiAction)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 no-print">
                        <h3 class="text-base font-bold text-slate-800 mb-2">Verifikasi Direktur</h3>
                        <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                            Sebagai {{ $user->label_jabatan }}, periksa isi surat ini lalu bubuhkan verifikasi agar
                            diteruskan ke Direktur Utama untuk ditandatangani. Bila masih perlu diperbaiki,
                            kembalikan ke sekretaris dengan catatan revisi.
                        </p>

                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <form action="{{ route('surat-keluar.verifikasi', $surat_keluar->id) }}" method="POST"
                                      onsubmit="event.preventDefault(); ConfirmModal.show({title:'Verifikasi Surat',message:'Surat ini akan diteruskan ke Direktur Utama untuk ditandatangani. Lanjutkan?',variant:'approve',confirmText:'Ya, Verifikasi'}).then(ok=>{if(ok)this.submit()})"
                                      class="inline flex-1">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl transition duration-200 shadow-md shadow-blue-500/10 text-xs cursor-pointer flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Verifikasi & Teruskan
                                    </button>
                                </form>

                                <button onclick="document.getElementById('reject-form-container').classList.toggle('hidden')" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-2.5 px-4 border border-red-100 rounded-xl transition duration-200 text-xs cursor-pointer flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Kembalikan
                                </button>
                            </div>

                            <div id="reject-form-container" class="hidden mt-4 pt-4 border-t border-slate-100">
                                <form action="{{ route('surat-keluar.reject', $surat_keluar->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <label class="block text-xs font-semibold text-slate-700 mb-2">
                                        Catatan Revisi <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="catatan_revisi" rows="3" required placeholder="Tuliskan poin yang perlu diperbaiki..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:bg-white outline-none transition-all text-slate-800 text-xs resize-none"></textarea>

                                    <div class="flex justify-end gap-3 mt-3">
                                        <button type="button" onclick="document.getElementById('reject-form-container').classList.add('hidden')" class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700">
                                            Batal
                                        </button>
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1.5 px-3 rounded-lg text-xs shadow-md shadow-red-500/10">
                                            Kembalikan ke Sekretaris
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if($showDirutAction)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 no-print">
                        <h3 class="text-base font-bold text-slate-800 mb-2">Persetujuan Surat Keluar</h3>
                        <p class="text-xs text-slate-500 mb-6 leading-relaxed">Sebagai Direktur Utama, Anda dapat menyetujui dokumen ini (stempel TTD dan QR Code akan disematkan otomatis) atau menolaknya dengan catatan revisi.</p>
                        
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <form action="{{ route('surat-keluar.approve', $surat_keluar->id) }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'E-Sign & Setujui',message:'Tanda tangan digital (E-Sign) dan QR Code akan disematkan ke dalam dokumen surat keluar ini. Tindakan ini tidak dapat dibatalkan. Lanjutkan?',variant:'approve',confirmText:'Ya, Setujui & E-Sign'}).then(ok=>{if(ok)this.submit()})" class="inline flex-1">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-xl transition duration-200 shadow-md shadow-emerald-500/10 text-xs cursor-pointer flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Setujui & E-Sign
                                    </button>
                                </form>
                                
                                <button onclick="document.getElementById('reject-form-container').classList.toggle('hidden')" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-2.5 px-4 border border-red-100 rounded-xl transition duration-200 text-xs cursor-pointer flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Tolak / Revisi
                                </button>
                            </div>

                            <div id="reject-form-container" class="hidden mt-4 pt-4 border-t border-slate-100">
                                <form action="{{ route('surat-keluar.reject', $surat_keluar->id) }}" method="POST">
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

                <!-- E-Signatures Display Status Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6 bg-white">
                    <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Verifikasi E-Signatures
                    </h3>

                    <!-- Tanda Tangan Direktur Utama -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400 font-bold uppercase tracking-wider">Direktur Utama</span>
                            @if($surat_keluar->approvedDirut)
                                <span class="text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">Signed</span>
                            @else
                                <span class="text-slate-400 font-semibold bg-slate-50 px-2 py-0.5 rounded">Pending</span>
                            @endif
                        </div>

                        @if($surat_keluar->approvedDirut)
                            <!-- Stempel Digital Dirut -->
                            <div class="p-3 bg-emerald-50/50 border border-emerald-100 rounded-xl space-y-2 relative overflow-hidden">
                                <div class="absolute -right-6 -bottom-6 text-emerald-100/50 opacity-20">
                                    <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.9c.773-.954 1.93-1.4 3.041-1.116L8.85 4.72a.75.75 0 00.9-.315l1.523-2.61a3.023 3.023 0 015.19 0l1.522 2.61a.75.75 0 00.9.315l3.642-.937a3.023 3.023 0 013.041 1.116l1.83 2.257a3.023 3.023 0 010 3.738l-1.83 2.257a3.023 3.023 0 01-3.041 1.116l-3.642-.937a.75.75 0 00-.9.315l-1.522 2.61a3.023 3.023 0 01-5.19 0l-1.523-2.61a.75.75 0 00-.9-.315l-3.641.937a3.023 3.023 0 01-3.042-1.116l-1.83-2.257a3.023 3.023 0 010-3.738l1.83-2.257z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div class="flex items-center gap-2 text-emerald-700 text-xs font-bold uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    E-Sign Terverifikasi
                                </div>
                                <p class="text-xs font-bold text-slate-800 m-0">{{ $surat_keluar->approvedDirut->name }}</p>
                                <p class="text-[10px] text-slate-500 m-0">Direktur Utama</p>
                                <p class="text-[9px] text-slate-400 m-0">Pada: {{ \Carbon\Carbon::parse($surat_keluar->approved_dirut_at)->locale('id')->translatedFormat('d M Y - H:i') }} WIB</p>
                                <p class="text-[8px] text-emerald-600 font-mono tracking-tighter m-0 truncate">HASH: {{ hash('sha256', $surat_keluar->id . $surat_keluar->approved_dirut_at) }}</p>
                            </div>
                        @else
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-slate-400 text-xs italic">
                                Belum ditandatangani
                            </div>
                        @endif
                    </div>
                </div>

                <!-- QR Code Verification Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 text-center bg-white">
                    <h4 class="text-sm font-bold text-slate-800 mb-2">QR Code Verifikasi Resmi</h4>
                    <p class="text-xs text-slate-500 mb-4">Pindai QR Code di bawah untuk memverifikasi keabsahan tanda tangan elektronik (E-Sign) surat ini secara publik.</p>
                    
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 inline-block mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('surat-keluar.verify', $surat_keluar->verify_token)) }}"
                             alt="QR Code Verifikasi E-Sign"
                             class="w-36 h-36 mx-auto bg-white p-2 rounded-lg border border-slate-200">
                    </div>
                    
                    <div class="text-[10px] font-medium text-slate-400">
                        <a href="{{ route('surat-keluar.verify', $surat_keluar->verify_token) }}" target="_blank" class="text-blue-500 hover:underline">
                            {{ route('surat-keluar.verify', $surat_keluar->verify_token) }}
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
