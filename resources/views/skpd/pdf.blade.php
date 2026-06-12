<!DOCTYPE html>

<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $skpd->nomor_skpd }}</title>

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:10px;
    line-height:1.1;
    margin:10px 20px;
}

p{
    margin:2px 0;
}

.kop{
    text-align:center;
    border-bottom:2px solid #000;
    padding-bottom:3px;
    margin-bottom:10px;
}

.kop img{
    width:100%;
    height:60px;
}

.judul{
    text-align:center;
    margin-bottom:10px;
}

.judul h2{
    margin:0;
    font-size:14px;
    text-decoration:underline;
}

.info{
    width:100%;
    border-collapse:collapse;
    margin-top:5px;
    margin-bottom:5px;
}

.info td{
    padding:1px 0;
    vertical-align:top;
}

.label{
    width:140px;
}

.rincian{
    width:100%;
    border-collapse:collapse;
    margin-top:8px;
}

.rincian td{
    border:1px solid #000;
    padding:2px 4px;
}

.total{
    font-weight:bold;
    background:#f2f2f2;
}

.ttd{
    margin-top:8px;
}

.ttd img{
    height:70px;
    margin:0;
}

</style>

</head>
<body>

<div class="kop">

    <h2 style="margin:0;">
        PT. FASADETAMA INDONESIA
    </h2>

    <p>
        Admin Office : Graha DLA Lt. 2 Jl. Otto Iskandar Dinata No. 392
        Kel. Nyengseret Kec. Astana Anyar Bandung 40242
    </p>

    <p>
        Workshop : Jl. Mars X No. 2 Margahayu Raya
        Kel. Manjahlega Kec. Rancasari Bandung 40286
    </p>

    <p>
        Telp./HP : 022-42826023 / 082295614803
        - Email : fasadetamaindonesia@yahoo.com
    </p>

</div>
<h2>SURAT KETERANGAN PERJALANAN DINAS</h2>

<p>
    Nomor : {{ $skpd->nomor_skpd }}
</p>
```

</div>

<p>
Yang bertanda tangan di bawah ini menerangkan bahwa:
</p>

<table class="info">

```
<tr>
    <td class="label">Nama Pegawai</td>
    <td width="10">:</td>
    <td>{{ $skpd->nama_pegawai }}</td>
</tr>

<tr>
    <td class="label">NIP</td>
    <td>:</td>
    <td>{{ $skpd->nip }}</td>
</tr>
```

</table>

<p>
Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:
</p>

<table class="info">

```
<tr>
    <td class="label">Tujuan Dinas</td>
    <td width="10">:</td>
    <td>{{ $skpd->tujuan_dinas }}</td>
</tr>

<tr>
    <td class="label">Keperluan</td>
    <td>:</td>
    <td>{{ $skpd->keperluan }}</td>
</tr>

<tr>
    <td class="label">Periode Perjalanan Dinas</td>
    <td>:</td>
    <td>
        {{ \Carbon\Carbon::parse($skpd->tanggal_berangkat)->locale('id')->translatedFormat('d F Y') }}
        s.d.
        {{ \Carbon\Carbon::parse($skpd->tanggal_kembali)->locale('id')->translatedFormat('d F Y') }}
        ({{ $skpd->durasi_hari }} Hari)
    </td>
</tr>
```

</table>

<p style="margin-top:12px;">
    <b>Rincian Biaya Perjalanan Dinas</b>
</p>

<table class="rincian">

```
<tr>
    <td width="70%">
        Biaya Transportasi
    </td>
    <td>
        Rp {{ number_format($skpd->biaya_transport,0,',','.') }}
    </td>
</tr>

<tr>
    <td>
        Biaya Penginapan
    </td>
    <td>
        Rp {{ number_format($skpd->biaya_penginapan,0,',','.') }}
    </td>
</tr>

<tr>
    <td>
        Biaya Konsumsi / Hari
    </td>
    <td>
        Rp {{ number_format($skpd->biaya_konsumsi_per_hari,0,',','.') }}
    </td>
</tr>

<tr class="total">
    <td>
        Total Biaya
    </td>
    <td>
        Rp {{ number_format($skpd->total_biaya,0,',','.') }}
    </td>
</tr>
```

</table>

<p style="margin-top:10px;">
    Demikian Surat Keterangan Perjalanan Dinas ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>

<div class="ttd">

```
<p>
    Bandung,
    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
</p>

<p>
    Hormat kami,
</p>

<p>
    <b>PT. FASADETAMA INDONESIA</b>
</p>

<br><br><br><br>

<p>
    <b><u>Fredy Nuriat, S.Si.</u></b>
</p>

<p>
    Direktur Utama
</p>

</div>

</body>
</html>
