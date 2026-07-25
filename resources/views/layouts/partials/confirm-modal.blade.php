<style>
    /* ===== CONFIRM MODAL ===== */
    #confirm-overlay {
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    #confirm-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    #confirm-dialog {
        transform: scale(0.9) translateY(10px);
        opacity: 0;
        transition: transform 0.25s cubic-bezier(0.21, 1.02, 0.73, 1), opacity 0.2s ease;
    }
    #confirm-overlay.active #confirm-dialog {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
</style>

{{-- ===== SaaS Confirmation Modal ===== --}}
<div id="confirm-overlay" class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45); backdrop-filter: blur(4px);">
    <div id="confirm-dialog" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[420px] overflow-hidden">
        {{-- Icon Area --}}
        <div class="flex justify-center pt-7 pb-2">
            <div id="confirm-icon-wrap" class="w-14 h-14 rounded-full flex items-center justify-center">
                <svg id="confirm-icon-svg" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
            </div>
        </div>
        {{-- Text --}}
        <div class="text-center px-7 pb-2">
            <h3 id="confirm-title" class="text-lg font-bold text-slate-800"></h3>
            <p id="confirm-message" class="text-sm text-slate-500 mt-2 leading-relaxed"></p>
        </div>
        {{-- Actions --}}
        <div class="flex gap-3 px-7 pt-4 pb-7">
            <button id="confirm-cancel-btn" onclick="ConfirmModal.cancel()" 
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                Batal
            </button>
            <button id="confirm-ok-btn" onclick="ConfirmModal.ok()"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all shadow-md">
            </button>
        </div>
    </div>
</div>

{{-- ===== SaaS Confirmation Modal Engine ===== --}}
<script>
    window.ConfirmModal = {
        _resolve: null,
        _overlay: null,

        variants: {
            danger: {
                iconBg: 'bg-red-100',
                iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>',
                iconColor: 'text-red-600',
                btnBg: 'bg-red-600 hover:bg-red-700 shadow-red-500/20',
                btnText: 'Ya, Hapus',
            },
            warning: {
                iconBg: 'bg-amber-100',
                iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
                iconColor: 'text-amber-600',
                btnBg: 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20',
                btnText: 'Ya, Lanjutkan',
            },
            approve: {
                iconBg: 'bg-emerald-100',
                iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                iconColor: 'text-emerald-600',
                btnBg: 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20',
                btnText: 'Ya, Setujui',
            },
            info: {
                iconBg: 'bg-blue-100',
                iconPath: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                iconColor: 'text-blue-600',
                btnBg: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20',
                btnText: 'Ya, Konfirmasi',
            }
        },

        show({ title = 'Konfirmasi', message = 'Apakah Anda yakin?', variant = 'warning', confirmText = null, cancelText = 'Batal' } = {}) {
            return new Promise((resolve) => {
                this._resolve = resolve;
                this._overlay = document.getElementById('confirm-overlay');

                const v = this.variants[variant] || this.variants.warning;

                // Set icon
                const iconWrap = document.getElementById('confirm-icon-wrap');
                iconWrap.className = `w-14 h-14 rounded-full flex items-center justify-center ${v.iconBg}`;
                const iconSvg = document.getElementById('confirm-icon-svg');
                iconSvg.className = `w-7 h-7 ${v.iconColor}`;
                iconSvg.innerHTML = v.iconPath;

                // Set text
                document.getElementById('confirm-title').textContent = title;
                document.getElementById('confirm-message').textContent = message;

                // Set buttons
                const okBtn = document.getElementById('confirm-ok-btn');
                okBtn.className = `flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all shadow-md cursor-pointer ${v.btnBg}`;
                okBtn.textContent = confirmText || v.btnText;

                document.getElementById('confirm-cancel-btn').textContent = cancelText;

                // Show
                this._overlay.classList.add('active');

                // Close on backdrop click
                this._overlay.onclick = (e) => {
                    if (e.target === this._overlay) this.cancel();
                };

                // Close on Escape
                this._escHandler = (e) => { if (e.key === 'Escape') this.cancel(); };
                document.addEventListener('keydown', this._escHandler);
            });
        },

        ok() {
            this._cleanup();
            if (this._resolve) this._resolve(true);
        },

        cancel() {
            this._cleanup();
            if (this._resolve) this._resolve(false);
        },

        _cleanup() {
            if (this._overlay) this._overlay.classList.remove('active');
            if (this._escHandler) document.removeEventListener('keydown', this._escHandler);
        }
    };
</script>
