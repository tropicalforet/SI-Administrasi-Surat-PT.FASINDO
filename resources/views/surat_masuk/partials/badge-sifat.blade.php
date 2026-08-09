{{--
    Sifat "Biasa" sengaja tidak diberi badge agar surat Penting dan Segera
    langsung menonjol di daftar yang panjang.
--}}
@if(($surat->sifat ?? 'biasa') !== 'biasa')
    @php
        $warnaSifat = $surat->sifat === 'segera'
            ? 'bg-red-50 text-red-700 border-red-200'
            : 'bg-amber-50 text-amber-700 border-amber-200';
    @endphp

    <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $warnaSifat }}">
        {{ $surat->label_sifat }}
    </span>
@endif
