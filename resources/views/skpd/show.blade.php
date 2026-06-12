<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKPD {{ $skpd->nomor_skpd }}</title>
    <script src="https://cdn.tailwindcss.com"></script>

<style>
    @media print {

        .no-print {
            display: none !important;
        }

        body {
            background: white;
            padding: 0;
            margin: 0;
        }

        .surat {
            box-shadow: none !important;
            margin: 0;
            padding: 15px !important;
        }
    }
</style>

</head>

<body class="bg-gray-100 p-4">

<div class="max-w-4xl mx-auto bg-white p-6 shadow-lg surat">

<div class="flex justify-between mb-4 no-print">

<a href="{{ route('skpd.index') }}"
   class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
    ← Kembali
</a>

<div class="flex gap-2">

    <a href="{{ route('skpd.download-pdf', $skpd->id) }}"
       class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
        Download PDF
    </a>

    <button onclick="window.print()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Cetak Surat
    </button>

</div>

</div>

<!-- KOP SURAT -->

<div class="text-center border-b-4 border-black pb-2 mb-4">

<img src="{{ asset('images/kopsurat.png') }}"
     alt="Logo Fasadetama"
     class="mx-auto h-16 mb-1">

<p class="text-xs font-semibold">
    Admin Office : Graha DLA Lt. 2 Jl. Otto Iskandar Dinata No. 392
    Kel. Nyengseret Kec. Astana Anyar Bandung 40242
</p>

<p class="text-xs font-semibold">
    Workshop : Jl. Mars X No. 2 Margahayu Raya
    Kel. Manjahlega Kec. Rancasari Bandung 40286
</p>

<p class="text-xs font-semibold">
    Telp./HP : 022-42826023 / 082295614803
    &nbsp;&nbsp;
    E-mail : fasadetamaindonesia@yahoo.com
</p>

</div>

<!-- JUDUL -->

<div class="text-center mb-4">

<h2 class="text-lg font-bold uppercase underline">
    Surat Keterangan Perjalanan Dinas
</h2>

<p class="mt-1 text-sm">
    Nomor : {{ $skpd->nomor_skpd }}
</p>

</div>

<!-- ISI SURAT -->

<div class="leading-5 text-sm text-justify">

<p>
    Yang bertanda tangan di bawah ini menerangkan bahwa:
</p>

<table class="mt-2 ml-4">

    <tr>
        <td class="w-40">Nama Pegawai</td>
        <td>: {{ $skpd->nama_pegawai }}</td>
    </tr>

    <tr>
        <td>NIP</td>
        <td>: {{ $skpd->nip }}</td>
    </tr>

</table>

<p class="mt-3">
    Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:
</p>

<table class="mt-2 ml-4">

    <tr>
        <td class="w-40">Tujuan Dinas</td>
        <td>: {{ $skpd->tujuan_dinas }}</td>
    </tr>

    <tr>
        <td>Keperluan</td>
        <td>: {{ $skpd->keperluan }}</td>
    </tr>

    <tr>
        <td>Periode Perjalanan Dinas</td>
        <td>
            :
            {{ \Carbon\Carbon::parse($skpd->tanggal_berangkat)->locale('id')->translatedFormat('d F Y') }}
            s.d.
            {{ \Carbon\Carbon::parse($skpd->tanggal_kembali)->locale('id')->translatedFormat('d F Y') }}
            ({{ $skpd->durasi_hari }} Hari)
        </td>
    </tr>

</table>

<div class="mt-4">

    <h3 class="font-bold text-base mb-2">
        Rincian Biaya Perjalanan Dinas
    </h3>

    <table class="w-full border border-collapse text-sm">

        <tr>
            <td class="border p-1">Biaya Transportasi</td>
            <td class="border p-1">
                Rp {{ number_format($skpd->biaya_transport,0,',','.') }}
            </td>
        </tr>

        <tr>
            <td class="border p-1">Biaya Penginapan</td>
            <td class="border p-1">
                Rp {{ number_format($skpd->biaya_penginapan,0,',','.') }}
            </td>
        </tr>

        <tr>
            <td class="border p-1">Biaya Konsumsi / Hari</td>
            <td class="border p-1">
                Rp {{ number_format($skpd->biaya_konsumsi_per_hari,0,',','.') }}
            </td>
        </tr>

        <tr class="bg-gray-100 font-bold">
            <td class="border p-1">Total Biaya</td>
            <td class="border p-1">
                Rp {{ number_format($skpd->total_biaya,0,',','.') }}
            </td>
        </tr>

    </table>

</div>

<p class="mt-4">
    Demikian Surat Keterangan Perjalanan Dinas ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>

</div>

<!-- TANDA TANGAN -->

<div class="mt-6">

<p>
    Bandung,
    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
</p>

<p class="mt-1">
    Hormat kami,
</p>

<p class="font-bold">
    PT. FASADETAMA INDONESIA
</p>

<img src="{{ asset('images/ttd-direktur.png') }}"
     alt="Tanda Tangan Direktur"
     class="h-20 my-1">

<p class="font-bold underline">
    Fredy Nuriat, S.Si.
</p>

<p>
    Direktur Utama
</p>

</div>

</div>

</body>
</html>
