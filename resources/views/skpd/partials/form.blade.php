{{--
    Formulir SKPD. Satu macam dokumen saja: perjalanan dinas. Pilihan "tugas
    internal" sempat ada saat modul Surat Tugas digabung ke sini, lalu dilepas
    kembali karena SKPD memang surat keterangan perjalanan dinas - tujuan dan
    lama perjalanan selalu relevan.
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
            @if(auth()->user()->isDirektur())
                Daftar ini berisi tiga posisi di bawah Anda beserta diri Anda sendiri.
            @else
                Daftar ini berisi seluruh pegawai dalam struktur organisasi.
            @endif
            Memilih nama Anda sendiri membuat dokumen ini tercatat sebagai usulan, bukan penugasan.
        </p>
        @error('user_id')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
    </div>
@endif

<div>
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
        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Berangkat</label>
        <input type="date" name="tanggal_berangkat" value="{{ $nilai['tanggal_berangkat'] }}" required
               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none transition-all text-slate-800 @error('tanggal_berangkat') border-red-500 @enderror">
        @error('tanggal_berangkat')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Kembali</label>
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
