<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Disposisi Surat - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex justify-center py-10 px-4 sm:px-6">

    <div class="w-full max-w-2xl">

        <!-- Tombol Kembali -->
        <div class="mb-4">
            <a href="{{ route('surat-masuk.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Surat Masuk
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

            <!-- Header -->
            <div class="mb-6 border-b border-slate-100 pb-5">
                <h2 class="text-2xl font-bold text-slate-800">
                    Disposisi Surat
                </h2>
                <p class="text-slate-500 text-sm mt-1.5">
                    Teruskan surat masuk dan berikan instruksi kepada pengguna terkait.
                </p>
            </div>

            <!-- Detail Surat Summary -->
            <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 mb-8">
                <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-3">Informasi Surat</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block text-slate-500 mb-1">Nomor Surat</span>
                        <span class="font-semibold text-slate-800">{{ $suratMasuk->nomor_surat }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-500 mb-1">Tanggal Surat</span>
                        <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($suratMasuk->tanggal_surat)->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-slate-500 mb-1">Pengirim</span>
                        <span class="font-semibold text-slate-800">{{ $suratMasuk->pengirim }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-slate-500 mb-1">Perihal</span>
                        <span class="font-semibold text-slate-800">{{ $suratMasuk->perihal }}</span>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada input Anda. Silakan periksa kembali form di bawah.</p>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('disposisi.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Hidden Input untuk Surat Masuk ID -->
                <input type="hidden" name="surat_masuk_id" value="{{ $suratMasuk->id }}">

                <!-- Penerima Disposisi -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Penerima Disposisi (Bisa pilih lebih dari satu)
                    </label>
                    <div class="space-y-2 mt-2">
                        @foreach($users as $user)
                            <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="checkbox" name="kepada_user_id[]" value="{{ $user->id }}" 
                                       class="w-5 h-5 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                                       {{ is_array(old('kepada_user_id')) && in_array($user->id, old('kepada_user_id')) ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-800">
                                    {{ $user->name }} <span class="text-xs text-slate-500 ml-1">({{ strtoupper($user->role) }})</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('kepada_user_id')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batas Waktu Tindak Lanjut -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Batas Waktu Tindak Lanjut (Tenggat)
                    </label>
                    <input type="date" 
                           name="batas_waktu" 
                           value="{{ old('batas_waktu') }}"
                           min="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 cursor-pointer @error('batas_waktu') border-red-500 focus:ring-red-500 @enderror">
                    @error('batas_waktu')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Instruksi / Catatan -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Instruksi / Catatan Disposisi
                    </label>
                    
                    <!-- Pilihan Cepat Instruksi -->
                    <div class="mb-3">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pilihan Cepat Instruksi:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Segera tindak lanjuti" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Segera tindak lanjuti</span>
                            </label>
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Pelajari dan laporkan" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Pelajari dan laporkan</span>
                            </label>
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Siapkan konsep/balasan surat" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Siapkan konsep/balasan surat</span>
                            </label>
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Koordinasikan dengan bagian terkait" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Koordinasikan dengan bagian terkait</span>
                            </label>
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Hadir/Wakili pertemuan" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Hadir/Wakili pertemuan</span>
                            </label>
                            <label class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100/70 p-2 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                <input type="checkbox" value="Untuk diketahui / Arsip" class="quick-instruction rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-slate-700 font-medium">Untuk diketahui / Arsip</span>
                            </label>
                        </div>
                    </div>

                    <textarea id="instruksi-textarea"
                              name="instruksi"
                              rows="5"
                              required
                              placeholder="Tuliskan instruksi atau catatan tindak lanjut secara detail..."
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 resize-none placeholder-slate-400 @error('instruksi') border-red-500 focus:ring-red-500 @enderror">{{ old('instruksi') }}</textarea>
                    @error('instruksi')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-slate-100">
                    <a href="{{ route('surat-masuk.index') }}"
                       class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition-colors">
                        Batal
                    </a>

                    <button type="submit"
                             class="flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        Kirim Disposisi
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.quick-instruction');
            const textarea = document.getElementById('instruksi-textarea');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    let selected = [];
                    checkboxes.forEach(cb => {
                        if (cb.checked) {
                            selected.push("- " + cb.value);
                        }
                    });
                    
                    const currentVal = textarea.value.trim();
                    const nonListLines = currentVal.split("\n").filter(line => !line.trim().startsWith("- "));
                    
                    let newVal = selected.join("\n");
                    if (nonListLines.length > 0 && nonListLines.join("").trim() !== "") {
                        newVal = nonListLines.join("\n") + "\n\n" + newVal;
                    }
                    textarea.value = newVal.trim();
                });
            });
        });
    </script>

</body>
</html>