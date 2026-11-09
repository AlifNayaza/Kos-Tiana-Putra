import Alpine from 'alpinejs';

// Chart.js sengaja TIDAK diimpor di sini: dimuat terpisah (resources/js/charts.js) hanya di halaman bergrafik.
window.Alpine = Alpine;

/* ------------------------------------------------------------------ */
/* 1. Cegah klik ganda: tombol kirim dikunci setelah form dikirim       */
/*    (pesanan/pembayaran tidak boleh terkirim dua kali).               */
/*    Lewati dengan atribut data-no-lock pada <form>.                   */
/* ------------------------------------------------------------------ */
const SUBMIT_SELECTOR = 'button[type="submit"], button:not([type]), input[type="submit"]';

function unlockForm(form) {
    delete form.dataset.submitting;
    form.querySelectorAll(SUBMIT_SELECTOR).forEach((btn) => {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) return;
    if (form.method.toLowerCase() !== 'post' || form.hasAttribute('data-no-lock')) return;
    if (form.target === '_blank') return;

    // Dibatalkan oleh pengendali lain (mis. dialog konfirmasi ditolak): jangan dikunci.
    if (event.defaultPrevented) return;

    if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = '1';

    // Ditunda satu putaran agar nilai tombol yang menekan ikut terkirim sebelum dinonaktifkan.
    setTimeout(() => {
        form.querySelectorAll(SUBMIT_SELECTOR).forEach((btn) => {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
        });
    }, 0);
});

// Tombol "kembali" peramban memulihkan halaman dari cache: buka kunci lagi.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('form[data-submitting]').forEach(unlockForm);
    }
});

/* ------------------------------------------------------------------ */
/* 2. Pulihkan form modal setelah validasi gagal                        */
/*    Server menyuntikkan window.__kosOld (isian lama + penanda form).   */
/*    Form bertanda data-restore="id" dibuka kembali dan diisi ulang.    */
/* ------------------------------------------------------------------ */
function restoreModalForm() {
    const payload = window.__kosOld;
    if (!payload || !payload.old || !payload.old._restore) return;

    const old = payload.old;
    const form = document.querySelector('form[data-restore="' + old._restore + '"]');
    if (!form) return;

    const root = form.closest('[x-data]');
    const scope = root ? Alpine.$data(root) : null;

    // Mode ubah: pulihkan alamat tujuan form dan penanda "sedang mengubah".
    if (scope && form.dataset.urlVar && old._action) scope[form.dataset.urlVar] = old._action;
    if (scope && form.dataset.editVar) scope[form.dataset.editVar] = String(old._edit) === '1';

    Object.entries(old).forEach(([name, value]) => {
        if (name.startsWith('_')) return;

        form.querySelectorAll('[name="' + CSS.escape(name) + '"]').forEach((field) => {
            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === 1 || value === true || value === 'on';
            } else if (field.type === 'radio') {
                field.checked = field.value === String(value);
            } else {
                field.value = value ?? '';
            }
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    if (scope && form.dataset.openVar) scope[form.dataset.openVar] = true;

    // Fokus ke kolom pertama yang bermasalah agar pengguna langsung tahu apa yang perlu dibetulkan.
    setTimeout(() => {
        const bad = form.querySelector('p[role="alert"]');
        const target = bad && (bad.parentElement.querySelector('input, select, textarea'));
        if (target) target.focus({ preventScroll: false });
    }, 350);
}

document.addEventListener('alpine:initialized', restoreModalForm);

/* ------------------------------------------------------------------ */
/* 3. Toast dari mana saja: window.dispatchEvent(new CustomEvent(       */
/*    'kos-toast', { detail: { message, type } }))                      */
/* ------------------------------------------------------------------ */
window.kosToast = (message, type = 'info') =>
    window.dispatchEvent(new CustomEvent('kos-toast', { detail: { message, type } }));

Alpine.start();
