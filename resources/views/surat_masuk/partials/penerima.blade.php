{{--
    Tujuan surat: ke sebuah jabatan atau ke satu pengguna tertentu. Pilihan
    jabatan dijadikan bawaan karena surat dinas umumnya ditujukan ke jabatan,
    bukan ke orang.

    Daftar jabatan sengaja hanya memuat posisi tunggal (lihat
    User::ROLE_PENERIMA_SURAT). Untuk manager dan pelaksana, penerimanya harus
    disebut namanya karena ada beberapa orang di unit berbeda.

    Toggle ditulis dengan JavaScript biasa karena layout memuat Tailwind
    lewat CDN dan tidak menyertakan bundel Alpine.
--}}
<div>
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Penerima (Tujuan Surat)
    </label>

    <div class="flex gap-2 mb-3">
        <label class="flex-1 cursor-pointer">
            <input type="radio" name="penerima_tipe" value="role" class="sr-only peer js-penerima-tipe"
                   {{ $penerimaTipe === 'role' ? 'checked' : '' }}>
            <div class="px-4 py-2.5 text-center text-sm font-semibold rounded-xl border transition-all bg-slate-50 border-slate-200 text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600">
                Ke Jabatan
            </div>
        </label>

        <label class="flex-1 cursor-pointer">
            <input type="radio" name="penerima_tipe" value="user" class="sr-only peer js-penerima-tipe"
                   {{ $penerimaTipe === 'user' ? 'checked' : '' }}>
            <div class="px-4 py-2.5 text-center text-sm font-semibold rounded-xl border transition-all bg-slate-50 border-slate-200 text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600">
                Ke Pengguna Tertentu
            </div>
        </label>
    </div>

    <div id="penerimaRoleWrap" class="{{ $penerimaTipe === 'role' ? '' : 'hidden' }}">
        <select name="penerima_role"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('penerima_role') border-red-500 focus:ring-red-500 @enderror">
            <option value="">Pilih Jabatan Tujuan</option>
            @foreach(\App\Models\User::ROLE_PENERIMA_SURAT as $nilai => $label)
                <option value="{{ $nilai }}" {{ $penerimaRole == $nilai ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1.5">
            Pemegang jabatan ini langsung dapat melihat suratnya dan menerima notifikasi.
            Untuk manager atau pelaksana, pilih <strong>Ke Pengguna Tertentu</strong> dan sebut namanya &mdash;
            satu jabatan itu dipegang beberapa orang di unit yang berbeda.
        </p>
        @error('penerima_role')
            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
        @enderror
    </div>

    <div id="penerimaUserWrap" class="{{ $penerimaTipe === 'user' ? '' : 'hidden' }}">
        <select name="penerima_id"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('penerima_id') border-red-500 focus:ring-red-500 @enderror">
            <option value="">Pilih Penerima</option>
            @foreach($users as $user)
                {{-- Unit ikut ditulis karena satu jabatan seperti Manager
                     dipegang beberapa orang di direktorat berbeda. --}}
                <option value="{{ $user->id }}" {{ $penerimaId == $user->id ? 'selected' : '' }}>
                    {{ $user->name }} &mdash; {{ $user->label_jabatan }}{{ $user->unit ? ' (' . $user->label_unit . ')' : '' }}
                </option>
            @endforeach
        </select>
        @error('penerima_id')
            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
    (function () {
        var pilihan = document.querySelectorAll('.js-penerima-tipe');
        var wrapRole = document.getElementById('penerimaRoleWrap');
        var wrapUser = document.getElementById('penerimaUserWrap');

        function segarkan() {
            var terpilih = document.querySelector('.js-penerima-tipe:checked');
            var keRole = !terpilih || terpilih.value === 'role';

            wrapRole.classList.toggle('hidden', !keRole);
            wrapUser.classList.toggle('hidden', keRole);
        }

        pilihan.forEach(function (radio) {
            radio.addEventListener('change', segarkan);
        });

        segarkan();
    })();
</script>
