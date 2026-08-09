<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Surat Keluar - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div class="w-full max-w-xl">
        
        <div class="mb-4">
            <a href="{{ route('surat-keluar.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Daftar Surat Keluar
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

            <div class="mb-8 border-b border-slate-100 pb-5">
                <h2 class="text-2xl font-bold text-slate-800">
                    Tambah Surat Keluar
                </h2>
                <p class="text-slate-500 text-sm mt-1.5 flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Nomor surat akan di-generate otomatis oleh sistem.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada input Anda. Silakan periksa kembali form di bawah.</p>
                </div>
            @endif

            <form action="{{ route('surat-keluar.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-5">

                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kategori Surat
                    </label>
                    <select name="kategori_surat" id="kategori_surat"
                            required onchange="toggleKategoriLainnya(this)"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('kategori_surat') border-red-500 focus:ring-red-500 @enderror">
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <option value="SK" {{ old('kategori_surat') == 'SK' ? 'selected' : '' }}>Surat Keputusan (SK)</option>
                        <option value="SU" {{ old('kategori_surat') == 'SU' ? 'selected' : '' }}>Surat Undangan (SU)</option>
                        <option value="SP" {{ old('kategori_surat') == 'SP' ? 'selected' : '' }}>Surat Pemberitahuan (SP)</option>
                        <option value="ST" {{ old('kategori_surat') == 'ST' ? 'selected' : '' }}>Surat Tugas (ST)</option>
                        <option value="INV" {{ old('kategori_surat') == 'INV' ? 'selected' : '' }}>Invoice (INV)</option>
                        <option value="Lainnya" {{ old('kategori_surat') == 'Lainnya' ? 'selected' : '' }}>Lainnya (Tulis Sendiri)</option>
                    </select>
                    @error('kategori_surat')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input Kategori Lainnya (Hidden by Default) -->
                <div id="kategori_lainnya_container" class="{{ old('kategori_surat') == 'Lainnya' ? '' : 'hidden' }}">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tulis Kategori Surat
                    </label>
                    <input type="text"
                           name="kategori_surat_lainnya"
                           id="kategori_surat_lainnya"
                           value="{{ old('kategori_surat_lainnya') }}"
                           placeholder="Masukkan kategori surat"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 @error('kategori_surat_lainnya') border-red-500 focus:ring-red-500 @enderror">
                    @error('kategori_surat_lainnya')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tanggal Surat
                    </label>
                    <input type="date"
                           name="tanggal_surat"
                           value="{{ old('tanggal_surat') }}"
                           required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_surat') border-red-500 focus:ring-red-500 @enderror">
                    @error('tanggal_surat')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                @include('surat_keluar.partials.unit-verifikasi', ['unitVerifikasi' => old('unit_verifikasi')])

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tujuan (Kepada)
                    </label>
                    <input type="text"
                           name="tujuan"
                           value="{{ old('tujuan') }}"
                           required
                           placeholder="Nama instansi / perorangan yang dituju"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 @error('tujuan') border-red-500 focus:ring-red-500 @enderror">
                    @error('tujuan')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Perihal
                    </label>
                    <input type="text"
                           name="perihal"
                           value="{{ old('perihal') }}"
                           required
                           placeholder="Maksud atau inti surat"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 @error('perihal') border-red-500 focus:ring-red-500 @enderror">
                    @error('perihal')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Upload Lampiran / Draf Surat <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="file"
                           name="file"
                           class="block w-full text-sm text-slate-500 
                                  file:mr-4 file:py-2.5 file:px-4 
                                  file:rounded-lg file:border-0 
                                  file:text-sm file:font-semibold 
                                  file:bg-blue-50 file:text-blue-700 
                                  hover:file:bg-blue-100 transition-all 
                                  bg-slate-50 border border-slate-200 rounded-xl cursor-pointer focus:outline-none @error('file') border-red-500 @enderror">
                    <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Format yang didukung: PDF, JPG, PNG, DOCX (Maks. 5MB)
                    </p>
                    @error('file')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror

                    <!-- Info Box Placeholders DOCX -->
                    <div class="mt-4 p-4 bg-blue-50/50 border border-blue-100 rounded-xl text-xs space-y-2 text-slate-600">
                        <h4 class="font-bold text-blue-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 01-2 2h0a2 2 0 01-2-2v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            Tips E-Sign Dokumen Word (DOCX):
                        </h4>
                        <p class="leading-relaxed">Jika mengunggah draf berformat Word, Anda dapat menuliskan kode-kode penanda (*placeholders*) berikut di dalam berkas Word Anda. Sistem akan otomatis menggantinya dengan tanda tangan asli dan QR Code saat disetujui Dirut:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${nomor_surat}</code> : Nomor surat keluar dinamis yang dibuat sistem.</li>
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${ttd_dirut}</code> : Lokasi gambar tanda tangan digital.</li>
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${qr_code}</code> : Lokasi QR Code untuk verifikasi keaslian surat.</li>
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${nama_dirut}</code> : Nama lengkap penanda tangan (Direktur Utama).</li>
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${tanggal_ttd}</code> : Tanggal penyematan E-Sign.</li>
                            <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${hash_verify}</code> : Kode enkripsi hash SHA-256 dokumen.</li>
                        </ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 mt-4">
                    <a href="{{ route('surat-keluar.index') }}"
                       class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition-colors">
                        Batal
                    </a>

                    <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Simpan Draf Surat
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script>
        function toggleKategoriLainnya(select) {
            const container = document.getElementById('kategori_lainnya_container');
            const input = document.getElementById('kategori_surat_lainnya');
            
            if (select.value === 'Lainnya') {
                container.classList.remove('hidden');
                input.required = true;
            } else {
                container.classList.add('hidden');
                input.required = false;
                input.value = '';
            }
        }
    </script>
</body>
</html>