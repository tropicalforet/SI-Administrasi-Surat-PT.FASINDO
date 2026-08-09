{{--
    Badge status surat masuk. Dipakai bersama oleh daftar surat dan laporan
    agar warna maupun labelnya tidak lagi berbeda antar halaman.
--}}
@php
    $warnaStatus = match($surat->status) {
        'didisposisikan' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'selesai'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        default          => 'bg-blue-50 text-blue-700 border-blue-200',
    };
@endphp

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $warnaStatus }}">
    {{ $surat->label_status }}
</span>
