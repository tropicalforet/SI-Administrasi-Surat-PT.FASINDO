@extends('layouts.app')

@section('content')
<div class="py-10 px-4 sm:px-6 max-w-3xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('skpd.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Data SKPD
        </a>
    </div>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

        <div class="mb-8 border-b border-slate-100 pb-5">
            <h2 class="text-2xl font-bold text-slate-800">
                Buat SKPD
            </h2>
            <p class="text-slate-500 text-sm mt-1.5">
                Buat Surat Keterangan Perjalanan Dinas berdasarkan Surat Tugas nomor <strong>{{ $suratTugas->nomor_surat_tugas }}</strong>.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada form. Silakan periksa kembali isian Anda di bawah.</p>
            </div>
        @endif

        <form action="{{ route('skpd.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">

            @csrf
            
            <input type="hidden" name="surat_tugas_id" value="{{ $suratTugas->id }}">

            <!-- GRUP 1: INFORMASI PEGAWAI -->
            <div class="space-y-5">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Pegawai</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Nama Pegawai
                        </label>
                        <input type="text"
                               name="nama_pegawai"
                               value="{{ auth()->user()->name }}"
                               readonly
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <!-- GRUP 2: DETAIL PERJALANAN -->
            <div class="space-y-5">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Detail Perjalanan</h3>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tujuan Dinas
                    </label>
                    <input type="text"
                           name="tujuan_dinas"
                           value="{{ old('tujuan_dinas', $suratTugas->tujuan) }}"
                           required
                           readonly
                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                    @error('tujuan_dinas')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Keperluan
                    </label>
                    <textarea name="keperluan"
                              rows="3"
                              required
                              placeholder="Jelaskan maksud dan tujuan perjalanan dinas"
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 resize-none placeholder-slate-400 @error('keperluan') border-red-500 focus:ring-red-500 @enderror">{{ old('keperluan') }}</textarea>
                    @error('keperluan')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Tanggal Berangkat
                        </label>
                        <input type="date"
                               name="tanggal_berangkat"
                               value="{{ old('tanggal_berangkat', $suratTugas->tanggal_mulai) }}"
                               required
                               readonly
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                        @error('tanggal_berangkat')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Tanggal Kembali
                        </label>
                        <input type="date"
                               name="tanggal_kembali"
                               value="{{ old('tanggal_kembali', $suratTugas->tanggal_selesai) }}"
                               required
                               readonly
                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                        @error('tanggal_kembali')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>



            <!-- UPLOAD LAMPIRAN -->
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Upload Lampiran <span class="text-slate-400 font-normal">(Opsional)</span>
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
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Format: PDF, JPG, PNG (Maks. 2MB)
                    </p>
                    @error('file')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- AKSI -->
            <div class="flex items-center justify-end gap-3 pt-8 mt-4 border-t border-slate-100">
                <a href="{{ route('skpd.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition-colors">
                    Batal
                </a>

                <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Kirim Pengajuan
                </button>
            </div>

        </form>

    </div>
</div>
@endsection