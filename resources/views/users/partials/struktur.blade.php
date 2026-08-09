{{--
    Role menentukan level wewenang, unit menentukan cabang organisasi.
    Keduanya dipisah karena bagan organisasi punya dua dimensi: satu kolom
    role tidak dapat mewakili enam jabatan di dua direktorat sekaligus.
--}}
<div class="mb-6">
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Role (Level Wewenang)
    </label>

    <select name="role" id="roleSelect" class="w-full border rounded-lg px-4 py-2" required>
        <option value="">-- Pilih Role --</option>
        @foreach(\App\Models\User::ROLE_PENERIMA_SURAT as $nilai => $label)
            <option value="{{ $nilai }}" {{ $role == $nilai ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
        <option value="administrator" {{ $role == 'administrator' ? 'selected' : '' }}>
            Administrator Sistem
        </option>
    </select>

    @error('role')
        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Unit Kerja
    </label>

    <select name="unit" class="w-full border rounded-lg px-4 py-2">
        <option value="">-- Tidak berada di unit tertentu --</option>
        @foreach(\App\Models\User::UNIT as $nilai => $label)
            <option value="{{ $nilai }}" {{ $unit == $nilai ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <p class="text-xs text-slate-500 mt-1.5">
        Wajib untuk Direktur, Manager, dan Pelaksana. Unit inilah yang menentukan
        siapa saja yang dapat dituju saat mendisposisikan surat.
    </p>

    @error('unit')
        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Nama Jabatan <span class="font-normal text-slate-400">(opsional)</span>
    </label>

    <input type="text"
           name="jabatan"
           value="{{ $jabatan }}"
           placeholder="Contoh: Manager Pengadaan dan Logistik"
           class="w-full border rounded-lg px-4 py-2">

    <p class="text-xs text-slate-500 mt-1.5">
        Jabatan sebenarnya sesuai bagan organisasi. Bila dikosongkan, yang
        ditampilkan adalah nama role.
    </p>

    @error('jabatan')
        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
    @enderror
</div>
