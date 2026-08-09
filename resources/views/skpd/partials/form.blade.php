{{--
    Formulir SKPD. Mencakup dua macam penugasan: perjalanan dinas dan tugas
    internal. Tujuan hanya diminta bila memang bepergian, sehingga penugasan
    panitia internal tidak dipaksa mengisi kolom yang tidak relevan.

    Toggle ditulis dengan JavaScript biasa karena layout memuat Tailwind lewat
    CDN dan tidak menyertakan bundel Alpine.
--}}

@if($users->isNotEmpty())
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Pegawai yang Ditugaskan</label>
        <select name="user_id" required
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('user_id') border-red-500 @enderror">
            <option value="">Pilih Pegawai</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $nilai['user_id'] == $u->id ? 'selected' : '' }}>
                    {{ $u->name }} &mdash; {{ $u->label_jabatan }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1.5">
            Anda menugaskan pegawai lain, sehingga dokumen ini tercatat sebagai penugasan.
        </p>
        @error('user_id')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
    </div>
@endif

<div>
    <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Penugasan</label>

    <div class="flex gap-2">
        @foreach(\App\Models\Skpd::JENIS as $kode => $label)
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="jenis" value="{{ $kode }}" class="sr-only peer js-jenis"
                       {{ $nilai['jenis'] === $kode ? 'checked' : '' }}>
                <div class="px-4 py-2.5 text-center text-sm font-semibold rounded-xl border transition-all bg-slate-50 border-slate-200 text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600">
                    {{ $label }}
                </div>
            </label>
        @endforeach
    </div>

    <p class="text-xs text-slate-500 mt-1.5">
        Tugas internal misalnya menjadi panitia acara kantor &mdash; tidak perlu tujuan perjalanan.
    </p>
    @error('jenis')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
</div>

<div id="wrapTujuan" class="{{ $nilai['jenis'] === 'perjalanan_dinas' ? '' : 'hidden' }}">
    <label class="block text-sm font-semibold text-slate-700 mb-2">Tujuan Perjalanan</label>
    <input type="text" name="tujuan_dinas" value="{{ $nilai['tujuan_dinas'] }}"
           placeholder="Contoh: Surabaya"
           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tujuan_dinas') border-red-500 @enderror">
    @error('tujuan_dinas')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-semibold text-slate-700 mb-2">Keperluan / Perihal Tugas</label>
    <textarea name="keperluan" rows="3" required
              placeholder="Jelaskan maksud penugasan ini"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 resize-none @error('keperluan') border-red-500 @enderror">{{ $nilai['keperluan'] }}</textarea>
    @error('keperluan')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            <span class="js-label-mulai">{{ $nilai['jenis'] === 'perjalanan_dinas' ? 'Tanggal Berangkat' : 'Tanggal Mulai' }}</span>
        </label>
        <input type="date" name="tanggal_berangkat" value="{{ $nilai['tanggal_berangkat'] }}" required
               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_berangkat') border-red-500 @enderror">
        @error('tanggal_berangkat')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            <span class="js-label-selesai">{{ $nilai['jenis'] === 'perjalanan_dinas' ? 'Tanggal Kembali' : 'Tanggal Selesai' }}</span>
        </label>
        <input type="date" name="tanggal_kembali" value="{{ $nilai['tanggal_kembali'] }}" required
               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_kembali') border-red-500 @enderror">
        @error('tanggal_kembali')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-sm font-semibold text-slate-700 mb-2">
        Lampiran <span class="text-slate-400 font-normal">(opsional)</span>
    </label>
    <input type="file" name="file"
           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer">
    <p class="text-xs text-slate-500 mt-2">Format: PDF, JPG, PNG (maks. 2 MB)</p>
    @error('file')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
</div>

<script>
    (function () {
        var pilihan = document.querySelectorAll('.js-jenis');
        var wrapTujuan = document.getElementById('wrapTujuan');
        var labelMulai = document.querySelector('.js-label-mulai');
        var labelSelesai = document.querySelector('.js-label-selesai');

        function segarkan() {
            var terpilih = document.querySelector('.js-jenis:checked');
            var perjalanan = !terpilih || terpilih.value === 'perjalanan_dinas';

            wrapTujuan.classList.toggle('hidden', !perjalanan);
            labelMulai.textContent = perjalanan ? 'Tanggal Berangkat' : 'Tanggal Mulai';
            labelSelesai.textContent = perjalanan ? 'Tanggal Kembali' : 'Tanggal Selesai';
        }

        pilihan.forEach(function (r) { r.addEventListener('change', segarkan); });
        segarkan();
    })();
</script>
