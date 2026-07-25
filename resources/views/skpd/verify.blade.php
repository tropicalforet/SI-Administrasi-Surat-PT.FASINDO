<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi SKPD E-Sign - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <!-- Content Area -->
    <div class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl w-full bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            
            <!-- Top Header Brand -->
            <div class="bg-slate-900 p-6 text-white text-center flex flex-col items-center justify-center gap-3">
                <div class="w-12 h-12 bg-white rounded-xl p-1.5 shadow-md">
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
                    @else
                        <div class="w-full h-full bg-blue-600 rounded flex items-center justify-center font-bold text-white text-sm">F</div>
                    @endif
                </div>
                <div>
                    <h1 class="text-md font-bold tracking-wide">PT. FASADETAMA INDONESIA</h1>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5">Sistem Verifikasi Surat Perjalanan Dinas</p>
                </div>
            </div>

            <!-- Verification Status Banner -->
            <div class="p-8 text-center border-b border-slate-100">
                @if(strtolower($skpd->status) === 'disetujui')
                    <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-emerald-200 text-emerald-600">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-emerald-800">SKPD TERVERIFIKASI SAH</h2>
                    <p class="text-xs text-slate-500 mt-1.5">Surat Keterangan Perjalanan Dinas ini telah ditandatangani secara elektronik (E-Sign) oleh Direktur Utama.</p>
                @else
                    <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-amber-200 text-amber-600">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-amber-800">DOKUMEN DALAM PROSES / DITOLAK</h2>
                    <p class="text-xs text-slate-500 mt-1.5">Pengajuan Perjalanan Dinas ini belum mendapatkan persetujuan atau telah ditolak.</p>
                @endif
            </div>

            <!-- Document Details -->
            <div class="p-8 space-y-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Detail Informasi SKPD</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <span class="text-slate-400 block font-medium">Nomor SKPD</span>
                        <span class="font-bold text-slate-800 mt-0.5 block">{{ $skpd->nomor_skpd ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Pelaksana Dinas</span>
                        <span class="font-bold text-slate-800 mt-0.5 block">{{ $skpd->nama_pegawai }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Tujuan Dinas</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block">{{ $skpd->tujuan_dinas }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Durasi Perjalanan</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block">
                            {{ \Carbon\Carbon::parse($skpd->tanggal_berangkat)->locale('id')->translatedFormat('d M Y') }} s/d 
                            {{ \Carbon\Carbon::parse($skpd->tanggal_kembali)->locale('id')->translatedFormat('d M Y') }} 
                            ({{ $skpd->durasi_hari }} Hari)
                        </span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-slate-400 block font-medium">Keperluan / Maksud Dinas</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block">{{ $skpd->keperluan }}</span>
                    </div>
                </div>

                <hr class="border-slate-100 my-6">

                <!-- Digital Signatures list -->
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Penandatangan Digital (E-Sign)</h3>

                <div class="space-y-4">
                    <!-- Penandatangan: Direktur Utama -->
                    <div class="p-4 rounded-2xl border {{ strtolower($skpd->status) === 'disetujui' ? 'bg-emerald-50/30 border-emerald-100' : 'bg-slate-50/50 border-slate-100' }} flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-xl {{ strtolower($skpd->status) === 'disetujui' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-400' }} mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">
                                    Fredy Nuriat, S.Si.
                                </h4>
                                <p class="text-xs text-slate-500">Direktur Utama</p>
                                @if(strtolower($skpd->status) === 'disetujui')
                                    <p class="text-[10px] text-slate-400 mt-1">Pada: {{ \Carbon\Carbon::parse($skpd->updated_at)->locale('id')->translatedFormat('d M Y - H:i') }} WIB</p>
                                @endif
                            </div>
                        </div>
                        <div>
                            @if(strtolower($skpd->status) === 'disetujui')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    SIGNED
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                                    PENDING
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Footer -->
    <div class="p-6 text-center text-xs text-slate-400 border-t border-slate-100 bg-white">
        &copy; {{ date('Y') }} PT Fasadetama Indonesia. All rights reserved.<br>
        Sistem Perjalanan Dinas Online (E-Office)
    </div>

</body>
</html>
