{{--
    Mode cadangan bila aset belum di-build (public/build/manifest.json tidak ada).
    Tampilan tetap terbaca, tetapi komponen khusus (stat-card, action-tile, table-stack) dan tema peran
    hanya tersedia penuh setelah menjalankan "npm run build" atau "npm run dev".
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body { font-family: 'Plus Jakarta Sans', sans-serif; } main h1, main h2, main h3 { font-family: 'Bricolage Grotesque', 'Plus Jakarta Sans', sans-serif; }</style>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                fontSize: { '2xs': ['0.75rem', '1.0625rem'], xs: ['0.8125rem', '1.2rem'], sm: ['0.9375rem', '1.4rem'] },
                colors: {
                    canvas: '#f3f1ea', surface: '#fffefb', line: '#dedbd0',
                    ink: '#17211f', 'ink-soft': '#46544f', 'ink-faint': '#5f6c67',
                    brand: '#006059', 'brand-deep': '#004540', 'brand-wash': '#dcebe7',
                    ochre: '#c98a2b', 'ochre-ink': '#85560a', 'ochre-wash': '#f7ecd6',
                    wait: '#8a5600', 'wait-wash': '#f8efdc',
                    process: '#2f5486', 'process-wash': '#e8eef7',
                    done: '#3a6b46', 'done-wash': '#e6f1e9',
                    alert: '#a3341c', 'alert-wash': '#faeae6',
                },
            },
        },
    };
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
@if(!empty($chart))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
@endif
