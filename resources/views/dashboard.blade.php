<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans p-6">

<div class="max-w-6xl mx-auto">

<div class="bg-white rounded-xl shadow-lg p-8">

```
<div class="text-center mb-8">

    <h1 class="text-4xl font-bold text-gray-800">
        PT. Fasadetama Indonesia
    </h1>

    <p class="text-gray-500 mt-2">
        Dashboard E-Office
    </p>

</div>

<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8">
    <p class="font-medium">
        Login berhasil
    </p>
</div>

<!-- Statistik -->

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

    <div class="bg-blue-50 p-5 rounded-xl border border-blue-100">
        <p class="text-sm text-gray-500">Surat Masuk</p>
        <h2 class="text-3xl font-bold text-blue-600 mt-2">
            {{ $totalSuratMasuk }}
        </h2>
    </div>

    <div class="bg-emerald-50 p-5 rounded-xl border border-emerald-100">
        <p class="text-sm text-gray-500">Surat Keluar</p>
        <h2 class="text-3xl font-bold text-emerald-600 mt-2">
            {{ $totalSuratKeluar }}
        </h2>
    </div>

    <div class="bg-yellow-50 p-5 rounded-xl border border-yellow-100">
        <p class="text-sm text-gray-500">Draft</p>
        <h2 class="text-3xl font-bold text-yellow-600 mt-2">
            {{ $totalDraft }}
        </h2>
    </div>

    <div class="bg-cyan-50 p-5 rounded-xl border border-cyan-100">
        <p class="text-sm text-gray-500">Dikirim</p>
        <h2 class="text-3xl font-bold text-cyan-600 mt-2">
            {{ $totalDikirim }}
        </h2>
    </div>

    <div class="bg-green-50 p-5 rounded-xl border border-green-100">
        <p class="text-sm text-gray-500">Selesai</p>
        <h2 class="text-3xl font-bold text-green-600 mt-2">
            {{ $totalSelesai }}
        </h2>
    </div>

</div>

<!-- Menu -->

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <a href="{{ route('surat-masuk.index') }}"
       class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-xl transition">

        Menu Surat Masuk

    </a>

    <a href="{{ route('surat-keluar.index') }}"
       class="flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-xl transition">

        Menu Surat Keluar

    </a>

    <a href="{{ route('skpd.index') }}"
       class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold py-4 rounded-xl transition">

        Menu SKPD

    </a>

</div>

<form method="POST" action="{{ route('logout') }}">
    @csrf

    <button type="submit"
            class="w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-3 rounded-xl border border-red-200 transition">

        Logout

    </button>
</form>
```

</div>

</div>

</body>
</html>
