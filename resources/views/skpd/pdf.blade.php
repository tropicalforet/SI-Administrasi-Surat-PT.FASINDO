<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SKPD - {{ $skpd->nomor_skpd }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm 1.5cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.375;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .m-0 { margin: 0; }
        .mb-0.5 { margin-bottom: 2px; }
        .mb-1.5 { margin-bottom: 6px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 20px; }
        .mt-1 { margin-top: 4px; }
        .my-0.5 { margin: 2px 0; }
        .w-full { width: 100%; }
        .w-\[28\%\] { width: 28%; }
        .w-\[2\%\] { width: 2%; }
        .w-\[70\%\] { width: 70%; }
        .w-\[35\%\] { width: 35%; }
        .w-64 { width: 256px; }
        .h-16 { height: 64px; }
        .h-20 { height: 80px; }
        .h-14 { height: 56px; }
        .p-1 { padding: 4px; }
        .pl-3 { padding-left: 12px; }
        .pr-3 { padding-right: 12px; }
        .border-collapse { border-collapse: collapse; }
        .border { border: 1px solid #000; }
        .border-black { border-color: #000; }
        .bg-gray-100 { background-color: #f3f4f6; }
        .align-top { vertical-align: top; }
        .py-0 { padding-top: 0; padding-bottom: 0; }
        .text-\[10pt\] { font-size: 10pt; }
        .text-\[10\.5pt\] { font-size: 10.5pt; }
        .text-\[11pt\] { font-size: 11pt; }
        .text-\[13pt\] { font-size: 13pt; }
        .kop-border {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .ttd-container {
            width: 100%;
            margin-top: 20px;
        }
        .ttd-box {
            float: right;
            width: 256px;
            text-align: center;
        }
        .clear { clear: both; }
    </style>
</head>
<body>

{{-- Penanda bagi dokumen yang belum disetujui, agar hasil cetak pratinjau
     tidak disangka dokumen final. --}}
@if($belumDisetujui ?? false)
    <div style="border: 2px dashed #b91c1c; color: #b91c1c; text-align: center;
                font-weight: bold; letter-spacing: 2px; padding: 6px;
                margin-bottom: 12px; font-size: 10pt;">
        PRATINJAU &mdash; BELUM DISETUJUI
    </div>
@endif


    <!-- KOP SURAT -->
    <div class="text-center kop-border">
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ public_path('images/logo.png') }}" class="h-20 mb-2" style="display: block; margin: 0 auto; object-fit: contain;">
        @endif
        
        <h1 class="text-[13pt] font-bold uppercase m-0">PT. FASADETAMA INDONESIA</h1>
        
        <p class="text-[10pt] m-0">
            Admin Office : Graha DLA Lt. 2 Jl. Otto Iskandar Dinata No. 392
            Kel. Nyengseret Kec. Astana Anyar Bandung 40242
        </p>
        <p class="text-[10pt] m-0">
            Workshop : Jl. Mars X No. 2 Margahayu Raya Kel. Manjahlega Kec. Rancasari Bandung 40286
        </p>
        <p class="text-[10pt] m-0">
            Telp./HP : 022-42826023 / 082295614803 &nbsp;|&nbsp; E-mail : fasadetamaindonesia@yahoo.com
        </p>
    </div>

    <!-- JUDUL SURAT -->
    <div class="text-center mb-5">
        <h2 class="text-[13pt] font-bold uppercase underline m-0">
            Surat Keterangan Perjalanan Dinas
        </h2>
        <p class="text-[11pt] mt-1 m-0">
            Nomor : {{ $skpd->nomor_skpd }}
        </p>
    </div>

    <!-- ISI SURAT -->
    <div class="text-[11pt] text-justify">
        
        <p class="mb-2">
            Yang bertanda tangan di bawah ini menerangkan bahwa:
        </p>

        <table class="w-full mb-3 border-collapse">
            <tr>
                <td class="w-[28%] py-0 align-top">Nama Pegawai</td>
                <td class="w-[2%] py-0 align-top">:</td>
                <td class="w-[70%] py-0 align-top font-bold">{{ $skpd->nama_pegawai }}</td>
            </tr>
        </table>

        <p class="mb-2">
            Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:
        </p>

        <table class="w-full mb-4 border-collapse">
            <tr>
                <td class="w-[28%] py-0 align-top">Tujuan Dinas</td>
                <td class="w-[2%] py-0 align-top">:</td>
                <td class="w-[70%] py-0 align-top">{{ $skpd->tujuan_dinas }}</td>
            </tr>
            <tr>
                <td class="py-0 align-top">Keperluan</td>
                <td class="py-0 align-top">:</td>
                <td class="py-0 align-top">{{ $skpd->keperluan }}</td>
            </tr>
            <tr>
                <td class="py-0 align-top">Periode Perjalanan</td>
                <td class="py-0 align-top">:</td>
                <td class="py-0 align-top">
                    {{ \Carbon\Carbon::parse($skpd->tanggal_berangkat)->locale('id')->translatedFormat('d F Y') }}
                    s.d.
                    {{ \Carbon\Carbon::parse($skpd->tanggal_kembali)->locale('id')->translatedFormat('d F Y') }}
                    ({{ $skpd->durasi_hari }} Hari)
                </td>
            </tr>
        </table>


        <p class="mb-4">
            Demikian Surat Keterangan Perjalanan Dinas ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.
        </p>

    </div>

    <!-- AREA TANDA TANGAN -->
    <div class="ttd-container">
        <table style="width: 100%; border: none; margin-top: 15px;">
            <tr>
                <!-- Kolom Kiri: QR Code Verifikasi (Jika disetujui) -->
                <td style="width: 50%; border: none; vertical-align: bottom; text-align: left; padding-left: 20px;">
                    @if(strtolower($skpd->status) === 'disetujui' && isset($qrCodeBase64) && $qrCodeBase64)
                        <div style="display: inline-block; text-align: center;">
                            <img src="data:image/png;base64,{{ $qrCodeBase64 }}" style="width: 65px; height: 65px;">
                            <p style="font-size: 7pt; color: #64748b; margin: 2px 0 0 0; font-family: sans-serif;">Pindai QR untuk verifikasi</p>
                            <p style="font-size: 6pt; color: #94a3b8; margin: 0; font-family: monospace;">Hash: {{ substr(hash('sha256', $skpd->id . $skpd->updated_at), 0, 16) }}...</p>
                        </div>
                    @endif
                </td>
                
                <!-- Kolom Kanan: Tanda Tangan & Stempel -->
                <td style="width: 50%; border: none; vertical-align: bottom; text-align: center;">
                    <div style="width: 240px; margin-left: auto; text-align: center; font-size: 10pt;">
                        <p style="margin: 0 0 2px 0;">
                            Bandung, {{ \Carbon\Carbon::parse($skpd->updated_at)->locale('id')->translatedFormat('d F Y') }}
                        </p>
                        <p style="margin: 0;">Hormat kami,</p>
                        <p style="font-weight: bold; margin: 0;">PT. FASADETAMA INDONESIA</p>
                        
                        <div style="height: 52px; margin: 2px 0; text-align: center; vertical-align: middle;">
                            @if(file_exists(public_path('images/ttd-direktur.png')) && strtolower($skpd->status) === 'disetujui')
                                <img src="{{ public_path('images/ttd-direktur.png') }}" style="height: 48px; display: inline-block;">
                            @else
                                <div style="height: 48px;"></div>
                            @endif
                        </div>
                        
                        <p style="font-weight: bold; text-decoration: underline; margin: 0;">Fredy Nuriat, S.Si.</p>
                        <p style="margin: 0;">Direktur Utama</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>