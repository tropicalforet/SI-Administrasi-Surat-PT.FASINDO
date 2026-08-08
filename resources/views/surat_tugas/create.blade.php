@extends('layouts.app')

@section('content')
<div class="py-10 px-4 sm:px-6 max-w-3xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('surat-tugas.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-sm font-medium transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">
        <div class="mb-8 border-b border-slate-100 pb-5">
            <h2 class="text-2xl font-bold text-slate-800">Buat Surat Tugas</h2>
            <p class="text-slate-500 text-sm mt-1.5">Buat Surat Tugas baru untuk pegawai.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600 font-medium">Terdapat kesalahan form. Periksa kembali isian Anda.</p>
            </div>
        @endif

        <form action="{{ route('surat-tugas.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="space-y-5">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Penugasan</h3>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pegawai yang Ditugaskan</label>
                    @if(in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1', 'direktur2', 'sekretaris']))
                        <select name="user_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 @error('user_id') border-red-500 @enderror">
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    @else
                        <input type="text" value="{{ auth()->user()->name }}" readonly class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-xl cursor-not-allowed outline-none font-medium">
                        <p class="text-xs text-slate-500 mt-1">Anda membuat penugasan untuk diri sendiri.</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Perihal Tugas</label>
                    <textarea name="perihal_tugas" rows="2" required placeholder="Contoh: Menghadiri rapat koordinasi" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all text-slate-800 @error('perihal_tugas') border-red-500 @enderror">{{ old('perihal_tugas') }}</textarea>
                    @error('perihal_tugas') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tujuan (Tempat/Instansi)</label>
                    <input type="text" name="tujuan" value="{{ old('tujuan') }}" required placeholder="Contoh: Dinas Kesehatan Kota Bandung" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all text-slate-800 @error('tujuan') border-red-500 @enderror">
                    @error('tujuan') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all text-slate-800 @error('tanggal_mulai') border-red-500 @enderror">
                        @error('tanggal_mulai') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all text-slate-800 @error('tanggal_selesai') border-red-500 @enderror">
                        @error('tanggal_selesai') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Upload Lampiran Dasar Penugasan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="file" name="file" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-8 mt-4 border-t border-slate-100">
                <a href="{{ route('surat-tugas.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Batal</a>
                <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md transition-all">Simpan Draft</button>
            </div>
        </form>
    </div>
</div>
@endsection
