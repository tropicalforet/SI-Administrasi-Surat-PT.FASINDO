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

    @if(strtolower($skpd->status) === 'ditolak' && $skpd->catatan_revisi)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <p class="text-sm font-bold text-red-800">Catatan Revisi dari Direktur Utama:</p>
                <p class="text-sm text-red-700 mt-1 font-medium italic">"{{ $skpd->catatan_revisi }}"</p>
                <p class="text-xs text-red-500 mt-2">Silakan perbaiki isian formulir di bawah ini kemudian kirim ulang.</p>
            </div>
        </div>
    @endif

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

        <div class="mb-8 border-b border-slate-100 pb-5">
            <h2 class="text-2xl font-bold text-slate-800">
                Edit SKPD
            </h2>
            <p class="text-slate-500 text-sm mt-1.5">
                Perbarui data Surat Keterangan Perjalanan Dinas atau rincian biaya.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada form. Silakan periksa kembali isian Anda di bawah.</p>
            </div>
        @endif

        <form action="{{ route('skpd.update', $skpd->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-6">

            @csrf
            @method('PUT')

            <!-- GRUP 1: INFORMASI UMUM & PEGAWAI -->
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nomor SKPD
                    </label>
                    <input type="text"
                           value="{{ $skpd->nomor_skpd }}"
                           readonly
                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                    <p class="text-xs text-slate-400 mt-1.5">Nomor SKPD dibuat otomatis oleh sistem dan tidak dapat diubah.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Nama Pegawai
                        </label>
                        <input type="text"
                               name="nama_pegawai"
                               value="{{ old('nama_pegawai', $skpd->nama_pegawai) }}"
                               required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('nama_pegawai') border-red-500 focus:ring-red-500 @enderror">
                        @error('nama_pegawai')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
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
                           value="{{ old('tujuan_dinas', $skpd->tujuan_dinas) }}"
                           required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tujuan_dinas') border-red-500 focus:ring-red-500 @enderror">
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
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 resize-none @error('keperluan') border-red-500 focus:ring-red-500 @enderror">{{ old('keperluan', $skpd->keperluan) }}</textarea>
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
                               value="{{ old('tanggal_berangkat', $skpd->tanggal_berangkat) }}"
                               required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_berangkat') border-red-500 focus:ring-red-500 @enderror">
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
                               value="{{ old('tanggal_kembali', $skpd->tanggal_kembali) }}"
                               required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_kembali') border-red-500 focus:ring-red-500 @enderror">
                        @error('tanggal_kembali')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>



            <!-- LAMPIRAN -->
            <div class="space-y-5">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Dokumen Lampiran Saat Ini
                        </label>
                        @if($skpd->file)
                            <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-50 text-blue-600 rounded-md">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">Lampiran tersedia</span>
                                </div>
                                <a href="{{ asset('storage/'.$skpd->file) }}"
                                   target="_blank"
                                   class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                    Lihat File
                                </a>
                            </div>
                        @else
                            <div class="flex items-center gap-2 p-3 bg-white border border-slate-200 rounded-lg text-slate-400 text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Tidak ada dokumen yang dilampirkan.
                            </div>
                        @endif
                    </div>

                    <hr class="border-slate-200">

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Ganti Lampiran <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="file"
                               name="file"
                               class="block w-full text-sm text-slate-500 
                                      file:mr-4 file:py-2.5 file:px-4 
                                      file:rounded-lg file:border-0 
                                      file:text-sm file:font-semibold 
                                      file:bg-white file:text-slate-700 
                                      file:border file:border-slate-200
                                      hover:file:bg-slate-100 transition-all 
                                      bg-transparent cursor-pointer focus:outline-none @error('file') border-red-500 @enderror">
                        <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Format: PDF, JPG, PNG (Maks. 2MB)
                        </p>
                        @error('file')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    @if(strtolower($skpd->status) === 'ditolak')
                        Kirim Ulang Pengajuan
                    @else
                        Update SKPD
                    @endif
                </button>
            </div>

        </form>

    </div>
</div>
@endsection