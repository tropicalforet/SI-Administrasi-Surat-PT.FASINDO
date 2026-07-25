<!DOCTYPE html>
<html>
<head>
    <title>Laporan SKPD</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header img { height: 80px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto; }
        .header h1 { margin: 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10pt; }
        h2 { text-align: center; font-size: 12pt; text-transform: uppercase; text-decoration: underline; margin-bottom: 15px; }
        .filters { margin-bottom: 15px; font-size: 9pt; }
        table { w-full; border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ public_path('images/logo.png') }}">
        @endif
        <h1>PT. FASADETAMA INDONESIA</h1>
        <p>Admin Office : Graha DLA Lt. 2 Jl. Otto Iskandar Dinata No. 392 Kel. Nyengseret Kec. Astana Anyar Bandung 40242</p>
        <p>Workshop : Jl. Mars X No. 2 Margahayu Raya Kel. Manjahlega Kec. Rancasari Bandung 40286</p>
        <p>Telp./HP : 022-42826023 / 082295614803 &nbsp;|&nbsp; E-mail : fasadetamaindonesia@yahoo.com</p>
    </div>

    <h2>Laporan Surat Perjalanan Dinas</h2>

    <div class="filters">
        <p><strong>Periode Berangkat:</strong> {{ $request->start_date ? \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') : '-' }} s/d {{ $request->end_date ? \Carbon\Carbon::parse($request->end_date)->format('d/m/Y') : '-' }}</p>
        <p><strong>Pegawai Pelaksana:</strong> {{ $request->nama_pegawai ?: 'Semua' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Nomor SKPD</th>
                <th style="width: 15%">Pegawai Pelaksana</th>
                <th style="width: 25%">Tujuan & Keperluan</th>
                <th style="width: 15%">Tgl. Berangkat</th>
                <th style="width: 15%">Tgl. Kembali</th>
                <th style="width: 10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->nomor_skpd }}</td>
                    <td>{{ $item->nama_pegawai }}</td>
                    <td><strong>{{ $item->tujuan_dinas }}</strong><br>{{ $item->keperluan }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal_berangkat)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal_kembali)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ ucfirst($item->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
