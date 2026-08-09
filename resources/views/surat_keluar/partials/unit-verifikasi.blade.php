{{--
    Menentukan direktorat mana yang memverifikasi surat sebelum naik ke Direktur
    Utama. Kategori surat (SK/SU/SP) tidak menyiratkan bidangnya, jadi
    penentuannya diserahkan kepada sekretaris saat menyusun surat.
--}}
<div class="mb-6">
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Diverifikasi Oleh (Direktorat)
    </label>

    <select name="unit_verifikasi"
            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('unit_verifikasi') border-red-500 focus:ring-red-500 @enderror">
        <option value="">Pilih Direktorat Verifikator</option>
        @foreach(\App\Models\User::UNIT as $nilai => $label)
            @continue($nilai === 'pimpinan')
            <option value="{{ $nilai }}" {{ $unitVerifikasi == $nilai ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <p class="text-xs text-slate-500 mt-1.5">
        Surat akan diverifikasi direktur pada direktorat ini lebih dulu, baru diteruskan
        ke Direktur Utama untuk ditandatangani.
    </p>

    @error('unit_verifikasi')
        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
    @enderror
</div>
