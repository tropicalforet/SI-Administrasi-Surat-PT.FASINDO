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
                value="{{ old('name') }}"
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
                value="{{ old('email') }}"
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

        @include('users.partials.struktur', [
            'role'    => old('role'),
            'unit'    => old('unit'),
            'jabatan' => old('jabatan'),
        ])

        {{-- Permission Checkboxes --}}
        <div id="permissionSection" class="mb-6 hidden">
            <label class="block mb-3 font-medium text-slate-800">
                Hak Akses (Permissions)
            </label>
            <p class="text-sm text-slate-500 mb-4">Centang modul yang boleh diakses oleh pengguna ini.</p>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-5">
                @foreach($permissions as $group => $groupPermissions)
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ $group }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($groupPermissions as $perm)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white px-3 py-2 rounded-lg transition-colors">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $perm->id }}"
                                           {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-slate-700">{{ $perm->label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
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

@push('scripts')
<script>
    const roleSelect = document.getElementById('roleSelect');
    const permSection = document.getElementById('permissionSection');
    const bypassRoles = ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'];

    function togglePermissions() {
        const role = roleSelect.value.toLowerCase();
        if (role && !bypassRoles.includes(role)) {
            permSection.classList.remove('hidden');
        } else {
            permSection.classList.add('hidden');
        }
    }

    roleSelect.addEventListener('change', togglePermissions);
    document.addEventListener('DOMContentLoaded', togglePermissions);
</script>
@endpush