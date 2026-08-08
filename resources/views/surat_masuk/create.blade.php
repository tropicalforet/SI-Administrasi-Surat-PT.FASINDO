<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Surat Masuk - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div class="w-full max-w-xl">
        
        <!-- Tombol Kembali -->
        <div class="mb-4">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard
            </a>
        </div>

        <!-- Card Container -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

            <!-- Header -->
            <div class="mb-8 border-b border-slate-100 pb-5">
                <h2 class="text-2xl font-bold text-slate-800">
                    Tambah Surat Masuk
                </h2>
                <p class="text-slate-500 text-sm mt-1.5">
                    Masukkan detail informasi dan unggah dokumen surat masuk yang baru.
                </p>
            </div>

            <!-- Global Error Message (Opsional, tapi bagus untuk notifikasi umum) -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada input Anda. Silakan periksa kembali form di bawah.</p>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('surat-masuk.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-5">

                @csrf

                <!-- Nomor Surat -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nomor Surat
                    </label>
                    <input type="text"
                           name="nomor_surat"
                           value="{{ old('nomor_surat') }}"
                           required
                           placeholder="Contoh: 001/SK/2024"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 @error('nomor_surat') border-red-500 focus:ring-red-500 @enderror">
                    @error('nomor_surat')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Surat -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kategori Surat
                    </label>
                    <select name="kategori_surat" id="kategori_surat"
                            required onchange="toggleKategoriLainnya(this)"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('kategori_surat') border-red-500 focus:ring-red-500 @enderror">
                        <option value="" disabled selected>Pilih Kategori Surat</option>
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


                <!-- Tanggal Surat -->
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

                <!-- Pengirim -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Pengirim
                    </label>
                    <input type="text"
                           name="pengirim"
                           value="{{ old('pengirim') }}"
                           required
                           placeholder="Nama instansi atau pengirim"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 placeholder-slate-400 @error('pengirim') border-red-500 focus:ring-red-500 @enderror">
                    @error('pengirim')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Penerima -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Penerima (Tujuan Surat)
                    </label>
                    <select name="penerima_id"
                            required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('penerima_id') border-red-500 focus:ring-red-500 @enderror">
                        <option value="" disabled selected>Pilih Penerima</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('penerima_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                        @endforeach
                    </select>
                    @error('penerima_id')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Perihal -->
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

                <!-- Upload File -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Upload File <span class="text-slate-400 font-normal">(Opsional)</span>
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
                        Format yang didukung: PDF, JPG, PNG (Maks. 2MB)
                    </p>
                    @error('file')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 mt-4">
                    <a href="{{ route('surat-masuk.index') }}"
                       class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition-colors">
                        Batal
                    </a>

                    <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Simpan Data
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