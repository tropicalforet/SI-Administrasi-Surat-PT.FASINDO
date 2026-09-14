{{--
    Bilah atas yang selalu tampak di setiap halaman. Isinya identitas pengguna
    yang sedang masuk - sebelumnya nama dan jabatan tidak muncul di mana pun
    kecuali di halaman profil, sehingga tidak ada penanda "saya sedang masuk
    sebagai siapa" saat berpindah menu.

    Bilah ini berada di kolom kanan (di samping sidebar), jadi ketika menu
    dibuka di layar kecil sidebar hanya menutupi sisi kiri dan identitas di
    sisi kanan tetap terbaca.
--}}

@php
    $pengguna = auth()->user();

    // Inisial dipakai sebagai avatar karena belum ada kolom foto profil.
    $inisial = collect(preg_split('/\s+/', trim($pengguna->name)))
        ->filter()
        ->take(2)
        ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
        ->implode('');
@endphp

<header class="flex-shrink-0 bg-white border-b border-slate-200 z-30">
    <div class="flex items-center justify-between gap-4 px-4 sm:px-6 h-16">

        {{-- Tombol menu hanya perlu di layar kecil; di layar lebar sidebar
             selalu tampak. --}}
        <button type="button" id="tombolMenu" aria-label="Buka menu"
                class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <div class="hidden lg:block">
            <p class="text-sm font-semibold text-slate-800">{{ $pengguna->label_jabatan }}</p>
            <p class="text-xs text-slate-500">PT. Fasadetama Indonesia</p>
        </div>

        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 ml-auto px-2 py-1.5 rounded-xl hover:bg-slate-100 transition-colors group">

            <div class="text-right leading-tight min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ $pengguna->name }}</p>
                <p class="text-xs text-slate-500 truncate">
                    {{ $pengguna->label_jabatan }}
                    @if($pengguna->unit)
                        &middot; {{ $pengguna->label_unit }}
                    @endif
                </p>
            </div>

            <div class="w-10 h-10 flex-shrink-0 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm ring-2 ring-blue-100 group-hover:ring-blue-200 transition-all">
                {{ $inisial }}
            </div>
        </a>
    </div>
</header>
