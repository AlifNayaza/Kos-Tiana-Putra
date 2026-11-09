import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/charts.js'],
            refresh: true,
            fonts: [
                // Font dihosting sendiri, hanya WOFF2 subset latin dan bobot yang dipakai (berkas ada di resources/fonts).
                // Tidak ada permintaan ke Google/pihak ketiga, dan hanya satu berkas per bobot.
                local('Plus Jakarta Sans', {
                    variants: [
                        { src: 'resources/fonts/plus-jakarta-sans-400.woff2', weight: 400 },
                        { src: 'resources/fonts/plus-jakarta-sans-500.woff2', weight: 500 },
                        { src: 'resources/fonts/plus-jakarta-sans-600.woff2', weight: 600 },
                        { src: 'resources/fonts/plus-jakarta-sans-700.woff2', weight: 700 },
                    ],
                    preload: [{ weight: 400 }],
                }),
                local('Bricolage Grotesque', {
                    variants: [
                        { src: 'resources/fonts/bricolage-grotesque-700.woff2', weight: 700 },
                        { src: 'resources/fonts/bricolage-grotesque-800.woff2', weight: 800 },
                    ],
                    preload: [{ weight: 800 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
