<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tindak Lanjut Disposisi - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 p-4 sm:p-6 lg:p-8">

<div class="max-w-7xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('disposisi.saya') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Disposisi Saya
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-8">
        
        <div class="p-6 sm:p-8 border-b border-slate-100">
            <h1 class="text-2xl font-bold text-slate-800">
                Tindak Lanjut Disposisi
            </h1>
            <p class="text-slate-500 text-sm mt-1.5">
                Silakan pelajari detail dan isi lampiran surat sebelum memberikan tindak lanjut.
            </p>
        </div>

        <div class="p-6 sm:p-8">
            
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 text-sm">
                <div class="flex items-center gap-2 mb-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Terdapat Kesalahan:
                </div>
                <ul class="list-disc ml-6 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Info Surat Masuk -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">
                            Informasi Surat Masuk
                        </h2>
                        
                        <div class="space-y-4 text-sm">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Nomor Surat</span>
                                <span class="text-slate-800 font-medium sm:w-2/3">{{ $disposisi->suratMasuk->nomor_surat }}</span>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Tanggal</span>
                                <span class="text-slate-800 sm:w-2/3">{{ \Carbon\Carbon::parse($disposisi->suratMasuk->tanggal_surat)->translatedFormat('d F Y') }}</span>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Pengirim</span>
                                <span class="text-slate-800 sm:w-2/3">{{ $disposisi->suratMasuk->pengirim }}</span>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Perihal</span>
                                <span class="text-slate-800 sm:w-2/3">{{ $disposisi->suratMasuk->perihal }}</span>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Dari Disposisi</span>
                                <span class="text-slate-800 sm:w-2/3 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">{{ substr($disposisi->dariUser->name, 0, 1) }}</span>
                                    {{ $disposisi->dariUser->name }}
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Status Saat Ini</span>
                                <div class="sm:w-2/3">
                                    @if($disposisi->status == 'menunggu')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">Menunggu</span>
                                    @elseif($disposisi->status == 'diproses')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Diproses</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 mt-2">
                                <span class="font-semibold text-slate-600 sm:w-1/3">Tenggat Waktu</span>
                                <div class="sm:w-2/3 font-semibold text-slate-800">
                                    @if($disposisi->batas_waktu)
                                        {{ \Carbon\Carbon::parse($disposisi->batas_waktu)->translatedFormat('d F Y') }}
                                        
                                        @php
                                            $isOverdue = $disposisi->status !== 'selesai' && \Carbon\Carbon::parse($disposisi->batas_waktu)->isPast() && !\Carbon\Carbon::parse($disposisi->batas_waktu)->isToday();
                                            $isDueToday = $disposisi->status !== 'selesai' && \Carbon\Carbon::parse($disposisi->batas_waktu)->isToday();
                                        @endphp
                                        
                                        @if($isOverdue)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                                Terlambat
                                            </span>
                                        @elseif($isDueToday)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                Hari Ini
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic font-normal">Tanpa Batas Waktu</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Dokumen -->
                <div class="lg:col-span-7">
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col h-full shadow-sm">
                        <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Preview Dokumen</h2>
                            
                            @if($disposisi->suratMasuk->file)
                            <a href="{{ asset('storage/'.$disposisi->suratMasuk->file) }}" target="_blank"
                               class="flex items-center gap-1.5 text-xs font-semibold bg-white border border-slate-300 text-slate-600 hover:text-blue-600 hover:border-blue-300 px-3 py-1.5 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Tab Baru
                            </a>
                            @endif
                        </div>

                        <div class="p-2 flex-1 bg-slate-100">
                            @if($disposisi->suratMasuk->file)
                                @php
                                    $ext = strtolower(pathinfo($disposisi->suratMasuk->file, PATHINFO_EXTENSION));
                                @endphp

                                @if($ext == 'pdf')
                                    <iframe src="{{ asset('storage/'.$disposisi->suratMasuk->file) }}" 
                                            class="w-full h-[400px] lg:h-[500px] rounded-lg shadow-sm border border-slate-200 bg-white"></iframe>
                                @else
                                    <div class="w-full h-[400px] lg:h-[500px] overflow-auto rounded-lg shadow-sm border border-slate-200 bg-white p-2 flex justify-center">
                                        <img src="{{ asset('storage/'.$disposisi->suratMasuk->file) }}" class="max-w-full h-auto rounded">
                                    </div>
                                @endif
                            @else
                                <div class="w-full h-[400px] lg:h-[500px] flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-lg text-slate-400 bg-white">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p>Tidak ada lampiran surat.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <hr class="my-10 border-slate-100">

            <!-- Form Update Status & Tindak Lanjut -->
            <form action="{{ route('disposisi.update', $disposisi->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Instruksi Disposisi <span class="text-slate-400 font-normal">(Dari {{ $disposisi->dariUser->name }})</span>
                        </label>
                        <textarea readonly
                                  rows="8"
                                  class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-600 cursor-not-allowed outline-none resize-none leading-relaxed">{{ $disposisi->instruksi }}</textarea>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Update Status
                            </label>
                            <select name="status"
                                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 cursor-pointer shadow-sm @error('status') border-red-500 focus:ring-red-500 @enderror">
                                <option value="menunggu" {{ old('status', $disposisi->status) == 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                                <option value="diproses" {{ old('status', $disposisi->status) == 'diproses' ? 'selected' : '' }}>Diproses</option>
                                <option value="selesai" {{ old('status', $disposisi->status) == 'selesai' ? 'selected' : '' }}>Selesai</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Catatan Hasil Tindak Lanjut
                            </label>
                            <textarea name="catatan_tindak_lanjut"
                                      rows="3"
                                      class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 resize-none shadow-sm placeholder-slate-400 @error('catatan_tindak_lanjut') border-red-500 focus:ring-red-500 @enderror"
                                      placeholder="Tuliskan hasil laporan atau tindak lanjut dari instruksi di atas...">{{ old('catatan_tindak_lanjut', $disposisi->catatan_tindak_lanjut) }}</textarea>
                            @error('catatan_tindak_lanjut')
                                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Lampiran Bukti / Laporan Kerja (Opsional)
                            </label>
                            <input type="file" 
                                   name="file_tindak_lanjut"
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50 shadow-sm focus:ring-2 focus:ring-blue-500 @error('file_tindak_lanjut') border-red-500 @enderror">
                            
                            @if($disposisi->file_tindak_lanjut)
                                <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500 bg-emerald-50 border border-emerald-100 rounded-xl p-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>
                                        File terunggah: 
                                        <a href="{{ asset('storage/' . $disposisi->file_tindak_lanjut) }}" target="_blank" class="text-blue-600 font-bold hover:underline">
                                            Lihat Laporan Kerja
                                        </a>
                                    </span>
                                </div>
                            @endif
                            @error('file_tindak_lanjut')
                                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                            @enderror
                            <p class="text-[10px] text-slate-400 mt-1">Format: PDF, JPG, PNG, DOC, ZIP. Maksimal 5MB.</p>
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 pt-8 mt-6 border-t border-slate-100">
                    <a href="{{ route('disposisi.saya') }}"
                       class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                        Batal
                    </a>

                    @if(auth()->user()->role == 'direktur1' || auth()->user()->role == 'direktur2')
                    <a href="{{ route('disposisi.continue',$disposisi->id) }}"
                       class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow transition-colors">
                        Teruskan Disposisi
                    </a>
                    @endif

                    <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow transition-colors">
                        Simpan Tindak Lanjut
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Timeline Riwayat -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
        
        <h2 class="text-xl font-bold text-slate-800 mb-8 flex items-center gap-2">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Riwayat Disposisi Surat
        </h2>

        <div class="space-y-0 pl-2">
            @forelse($timeline as $item)
                <div class="relative pl-8 pb-8 last:pb-0 group">
                    
                    @if(!$loop->last)
                        <div class="absolute left-[11px] top-8 bottom-0 w-0.5 bg-slate-200"></div>
                    @endif
                    <div class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-blue-100 border-4 border-white flex items-center justify-center shadow-sm">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow hover:border-slate-200">
                        
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2 flex-wrap">
                                    {{ $item->dariUser->name }}
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    {{ $item->kepadaUser->name }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ \Carbon\Carbon::parse($item->tanggal_disposisi)->translatedFormat('d M Y - H:i') }}
                                </p>
                            </div>
                            
                            <div>
                                @if($item->status == 'menunggu')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">Menunggu</span>
                                @elseif($item->status == 'diproses')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Diproses</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Instruksi:</span>
                            <div class="mt-1.5 p-3.5 bg-slate-50 border border-slate-100 rounded-lg text-slate-700 text-sm leading-relaxed">
                                {{ $item->instruksi }}
                            </div>
                        </div>

                        @if($item->catatan_tindak_lanjut)
                        <div>
                            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Tindak Lanjut:</span>
                            <div class="mt-1.5 p-3.5 bg-emerald-50/50 border border-emerald-100 rounded-lg text-emerald-800 text-sm leading-relaxed">
                                {{ $item->catatan_tindak_lanjut }}
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 bg-slate-50 border border-dashed border-slate-200 rounded-xl">
                    Belum ada riwayat disposisi untuk surat ini.
                </div>
            @endforelse
        </div>

    </div>

</div>

</body>
</html>