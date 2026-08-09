{{--
    Bagian penyortiran: sifat menentukan prioritas penanganan, jalur
    penerimaan mencatat lewat mana surat sampai ke kantor.
--}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            Sifat Surat
        </label>
        <select name="sifat"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('sifat') border-red-500 focus:ring-red-500 @enderror">
            @foreach(\App\Models\SuratMasuk::SIFAT as $nilai => $label)
                <option value="{{ $nilai }}" {{ $sifat == $nilai ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('sifat')
            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            Diterima Melalui
        </label>
        <select name="jalur_penerimaan"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('jalur_penerimaan') border-red-500 focus:ring-red-500 @enderror">
            <option value="">Pilih Jalur Penerimaan</option>
            @foreach(\App\Models\SuratMasuk::JALUR_PENERIMAAN as $nilai => $label)
                <option value="{{ $nilai }}" {{ $jalur == $nilai ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('jalur_penerimaan')
            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>
