<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teruskan Disposisi - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex justify-center py-10 px-4 sm:px-6">

    <div class="w-full max-w-3xl">

        <div class="mb-4">
            <a href="{{ route('disposisi.saya') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Disposisi Saya
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

            <div class="mb-8 border-b border-slate-100 pb-5 flex items-center gap-3">
                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        Teruskan Disposisi
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Delegasikan atau teruskan instruksi surat ini kepada staf/pengguna lain.
                    </p>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-8 relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-slate-300"></div>
                
                <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">
                    Referensi Surat & Instruksi
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Nomor Surat</span>
                        <span class="text-sm font-bold text-slate-800">{{ $disposisi->suratMasuk->nomor_surat }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Perihal</span>
                        <span class="text-sm font-medium text-slate-700">{{ $disposisi->suratMasuk->perihal }}</span>
                    </div>
                </div>
                
                <div class="bg-white border border-slate-200 rounded-lg p-3.5">
                    <span class="block text-xs font-semibold text-slate-500 mb-1">Instruksi Sebelumnya:</span>
                    <p class="text-sm text-slate-700 leading-relaxed italic">
                        "{{ $disposisi->instruksi }}"
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada input Anda. Silakan periksa kembali form di bawah.</p>
                </div>
            @endif

            <form action="{{ route('disposisi.continue.store') }}" method="POST">
                @csrf
                
                <input type="hidden" name="parent_disposisi_id" value="{{ $disposisi->id }}">
                <input type="hidden" name="surat_masuk_id" value="{{ $disposisi->suratMasuk->id }}">

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Teruskan Kepada
                        </label>
                        <select name="kepada_user_id"
                                required
                                class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 cursor-pointer shadow-sm @error('kepada_user_id') border-red-500 focus:ring-red-500 @enderror">
                            <option value="" disabled {{ old('kepada_user_id') ? '' : 'selected' }} class="text-slate-400">-- Pilih Penerima Disposisi --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('kepada_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
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
                               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 cursor-pointer shadow-sm @error('batas_waktu') border-red-500 focus:ring-red-500 @enderror">
                        @error('batas_waktu')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Instruksi Baru
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
                                  class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 resize-none shadow-sm placeholder-slate-400 @error('instruksi') border-red-500 focus:ring-red-500 @enderror"
                                  placeholder="Masukkan instruksi tambahan atau arahan kepada staf yang dipilih...">{{ old('instruksi') }}</textarea>
                        @error('instruksi')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-8 mt-6 border-t border-slate-100">
                    <a href="{{ route('disposisi.saya') }}"
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