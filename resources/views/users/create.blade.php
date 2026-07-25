@extends('layouts.app')

@section('content')

<div class="bg-white rounded-xl shadow p-6 max-w-3xl">

    <h2 class="text-2xl font-bold mb-6">
        Tambah User
    </h2>

    <form action="{{ route('users.store') }}" method="POST">

        @csrf

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Nama
            </label>

            <input
                type="text"
                name="name"
                class="w-full border rounded-lg px-4 py-2"
                required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Email
            </label>

            <input
                type="email"
                name="email"
                class="w-full border rounded-lg px-4 py-2"
                required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Password
            </label>

            <input
                type="password"
                name="password"
                class="w-full border rounded-lg px-4 py-2"
                required>
        </div>

        <div class="mb-6">
            <label class="block mb-2 font-medium">
                Role
            </label>

            <select
                name="role"
                class="w-full border rounded-lg px-4 py-2"
                required>

                <option value="">-- Pilih Role --</option>

                <option value="admin">
                    Administrator
                </option>

                <option value="sekretaris">
                    Sekretaris
                </option>

                <option value="dirut">
                    Direktur Utama
                </option>

                <option value="direktur1">
                    Direktur I
                </option>

                <option value="direktur2">
                    Direktur II
                </option>

                <option value="staff">
                    Staff
                </option>

            </select>

        </div>

        <div class="flex gap-3">

            <button
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">

                Simpan

            </button>

            <a href="{{ route('users.index') }}"
               class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded-lg">

                Kembali

            </a>

        </div>

    </form>

</div>

@endsection