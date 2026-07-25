<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Office - PT. Fasadetama Indonesia</title>

    {{-- Preconnect untuk mempercepat koneksi ke CDN --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden antialiased">

    {{-- ===== SaaS Toast Notification Container ===== --}}
    <div id="toast-container" class="fixed top-5 right-5 z-[99998] flex flex-col gap-3 pointer-events-none" style="max-width: 420px; width: 100%;"></div>

    <style>
        /* ===== TOAST ANIMATIONS ===== */
        @keyframes toast-slide-in {
            0% { transform: translateX(120%); opacity: 0; }
            60% { transform: translateX(-8px); opacity: 1; }
            100% { transform: translateX(0); opacity: 1; }
        }
        @keyframes toast-fade-out {
            0% { transform: translateX(0); opacity: 1; max-height: 120px; margin-bottom: 12px; }
            100% { transform: translateX(120%); opacity: 0; max-height: 0; margin-bottom: 0; padding: 0; }
        }
        @keyframes toast-progress {
            0% { width: 100%; }
            100% { width: 0%; }
        }
        .toast-item {
            animation: toast-slide-in 0.45s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
            pointer-events: all;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .toast-item.removing {
            animation: toast-fade-out 0.4s cubic-bezier(0.55, 0, 1, 0.45) forwards;
        }
        .toast-progress-bar {
            animation: toast-progress linear forwards;
        }
    </style>

    @include('layouts.partials.confirm-modal')
    </div>

    @include('layouts.partials.sidebar')

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-slate-50">
        @yield('content')
    </main>

    {{-- ===== SaaS Toast Notification Engine ===== --}}
    <script>
        window.Toast = {
            container: null,
            
            config: {
                success: {
                    bg: 'bg-white/95 border-emerald-200',
                    icon: '<svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    accent: 'bg-emerald-500',
                    title: 'Berhasil!',
                    progressColor: 'bg-emerald-400',
                },
                error: {
                    bg: 'bg-white/95 border-red-200',
                    icon: '<svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    accent: 'bg-red-500',
                    title: 'Gagal!',
                    progressColor: 'bg-red-400',
                },
                warning: {
                    bg: 'bg-white/95 border-amber-200',
                    icon: '<svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    accent: 'bg-amber-500',
                    title: 'Peringatan!',
                    progressColor: 'bg-amber-400',
                },
                info: {
                    bg: 'bg-white/95 border-blue-200',
                    icon: '<svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    accent: 'bg-blue-500',
                    title: 'Informasi',
                    progressColor: 'bg-blue-400',
                }
            },

            init() {
                this.container = document.getElementById('toast-container');
            },

            show(type, message, duration = 5000) {
                if (!this.container) this.init();
                const cfg = this.config[type] || this.config.info;

                const toast = document.createElement('div');
                toast.className = `toast-item relative overflow-hidden rounded-xl border shadow-lg shadow-slate-900/5 ${cfg.bg}`;
                toast.innerHTML = `
                    <div class="absolute top-0 left-0 w-1 h-full ${cfg.accent} rounded-l-xl"></div>
                    <div class="flex items-start gap-3 pl-5 pr-4 py-4">
                        ${cfg.icon}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">${cfg.title}</p>
                            <p class="text-sm text-slate-600 mt-0.5 leading-relaxed">${message}</p>
                        </div>
                        <button onclick="Toast.dismiss(this.closest('.toast-item'))" 
                                class="flex-shrink-0 p-1 rounded-lg hover:bg-slate-100 transition-colors group">
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-slate-100/50">
                        <div class="toast-progress-bar h-full ${cfg.progressColor} rounded-full opacity-60" 
                             style="animation-duration: ${duration}ms"></div>
                    </div>
                `;

                this.container.appendChild(toast);

                // Auto dismiss
                const timer = setTimeout(() => this.dismiss(toast), duration);
                toast._timer = timer;

                // Pause on hover
                toast.addEventListener('mouseenter', () => {
                    clearTimeout(toast._timer);
                    const progressBar = toast.querySelector('.toast-progress-bar');
                    if (progressBar) progressBar.style.animationPlayState = 'paused';
                });
                toast.addEventListener('mouseleave', () => {
                    toast._timer = setTimeout(() => this.dismiss(toast), 2000);
                    const progressBar = toast.querySelector('.toast-progress-bar');
                    if (progressBar) {
                        progressBar.style.animationDuration = '2s';
                        progressBar.style.animationPlayState = 'running';
                    }
                });
            },

            dismiss(el) {
                if (!el || el.classList.contains('removing')) return;
                clearTimeout(el._timer);
                el.classList.add('removing');
                el.addEventListener('animationend', () => el.remove());
            },

            success(msg, duration) { this.show('success', msg, duration); },
            error(msg, duration)   { this.show('error', msg, duration); },
            warning(msg, duration) { this.show('warning', msg, duration); },
            info(msg, duration)    { this.show('info', msg, duration); },
        };
    </script>



    {{-- Auto-trigger dari Laravel Session Flash --}}
    @if(session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => Toast.success(@json(session('success'))));</script>
        <script>document.addEventListener('turbo:load', function _s() { Toast.success(@json(session('success'))); document.removeEventListener('turbo:load', _s); });</script>
    @endif
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded', () => Toast.error(@json(session('error'))));</script>
        <script>document.addEventListener('turbo:load', function _e() { Toast.error(@json(session('error'))); document.removeEventListener('turbo:load', _e); });</script>
    @endif
    @if(session('warning'))
        <script>document.addEventListener('DOMContentLoaded', () => Toast.warning(@json(session('warning'))));</script>
    @endif
    @if(session('info'))
        <script>document.addEventListener('DOMContentLoaded', () => Toast.info(@json(session('info'))));</script>
    @endif

    {{-- Stack untuk script halaman spesifik --}}
    @stack('scripts')

</body>
</html>
