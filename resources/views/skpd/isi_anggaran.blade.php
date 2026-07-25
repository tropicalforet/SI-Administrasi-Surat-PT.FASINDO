@extends('layouts.app')

@section('title', 'Isi Anggaran SKPD')

@section('content')
<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Isi Anggaran SKPD</h1>
                <p class="text-sm text-slate-500">Isi rincian biaya untuk pengajuan SKPD</p>
            </div>
        </div>
        <a href="{{ route('skpd.show', $skpd->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>

    <!-- FORM -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        
        <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Nomor SKPD</span>
                    <span class="block text-sm font-semibold text-slate-800 mt-1">{{ $skpd->nomor_skpd }}</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Pegawai</span>
                    <span class="block text-sm font-semibold text-slate-800 mt-1">{{ $skpd->nama_pegawai }}</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Durasi</span>
                    <span class="block text-sm font-semibold text-slate-800 mt-1">{{ $skpd->durasi_hari }} Hari</span>
                </div>
                <div>
                    <span class="block text-xs text-slate-500 font-medium">Tujuan</span>
                    <span class="block text-sm font-semibold text-slate-800 mt-1">{{ $skpd->tujuan_dinas }}</span>
                </div>
            </div>
        </div>

        <form action="{{ route('skpd.simpan-anggaran', $skpd->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Rincian Biaya Perjalanan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Transport (Rp)
                        </label>
                        <input type="number"
                               name="biaya_transport"
                               value="{{ old('biaya_transport', $skpd->biaya_transport) }}"
                               required
                               min="0"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('biaya_transport') border-red-500 focus:ring-red-500 @enderror">
                        @error('biaya_transport')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Penginapan (Rp)
                        </label>
                        <input type="number"
                               name="biaya_penginapan"
                               value="{{ old('biaya_penginapan', $skpd->biaya_penginapan) }}"
                               required
                               min="0"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('biaya_penginapan') border-red-500 focus:ring-red-500 @enderror">
                        @error('biaya_penginapan')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Konsumsi/Hari (Rp)
                        </label>
                        <input type="number"
                               name="biaya_konsumsi_per_hari"
                               value="{{ old('biaya_konsumsi_per_hari', $skpd->biaya_konsumsi_per_hari) }}"
                               required
                               min="0"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('biaya_konsumsi_per_hari') border-red-500 focus:ring-red-500 @enderror">
                        @error('biaya_konsumsi_per_hari')
                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- AKSI -->
            <div class="flex items-center justify-end gap-3 pt-8 mt-4 border-t border-slate-100">
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Anggaran
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
