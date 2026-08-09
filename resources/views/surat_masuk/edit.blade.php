<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat Masuk - E-Office</title>
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
            <a href="{{ route('surat-masuk.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Daftar Surat
            </a>
        </div>

        <!-- Card Container -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

            <!-- Header -->
            <div class="mb-8 border-b border-slate-100 pb-5">
                <h2 class="text-2xl font-bold text-slate-800">
                    Edit Surat Masuk
                </h2>
                <p class="text-slate-500 text-sm mt-1.5">
                    Perbarui informasi surat masuk. Kosongkan upload file jika tidak ingin mengubah dokumen.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada input Anda. Silakan periksa kembali form di bawah.</p>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('surat-masuk.update', $surat_masuk->id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-5">

                @csrf
                @method('PUT') <!-- Ini wajib untuk route update/edit di Laravel -->

                <!-- Nomor Surat -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nomor Surat
                    </label>
                    <input type="text"
                           name="nomor_surat"
                           value="{{ old('nomor_surat', $surat_masuk->nomor_surat) }}"
                           required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('nomor_surat') border-red-500 focus:ring-red-500 @enderror">
                    @error('nomor_surat')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Surat -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kategori Surat
                    </label>
                    @php
                        $isCustomKategori = !in_array($surat_masuk->kategori_surat, ['SK', 'SU', 'SP', 'ST', 'INV']);
                        $selectedKategori = old('kategori_surat', $isCustomKategori ? 'Lainnya' : $surat_masuk->kategori_surat);
                        $customKategoriValue = old('kategori_surat_lainnya', $isCustomKategori ? $surat_masuk->kategori_surat : '');
                    @endphp
                    <select name="kategori_surat" id="kategori_surat"
                            required onchange="toggleKategoriLainnya(this)"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('kategori_surat') border-red-500 focus:ring-red-500 @enderror">
                        <option value="" disabled>Pilih Kategori Surat</option>
                        <option value="SK" {{ $selectedKategori == 'SK' ? 'selected' : '' }}>Surat Keputusan (SK)</option>
                        <option value="SU" {{ $selectedKategori == 'SU' ? 'selected' : '' }}>Surat Undangan (SU)</option>
                        <option value="SP" {{ $selectedKategori == 'SP' ? 'selected' : '' }}>Surat Pemberitahuan (SP)</option>
                        <option value="ST" {{ $selectedKategori == 'ST' ? 'selected' : '' }}>Surat Tugas (ST)</option>
                        <option value="INV" {{ $selectedKategori == 'INV' ? 'selected' : '' }}>Invoice (INV)</option>
                        <option value="Lainnya" {{ $selectedKategori == 'Lainnya' ? 'selected' : '' }}>Lainnya (Tulis Sendiri)</option>
                    </select>
                    @error('kategori_surat')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input Kategori Lainnya (Hidden by Default) -->
                <div id="kategori_lainnya_container" class="{{ $selectedKategori == 'Lainnya' ? '' : 'hidden' }}">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tulis Kategori Surat
                    </label>
                    <input type="text"
                           name="kategori_surat_lainnya"
                           id="kategori_surat_lainnya"
                           value="{{ $customKategoriValue }}"
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
                           value="{{ old('tanggal_surat', $surat_masuk->tanggal_surat) }}"
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
                           value="{{ old('pengirim', $surat_masuk->pengirim) }}"
                           required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('pengirim') border-red-500 focus:ring-red-500 @enderror">
                    @error('pengirim')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sifat & Jalur Penerimaan -->
                @include('surat_masuk.partials.sifat-jalur', [
                    'sifat' => old('sifat', $surat_masuk->sifat),
                    'jalur' => old('jalur_penerimaan', $surat_masuk->jalur_penerimaan),
                ])

                <!-- Penerima -->
                @include('surat_masuk.partials.penerima', [
                    'users'         => $users,
                    'penerimaTipe'  => old('penerima_tipe', $surat_masuk->penerima_role ? 'role' : 'user'),
                    'penerimaId'    => old('penerima_id', $surat_masuk->penerima_id),
                    'penerimaRole'  => old('penerima_role', $surat_masuk->penerima_role),
                ])

                <!-- Perihal -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Perihal
                    </label>
                    <input type="text"
                           name="perihal"
                           value="{{ old('perihal', $surat_masuk->perihal) }}"
                           required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('perihal') border-red-500 focus:ring-red-500 @enderror">
                    @error('perihal')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Upload File Baru (Opsional) -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Ganti File <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    
                    @if($surat_masuk->file)
                        <div class="mb-3 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                            <span class="text-sm text-blue-700 truncate mr-4">File saat ini tersimpan</span>
                            <a href="{{ asset('storage/' . $surat_masuk->file) }}" target="_blank" class="text-xs font-semibold text-blue-600 hover:underline shrink-0">Lihat File</a>
                        </div>
                    @endif

                    <input type="file"
                           name="file"
                           class="block w-full text-sm text-slate-500 
                                  file:mr-4 file:py-2.5 file:px-4 
                                  file:rounded-lg file:border-0 
                                  file:text-sm file:font-semibold 
                                  file:bg-slate-100 file:text-slate-700 
                                  hover:file:bg-slate-200 transition-all 
                                  bg-slate-50 border border-slate-200 rounded-xl cursor-pointer focus:outline-none @error('file') border-red-500 @enderror">
                    <p class="text-xs text-slate-400 mt-2">
                        Biarkan kosong jika tidak ingin mengubah file dokumen. (Maks. 2MB)
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
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-md shadow-amber-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Update Data
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